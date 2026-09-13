<?php
$pageTitle = "Reports & Analytics - NexaCRM";
require_once __DIR__ . '/config/db.php';

// 1. Pipeline Summary View
$pipeline = $pdo->query("SELECT * FROM v_pipeline_summary")->fetchAll();

// 2. Sales Rep Performance Leaderboard View
$reps = $pdo->query("SELECT * FROM v_sales_rep_performance")->fetchAll();

// 3. Inactive Accounts Query (LEFT JOIN finding companies with NO deals!)
$inactiveSql = "
    SELECT 
        c.id, c.name, c.industry, c.annual_revenue
    FROM companies c
    LEFT JOIN deals d ON c.id = d.company_id
    WHERE d.id IS NULL
";
$inactiveCompanies = $pdo->query($inactiveSql)->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-white">Sales Reports &amp; Analytics</h1>
    <p class="text-sm text-slate-400">Demonstrates SQL Views, complex multi-table aggregations, and subqueries.</p>
</div>

<div class="space-y-8">

    <!-- Section 1: Pipeline Stage Summary (v_pipeline_summary) -->
    <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 shadow-sm">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-lg font-bold text-white">Pipeline Summary by Stage</h2>
                <p class="text-xs text-slate-400">Queried directly from database view <code>v_pipeline_summary</code>.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-900/60 text-xs uppercase text-slate-400 border-b border-slate-700">
                    <tr>
                        <th class="p-3.5">Stage</th>
                        <th class="p-3.5 text-center">Deal Count</th>
                        <th class="p-3.5 text-right">Total Value</th>
                        <th class="p-3.5 text-right">Average Deal Size</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/60">
                    <?php foreach ($pipeline as $row): ?>
                        <tr class="hover:bg-slate-700/30 transition">
                            <td class="p-3.5 font-bold text-white capitalize">
                                <?= str_replace('_', ' ', $row['stage']) ?>
                            </td>
                            <td class="p-3.5 text-center font-medium"><?= $row['deal_count'] ?></td>
                            <td class="p-3.5 text-right font-bold text-emerald-400">
                                $<?= number_format($row['total_pipeline_value'], 2) ?>
                            </td>
                            <td class="p-3.5 text-right text-slate-300">
                                $<?= number_format($row['avg_deal_size'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Sales Rep Leaderboard (v_sales_rep_performance) -->
    <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 shadow-sm">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-lg font-bold text-white">Sales Rep Performance Leaderboard</h2>
                <p class="text-xs text-slate-400">Queried from <code>v_sales_rep_performance</code> (Calculates win-rates &amp; closed revenue).</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-900/60 text-xs uppercase text-slate-400 border-b border-slate-700">
                    <tr>
                        <th class="p-3.5">Sales Rep</th>
                        <th class="p-3.5 text-center">Deals Managed</th>
                        <th class="p-3.5 text-center">Won</th>
                        <th class="p-3.5 text-center">Lost</th>
                        <th class="p-3.5 text-right">Total Won Revenue</th>
                        <th class="p-3.5 text-right">Win Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/60">
                    <?php foreach ($reps as $rep): ?>
                        <tr class="hover:bg-slate-700/30 transition">
                            <td class="p-3.5 font-bold text-white">
                                <?= htmlspecialchars($rep['rep_name']) ?>
                                <span class="text-xs text-slate-500 font-normal block font-mono"><?= htmlspecialchars($rep['email']) ?></span>
                            </td>
                            <td class="p-3.5 text-center font-medium"><?= $rep['total_deals_managed'] ?></td>
                            <td class="p-3.5 text-center text-emerald-400 font-bold"><?= $rep['won_deals'] ?></td>
                            <td class="p-3.5 text-center text-rose-400 font-bold"><?= $rep['lost_deals'] ?></td>
                            <td class="p-3.5 text-right font-extrabold text-emerald-400">
                                $<?= number_format($rep['total_won_revenue'], 2) ?>
                            </td>
                            <td class="p-3.5 text-right font-bold text-indigo-400">
                                <?= $rep['win_rate_percentage'] ?>%
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 3: Dormant / Inactive Accounts (Zero Deals) -->
    <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 shadow-sm">
        <h2 class="text-lg font-bold text-white mb-1">Dormant Accounts (Zero Opportunities)</h2>
        <p class="text-xs text-slate-400 mb-4">Classic SQL exercise: <code>SELECT ... FROM companies LEFT JOIN deals WHERE deals.id IS NULL</code>.</p>

        <?php if (empty($inactiveCompanies)): ?>
            <p class="text-sm text-slate-500 italic">All client accounts have at least one sales opportunity logged!</p>
        <?php else: ?>
            <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-3">
                <?php foreach ($inactiveCompanies as $in): ?>
                    <div class="bg-slate-900 border border-slate-700 p-3.5 rounded-lg">
                        <div class="font-bold text-white text-sm"><?= htmlspecialchars($in['name']) ?></div>
                        <div class="text-xs text-slate-400"><?= htmlspecialchars($in['industry']) ?></div>
                        <div class="text-xs text-slate-500 mt-2">Revenue: $<?= number_format($in['annual_revenue'], 2) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
