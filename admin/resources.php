<?php
/**
 * Resource Moderation & Catalog Management (Admin)
 */

$pageTitle = "Resource Moderation & Archive";
$isDashboard = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole([ROLE_ADMIN, ROLE_MODERATOR]);
$pdo = Database::getConnection();

// Fetch Resources with all relations
$resources = $pdo->query("SELECT r.*, c.course_code, c.course_title, d.dept_code, cat.name AS category_name, u.full_name AS author_name 
                          FROM `resources` r 
                          JOIN `courses` c ON r.course_id = c.course_id 
                          JOIN `departments` d ON c.dept_id = d.dept_id 
                          JOIN `categories` cat ON r.category_id = cat.category_id 
                          JOIN `users` u ON r.uploaded_by = u.user_id 
                          ORDER BY r.created_at DESC")->fetchAll();
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
            <li><a href="<?= BASE_URL ?>/admin/resources.php" class="sidebar-link active"><i class="fa-solid fa-folder-tree"></i> Note Moderation</a></li>
            <li><a href="<?= BASE_URL ?>/admin/logs.php" class="sidebar-link"><i class="fa-solid fa-clock-rotate-left"></i> Audit Trail</a></li>
            <li><a href="<?= BASE_URL ?>/admin/db_status.php" class="sidebar-link"><i class="fa-solid fa-database"></i> Database Diagnostics</a></li>
            <li><a href="<?= BASE_URL ?>/index.php" class="sidebar-link"><i class="fa-solid fa-arrow-left"></i> Public Portal</a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Academic Resources Master Table</h1>
                <p class="text-secondary small mb-0">Approve, reject, download, or permanently remove uploaded study documents.</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/faculty/upload.php" class="btn btn-primary-custom btn-sm">
                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload New Note
                </a>
            </div>
        </div>

        <?php displayFlash(); ?>

        <div class="data-card">
            <div class="data-card-header">
                <h5><i class="fa-solid fa-folder-open text-primary me-2"></i> All Notes & Documents (<?= count($resources) ?>)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Resource Title</th>
                            <th>Course & Dept</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Metrics</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resources as $res): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid <?= getFileIconClass($res['file_type']) ?> fs-5"></i>
                                        <div>
                                            <a href="<?= BASE_URL ?>/resource_details.php?id=<?= $res['resource_id'] ?>" class="font-weight-bold text-dark text-decoration-none">
                                                <?= htmlspecialchars($res['title']) ?>
                                            </a>
                                            <div class="text-muted font-xs"><?= formatBytes($res['file_size']) ?> &bull; <?= htmlspecialchars($res['category_name']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="course-badge"><?= htmlspecialchars($res['course_code']) ?></span>
                                    <span class="badge bg-light text-secondary border font-xs"><?= htmlspecialchars($res['dept_code']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($res['author_name']) ?></td>
                                <td>
                                    <span class="badge-status badge-<?= $res['status'] ?>">
                                        <?= ucfirst($res['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small text-secondary">
                                        <i class="fa-solid fa-download me-1"></i> <?= $res['download_count'] ?> | 
                                        <i class="fa-solid fa-star text-warning me-1"></i> <?= number_format($res['avg_rating'], 1) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <?php if ($res['status'] === 'pending'): ?>
                                            <form action="<?= BASE_URL ?>/actions/resource_action.php" method="POST" class="d-inline">
                                                <?= Security::csrfField() ?>
                                                <input type="hidden" name="action" value="moderate">
                                                <input type="hidden" name="resource_id" value="<?= $res['resource_id'] ?>">
                                                <input type="hidden" name="status" value="approved">
                                                <input type="hidden" name="redirect_to" value="<?= BASE_URL ?>/admin/resources.php">
                                                <button type="submit" class="btn btn-success btn-sm py-1 px-2" title="Approve with Stored Procedure">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <a href="<?= BASE_URL ?>/actions/download_action.php?id=<?= $res['resource_id'] ?>" class="btn btn-outline-custom btn-sm py-1 px-2" title="Download File">
                                            <i class="fa-solid fa-download"></i>
                                        </a>

                                        <a href="<?= BASE_URL ?>/actions/resource_action.php?action=delete&id=<?= $res['resource_id'] ?>" class="btn btn-outline-danger btn-sm py-1 px-2" onclick="return confirm('Delete this note permanently?');" title="Delete Note">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
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
