<?php
// Shared Navigation Header
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'NexaCRM' ?></title>
    <!-- Tailwind CSS for clean, modern styling with zero build step -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col font-sans">

    <!-- Top Navigation Bar -->
    <header class="bg-slate-800/90 border-b border-slate-700/80 sticky top-0 z-50 backdrop-blur">
        <div class="max-w-6xl mx-auto px-4 py-3.5 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <span class="text-2xl">⚡</span>
                <div>
                    <a href="index.php" class="font-bold text-lg text-white hover:text-indigo-400 transition">NexaCRM</a>
                    <span class="text-xs text-indigo-400 font-mono block">CSE311 Course Project</span>
                </div>
            </div>
            <nav class="flex space-x-1 sm:space-x-2 text-sm font-medium">
                <a href="index.php" class="px-3 py-1.5 rounded-lg <?= $currentPage == 'index.php' ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-700' ?>">Dashboard</a>
                <a href="companies.php" class="px-3 py-1.5 rounded-lg <?= $currentPage == 'companies.php' ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-700' ?>">Companies</a>
                <a href="contacts.php" class="px-3 py-1.5 rounded-lg <?= $currentPage == 'contacts.php' ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-700' ?>">Contacts</a>
                <a href="deals.php" class="px-3 py-1.5 rounded-lg <?= $currentPage == 'deals.php' ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-700' ?>">Deals</a>
                <a href="reports.php" class="px-3 py-1.5 rounded-lg <?= $currentPage == 'reports.php' ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:text-white hover:bg-slate-700' ?>">Reports</a>
            </nav>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8 flex-1 w-full">
