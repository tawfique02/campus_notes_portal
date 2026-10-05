<?php
/**
 * Student Academic Portal Dashboard
 */

$pageTitle = "Student Academic Portal";
$isDashboard = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireAuth();
$pdo = Database::getConnection();
$userId = $currentUser['id'];

// Fetch Bookmarks count
$bmCount = $pdo->prepare("SELECT COUNT(*) FROM `bookmarks` WHERE `user_id` = ?");
$bmCount->execute([$userId]);
$totalBookmarks = $bmCount->fetchColumn();

// Fetch Downloads count
$dlCount = $pdo->prepare("SELECT COUNT(*) FROM `download_logs` WHERE `user_id` = ?");
$dlCount->execute([$userId]);
$totalDownloads = $dlCount->fetchColumn();

// Fetch Saved Bookmarks
$bookmarks = $pdo->prepare("SELECT r.*, c.course_code, d.dept_code, cat.name AS category_name, u.full_name AS author_name 
                            FROM `bookmarks` bm 
                            JOIN `resources` r ON bm.resource_id = r.resource_id 
                            JOIN `courses` c ON r.course_id = c.course_id 
                            JOIN `departments` d ON c.dept_id = d.dept_id 
                            JOIN `categories` cat ON r.category_id = cat.category_id 
                            JOIN `users` u ON r.uploaded_by = u.user_id 
                            WHERE bm.user_id = ? 
                            ORDER BY bm.created_at DESC LIMIT 4");
$bookmarks->execute([$userId]);
$savedNotes = $bookmarks->fetchAll();
?>

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="<?= BASE_URL ?>/student/index.php" class="sidebar-brand"><i class="fa-solid fa-graduation-cap text-primary"></i> Student Portal</a>
        </div>
        <div class="sidebar-user">
            <div class="user-avatar-circle"><?= strtoupper(substr($currentUser['name'], 0, 1)) ?></div>
            <div class="user-info-text">
                <div class="user-name"><?= htmlspecialchars($currentUser['name']) ?></div>
                <div class="user-role">Student</div>
            </div>
        </div>
        <ul class="sidebar-nav">
            <li class="sidebar-heading">My Learning Space</li>
            <li><a href="<?= BASE_URL ?>/student/index.php" class="sidebar-link active"><i class="fa-solid fa-gauge-high"></i> My Portal</a></li>
            <li><a href="<?= BASE_URL ?>/student/bookmarks.php" class="sidebar-link"><i class="fa-solid fa-bookmark"></i> Saved Bookmarks (<?= $totalBookmarks ?>)</a></li>
            <li><a href="<?= BASE_URL ?>/student/my_downloads.php" class="sidebar-link"><i class="fa-solid fa-download"></i> Download History</a></li>
            <li><a href="<?= BASE_URL ?>/faculty/upload.php" class="sidebar-link"><i class="fa-solid fa-cloud-arrow-up"></i> Share a Note</a></li>
            <li class="sidebar-heading">Navigation</li>
            <li><a href="<?= BASE_URL ?>/index.php" class="sidebar-link"><i class="fa-solid fa-arrow-left"></i> Public Portal</a></li>
            <li><a href="<?= BASE_URL ?>/explore.php" class="sidebar-link"><i class="fa-solid fa-compass"></i> Explore Catalog</a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Welcome, <?= htmlspecialchars($currentUser['name']) ?>!</h1>
                <p class="text-secondary small mb-0">Your personal academic repository, bookmarked study guides, and exam archives.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/explore.php" class="btn btn-outline-custom btn-sm">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Browse Catalog
                </a>
                <a href="<?= BASE_URL ?>/faculty/upload.php" class="btn btn-primary-custom btn-sm">
                    <i class="fa-solid fa-plus me-1"></i> Share Note
                </a>
            </div>
        </div>

        <?php displayFlash(); ?>

        <!-- METRICS ROW -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-icon primary"><i class="fa-solid fa-bookmark"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= number_format($totalBookmarks) ?></div>
                        <div class="stat-label">Saved Bookmarks</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-icon info"><i class="fa-solid fa-cloud-arrow-down"></i></div>
                    <div class="stat-info">
                        <div class="stat-value"><?= number_format($totalDownloads) ?></div>
                        <div class="stat-label">Materials Downloaded</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BOOKMARKS SECTION -->
        <div class="data-card">
            <div class="data-card-header">
                <h5><i class="fa-solid fa-bookmark text-danger me-2"></i> Quick Access Saved Notes</h5>
                <a href="<?= BASE_URL ?>/student/bookmarks.php" class="btn btn-outline-custom btn-sm">View All Bookmarks</a>
            </div>
            <div class="p-3">
                <?php if (empty($savedNotes)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa-regular fa-bookmark text-secondary fs-1 mb-2 d-block"></i>
                        You haven't bookmarked any study materials yet.
                        <div class="mt-2"><a href="<?= BASE_URL ?>/explore.php" class="btn btn-primary-custom btn-sm">Explore Notes</a></div>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($savedNotes as $note): ?>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-light hover-shadow transition">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <span class="course-badge"><?= htmlspecialchars($note['course_code']) ?></span>
                                        <div class="small text-warning"><?= renderStarRating($note['avg_rating']) ?></div>
                                    </div>
                                    <h6 class="font-weight-bold mb-1">
                                        <a href="<?= BASE_URL ?>/resource_details.php?id=<?= $note['resource_id'] ?>" class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($note['title']) ?>
                                        </a>
                                    </h6>
                                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top text-secondary small">
                                        <span><i class="fa-solid fa-user-pen me-1"></i> <?= htmlspecialchars(explode(' ', $note['author_name'])[0]) ?></span>
                                        <a href="<?= BASE_URL ?>/actions/download_action.php?id=<?= $note['resource_id'] ?>" class="btn btn-primary-custom btn-sm py-1 px-3">
                                            <i class="fa-solid fa-download me-1"></i> Download
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
