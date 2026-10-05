<?php
/**
 * Database Engine & Schema Diagnostics
 */

$pageTitle = "DBMS Diagnostics & Table Status";
$isDashboard = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole(ROLE_ADMIN);
$pdo = Database::getConnection();

// Query Table Status from information_schema
$dbName = 'campus_notes_db';
$tableStmt = $pdo->prepare("SELECT 
        TABLE_NAME, 
        ENGINE, 
        TABLE_ROWS, 
        DATA_LENGTH, 
        INDEX_LENGTH, 
        (DATA_LENGTH + INDEX_LENGTH) AS TOTAL_SIZE,
        CREATE_TIME
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = ?
    ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC");
$tableStmt->execute([$dbName]);
$tables = $tableStmt->fetchAll();

// Query Triggers
$triggers = $pdo->query("SHOW TRIGGERS FROM `$dbName`")->fetchAll();

// Total DB Size Calculation
$totalDbBytes = 0;
foreach ($tables as $t) {
    $totalDbBytes += $t['TOTAL_SIZE'];
}
?>

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="<?= BASE_URL ?>/admin/index.php" class="sidebar-brand"><i class="fa-solid fa-shield-halved text-primary"></i> Management</a>
        </div>
        <div class="sidebar-user">
            <div class="user-avatar-circle"><?= strtoupper(substr($currentUser['name'], 0, 1)) ?></div>
            <div class="user-info-text">
                <div class="user-name"><?= htmlspecialchars($currentUser['name']) ?></div>
                <div class="user-role">Super Admin</div>
            </div>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?= BASE_URL ?>/admin/index.php" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>/admin/users.php" class="sidebar-link"><i class="fa-solid fa-users-gear"></i> User Management</a></li>
            <li><a href="<?= BASE_URL ?>/admin/departments.php" class="sidebar-link"><i class="fa-solid fa-building-columns"></i> Depts & Courses</a></li>
            <li><a href="<?= BASE_URL ?>/admin/resources.php" class="sidebar-link"><i class="fa-solid fa-folder-tree"></i> Note Moderation</a></li>
            <li><a href="<?= BASE_URL ?>/admin/logs.php" class="sidebar-link"><i class="fa-solid fa-clock-rotate-left"></i> Audit Trail</a></li>
            <li><a href="<?= BASE_URL ?>/admin/db_status.php" class="sidebar-link active"><i class="fa-solid fa-database"></i> Database Diagnostics</a></li>
            <li><a href="<?= BASE_URL ?>/index.php" class="sidebar-link"><i class="fa-solid fa-arrow-left"></i> Public Portal</a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">DBMS Storage & Diagnostics</h1>
                <p class="text-secondary small mb-0">MySQL (InnoDB) physical table allocation, trigger verification, and relational constraints.</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/actions/admin_action.php?action=export_db" class="btn btn-primary-custom btn-sm">
                    <i class="fa-solid fa-file-export me-1"></i> Generate SQL Backup Dump
                </a>
            </div>
        </div>

        <?php displayFlash(); ?>

        <!-- METRICS ROW -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon primary"><i class="fa-solid fa-table"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= count($tables) ?></div>
                        <div class="stat-label">Relational Tables</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon success"><i class="fa-solid fa-bolt"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= count($triggers) ?></div>
                        <div class="stat-label">Active Triggers</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon info"><i class="fa-solid fa-hard-drive"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= formatBytes($totalDbBytes) ?></div>
                        <div class="stat-label">Total Allocated Size</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLE STORAGE METRICS -->
        <div class="data-card mb-4">
            <div class="data-card-header">
                <h5><i class="fa-solid fa-server text-primary me-2"></i> Physical Table Footprint (information_schema)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Table Name</th>
                            <th>Storage Engine</th>
                            <th>Estimated Rows</th>
                            <th>Data Size</th>
                            <th>Index Size</th>
                            <th>Total Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tables as $t): ?>
                            <tr>
                                <td><strong class="text-primary font-monospace"><?= htmlspecialchars($t['TABLE_NAME']) ?></strong></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($t['ENGINE']) ?></span></td>
                                <td><?= number_format($t['TABLE_ROWS']) ?></td>
                                <td><?= formatBytes($t['DATA_LENGTH']) ?></td>
                                <td><?= formatBytes($t['INDEX_LENGTH']) ?></td>
                                <td><strong><?= formatBytes($t['TOTAL_SIZE']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TRIGGERS LIST -->
        <div class="data-card">
            <div class="data-card-header">
                <h5><i class="fa-solid fa-gears text-warning me-2"></i> Automated DBMS Triggers</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Trigger Name</th>
                            <th>Event</th>
                            <th>Target Table</th>
                            <th>Timing</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($triggers as $trg): ?>
                            <tr>
                                <td><strong class="font-monospace text-dark"><?= htmlspecialchars($trg['Trigger']) ?></strong></td>
                                <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= htmlspecialchars($trg['Event']) ?></span></td>
                                <td><span class="font-monospace"><?= htmlspecialchars($trg['Table']) ?></span></td>
                                <td><span class="badge bg-light text-secondary border"><?= htmlspecialchars($trg['Timing']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
