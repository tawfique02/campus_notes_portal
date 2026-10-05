<?php
/**
 * Administrator Overview Dashboard
 */

$pageTitle = "Super Admin Dashboard";
$isDashboard = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole(ROLE_ADMIN);
$pdo = Database::getConnection();

// Metrics
$totalUsers = $pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
$totalResources = $pdo->query("SELECT COUNT(*) FROM `resources`")->fetchColumn();
$pendingNotes = $pdo->query("SELECT COUNT(*) FROM `resources` WHERE `status` = 'pending'")->fetchColumn();
$totalDownloads = $pdo->query("SELECT IFNULL(SUM(`download_count`), 0) FROM `resources`")->fetchColumn();

// Dept Stats from DBMS View `view_department_statistics`
$deptStats = $pdo->query("SELECT * FROM `view_department_statistics` ORDER BY `total_resources` DESC")->fetchAll();

// Top Authors from DBMS View `view_user_contributions`
$topContributors = $pdo->query("SELECT * FROM `view_user_contributions` WHERE `total_uploads` > 0 ORDER BY `total_downloads_received` DESC LIMIT 5")->fetchAll();

// Recent Activity Logs
$recentLogs = $pdo->query("SELECT l.*, u.full_name FROM `activity_logs` l LEFT JOIN `users` u ON l.user_id = u.user_id ORDER BY l.created_at DESC LIMIT 6")->fetchAll();
?>

<div class="dashboard-wrapper">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="<?= BASE_URL ?>/admin/index.php" class="sidebar-brand">
                <i class="fa-solid fa-shield-halved text-primary"></i> Admin Center
            </a>
        </div>
        <div class="sidebar-user">
            <div class="user-avatar-circle"><?= strtoupper(substr($currentUser['name'], 0, 1)) ?></div>
            <div class="user-info-text">
                <div class="user-name"><?= htmlspecialchars($currentUser['name']) ?></div>
                <div class="user-role">Super Admin</div>
            </div>
        </div>
        <ul class="sidebar-nav">
            <li class="sidebar-heading">Core Administration</li>
            <li><a href="<?= BASE_URL ?>/admin/index.php" class="sidebar-link active"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>/admin/users.php" class="sidebar-link"><i class="fa-solid fa-users-gear"></i> User Management</a></li>
            <li><a href="<?= BASE_URL ?>/admin/departments.php" class="sidebar-link"><i class="fa-solid fa-building-columns"></i> Depts & Courses</a></li>
            <li><a href="<?= BASE_URL ?>/admin/resources.php" class="sidebar-link"><i class="fa-solid fa-folder-tree"></i> Note Moderation <?php if ($pendingNotes > 0): ?><span class="badge bg-warning text-dark"><?= $pendingNotes ?></span><?php endif; ?></a></li>
            <li class="sidebar-heading">DBMS Engine & Logs</li>
            <li><a href="<?= BASE_URL ?>/admin/logs.php" class="sidebar-link"><i class="fa-solid fa-clock-rotate-left"></i> Audit Trail</a></li>
            <li><a href="<?= BASE_URL ?>/admin/db_status.php" class="sidebar-link"><i class="fa-solid fa-database"></i> Database Diagnostics</a></li>
            <li class="sidebar-heading">Navigation</li>
            <li><a href="<?= BASE_URL ?>/index.php" class="sidebar-link"><i class="fa-solid fa-arrow-left"></i> Public Portal</a></li>
            <li><a href="<?= BASE_URL ?>/actions/auth_action.php?action=logout" class="sidebar-link text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign Out</a></li>
        </ul>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard-content">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">System Overview</h1>
                <p class="text-secondary small mb-0">Live analytics, DBMS relational integrity & user metrics</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/actions/admin_action.php?action=export_db" class="btn btn-outline-custom btn-sm">
                    <i class="fa-solid fa-download me-1"></i> Export SQL Dump
                </a>
                <a href="<?= BASE_URL ?>/faculty/upload.php" class="btn btn-primary-custom btn-sm">
                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Material
                </a>
            </div>
        </div>

        <?php displayFlash(); ?>

        <!-- KPI METRICS ROW -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon primary"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= number_format($totalUsers) ?></div>
                        <div class="stat-label">Registered Accounts</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon success"><i class="fa-solid fa-book-bookmark"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= number_format($totalResources) ?></div>
                        <div class="stat-label">Total Resources</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon info"><i class="fa-solid fa-download"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= number_format($totalDownloads) ?></div>
                        <div class="stat-label">Resource Downloads</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon warning"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= number_format($pendingNotes) ?></div>
                        <div class="stat-label">Pending Approval</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DEPARTMENT STATISTICS (VIEW-BACKED) -->
        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <div class="data-card h-100">
                    <div class="data-card-header">
                        <h5><i class="fa-solid fa-chart-pie text-primary me-2"></i> Department Analytics (DBMS View)</h5>
                        <span class="badge bg-light text-secondary border font-xs">view_department_statistics</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Department</th>
                                    <th>Courses</th>
                                    <th>Notes</th>
                                    <th>Total Downloads</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deptStats as $ds): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($ds['dept_code']) ?></strong>
                                            <div class="text-muted font-xs"><?= htmlspecialchars($ds['dept_name']) ?></div>
                                        </td>
                                        <td><?= $ds['total_courses'] ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= $ds['total_resources'] ?></span></td>
                                        <td><strong><?= number_format($ds['total_downloads']) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TOP SCHOLARS / CONTRIBUTORS (VIEW-BACKED) -->
            <div class="col-lg-5">
                <div class="data-card h-100">
                    <div class="data-card-header">
                        <h5><i class="fa-solid fa-trophy text-warning me-2"></i> Top Contributors</h5>
                        <span class="badge bg-light text-secondary border font-xs">view_user_contributions</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Uploads</th>
                                    <th>Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topContributors as $tc): ?>
                                    <tr>
                                        <td>
                                            <div class="font-weight-bold text-dark"><?= htmlspecialchars($tc['full_name']) ?></div>
                                            <span class="text-muted font-xs"><?= htmlspecialchars($tc['role_name']) ?></span>
                                        </td>
                                        <td><?= $tc['approved_uploads'] ?></td>
                                        <td><i class="fa-solid fa-star text-warning"></i> <?= number_format($tc['average_author_rating'], 1) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECENT AUDIT TRAIL -->
        <div class="data-card">
            <div class="data-card-header">
                <h5><i class="fa-solid fa-list-check text-info me-2"></i> Recent System Activity Logs</h5>
                <a href="<?= BASE_URL ?>/admin/logs.php" class="btn btn-outline-custom btn-sm">View Full Audit Log</a>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Target Entity</th>
                            <th>Details</th>
                            <th>IP Address</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLogs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['full_name'] ?? 'System') ?></td>
                                <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= htmlspecialchars($log['action']) ?></span></td>
                                <td><?= htmlspecialchars($log['entity_type']) ?> #<?= $log['entity_id'] ?></td>
                                <td class="text-secondary small"><?= htmlspecialchars($log['details']) ?></td>
                                <td><code><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></code></td>
                                <td class="text-muted small"><?= timeAgo($log['created_at']) ?></td>
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
