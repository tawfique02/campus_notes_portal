<?php
/**
 * Student Saved Bookmarks Manager
 */

$pageTitle = "My Bookmarked Notes";
$isDashboard = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireAuth();
$pdo = Database::getConnection();
$userId = $currentUser['id'];

// Fetch all bookmarked resources
$stmt = $pdo->prepare("SELECT r.*, c.course_code, c.course_title, d.dept_code, cat.name AS category_name, u.full_name AS author_name, bm.created_at AS bookmarked_at 
                       FROM `bookmarks` bm 
                       JOIN `resources` r ON bm.resource_id = r.resource_id 
                       JOIN `courses` c ON r.course_id = c.course_id 
                       JOIN `departments` d ON c.dept_id = d.dept_id 
                       JOIN `categories` cat ON r.category_id = cat.category_id 
                       JOIN `users` u ON r.uploaded_by = u.user_id 
                       WHERE bm.user_id = ? 
                       ORDER BY bm.created_at DESC");
$stmt->execute([$userId]);
$bookmarks = $stmt->fetchAll();
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
            <li><a href="<?= BASE_URL ?>/student/index.php" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i> My Portal</a></li>
            <li><a href="<?= BASE_URL ?>/student/bookmarks.php" class="sidebar-link active"><i class="fa-solid fa-bookmark"></i> Saved Bookmarks (<?= count($bookmarks) ?>)</a></li>
            <li><a href="<?= BASE_URL ?>/student/my_downloads.php" class="sidebar-link"><i class="fa-solid fa-download"></i> Download History</a></li>
            <li><a href="<?= BASE_URL ?>/faculty/upload.php" class="sidebar-link"><i class="fa-solid fa-cloud-arrow-up"></i> Share a Note</a></li>
            <li><a href="<?= BASE_URL ?>/index.php" class="sidebar-link"><i class="fa-solid fa-arrow-left"></i> Public Portal</a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Saved Academic Resources (<?= count($bookmarks) ?>)</h1>
                <p class="text-secondary small mb-0">Quickly revisit and download your bookmarked materials before exams.</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/explore.php" class="btn btn-primary-custom btn-sm">
                    <i class="fa-solid fa-plus me-1"></i> Discover More
                </a>
            </div>
        </div>

        <?php displayFlash(); ?>

        <div class="data-card">
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Resource Title</th>
                            <th>Course</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Rating</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookmarks)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-regular fa-bookmark text-secondary fs-1 mb-2 d-block"></i>
                                    No saved bookmarks. Browse the catalog and click the bookmark icon on any note.
                                    <div class="mt-2"><a href="<?= BASE_URL ?>/explore.php" class="btn btn-primary-custom btn-sm">Browse Catalog</a></div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookmarks as $bm): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid <?= getFileIconClass($bm['file_type']) ?> fs-5"></i>
                                            <div>
                                                <a href="<?= BASE_URL ?>/resource_details.php?id=<?= $bm['resource_id'] ?>" class="font-weight-bold text-dark text-decoration-none">
                                                    <?= htmlspecialchars($bm['title']) ?>
                                                </a>
                                                <div class="text-muted font-xs"><?= formatBytes($bm['file_size']) ?> &bull; Saved <?= timeAgo($bm['bookmarked_at']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="course-badge"><?= htmlspecialchars($bm['course_code']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($bm['category_name']) ?></td>
                                    <td><?= htmlspecialchars($bm['author_name']) ?></td>
                                    <td><?= renderStarRating($bm['avg_rating']) ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="<?= BASE_URL ?>/actions/download_action.php?id=<?= $bm['resource_id'] ?>" class="btn btn-primary-custom btn-sm py-1 px-2">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                            <button class="btn btn-outline-danger btn-sm py-1 px-2 btn-bookmark-toggle" data-resource-id="<?= $bm['resource_id'] ?>" data-url="<?= BASE_URL ?>/actions/bookmark_action.php" onclick="location.reload();">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
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
