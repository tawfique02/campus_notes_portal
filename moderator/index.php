<?php
/**
 * Database Updater & Moderator Dashboard
 */

$pageTitle = "Updater & Moderator Dashboard";
$isDashboard = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole([ROLE_ADMIN, ROLE_MODERATOR]);
$pdo = Database::getConnection();

// Metrics
$pendingNotes = $pdo->query("SELECT COUNT(*) FROM `resources` WHERE `status` = 'pending'")->fetchColumn();
$approvedNotes = $pdo->query("SELECT COUNT(*) FROM `resources` WHERE `status` = 'approved'")->fetchColumn();
$pendingReports = $pdo->query("SELECT COUNT(*) FROM `reports` WHERE `status` = 'pending'")->fetchColumn();
$totalCourses = $pdo->query("SELECT COUNT(*) FROM `courses`")->fetchColumn();

// Fetch Pending Queue
$queue = $pdo->query("SELECT r.*, c.course_code, d.dept_code, cat.name AS category_name, u.full_name AS author_name 
                      FROM `resources` r 
                      JOIN `courses` c ON r.course_id = c.course_id 
                      JOIN `departments` d ON c.dept_id = d.dept_id 
                      JOIN `categories` cat ON r.category_id = cat.category_id 
                      JOIN `users` u ON r.uploaded_by = u.user_id 
                      WHERE r.status = 'pending' 
                      ORDER BY r.created_at ASC LIMIT 5")->fetchAll();
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
            <li class="sidebar-heading">Content Operations</li>
            <li><a href="<?= BASE_URL ?>/moderator/index.php" class="sidebar-link active"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>/moderator/pending_reviews.php" class="sidebar-link"><i class="fa-solid fa-list-check"></i> Pending Queue <?php if ($pendingNotes > 0): ?><span class="badge bg-warning text-dark"><?= $pendingNotes ?></span><?php endif; ?></a></li>
            <li><a href="<?= BASE_URL ?>/moderator/reported_items.php" class="sidebar-link"><i class="fa-solid fa-flag"></i> User Reports <?php if ($pendingReports > 0): ?><span class="badge bg-danger"><?= $pendingReports ?></span><?php endif; ?></a></li>
            <li class="sidebar-heading">Data Structure</li>
            <li><a href="<?= BASE_URL ?>/admin/departments.php" class="sidebar-link"><i class="fa-solid fa-building-columns"></i> Manage Courses & Depts</a></li>
            <li class="sidebar-heading">Navigation</li>
            <li><a href="<?= BASE_URL ?>/index.php" class="sidebar-link"><i class="fa-solid fa-arrow-left"></i> Public Portal</a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Database Updater & Content Review</h1>
                <p class="text-secondary small mb-0">Maintain academic data cleanliness, course mappings, and vetting pipelines.</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/moderator/pending_reviews.php" class="btn btn-primary-custom btn-sm">
                    <i class="fa-solid fa-list-check me-1"></i> Review Pending Notes (<?= $pendingNotes ?>)
                </a>
            </div>
        </div>

        <?php displayFlash(); ?>

        <!-- METRICS ROW -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon warning"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= number_format($pendingNotes) ?></div>
                        <div class="stat-label">Pending Reviews</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon success"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= number_format($approvedNotes) ?></div>
                        <div class="stat-label">Approved Catalog</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon danger"><i class="fa-solid fa-flag"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= number_format($pendingReports) ?></div>
                        <div class="stat-label">User Flags & Reports</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon info"><i class="fa-solid fa-book-open"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= number_format($totalCourses) ?></div>
                        <div class="stat-label">Mapped Courses</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PENDING APPROVAL LIST -->
        <div class="data-card">
            <div class="data-card-header">
                <h5><i class="fa-solid fa-clipboard-check text-primary me-2"></i> Review Queue</h5>
                <a href="<?= BASE_URL ?>/moderator/pending_reviews.php" class="btn btn-outline-custom btn-sm">Open Full Queue</a>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Resource Title</th>
                            <th>Course</th>
                            <th>Author</th>
                            <th>Submitted</th>
                            <th>Quick Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($queue)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-check-circle text-success fs-3 mb-2 d-block"></i>
                                    Moderation queue is clean! All submitted notes have been vetted.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($queue as $q): ?>
                                <tr>
                                    <td>
                                        <div class="font-weight-bold text-dark"><?= htmlspecialchars($q['title']) ?></div>
                                        <div class="text-muted font-xs"><?= formatBytes($q['file_size']) ?> &bull; <?= htmlspecialchars($q['category_name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="course-badge"><?= htmlspecialchars($q['course_code']) ?></span>
                                        <span class="badge bg-light text-secondary border font-xs"><?= htmlspecialchars($q['dept_code']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($q['author_name']) ?></td>
                                    <td class="text-muted small"><?= timeAgo($q['created_at']) ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <!-- Approve Button (Calls sp_approve_resource) -->
                                            <form action="<?= BASE_URL ?>/actions/resource_action.php" method="POST" class="d-inline">
                                                <?= Security::csrfField() ?>
                                                <input type="hidden" name="action" value="moderate">
                                                <input type="hidden" name="resource_id" value="<?= $q['resource_id'] ?>">
                                                <input type="hidden" name="status" value="approved">
                                                <input type="hidden" name="redirect_to" value="<?= BASE_URL ?>/moderator/index.php">
                                                <button type="submit" class="btn btn-success btn-sm py-1 px-2" title="Approve with Stored Procedure">
                                                    <i class="fa-solid fa-check me-1"></i> Approve
                                                </button>
                                            </form>
                                            <a href="<?= BASE_URL ?>/actions/download_action.php?id=<?= $q['resource_id'] ?>" class="btn btn-outline-custom btn-sm py-1 px-2" title="Inspect File">
                                                <i class="fa-solid fa-eye"></i>
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
