<?php
/**
 * NexaCRM - Database Connection (PDO)
 * Target: XAMPP MySQL / MariaDB
 * 
 * ACADEMIC NOTES:
 * - Uses PDO (PHP Data Objects) for database abstraction.
 * - Disables emulated prepared statements to force true parameter binding.
 * - Enables ERRMODE_EXCEPTION to ensure all SQL errors trigger catchable exceptions.
 */

$host = '127.0.0.1';
$port = '3306';
$db   = 'nexacrm_db';
$user = 'root';     // Default XAMPP username
$pass = '';         // Default XAMPP password (empty)

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Enforces true prepared statements
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database Connection Error: " . htmlspecialchars($e->getMessage()) . 
        "<br><br><strong>Tip:</strong> Ensure Apache & MySQL are running in XAMPP, and database <code>nexacrm_db</code> is imported from <code>sql/01_schema.sql</code>.");
}
