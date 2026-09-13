<?php
$pageTitle = "Companies - NexaCRM";
require_once __DIR__ . '/config/db.php';

$message = '';
$error = '';

// Handle Form Submission (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_company') {
    $name = trim($_POST['name'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $revenue = (float)($_POST['annual_revenue'] ?? 0);

    if (empty($name) || empty($industry)) {
        $error = "Company name and industry are required.";
    } else {
        try {
            // Prepared statement preventing SQL Injection
            $stmt = $pdo->prepare("
                INSERT INTO companies (name, industry, website, phone, annual_revenue) 
                VALUES (:name, :industry, :website, :phone, :revenue)
            ");
            $stmt->execute([
                ':name'     => $name,
                ':industry' => $industry,
                ':website'  => $website,
                ':phone'    => $phone,
                ':revenue'  => $revenue
            ]);
            $message = "Company '$name' added successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate key error
                $error = "A company named '$name' already exists.";
            } else {
                $error = "Error adding company: " . $e->getMessage();
            }
        }
    }
}

// Fetch Companies with Count of Contacts and Deals (LEFT JOIN + GROUP BY)
$sql = "
    SELECT 
        c.id, c.name, c.industry, c.website, c.phone, c.annual_revenue,
        COUNT(DISTINCT ct.id) AS total_contacts,
        COUNT(DISTINCT d.id) AS total_deals,
        COALESCE(SUM(d.value), 0) AS total_deal_value
    FROM companies c
    LEFT JOIN contacts ct ON c.id = ct.company_id
    LEFT JOIN deals d ON c.id = d.company_id
    GROUP BY c.id, c.name, c.industry, c.website, c.phone, c.annual_revenue
    ORDER BY c.name ASC
";
$companies = $pdo->query($sql)->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-white">Companies (Accounts)</h1>
        <p class="text-sm text-slate-400">Client organizations and accounts in your sales pipeline.</p>
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

<div class="grid lg:grid-cols-3 gap-8">
    <!-- Left: Companies List Table -->
    <div class="lg:col-span-2">
        <div class="bg-slate-800 border border-slate-700 rounded-xl overflow-hidden shadow-sm">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-900/60 text-xs uppercase text-slate-400 border-b border-slate-700">
                    <tr>
                        <th class="p-3.5">Company Name</th>
                        <th class="p-3.5">Industry</th>
                        <th class="p-3.5 text-center">Contacts</th>
                        <th class="p-3.5 text-center">Deals</th>
                        <th class="p-3.5 text-right">Pipeline Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/60">
                    <?php foreach ($companies as $comp): ?>
                        <tr class="hover:bg-slate-700/30 transition">
                            <td class="p-3.5 font-semibold text-white">
                                <?= htmlspecialchars($comp['name']) ?>
                                <?php if ($comp['website']): ?>
                                    <a href="<?= htmlspecialchars($comp['website']) ?>" target="_blank" class="text-xs text-indigo-400 block font-normal hover:underline">
                                        <?= htmlspecialchars($comp['website']) ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="p-3.5 text-slate-400"><?= htmlspecialchars($comp['industry']) ?></td>
                            <td class="p-3.5 text-center font-medium"><?= $comp['total_contacts'] ?></td>
                            <td class="p-3.5 text-center font-medium"><?= $comp['total_deals'] ?></td>
                            <td class="p-3.5 text-right font-semibold text-emerald-400">
                                $<?= number_format($comp['total_deal_value'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right: Add Company Form (POST) -->
    <div>
        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 shadow-sm">
            <h2 class="text-lg font-bold text-white mb-1">Add New Company</h2>
            <p class="text-xs text-slate-400 mb-4">Inserts a new record into the <code>companies</code> table via PDO.</p>

            <form action="companies.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create_company">

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Company Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Acme Corp" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Industry *</label>
                    <input type="text" name="industry" required placeholder="e.g. Healthcare, Software" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Website</label>
                    <input type="url" name="website" placeholder="https://example.com" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Phone</label>
                    <input type="text" name="phone" placeholder="+1-555-0199" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Annual Revenue ($)</label>
                    <input type="number" step="0.01" name="annual_revenue" placeholder="1000000.00" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>

                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                    + Save Company
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
