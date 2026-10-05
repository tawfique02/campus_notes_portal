<?php
/**
 * System Audit & Activity Logs Viewer
 */

$pageTitle = "System Activity & Audit Logs";
$isDashboard = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole([ROLE_ADMIN, ROLE_MODERATOR]);
$pdo = Database::getConnection();

// Fetch Audit Logs with User information
$logs = $pdo->query("SELECT l.*, u.full_name, u.email, r.role_name 
                    FROM `activity_logs` l 
                    LEFT JOIN `users` u ON l.user_id = u.user_id 
                    LEFT JOIN `roles` r ON u.role_id = r.role_id 
                    ORDER BY l.created_at DESC LIMIT 100")->fetchAll();
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
                <div class="user-role"><?= htmlspecialchars($currentUser['role_name']) ?></div>
            </div>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?= BASE_URL ?>/admin/index.php" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            <?php if ($currentUser['role_id'] == ROLE_ADMIN): ?>
                <li><a href="<?= BASE_URL ?>/admin/users.php" class="sidebar-link"><i class="fa-solid fa-users-gear"></i> User Management</a></li>
            <?php endif; ?>
            <li><a href="<?= BASE_URL ?>/admin/departments.php" class="sidebar-link"><i class="fa-solid fa-building-columns"></i> Depts & Courses</a></li>
            <li><a href="<?= BASE_URL ?>/admin/resources.php" class="sidebar-link"><i class="fa-solid fa-folder-tree"></i> Note Moderation</a></li>
            <li><a href="<?= BASE_URL ?>/admin/logs.php" class="sidebar-link active"><i class="fa-solid fa-clock-rotate-left"></i> Audit Trail</a></li>
            <li><a href="<?= BASE_URL ?>/admin/db_status.php" class="sidebar-link"><i class="fa-solid fa-database"></i> Database Diagnostics</a></li>
            <li><a href="<?= BASE_URL ?>/index.php" class="sidebar-link"><i class="fa-solid fa-arrow-left"></i> Public Portal</a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">System Audit Trail</h1>
                <p class="text-secondary small mb-0">Complete relational log of user actions, status modifications, and security events.</p>
            </div>
        </div>

        <div class="data-card">
            <div class="data-card-header">
                <h5><i class="fa-solid fa-shield-cat text-primary me-2"></i> Audit Records (Last 100 Transactions)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Log ID</th>
                            <th>Actor / User</th>
                            <th>Action Performed</th>
                            <th>Target Entity</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><code>#<?= $log['activity_id'] ?></code></td>
                                <td>
                                    <?php if ($log['full_name']): ?>
                                        <div class="font-weight-bold text-dark"><?= htmlspecialchars($log['full_name']) ?></div>
                                        <div class="text-muted font-xs"><?= ucfirst($log['role_name'] ?? '') ?></div>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">System Daemon</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-xs">
                                        <?= htmlspecialchars($log['action']) ?>
                                    </span>
                                </td>
                                <td><code><?= htmlspecialchars($log['entity_type']) ?> #<?= $log['entity_id'] ?></code></td>
                                <td class="text-secondary small"><?= htmlspecialchars($log['details']) ?></td>
                                <td><code><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></code></td>
                                <td class="text-muted small"><?= htmlspecialchars($log['created_at']) ?></td>
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
