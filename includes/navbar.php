<?php
/**
 * Master Navigation Bar
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth_middleware.php';

$user = getCurrentUser();

// Clean Name helper for Navbar
$shortName = 'User';
if ($user && !empty($user['name'])) {
    $parts = explode(' ', trim($user['name']));
    if (in_array(strtolower($parts[0]), ['dr.', 'prof.', 'mr.', 'ms.', 'mrs.']) && isset($parts[1])) {
        $shortName = $parts[0] . ' ' . $parts[1];
    } else {
        $shortName = $parts[0];
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
            <span class="brand-badge"><i class="fa-solid fa-graduation-cap"></i></span>
            <span>
                <span class="brand-text-main">CampusNotes</span>
                <span class="brand-text-sub">Academic Resource Portal</span>
            </span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <i class="fa-solid fa-bars-staggered" style="color:var(--primary)"></i>
        </button>


        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php">
                        <i class="fa-solid fa-house"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'explore.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>/explore.php">
                        <i class="fa-solid fa-compass"></i> Explore Notes
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <?php if ($user): ?>
                    <!-- Role-specific Dashboard Shortcut -->
                    <?php if ($user['role_id'] == ROLE_ADMIN): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn-outline-custom btn-sm">
                            <i class="fa-solid fa-shield-halved text-primary"></i> Admin Panel
                        </a>
                    <?php elseif ($user['role_id'] == ROLE_MODERATOR): ?>
                        <a href="<?= BASE_URL ?>/moderator/index.php" class="btn btn-outline-custom btn-sm">
                            <i class="fa-solid fa-database text-info"></i> Updater Panel
                        </a>
                    <?php elseif ($user['role_id'] == ROLE_FACULTY): ?>
                        <a href="<?= BASE_URL ?>/faculty/upload.php" class="btn btn-primary-custom btn-sm">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Upload Note
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/student/index.php" class="btn btn-outline-custom btn-sm">
                            <i class="fa-solid fa-user-graduate text-primary"></i> My Portal
                        </a>
                    <?php endif; ?>

                    <!-- Sleek User Profile Dropdown Button -->
                    <div class="dropdown">
                        <a href="#" class="user-profile-btn dropdown-toggle d-inline-flex align-items-center gap-2 text-decoration-none" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar-circle">
                                <?= getAvatarLetter($user['name']) ?>
                            </div>
                            <span class="user-display-name fw-bold d-none d-sm-inline"><?= htmlspecialchars(getDisplayName($user['name'])) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 p-2 rounded-4" style="min-width: 220px;">
                            <li class="px-3 py-2 border-bottom mb-1">
                                <div class="fw-bold text-dark font-sm"><?= htmlspecialchars($user['name']) ?></div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-1 font-xs">
                                    <?= ucfirst($user['role_name']) ?>
                                </span>
                            </li>
                            <?php if ($user['role_id'] == ROLE_ADMIN): ?>
                                <li><a class="dropdown-item rounded-3 py-2" href="<?= BASE_URL ?>/admin/index.php"><i class="fa-solid fa-gauge-high me-2 text-muted"></i> Admin Dashboard</a></li>
                                <li><a class="dropdown-item rounded-3 py-2" href="<?= BASE_URL ?>/admin/users.php"><i class="fa-solid fa-users-gear me-2 text-muted"></i> Manage Users</a></li>
                                <li><a class="dropdown-item rounded-3 py-2" href="<?= BASE_URL ?>/admin/departments.php"><i class="fa-solid fa-building-columns me-2 text-muted"></i> Depts & Courses</a></li>
                                <li><a class="dropdown-item rounded-3 py-2" href="<?= BASE_URL ?>/admin/logs.php"><i class="fa-solid fa-clock-rotate-left me-2 text-muted"></i> Audit Logs</a></li>
                            <?php elseif ($user['role_id'] == ROLE_MODERATOR): ?>
                                <li><a class="dropdown-item rounded-3 py-2" href="<?= BASE_URL ?>/moderator/index.php"><i class="fa-solid fa-gauge-high me-2 text-muted"></i> Updater Dashboard</a></li>
                                <li><a class="dropdown-item rounded-3 py-2" href="<?= BASE_URL ?>/moderator/pending_reviews.php"><i class="fa-solid fa-list-check me-2 text-muted"></i> Moderation Queue</a></li>
                                <li><a class="dropdown-item rounded-3 py-2" href="<?= BASE_URL ?>/moderator/reported_items.php"><i class="fa-solid fa-flag me-2 text-muted"></i> Content Reports</a></li>
                            <?php elseif ($user['role_id'] == ROLE_FACULTY): ?>
                                <li><a class="dropdown-item rounded-3 py-2" href="<?= BASE_URL ?>/faculty/index.php"><i class="fa-solid fa-gauge-high me-2 text-muted"></i> Faculty Dashboard</a></li>
                                <li><a class="dropdown-item rounded-3 py-2" href="<?= BASE_URL ?>/faculty/upload.php"><i class="fa-solid fa-plus me-2 text-muted"></i> Upload New Note</a></li>
                                <li><a class="dropdown-item rounded-3 py-2" href="<?= BASE_URL ?>/faculty/my_resources.php"><i class="fa-solid fa-folder-open me-2 text-muted"></i> My Uploads</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item rounded-3 py-2" href="<?= BASE_URL ?>/student/index.php"><i class="fa-solid fa-gauge-high me-2 text-muted"></i> Student Dashboard</a></li>
                                <li><a class="dropdown-item rounded-3 py-2" href="<?= BASE_URL ?>/student/bookmarks.php"><i class="fa-solid fa-bookmark me-2 text-muted"></i> Saved Notes</a></li>
                                <li><a class="dropdown-item rounded-3 py-2" href="<?= BASE_URL ?>/student/my_downloads.php"><i class="fa-solid fa-download me-2 text-muted"></i> My Downloads</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item rounded-3 py-2" href="<?= BASE_URL ?>/profile.php"><i class="fa-solid fa-id-card me-2 text-muted"></i> Profile Settings</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item rounded-3 py-2 text-danger" href="<?= BASE_URL ?>/actions/auth_action.php?action=logout">
                                    <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-custom btn-sm">Log In</a>
                    <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary-custom btn-sm">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
