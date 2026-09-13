<?php
$pageTitle = "Deal Details - NexaCRM";
require_once __DIR__ . '/config/db.php';

$dealId = (int)($_GET['id'] ?? 0);
if ($dealId <= 0) {
    header("Location: deals.php");
    exit;
}

// 1. Handle Logging New Activity on this Deal (POST)
$activityMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_activity') {
    $type    = $_POST['activity_type'] ?? 'note';
    $subject = trim($_POST['subject'] ?? '');
    $notes   = trim($_POST['notes'] ?? '');
    $userId  = (int)($_POST['user_id'] ?? 1);

    if (!empty($subject) && !empty($notes)) {
        $stmt = $pdo->prepare("
            INSERT INTO activities (deal_id, user_id, activity_type, subject, notes)
            VALUES (:deal_id, :user_id, :type, :subject, :notes)
        ");
        $stmt->execute([
            ':deal_id' => $dealId,
            ':user_id' => $userId,
            ':type'    => $type,
            ':subject' => $subject,
            ':notes'   => $notes
        ]);
        $activityMsg = "Activity logged successfully!";
    }
}

// 2. Fetch Deal Details with JOINs
$dealSql = "
    SELECT 
        d.id, d.title, d.value, d.stage, d.expected_close_date, d.closed_at, d.created_at,
        c.id AS company_id, c.name AS company_name, c.industry,
        ct.id AS contact_id, CONCAT(ct.first_name, ' ', ct.last_name) AS contact_name, ct.email AS contact_email,
        u.id AS rep_id, u.full_name AS rep_name
    FROM deals d
    JOIN companies c ON d.company_id = c.id
    JOIN users u ON d.assigned_user_id = u.id
    LEFT JOIN contacts ct ON d.primary_contact_id = ct.id
    WHERE d.id = :id
";
$stmt = $pdo->prepare($dealSql);
$stmt->execute([':id' => $dealId]);
$deal = $stmt->fetch();

if (!$deal) {
    die("Deal not found.");
}

// 3. Fetch Stage History Audit Trail
$histSql = "
    SELECT 
        h.from_stage, h.to_stage, h.changed_at,
        u.full_name AS changed_by
    FROM deal_stage_history h
    JOIN users u ON h.changed_by_user_id = u.id
    WHERE h.deal_id = :deal_id
    ORDER BY h.changed_at ASC
";
$hStmt = $pdo->prepare($histSql);
$hStmt->execute([':deal_id' => $dealId]);
$stageHistory = $hStmt->fetchAll();

// 4. Fetch Activities for this Deal
$actSql = "
    SELECT 
        a.activity_type, a.subject, a.notes, a.activity_date,
        u.full_name AS author_name
    FROM activities a
    JOIN users u ON a.user_id = u.id
    WHERE a.deal_id = :deal_id
    ORDER BY a.activity_date DESC
";
$aStmt = $pdo->prepare($actSql);
$aStmt->execute([':deal_id' => $dealId]);
$activities = $aStmt->fetchAll();

// 5. Fetch Generated Invoice (if deal is Closed Won)
$invoice = null;
if ($deal['stage'] === 'closed_won') {
    $invSql = "SELECT * FROM invoices WHERE deal_id = :deal_id";
    $iStmt = $pdo->prepare($invSql);
    $iStmt->execute([':deal_id' => $dealId]);
    $invoice = $iStmt->fetch();
}

$users = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetchAll();

require_once __DIR__ . '/header.php';
?>

