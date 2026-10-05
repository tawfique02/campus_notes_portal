<?php
/**
 * Faculty / Contributor My Uploads Manager
 */

$pageTitle = "My Uploaded Resources";
$isDashboard = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireAuth();
$pdo = Database::getConnection();
$userId = $currentUser['id'];

// Fetch all resources uploaded by this user
$stmt = $pdo->prepare("SELECT r.*, c.course_code, c.course_title, d.dept_code, cat.name AS category_name 
                       FROM `resources` r 
                       JOIN `courses` c ON r.course_id = c.course_id 
                       JOIN `departments` d ON c.dept_id = d.dept_id 
                       JOIN `categories` cat ON r.category_id = cat.category_id 
                       WHERE r.uploaded_by = ? 
                       ORDER BY r.created_at DESC");
$stmt->execute([$userId]);
$myResources = $stmt->fetchAll();
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
                <div class="user-role"><?= htmlspecialchars($currentUser['role_name']) ?></div>
            </div>
        </div>
        <ul class="sidebar-nav">
            <li><a href="<?= BASE_URL ?>/faculty/index.php" class="sidebar-link"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            <li><a href="<?= BASE_URL ?>/faculty/upload.php" class="sidebar-link"><i class="fa-solid fa-cloud-arrow-up"></i> Upload New Note</a></li>
            <li><a href="<?= BASE_URL ?>/faculty/my_resources.php" class="sidebar-link active"><i class="fa-solid fa-folder-open"></i> My Uploads</a></li>
            <li><a href="<?= BASE_URL ?>/index.php" class="sidebar-link"><i class="fa-solid fa-arrow-left"></i> Public Portal</a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">My Uploaded Documents (<?= count($myResources) ?>)</h1>
                <p class="text-secondary small mb-0">Manage, inspect, or delete your published course notes and lab sheets.</p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/faculty/upload.php" class="btn btn-primary-custom btn-sm">
                    <i class="fa-solid fa-plus me-1"></i> Upload More
                </a>
            </div>
        </div>

        <?php displayFlash(); ?>

        <div class="data-card">
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Document Title</th>
                            <th>Course</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Downloads & Ratings</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($myResources)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-file-arrow-up text-primary fs-1 mb-2 d-block"></i>
                                    You have not uploaded any resources yet.
                                    <div class="mt-2"><a href="<?= BASE_URL ?>/faculty/upload.php" class="btn btn-primary-custom btn-sm">Upload Material</a></div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($myResources as $res): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid <?= getFileIconClass($res['file_type']) ?> fs-5"></i>
                                            <div>
                                                <a href="<?= BASE_URL ?>/resource_details.php?id=<?= $res['resource_id'] ?>" class="font-weight-bold text-dark text-decoration-none">
                                                    <?= htmlspecialchars($res['title']) ?>
                                                </a>
                                                <div class="text-muted font-xs"><?= formatBytes($res['file_size']) ?> &bull; <?= timeAgo($res['created_at']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="course-badge"><?= htmlspecialchars($res['course_code']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($res['category_name']) ?></td>
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
                                        <div class="d-flex gap-2">
                                            <a href="<?= BASE_URL ?>/resource_details.php?id=<?= $res['resource_id'] ?>" class="btn btn-outline-custom btn-sm py-1 px-2">
                                                <i class="fa-solid fa-eye"></i> View
                                            </a>
                                            <a href="<?= BASE_URL ?>/actions/resource_action.php?action=delete&id=<?= $res['resource_id'] ?>" class="btn btn-outline-danger btn-sm py-1 px-2" onclick="return confirm('Delete this note permanently?');">
                                                <i class="fa-solid fa-trash"></i>
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
