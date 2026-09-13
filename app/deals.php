<?php
$pageTitle = "Deals - NexaCRM";
require_once __DIR__ . '/config/db.php';

$message = '';
$error = '';

// Handle Deal Creation (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_deal') {
    $companyId  = (int)($_POST['company_id'] ?? 0);
    $contactId  = !empty($_POST['primary_contact_id']) ? (int)$_POST['primary_contact_id'] : null;
    $userId     = (int)($_POST['assigned_user_id'] ?? 0);
    $title      = trim($_POST['title'] ?? '');
    $value      = (float)($_POST['value'] ?? 0);
    $stage      = $_POST['stage'] ?? 'lead';
    $closeDate  = !empty($_POST['expected_close_date']) ? $_POST['expected_close_date'] : null;

    if ($companyId <= 0 || $userId <= 0 || empty($title) || $value < 0) {
        $error = "Company, Assigned Rep, Title, and a non-negative value are required.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Insert Deal
            $stmt = $pdo->prepare("
                INSERT INTO deals (company_id, primary_contact_id, assigned_user_id, title, value, stage, expected_close_date)
                VALUES (:company_id, :contact_id, :user_id, :title, :value, :stage, :close_date)
            ");
            $stmt->execute([
                ':company_id' => $companyId,
                ':contact_id' => $contactId,
                ':user_id'    => $userId,
                ':title'      => $title,
                ':value'      => $value,
                ':stage'      => $stage,
                ':close_date' => $closeDate
            ]);
            $dealId = $pdo->lastInsertId();

            // 2. Insert initial stage into history audit log
            $histStmt = $pdo->prepare("
                INSERT INTO deal_stage_history (deal_id, from_stage, to_stage, changed_by_user_id)
                VALUES (:deal_id, NULL, :stage, :user_id)
            ");
            $histStmt->execute([
                ':deal_id' => $dealId,
                ':stage'   => $stage,
                ':user_id' => $userId
            ]);

            $pdo->commit();
            $message = "Deal '$title' created successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error creating deal: " . $e->getMessage();
        }
    }
}

// Fetch Dropdown Data
$companies = $pdo->query("SELECT id, name FROM companies ORDER BY name ASC")->fetchAll();
$contacts  = $pdo->query("SELECT id, CONCAT(first_name, ' ', last_name) AS name FROM contacts ORDER BY first_name ASC")->fetchAll();
$users     = $pdo->query("SELECT id, full_name FROM users ORDER BY full_name ASC")->fetchAll();

// Filter Deals by Stage
$stageFilter = $_GET['stage'] ?? '';
$whereClause = "";
$params = [];
if (!empty($stageFilter)) {
    $whereClause = "WHERE d.stage = :stage";
    $params[':stage'] = $stageFilter;
}

$dealsSql = "
    SELECT 
        d.id, d.title, d.value, d.stage, d.expected_close_date,
        c.name AS company_name,
        u.full_name AS rep_name
    FROM deals d
    JOIN companies c ON d.company_id = c.id
    JOIN users u ON d.assigned_user_id = u.id
    $whereClause
    ORDER BY d.created_at DESC
";
$stmt = $pdo->prepare($dealsSql);
$stmt->execute($params);
$deals = $stmt->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-white">Sales Pipeline (Deals)</h1>
        <p class="text-sm text-slate-400">Track and advance opportunities from Lead to Closed-Won.</p>
    </div>
</div>

<?php if ($message): ?>
    <div class="mb-6 p-4 rounded-lg bg-emerald-950/80 border border-emerald-500 text-emerald-300 text-sm font-medium">
        ✓ <?= htmlspecialchars($message) ?>
    </div>
<?php elseif ($error): ?>
    <div class="mb-6 p-4 rounded-lg bg-rose-950/80 border border-rose-500 text-rose-300 text-sm font-medium">
        ✗ <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Stage Filter Buttons -->
<div class="flex flex-wrap gap-2 mb-6">
    <a href="deals.php" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= empty($stageFilter) ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' ?>">All Stages</a>
    <a href="deals.php?stage=lead" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= $stageFilter == 'lead' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' ?>">Lead</a>
    <a href="deals.php?stage=proposal" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= $stageFilter == 'proposal' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' ?>">Proposal</a>
    <a href="deals.php?stage=negotiation" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= $stageFilter == 'negotiation' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' ?>">Negotiation</a>
    <a href="deals.php?stage=closed_won" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= $stageFilter == 'closed_won' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' ?>">Closed Won</a>
    <a href="deals.php?stage=closed_lost" class="px-3 py-1.5 rounded-lg text-xs font-semibold <?= $stageFilter == 'closed_lost' ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white' ?>">Closed Lost</a>
</div>

<div class="grid lg:grid-cols-3 gap-8">
    <!-- Left: Deals Table -->
    <div class="lg:col-span-2">
        <div class="bg-slate-800 border border-slate-700 rounded-xl overflow-hidden shadow-sm">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-900/60 text-xs uppercase text-slate-400 border-b border-slate-700">
                    <tr>
                        <th class="p-3.5">Deal Title</th>
                        <th class="p-3.5">Company</th>
                        <th class="p-3.5">Value</th>
                        <th class="p-3.5">Stage</th>
                        <th class="p-3.5 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/60">
                    <?php foreach ($deals as $deal): ?>
                        <tr class="hover:bg-slate-700/30 transition">
                            <td class="p-3.5 font-medium text-white">
                                <a href="deal_view.php?id=<?= $deal['id'] ?>" class="hover:text-indigo-400">
                                    <?= htmlspecialchars($deal['title']) ?>
                                </a>
                                <span class="block text-xs text-slate-500">Rep: <?= htmlspecialchars($deal['rep_name']) ?></span>
                            </td>
                            <td class="p-3.5 text-slate-400"><?= htmlspecialchars($deal['company_name']) ?></td>
                            <td class="p-3.5 font-bold text-slate-200">$<?= number_format($deal['value'], 2) ?></td>
                            <td class="p-3.5">
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
                                <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full capitalize <?= $color ?>">
                                    <?= str_replace('_', ' ', $deal['stage']) ?>
                                </span>
                            </td>
                            <td class="p-3.5 text-right">
                                <a href="deal_view.php?id=<?= $deal['id'] ?>" class="text-xs bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1.5 rounded-lg transition font-medium">
                                    Open
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right: Create Deal Form -->
    <div>
        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 shadow-sm">
            <h2 class="text-lg font-bold text-white mb-1">Create New Opportunity</h2>
            <p class="text-xs text-slate-400 mb-4">Inserts into <code>deals</code> and automatically logs initial stage into <code>deal_stage_history</code>.</p>

            <form action="deals.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create_deal">

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Deal Title *</label>
                    <input type="text" name="title" required placeholder="e.g. Enterprise License Expansion" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Associated Company *</label>
                    <select name="company_id" required 
                            class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                        <option value="">Select company...</option>
                        <?php foreach ($companies as $comp): ?>
                            <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Assigned Sales Rep *</label>
                    <select name="assigned_user_id" required 
                            class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Deal Value ($) *</label>
                        <input type="number" step="0.01" name="value" required placeholder="50000.00" 
                               class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Initial Stage</label>
                        <select name="stage" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                            <option value="lead">Lead</option>
                            <option value="contacted">Contacted</option>
                            <option value="proposal">Proposal</option>
                            <option value="negotiation">Negotiation</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Expected Close Date</label>
                    <input type="date" name="expected_close_date" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>

                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                    + Save Opportunity
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
