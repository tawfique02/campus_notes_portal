<?php
/**
 * User & Role Management (Admin)
 */

$pageTitle = "Manage Users & Roles";
$isDashboard = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole(ROLE_ADMIN);
$pdo = Database::getConnection();

// Fetch all users with their role and department info
$users = $pdo->query("SELECT u.*, r.display_name AS role_display, r.role_name, d.dept_code, d.dept_name 
                      FROM `users` u 
                      JOIN `roles` r ON u.role_id = r.role_id 
                      LEFT JOIN `departments` d ON u.dept_id = d.dept_id 
                      ORDER BY u.created_at DESC")->fetchAll();

$roles = $pdo->query("SELECT * FROM `roles` ORDER BY `role_id` ASC")->fetchAll();
?>

<div class="dashboard-wrapper">
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="<?= BASE_URL ?>/admin/index.php" class="sidebar-brand"><i class="fa-solid fa-shield-halved text-primary"></i> Admin Center</a>
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
            <li><a href="<?= BASE_URL ?>/admin/index.php" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>/admin/users.php" class="sidebar-link active"><i class="fa-solid fa-users-gear"></i> User Management</a></li>
            <li><a href="<?= BASE_URL ?>/admin/departments.php" class="sidebar-link"><i class="fa-solid fa-building-columns"></i> Depts & Courses</a></li>
            <li><a href="<?= BASE_URL ?>/admin/resources.php" class="sidebar-link"><i class="fa-solid fa-folder-tree"></i> Note Moderation</a></li>
            <li class="sidebar-heading">DBMS Engine & Logs</li>
            <li><a href="<?= BASE_URL ?>/admin/logs.php" class="sidebar-link"><i class="fa-solid fa-clock-rotate-left"></i> Audit Trail</a></li>
            <li><a href="<?= BASE_URL ?>/admin/db_status.php" class="sidebar-link"><i class="fa-solid fa-database"></i> Database Diagnostics</a></li>
            <li class="sidebar-heading">Navigation</li>
            <li><a href="<?= BASE_URL ?>/index.php" class="sidebar-link"><i class="fa-solid fa-arrow-left"></i> Public Portal</a></li>
        </ul>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard-content">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">User & Role Delegation</h1>
                <p class="text-secondary small mb-0">Modify privileges, assign roles (Updater, Faculty, Student) or suspend accounts.</p>
            </div>
        </div>

        <?php displayFlash(); ?>

        <div class="data-card">
            <div class="data-card-header">
                <h5><i class="fa-solid fa-users text-primary me-2"></i> All Registered Accounts (<?= count($users) ?>)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>User Name & Email</th>
                            <th>Academic ID</th>
                            <th>Department</th>
                            <th>Current Role</th>
                            <th>Account Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-avatar-circle" style="width: 34px; height: 34px; font-size: 0.85rem;">
                                            <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="font-weight-bold text-dark"><?= htmlspecialchars($u['full_name']) ?></div>
                                            <div class="text-muted font-xs"><?= htmlspecialchars($u['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><code><?= htmlspecialchars($u['academic_id'] ?? 'N/A') ?></code></td>
                                <td><?= htmlspecialchars($u['dept_code'] ?? 'None') ?></td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <?= htmlspecialchars($u['role_display']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-status badge-<?= $u['status'] ?>">
                                        <?= ucfirst($u['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-outline-custom btn-sm py-1 px-2" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $u['user_id'] ?>">
                                        <i class="fa-solid fa-user-pen me-1"></i> Modify
                                    </button>

                                    <!-- EDIT MODAL -->
                                    <div class="modal fade" id="editUserModal<?= $u['user_id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="<?= BASE_URL ?>/actions/admin_action.php" method="POST">
                                                    <?= Security::csrfField() ?>
                                                    <input type="hidden" name="action" value="update_user">
                                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">

                                                    <div class="modal-header">
                                                        <h5 class="modal-title font-weight-bold">Edit User: <?= htmlspecialchars($u['full_name']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold">Role Assignment</label>
                                                            <select name="role_id" class="form-select">
                                                                <?php foreach ($roles as $r): ?>
                                                                    <option value="<?= $r['role_id'] ?>" <?= ($u['role_id'] == $r['role_id']) ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($r['display_name']) ?> (<?= $r['role_name'] ?>)
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold">Account Status</label>
                                                            <select name="status" class="form-select">
                                                                <option value="active" <?= ($u['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                                                <option value="inactive" <?= ($u['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                                                <option value="suspended" <?= ($u['status'] === 'suspended') ? 'selected' : '' ?>>Suspended</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary-custom">Save Privileges</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
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
