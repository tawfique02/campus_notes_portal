<?php
/**
 * Automated Database Installer and Seed Script
 * Campus Academic Resource & Notes Sharing Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$message = '';
$error = '';
$installed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || (php_sapi_name() === 'cli')) {
    try {
        $host = '127.0.0.1';
        $user = 'root';
        $pass = ''; // Default XAMPP

        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $sqlFile = __DIR__ . '/schema.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("schema.sql file not found in database directory.");
        }

        $sqlContent = file_get_contents($sqlFile);

        // Execute queries separated by DELIMITER or standard split
        // For stored procedures/triggers, split on standard blocks or execute via multi-query
        $pdo->exec($sqlContent);

        // Ensure uploads directory exists
        $uploadDir = ROOT_PATH . '/assets/uploads/notes/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Create sample placeholder PDF/text files
        $sampleFiles = [
            'sample_dbms_notes.pdf'      => "Sample DBMS Master Lecture Note: 1NF, 2NF, 3NF Normalization, ER Diagrams, and SQL queries.",
            'sample_question_bank.pdf'   => "Sample Past Exam Paper Question Bank with solutions for CSE-311 DBMS.",
            'sample_algo_cheatsheet.pdf' => "Sample Data Structures & Graph Algorithms Cheatsheet: Dijkstra, BFS, DFS, Sorting.",
            'sample_lab_manual.pdf'      => "Sample DBMS Laboratory Manual: SQL Stored Procedures and Triggers.",
            'sample_oop_notes.pdf'       => "Sample OOP Java Complete Notes: Classes, Inheritance, Polymorphism, JavaFX GUI.",
            'sample_bba_notes.pdf'       => "Sample BBA Principles of Management Notes: Leadership, Motivation and Strategy.",
            'sample_eee_slides.pdf'      => "Sample Signals and Systems Slides: Fourier Transform, Laplace Transform, z-Transform.",
            'sample_networks.pdf'        => "Sample Computer Networks Reference: OSI Model, TCP/IP, Routing, Subnetting.",
        ];

        foreach ($sampleFiles as $fileName => $dummyContent) {
            $filePath = $uploadDir . $fileName;
            if (!file_exists($filePath)) {
                file_put_contents($filePath, "%PDF-1.4\n%CampusNotesDemo\n" . $dummyContent);
            }
        }

        $message = "Database 'campus_notes_db' and all 13 relational tables, triggers, views, stored procedures, and sample data have been initialized successfully!";
        $installed = true;

        if (php_sapi_name() === 'cli') {
            echo "\n[SUCCESS] " . $message . "\n";
            exit(0);
        }

    } catch (Exception $e) {
        $error = "Installation Error: " . $e->getMessage();
        if (php_sapi_name() === 'cli') {
            echo "\n[ERROR] " . $error . "\n";
            exit(1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup Wizard - Campus Notes Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --success: #10b981;
            --danger: #ef4444;
            --border: #334155;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: var(--bg); color: var(--text-main); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .setup-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; width: 100%; max-width: 650px; padding: 35px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); }
        .badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 9999px; background: rgba(79, 70, 229, 0.2); color: #818cf8; font-size: 12px; font-weight: 600; margin-bottom: 16px; }
        h1 { font-size: 24px; font-weight: 800; margin-bottom: 8px; color: #fff; }
        p { color: var(--text-muted); font-size: 14px; line-height: 1.6; margin-bottom: 20px; }
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: #6ee7b7; }
        .alert-danger { background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: #fca5a5; }
        .demo-box { background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); border-radius: 12px; padding: 18px; margin-bottom: 25px; }
        .demo-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #cbd5e1; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .user-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .user-pill { background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border); padding: 10px; border-radius: 8px; font-size: 12px; }
        .user-pill strong { color: #818cf8; display: block; font-size: 13px; margin-bottom: 4px; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px 20px; background: var(--primary); color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; text-decoration: none; transition: 0.2s; }
        .btn:hover { background: var(--primary-dark); }
        .btn-success { background: var(--success); }
        .btn-success:hover { background: #059669; }
        .features-list { list-style: none; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 20px; font-size: 13px; color: #cbd5e1; }
        .features-list li { display: flex; align-items: center; gap: 8px; }
        .features-list li i { color: var(--success); }
    </style>
</head>
<body>
    <div class="setup-card">
        <div class="badge"><i class="fa-solid fa-database"></i> DBMS Project Setup Engine</div>
        <h1>Campus Academic Resource Portal</h1>
        <p>This automated installation wizard will configure your MySQL database with normalized tables (3NF), Foreign Key constraints, Triggers, Analytical Views, and Stored Procedures.</p>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <div><?= htmlspecialchars($message) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <ul class="features-list">
            <li><i class="fa-solid fa-check"></i> 13 Relational Tables (3NF)</li>
            <li><i class="fa-solid fa-check"></i> 4 Automated Triggers</li>
            <li><i class="fa-solid fa-check"></i> 3 Analytical DBMS Views</li>
            <li><i class="fa-solid fa-check"></i> 2 Stored Procedures</li>
            <li><i class="fa-solid fa-check"></i> Audit Activity Logs</li>
            <li><i class="fa-solid fa-check"></i> 4 Role RBAC Setup</li>
        </ul>

        <div class="demo-box">
            <div class="demo-title"><i class="fa-solid fa-users"></i> Pre-seeded Demo Accounts (Password: <code>password123</code>)</div>
            <div class="user-grid">
                <div class="user-pill">
                    <strong>Super Admin</strong>
                    <code>admin@campus.edu</code>
                </div>
                <div class="user-pill">
                    <strong>DB Updater / Moderator</strong>
                    <code>updater@campus.edu</code>
                </div>
                <div class="user-pill">
                    <strong>Faculty Member</strong>
                    <code>faculty@campus.edu</code>
                </div>
                <div class="user-pill">
                    <strong>Student</strong>
                    <code>student@campus.edu</code>
                </div>
            </div>
        </div>

        <?php if ($installed): ?>
            <a href="../login.php" class="btn btn-success"><i class="fa-solid fa-arrow-right-to-bracket"></i> Proceed to Login Portal</a>
        <?php else: ?>
            <form method="POST">
                <button type="submit" class="btn"><i class="fa-solid fa-play"></i> Initialize Database & Demo Records</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
