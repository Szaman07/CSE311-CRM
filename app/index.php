<?php
$pageTitle = "Dashboard - NexaCRM";
require_once __DIR__ . '/config/db.php';

// 1. Fetch Aggregated Metric Cards
$metricsSql = "
    SELECT 
        (SELECT COALESCE(SUM(value), 0) FROM deals WHERE stage = 'closed_won') AS won_revenue,
        (SELECT COALESCE(SUM(value), 0) FROM deals WHERE stage NOT IN ('closed_won', 'closed_lost')) AS pipeline_value,
        (SELECT COUNT(*) FROM companies) AS total_companies,
        (SELECT COUNT(*) FROM contacts) AS total_contacts
";
$metrics = $pdo->query($metricsSql)->fetch();

// 2. Fetch Recent Deals with JOINs
$dealsSql = "
    SELECT 
        d.id, d.title, d.value, d.stage, d.expected_close_date,
        c.name AS company_name,
        u.full_name AS rep_name
    FROM deals d
    JOIN companies c ON d.company_id = c.id
    JOIN users u ON d.assigned_user_id = u.id
    ORDER BY d.created_at DESC
    LIMIT 6
";
$deals = $pdo->query($dealsSql)->fetchAll();

// 3. Fetch Recent Activities
$activitiesSql = "
    SELECT 
        a.activity_type, a.subject, a.notes, a.activity_date,
        u.full_name AS author_name,
        c.name AS company_name
    FROM activities a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN deals d ON a.deal_id = d.id
    LEFT JOIN companies c ON d.company_id = c.id
    ORDER BY a.activity_date DESC
    LIMIT 4
";
$activities = $pdo->query($activitiesSql)->fetchAll();

require_once __DIR__ . '/header.php';
?>

<!-- Metric Cards Grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-slate-800 border border-slate-700 p-5 rounded-xl shadow-sm">
        <span class="text-xs text-slate-400 uppercase font-semibold">Won Revenue</span>
        <div class="text-2xl font-bold text-emerald-400 mt-1">$<?= number_format($metrics['won_revenue'], 2) ?></div>
        <span class="text-xs text-slate-500 mt-1 block">Closed contracts</span>
    </div>
    <div class="bg-slate-800 border border-slate-700 p-5 rounded-xl shadow-sm">
        <span class="text-xs text-slate-400 uppercase font-semibold">Active Pipeline</span>
        <div class="text-2xl font-bold text-indigo-400 mt-1">$<?= number_format($metrics['pipeline_value'], 2) ?></div>
        <span class="text-xs text-slate-500 mt-1 block">Deals in progress</span>
    </div>
    <div class="bg-slate-800 border border-slate-700 p-5 rounded-xl shadow-sm">
        <span class="text-xs text-slate-400 uppercase font-semibold">Total Accounts</span>
        <div class="text-2xl font-bold text-white mt-1"><?= $metrics['total_companies'] ?></div>
        <span class="text-xs text-slate-500 mt-1 block">Client companies</span>
    </div>
    <div class="bg-slate-800 border border-slate-700 p-5 rounded-xl shadow-sm">
        <span class="text-xs text-slate-400 uppercase font-semibold">Total Contacts</span>
        <div class="text-2xl font-bold text-white mt-1"><?= $metrics['total_contacts'] ?></div>
        <span class="text-xs text-slate-500 mt-1 block">People in directory</span>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-8">
    <!-- Main Column: Deals Pipeline Table -->
    <div class="lg:col-span-2 space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-bold text-white">Recent Sales Deals</h2>
            <a href="deals.php" class="text-xs text-indigo-400 hover:text-indigo-300 font-medium">View all deals &rarr;</a>
        </div>

        <div class="bg-slate-800 border border-slate-700 rounded-xl overflow-hidden shadow-sm">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-900/60 text-xs uppercase text-slate-400 border-b border-slate-700">
                    <tr>
                        <th class="p-3.5">Deal Title</th>
                        <th class="p-3.5">Company</th>
                        <th class="p-3.5">Value</th>
                        <th class="p-3.5">Stage</th>
                        <th class="p-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/60">
                    <?php foreach ($deals as $deal): ?>
                        <tr class="hover:bg-slate-700/30 transition">
                            <td class="p-3.5 font-medium text-white">
                                <a href="deal_view.php?id=<?= $deal['id'] ?>" class="hover:text-indigo-400">
                                    <?= htmlspecialchars($deal['title']) ?>
                                </a>
                            </td>
                            <td class="p-3.5 text-slate-400"><?= htmlspecialchars($deal['company_name']) ?></td>
                            <td class="p-3.5 font-semibold text-slate-200">$<?= number_format($deal['value'], 2) ?></td>
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
                                <a href="deal_view.php?id=<?= $deal['id'] ?>" class="text-xs bg-slate-700 hover:bg-slate-600 text-white px-2.5 py-1 rounded transition">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Side Column: Activity Timeline -->
    <div class="space-y-4">
        <h2 class="text-lg font-bold text-white">Recent Activities</h2>
        <div class="bg-slate-800 border border-slate-700 rounded-xl p-5 space-y-4">
            <?php foreach ($activities as $act): ?>
                <div class="border-l-2 border-indigo-500 pl-3.5 py-0.5 space-y-1">
                    <div class="flex justify-between items-center">
                        <span class="text-xs uppercase font-bold text-indigo-400"><?= htmlspecialchars($act['activity_type']) ?></span>
                        <span class="text-[11px] text-slate-500"><?= date('M j, Y', strtotime($act['activity_date'])) ?></span>
                    </div>
                    <div class="text-sm font-semibold text-white"><?= htmlspecialchars($act['subject']) ?></div>
                    <div class="text-xs text-slate-400"><?= htmlspecialchars($act['notes']) ?></div>
                    <div class="text-[11px] text-slate-500">By <?= htmlspecialchars($act['author_name']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
