<?php
/**
 * User Flagged / Reported Resources Resolution
 */

$pageTitle = "Reported Resources Moderation";
$isDashboard = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole([ROLE_ADMIN, ROLE_MODERATOR]);
$pdo = Database::getConnection();

// Fetch Reports
$reports = $pdo->query("SELECT rep.*, r.title AS resource_title, r.file_path, u.full_name AS reporter_name 
                        FROM `reports` rep 
                        JOIN `resources` r ON rep.resource_id = r.resource_id 
                        JOIN `users` u ON rep.reported_by = u.user_id 
                        ORDER BY rep.created_at DESC")->fetchAll();
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
            <li><a href="<?= BASE_URL ?>/moderator/pending_reviews.php" class="sidebar-link"><i class="fa-solid fa-list-check"></i> Pending Queue</a></li>
            <li><a href="<?= BASE_URL ?>/moderator/reported_items.php" class="sidebar-link active"><i class="fa-solid fa-flag"></i> User Reports</a></li>
            <li><a href="<?= BASE_URL ?>/admin/departments.php" class="sidebar-link"><i class="fa-solid fa-building-columns"></i> Manage Courses</a></li>
            <li><a href="<?= BASE_URL ?>/index.php" class="sidebar-link"><i class="fa-solid fa-arrow-left"></i> Public Portal</a></li>
        </ul>
    </aside>

    <main class="dashboard-content">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Flagged Content & Reports</h1>
                <p class="text-secondary small mb-0">Investigate broken downloads, inaccurate notes, or copyright issues.</p>
            </div>
        </div>

        <?php displayFlash(); ?>

        <div class="data-card">
            <div class="data-card-header">
                <h5><i class="fa-solid fa-flag text-danger me-2"></i> User Reports (<?= count($reports) ?>)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Resource Title</th>
                            <th>Reported By</th>
                            <th>Reason & Details</th>
                            <th>Status</th>
                            <th>Resolution</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reports)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-shield-halved text-success fs-1 mb-2 d-block"></i>
                                    No reports found. All academic resources are in good standing!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reports as $rep): ?>
                                <tr>
                                    <td><code>#<?= $rep['report_id'] ?></code></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/resource_details.php?id=<?= $rep['resource_id'] ?>" class="font-weight-bold text-dark text-decoration-none">
                                            <?= htmlspecialchars($rep['resource_title']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($rep['reporter_name']) ?></td>
                                    <td>
                                        <strong class="text-danger"><?= htmlspecialchars($rep['reason']) ?></strong>
                                        <div class="text-secondary small"><?= htmlspecialchars($rep['details']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge-status badge-<?= $rep['status'] ?>">
                                            <?= ucfirst($rep['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form action="<?= BASE_URL ?>/actions/admin_action.php" method="POST" class="d-flex gap-1">
                                            <?= Security::csrfField() ?>
                                            <input type="hidden" name="action" value="resolve_report">
                                            <input type="hidden" name="report_id" value="<?= $rep['report_id'] ?>">
                                            <select name="status" class="form-select form-select-sm" style="width: 120px;">
                                                <option value="resolved" <?= ($rep['status'] === 'resolved') ? 'selected' : '' ?>>Resolved</option>
                                                <option value="dismissed" <?= ($rep['status'] === 'dismissed') ? 'selected' : '' ?>>Dismiss</option>
                                            </select>
                                            <button type="submit" class="btn btn-primary-custom btn-sm py-1 px-2">Update</button>
                                        </form>
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
