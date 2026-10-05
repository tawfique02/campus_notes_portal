<?php
/**
 * Student Download History Log
 */

$pageTitle = "My Download History";
$isDashboard = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireAuth();
$pdo = Database::getConnection();
$userId = $currentUser['id'];

// Fetch user downloads
$stmt = $pdo->prepare("SELECT dl.*, r.title AS resource_title, r.file_size, r.file_type, c.course_code 
                       FROM `download_logs` dl 
                       JOIN `resources` r ON dl.resource_id = r.resource_id 
                       JOIN `courses` c ON r.course_id = c.course_id 
                       WHERE dl.user_id = ? 
                       ORDER BY dl.downloaded_at DESC");
$stmt->execute([$userId]);
$downloads = $stmt->fetchAll();
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
            <li><a href="<?= BASE_URL ?>/student/bookmarks.php" class="sidebar-link"><i class="fa-solid fa-bookmark"></i> Saved Bookmarks</a></li>
            <li><a href="<?= BASE_URL ?>/student/my_downloads.php" class="sidebar-link active"><i class="fa-solid fa-download"></i> Download History (<?= count($downloads) ?>)</a></li>
            <li><a href="<?= BASE_URL ?>/faculty/upload.php" class="sidebar-link"><i class="fa-solid fa-cloud-arrow-up"></i> Share a Note</a></li>
            <li><a href="<?= BASE_URL ?>/index.php" class="sidebar-link"><i class="fa-solid fa-arrow-left"></i> Public Portal</a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">My Download History (<?= count($downloads) ?>)</h1>
                <p class="text-secondary small mb-0">Record of academic materials you have accessed and downloaded.</p>
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
                            <th>Format & Size</th>
                            <th>Downloaded At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($downloads)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-cloud-arrow-down text-primary fs-1 mb-2 d-block"></i>
                                    You have not downloaded any documents yet.
                                    <div class="mt-2"><a href="<?= BASE_URL ?>/explore.php" class="btn btn-primary-custom btn-sm">Explore Resources</a></div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($downloads as $dl): ?>
                                <tr>
                                    <td>
                                        <a href="<?= BASE_URL ?>/resource_details.php?id=<?= $dl['resource_id'] ?>" class="font-weight-bold text-dark text-decoration-none">
                                            <?= htmlspecialchars($dl['resource_title']) ?>
                                        </a>
                                    </td>
                                    <td><span class="course-badge"><?= htmlspecialchars($dl['course_code']) ?></span></td>
                                    <td>
                                        <span class="text-uppercase fw-bold"><?= htmlspecialchars($dl['file_type']) ?></span>
                                        <span class="text-muted font-xs">(<?= formatBytes($dl['file_size']) ?>)</span>
                                    </td>
                                    <td class="text-muted small"><?= htmlspecialchars($dl['downloaded_at']) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/actions/download_action.php?id=<?= $dl['resource_id'] ?>" class="btn btn-primary-custom btn-sm py-1 px-2">
                                            <i class="fa-solid fa-download me-1"></i> Re-download
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
