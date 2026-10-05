<?php
/**
 * Faculty / Contributor Dashboard
 */

$pageTitle = "Faculty Academic Dashboard";
$isDashboard = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole([ROLE_ADMIN, ROLE_FACULTY, ROLE_MODERATOR]);
$pdo = Database::getConnection();

// Fetch stats for current user
$userId = $currentUser['id'];
$myUploadsCount = $pdo->prepare("SELECT COUNT(*) FROM `resources` WHERE `uploaded_by` = ?");
$myUploadsCount->execute([$userId]);
$totalUploads = $myUploadsCount->fetchColumn();

$myDownloadsCount = $pdo->prepare("SELECT IFNULL(SUM(`download_count`), 0) FROM `resources` WHERE `uploaded_by` = ?");
$myDownloadsCount->execute([$userId]);
$totalDownloads = $myDownloadsCount->fetchColumn();

$myAvgRating = $pdo->prepare("SELECT IFNULL(AVG(`avg_rating`), 0) FROM `resources` WHERE `uploaded_by` = ? AND `rating_count` > 0");
$myAvgRating->execute([$userId]);
$avgRating = $myAvgRating->fetchColumn();

// Fetch recent uploaded materials
$myRecentNotes = $pdo->prepare("SELECT r.*, c.course_code, cat.name AS category_name 
                                FROM `resources` r 
                                JOIN `courses` c ON r.course_id = c.course_id 
                                JOIN `categories` cat ON r.category_id = cat.category_id 
                                WHERE r.uploaded_by = ? 
                                ORDER BY r.created_at DESC LIMIT 5");
$myRecentNotes->execute([$userId]);
$recentNotes = $myRecentNotes->fetchAll();
?>

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="<?= BASE_URL ?>/faculty/index.php" class="sidebar-brand"><i class="fa-solid fa-chalkboard-user text-primary"></i> Faculty Panel</a>
        </div>
        <div class="sidebar-user">
            <div class="user-avatar-circle"><?= strtoupper(substr($currentUser['name'], 0, 1)) ?></div>
            <div class="user-info-text">
                <div class="user-name"><?= htmlspecialchars($currentUser['name']) ?></div>
                <div class="user-role">Faculty Member</div>
            </div>
        </div>
        <ul class="sidebar-nav">
            <li class="sidebar-heading">My Academic Workspace</li>
            <li><a href="<?= BASE_URL ?>/faculty/index.php" class="sidebar-link active"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>/faculty/upload.php" class="sidebar-link"><i class="fa-solid fa-cloud-arrow-up"></i> Upload New Note</a></li>
            <li><a href="<?= BASE_URL ?>/faculty/my_resources.php" class="sidebar-link"><i class="fa-solid fa-folder-open"></i> My Uploads (<?= $totalUploads ?>)</a></li>
            <li class="sidebar-heading">Navigation</li>
            <li><a href="<?= BASE_URL ?>/index.php" class="sidebar-link"><i class="fa-solid fa-arrow-left"></i> Public Portal</a></li>
            <li><a href="<?= BASE_URL ?>/explore.php" class="sidebar-link"><i class="fa-solid fa-compass"></i> Explore Catalog</a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Faculty Knowledge Hub</h1>
                <p class="text-secondary small mb-0">Track student engagement, lecture note downloads, and feedback ratings.</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/faculty/upload.php" class="btn btn-primary-custom btn-sm">
                    <i class="fa-solid fa-plus me-1"></i> Upload Course Material
                </a>
            </div>
        </div>

        <?php displayFlash(); ?>

        <!-- METRICS ROW -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon primary"><i class="fa-solid fa-file-invoice"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= number_format($totalUploads) ?></div>
                        <div class="stat-label">Published Documents</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon info"><i class="fa-solid fa-download"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= number_format($totalDownloads) ?></div>
                        <div class="stat-label">Total Student Downloads</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon warning"><i class="fa-solid fa-star"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= number_format($avgRating, 1) ?> / 5.0</div>
                        <div class="stat-label">Average Material Rating</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECENT UPLOADS -->
        <div class="data-card">
            <div class="data-card-header">
                <h5><i class="fa-solid fa-folder-open text-primary me-2"></i> Recent Shared Materials</h5>
                <a href="<?= BASE_URL ?>/faculty/my_resources.php" class="btn btn-outline-custom btn-sm">View All Uploads</a>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Resource Title</th>
                            <th>Course</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Engagement</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentNotes)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-cloud-arrow-up text-primary fs-1 mb-2 d-block"></i>
                                    You haven't uploaded any study materials yet.
                                    <div class="mt-2"><a href="<?= BASE_URL ?>/faculty/upload.php" class="btn btn-primary-custom btn-sm">Upload your first note</a></div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentNotes as $n): ?>
                                <tr>
                                    <td>
                                        <a href="<?= BASE_URL ?>/resource_details.php?id=<?= $n['resource_id'] ?>" class="font-weight-bold text-dark text-decoration-none">
                                            <?= htmlspecialchars($n['title']) ?>
                                        </a>
                                        <div class="text-muted font-xs"><?= formatBytes($n['file_size']) ?></div>
                                    </td>
                                    <td><span class="course-badge"><?= htmlspecialchars($n['course_code']) ?></span></td>
                                    <td><?= htmlspecialchars($n['category_name']) ?></td>
                                    <td>
                                        <span class="badge-status badge-<?= $n['status'] ?>">
                                            <?= ucfirst($n['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small text-secondary">
                                            <i class="fa-solid fa-download me-1"></i> <?= $n['download_count'] ?> |
                                            <i class="fa-solid fa-star text-warning me-1"></i> <?= number_format($n['avg_rating'], 1) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/resource_details.php?id=<?= $n['resource_id'] ?>" class="btn btn-outline-custom btn-sm py-1 px-2">
                                            <i class="fa-solid fa-eye"></i> View
                                        </a>
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