<!-- Header Banner -->
<div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-8">
    <div>
        <div class="flex items-center space-x-3">
            <h1 class="text-2xl font-bold text-white"><?= htmlspecialchars($deal['title']) ?></h1>
            <?php
            $stageColors = [
                'lead'        => 'bg-slate-700 text-slate-300',
                'contacted'   => 'bg-blue-950 text-blue-300 border border-blue-800',
                'proposal'    => 'bg-amber-950 text-amber-300 border border-amber-800',
                'negotiation' => 'bg-purple-950 text-purple-300 border border-purple-800',
                'closed_won'  => 'bg-emerald-950 text-emerald-300 border border-emerald-800',
                'closed_lost' => 'bg-rose-950 text-rose-300 border border-rose-800',
            ];
            $color = $stageColors[$deal['stage']] ?? 'bg-slate-700 text-slate-300';
            ?>
            <span class="px-3 py-1 text-xs font-bold rounded-full capitalize <?= $color ?>">
                <?= str_replace('_', ' ', $deal['stage']) ?>
            </span>
        </div>
        <p class="text-sm text-slate-400 mt-1">
            Account: <strong class="text-white"><?= htmlspecialchars($deal['company_name']) ?></strong> &bull; 
            Rep: <strong class="text-white"><?= htmlspecialchars($deal['rep_name']) ?></strong>
        </p>
    </div>
    <div class="text-right">
        <span class="text-xs text-slate-400 uppercase font-semibold">Deal Value</span>
        <div class="text-3xl font-extrabold text-emerald-400">$<?= number_format($deal['value'], 2) ?></div>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="mb-6 p-4 rounded-xl bg-emerald-950/80 border border-emerald-500 text-emerald-300 text-sm font-semibold">
        ✓ Deal successfully Closed as Won! Atomic invoice generation completed.
    </div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="mb-6 p-4 rounded-xl bg-rose-950/80 border border-rose-500 text-rose-300 text-sm font-semibold">
        ✗ Transaction Error: <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-8">

    <!-- Left Two Columns: Actions, Invoice & Stage History -->
    <div class="lg:col-span-2 space-y-8">

        <!-- SIGNATURE FEATURE CARD: Close Won & Invoice Generation -->
        <?php if ($deal['stage'] !== 'closed_won' && $deal['stage'] !== 'closed_lost'): ?>
            <div class="bg-indigo-950/40 border-2 border-indigo-500/50 rounded-2xl p-6 shadow-lg">
                <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-white">Advance Deal to Closed-Won</h2>
                        <p class="text-xs text-slate-300 mt-1 max-w-md">
                            Executes an <strong>ACID transaction</strong>: locks row with <code>FOR UPDATE</code>, transitions stage to <code>closed_won</code>, writes to <code>deal_stage_history</code>, and atomically auto-generates the billing <code>invoice</code>.
                        </p>
                    </div>
                    <form action="close_deal.php" method="POST" onsubmit="return confirm('Execute atomic transaction to Close Won this deal and issue an invoice?');">
                        <input type="hidden" name="deal_id" value="<?= $deal['id'] ?>">
                        <input type="hidden" name="user_id" value="<?= $deal['rep_id'] ?>">
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-5 py-3 rounded-xl shadow-md transition text-sm flex items-center space-x-2">
                            <span>Close Won &amp; Generate Invoice</span>
                            <span>⚡</span>
                        </button>
                    </form>
                </div>
            </div>
        <?php elseif ($invoice): ?>
            <!-- Invoice Generated Display Card -->
            <div class="bg-emerald-950/30 border border-emerald-600/50 rounded-2xl p-6 shadow-md">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <span class="text-xs font-mono uppercase tracking-wider text-emerald-400 font-semibold">Official Generated Invoice</span>
                        <h3 class="text-xl font-bold text-white mt-1"><?= htmlspecialchars($invoice['invoice_number']) ?></h3>
                    </div>
                    <span class="px-3 py-1 text-xs font-bold rounded-full uppercase <?= $invoice['status'] == 'paid' ? 'bg-emerald-800 text-white' : 'bg-amber-900 text-amber-200' ?>">
                        <?= $invoice['status'] ?>
                    </span>
                </div>
                <div class="grid grid-cols-3 gap-4 border-t border-emerald-800/40 pt-4 text-sm">
                    <div>
                        <span class="text-xs text-slate-400 block">Subtotal</span>
                        <span class="font-bold text-slate-200">$<?= number_format($invoice['subtotal'], 2) ?></span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block">Tax (5%)</span>
                        <span class="font-bold text-slate-200">$<?= number_format($invoice['tax_amount'], 2) ?></span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block">Total Due</span>
                        <span class="font-extrabold text-emerald-400 text-base">$<?= number_format($invoice['total_amount'], 2) ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stage Progression Audit Timeline -->
        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 shadow-sm">
            <h2 class="text-lg font-bold text-white mb-1">Stage Progression Audit Trail</h2>
            <p class="text-xs text-slate-400 mb-6">Immutable audit record from <code>deal_stage_history</code>.</p>

            <div class="space-y-6 relative before:absolute before:inset-0 before:left-3.5 before:w-0.5 before:bg-slate-700">
                <?php foreach ($stageHistory as $idx => $step): ?>
                    <div class="relative flex items-start space-x-4">
                        <div class="w-7 h-7 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-bold ring-4 ring-slate-800 z-10">
                            <?= $idx + 1 ?>
                        </div>
                        <div class="flex-1 bg-slate-900 border border-slate-700/80 p-3.5 rounded-xl">
                            <div class="flex justify-between items-center text-xs">
                                <span class="font-bold text-white capitalize">
                                    <?= $step['from_stage'] ? htmlspecialchars($step['from_stage']) . ' &rarr; ' : 'Initial Stage: ' ?>
                                    <span class="text-indigo-400 font-semibold"><?= htmlspecialchars($step['to_stage']) ?></span>
                                </span>
                                <span class="text-slate-500 font-mono"><?= date('M j, Y H:i', strtotime($step['changed_at'])) ?></span>
                            </div>
                            <span class="text-xs text-slate-400 mt-1 block">Attributed to Rep: <strong><?= htmlspecialchars($step['changed_by']) ?></strong></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 shadow-sm space-y-4">
            <h2 class="text-lg font-bold text-white">Deal Activities &amp; Notes</h2>
            <?php if (empty($activities)): ?>
                <p class="text-sm text-slate-500 italic">No activities logged for this deal yet.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($activities as $act): ?>
                        <div class="bg-slate-900/80 border border-slate-700/60 p-4 rounded-xl space-y-1">
                            <div class="flex justify-between items-center">
                                <span class="text-xs uppercase font-bold text-indigo-400"><?= htmlspecialchars($act['activity_type']) ?></span>
                                <span class="text-xs text-slate-500 font-mono"><?= date('M j, Y H:i', strtotime($act['activity_date'])) ?></span>
                            </div>
                            <div class="text-sm font-semibold text-white"><?= htmlspecialchars($act['subject']) ?></div>
                            <div class="text-xs text-slate-300"><?= nl2br(htmlspecialchars($act['notes'])) ?></div>
                            <div class="text-[11px] text-slate-500">Logged by <?= htmlspecialchars($act['author_name']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Right Column: Log Activity Form -->
    <div>
        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 shadow-sm sticky top-20">
            <h3 class="text-base font-bold text-white mb-1">Log New Interaction</h3>
            <p class="text-xs text-slate-400 mb-4">Add call notes, meeting summaries, or emails.</p>

            <?php if ($activityMsg): ?>
                <div class="mb-4 p-3 bg-emerald-950 border border-emerald-600 text-emerald-300 text-xs rounded-lg">
                    ✓ <?= htmlspecialchars($activityMsg) ?>
                </div>
            <?php endif; ?>

            <form action="deal_view.php?id=<?= $deal['id'] ?>" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_activity">

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Interaction Type</label>
                    <select name="activity_type" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                        <option value="call">Phone Call</option>
                        <option value="meeting">Meeting</option>
                        <option value="email">Email</option>
                        <option value="note">Internal Note</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Subject *</label>
                    <input type="text" name="subject" required placeholder="e.g. Discussed pricing" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Notes / Summary *</label>
                    <textarea name="notes" rows="3" required placeholder="Meeting outcomes..." 
                              class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Logged By Rep</label>
                    <select name="user_id" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-sm text-white focus:outline-none focus:border-indigo-500">
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $u['id'] == $deal['rep_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                    + Log Activity
                </button>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
