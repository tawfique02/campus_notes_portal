<?php
/**
 * Resource Moderation Queue (Approve / Reject via Stored Procedure)
 */

$pageTitle = "Resource Moderation Queue";
$isDashboard = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole([ROLE_ADMIN, ROLE_MODERATOR]);
$pdo = Database::getConnection();

$queue = $pdo->query("SELECT r.*, c.course_code, c.course_title, d.dept_code, cat.name AS category_name, u.full_name AS author_name, u.email AS author_email 
                      FROM `resources` r 
                      JOIN `courses` c ON r.course_id = c.course_id 
                      JOIN `departments` d ON c.dept_id = d.dept_id 
                      JOIN `categories` cat ON r.category_id = cat.category_id 
                      JOIN `users` u ON r.uploaded_by = u.user_id 
                      WHERE r.status = 'pending' 
                      ORDER BY r.created_at ASC")->fetchAll();
?>

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="<?= BASE_URL ?>/moderator/index.php" class="sidebar-brand"><i class="fa-solid fa-database text-info"></i> Updater Panel</a>
        </div>
        <div class="sidebar-user">
            <div class="user-avatar-circle"><?= strtoupper(substr($currentUser['name'], 0, 1)) ?></div>
            <div class="user-info-text">
                <div class="user-name"><?= htmlspecialchars($currentUser['name']) ?></div>
                <div class="user-role">DB Updater / Mod</div>
            </div>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?= BASE_URL ?>/moderator/index.php" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>/moderator/pending_reviews.php" class="sidebar-link active"><i class="fa-solid fa-list-check"></i> Pending Queue</a></li>
            <li><a href="<?= BASE_URL ?>/moderator/reported_items.php" class="sidebar-link"><i class="fa-solid fa-flag"></i> User Reports</a></li>
            <li><a href="<?= BASE_URL ?>/admin/departments.php" class="sidebar-link"><i class="fa-solid fa-building-columns"></i> Manage Courses</a></li>
            <li><a href="<?= BASE_URL ?>/index.php" class="sidebar-link"><i class="fa-solid fa-arrow-left"></i> Public Portal</a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Pending Moderation Queue</h1>
                <p class="text-secondary small mb-0">Evaluate student note submissions before they appear in public search.</p>
            </div>
        </div>

        <?php displayFlash(); ?>

        <div class="data-card">
            <div class="data-card-header">
                <h5><i class="fa-solid fa-hourglass-half text-warning me-2"></i> Documents Awaiting Verification (<?= count($queue) ?>)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Resource Title & Description</th>
                            <th>Course</th>
                            <th>Contributor</th>
                            <th>File Info</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($queue)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-circle-check text-success fs-1 mb-2 d-block"></i>
                                    All submitted resources have been vetted and approved!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($queue as $item): ?>
                                <tr>
                                    <td style="max-width: 320px;">
                                        <div class="font-weight-bold text-dark"><?= htmlspecialchars($item['title']) ?></div>
                                        <div class="text-secondary small text-truncate"><?= htmlspecialchars($item['description']) ?></div>
                                    </td>
                                    <td>
                                        <span class="course-badge"><?= htmlspecialchars($item['course_code']) ?></span>
                                        <div class="text-muted font-xs"><?= htmlspecialchars($item['dept_code']) ?></div>
                                    </td>
                                    <td>
                                        <div class="font-sm font-weight-bold"><?= htmlspecialchars($item['author_name']) ?></div>
                                        <div class="text-muted font-xs"><?= htmlspecialchars($item['author_email']) ?></div>
                                    </td>
                                    <td>
                                        <span class="tag-chip text-uppercase"><?= htmlspecialchars($item['file_type']) ?></span>
                                        <div class="text-muted font-xs mt-1"><?= formatBytes($item['file_size']) ?></div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <!-- Approve Form (Calls sp_approve_resource) -->
                                            <form action="<?= BASE_URL ?>/actions/resource_action.php" method="POST" class="d-inline">
                                                <?= Security::csrfField() ?>
                                                <input type="hidden" name="action" value="moderate">
                                                <input type="hidden" name="resource_id" value="<?= $item['resource_id'] ?>">
                                                <input type="hidden" name="status" value="approved">
                                                <input type="hidden" name="moderation_notes" value="Approved by moderator review.">
                                                <button type="submit" class="btn btn-success btn-sm" title="Approve & Publish">
                                                    <i class="fa-solid fa-check me-1"></i> Approve
                                                </button>
                                            </form>

                                            <!-- Reject Form -->
                                            <form action="<?= BASE_URL ?>/actions/resource_action.php" method="POST" class="d-inline">
                                                <?= Security::csrfField() ?>
                                                <input type="hidden" name="action" value="moderate">
                                                <input type="hidden" name="resource_id" value="<?= $item['resource_id'] ?>">
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Reject this note submission?');" title="Reject">
                                                    <i class="fa-solid fa-xmark me-1"></i> Reject
                                                </button>
                                            </form>

                                            <a href="<?= BASE_URL ?>/actions/download_action.php?id=<?= $item['resource_id'] ?>" class="btn btn-outline-custom btn-sm" title="Download & Check File">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
