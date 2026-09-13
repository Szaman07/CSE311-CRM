<?php
$pageTitle = "Contacts - NexaCRM";
require_once __DIR__ . '/config/db.php';

$message = '';
$error = '';

// Handle Form Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_contact') {
    $companyId = (int)($_POST['company_id'] ?? 0);
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $title     = trim($_POST['job_title'] ?? '');

    if ($companyId <= 0 || empty($firstName) || empty($lastName) || empty($email)) {
        $error = "Company, First Name, Last Name, and Email are required.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO contacts (company_id, first_name, last_name, email, phone, job_title)
                VALUES (:company_id, :first_name, :last_name, :email, :phone, :title)
            ");
            $stmt->execute([
                ':company_id'  => $companyId,
                ':first_name'  => $firstName,
                ':last_name'   => $lastName,
                ':email'       => $email,
                ':phone'       => $phone,
                ':title'       => $title
            ]);
            $message = "Contact '$firstName $lastName' created successfully!";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "A contact with email '$email' already exists.";
            } else {
                $error = "Error adding contact: " . $e->getMessage();
            }
        }
    }
}

// Fetch all companies for the dropdown
$companies = $pdo->query("SELECT id, name FROM companies ORDER BY name ASC")->fetchAll();

// Fetch all contacts with company name (INNER JOIN)
$sql = "
    SELECT 
        ct.id, ct.first_name, ct.last_name, ct.email, ct.phone, ct.job_title,
        c.name AS company_name
    FROM contacts ct
    INNER JOIN companies c ON ct.company_id = c.id
    ORDER BY ct.first_name ASC
";
$contacts = $pdo->query($sql)->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-white">Contacts (People)</h1>
        <p class="text-sm text-slate-400">Individuals associated with your client accounts.</p>
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
    <!-- Left: Contacts Table -->
    <div class="lg:col-span-2">
        <div class="bg-slate-800 border border-slate-700 rounded-xl overflow-hidden shadow-sm">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-900/60 text-xs uppercase text-slate-400 border-b border-slate-700">
                    <tr>
                        <th class="p-3.5">Name</th>
                        <th class="p-3.5">Company</th>
                        <th class="p-3.5">Title</th>
                        <th class="p-3.5">Contact Info</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/60">
                    <?php foreach ($contacts as $contact): ?>
                        <tr class="hover:bg-slate-700/30 transition">
                            <td class="p-3.5 font-semibold text-white">
                                <?= htmlspecialchars($contact['first_name'] . ' ' . $contact['last_name']) ?>
                            </td>
                            <td class="p-3.5 text-slate-400 font-medium">
                                <?= htmlspecialchars($contact['company_name']) ?>
                            </td>
                            <td class="p-3.5 text-slate-300"><?= htmlspecialchars($contact['job_title'] ?? 'N/A') ?></td>
                            <td class="p-3.5 text-xs text-slate-400">
                                <span class="block text-indigo-300 font-mono"><?= htmlspecialchars($contact['email']) ?></span>
                                <span><?= htmlspecialchars($contact['phone'] ?? '') ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right: Add Contact Form -->
    <div>
        <div class="bg-slate-800 border border-slate-700 rounded-xl p-6 shadow-sm">
            <h2 class="text-lg font-bold text-white mb-1">Add New Contact</h2>
            <p class="text-xs text-slate-400 mb-4">Creates a contact tuple referencing a foreign key <code>company_id</code>.</p>

            <form action="contacts.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create_contact">

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Associated Company *</label>
                    <select name="company_id" required 
                            class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                        <option value="">Select a company...</option>
                        <?php foreach ($companies as $comp): ?>
                            <option value="<?= $comp['id'] ?>"><?= htmlspecialchars($comp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">First Name *</label>
                        <input type="text" name="first_name" required placeholder="Jane" 
                               class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Last Name *</label>
                        <input type="text" name="last_name" required placeholder="Doe" 
                               class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Email Address *</label>
                    <input type="email" name="email" required placeholder="jane@example.com" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Phone Number</label>
                    <input type="text" name="phone" placeholder="+1-555-0199" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-1">Job Title</label>
                    <input type="text" name="job_title" placeholder="Director of Engineering" 
                           class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>

                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                    + Save Contact
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
