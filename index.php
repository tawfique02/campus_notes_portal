<?php
/**
 * Homepage - Campus Academic Resource & Notes Sharing Portal
 */

$pageTitle = "Home - University Academic Repository";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$pdo = Database::getConnection();

// Fetch Global Statistics
$stats = [
    'resources' => 0,
    'downloads' => 0,
    'courses' => 0,
    'users' => 0
];

try {
    $stats['resources'] = $pdo->query("SELECT COUNT(*) FROM `resources` WHERE `status` = 'approved'")->fetchColumn();
    $stats['downloads'] = $pdo->query("SELECT IFNULL(SUM(`download_count`), 0) FROM `resources` WHERE `status` = 'approved'")->fetchColumn();
    $stats['courses'] = $pdo->query("SELECT COUNT(*) FROM `courses`")->fetchColumn();
    $stats['users'] = $pdo->query("SELECT COUNT(*) FROM `users` WHERE `status` = 'active'")->fetchColumn();

    // Fetch Top Rated Resources from DBMS View `view_top_rated_resources`
    $topNotesStmt = $pdo->query("SELECT * FROM `view_top_rated_resources` ORDER BY `avg_rating` DESC, `download_count` DESC LIMIT 6");
    $topNotes = $topNotesStmt->fetchAll();

    // Fetch Categories
    $categories = $pdo->query("SELECT * FROM `categories` ORDER BY `category_id` ASC")->fetchAll();

    // Fetch Departments for quick filter
    $departments = $pdo->query("SELECT * FROM `departments` ORDER BY `dept_name` ASC")->fetchAll();

} catch (Exception $e) {
    // If table doesn't exist yet, show alert
    $topNotes = [];
    $categories = [];
    $departments = [];
}
?>

<!-- HERO SECTION â€” Nexus Notes Hub -->
<section class="hero-section">
    <!-- Animated Blobs -->
    <div class="hero-blob hero-blob-1"></div>
    <div class="hero-blob hero-blob-2"></div>

    <div class="container position-relative" style="z-index:2">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">

                <!-- Live Status Badge -->
                <div class="hero-badge mb-3 d-inline-flex">
                    <span class="live-dot"></span>
                    Nexus University &mdash; Academic Resource Hub
                </div>

                <h1 class="hero-title">
                    Access, Share &amp; <span class="highlight">Elevate</span><br>Your Nexus Learning
                </h1>
                <p class="hero-subtitle">
                    Find verified lecture notes, solved midterm question banks, lab manuals, and faculty presentations â€” curated for CSE, EEE, BBA &amp; Law students at Nexus.
                </p>

                <!-- Smart Search Bar -->
                <form action="<?= BASE_URL ?>/explore.php" method="GET" class="hero-search-box">
                    <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="q" placeholder="Search course code, title e.g. CSE-311, DBMS, Algorithms..." autocomplete="off">
                    <button type="submit" class="btn-search">
                        <i class="fa-solid fa-bolt me-1"></i> Search
                    </button>
                </form>

                <!-- Trending Tags -->
                <div class="d-flex align-items-center gap-2 flex-wrap mt-3">
                    <span class="text-white-50" style="font-size:0.82rem; font-weight:600;">
                        <i class="fa-solid fa-fire text-warning me-1"></i> Trending:
                    </span>
                    <a href="<?= BASE_URL ?>/explore.php?q=DBMS" class="trending-chip">CSE-311 DBMS</a>
                    <a href="<?= BASE_URL ?>/explore.php?q=OOP+Java" class="trending-chip">CSE-213 OOP</a>
                    <a href="<?= BASE_URL ?>/explore.php?q=Signals" class="trending-chip">EEE-201 Signals</a>
                    <a href="<?= BASE_URL ?>/explore.php?q=Management" class="trending-chip">BBA-101 Mgmt</a>
                    <a href="<?= BASE_URL ?>/explore.php?q=Algorithms" class="trending-chip">CSE-225 Algo</a>
                    <a href="<?= BASE_URL ?>/explore.php?q=Networks" class="trending-chip">CSE-317 Networks</a>
                </div>
            </div>

            <!-- Hero Stats -->
            <div class="col-lg-5">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="stat-pill">
                            <div class="stat-icon" style="background:rgba(99,102,241,0.15); color:#a5b4fc;">
                                <i class="fa-solid fa-file-lines"></i>
                            </div>
                            <span class="number" id="count-resources" data-target="<?= $stats['resources'] ?>"><?= $stats['resources'] ?></span>
                            <span class="label">Verified Notes</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-pill">
                            <div class="stat-icon" style="background:rgba(14,165,233,0.15); color:#7dd3fc;">
                                <i class="fa-solid fa-download"></i>
                            </div>
                            <span class="number" id="count-downloads" data-target="<?= $stats['downloads'] ?>"><?= $stats['downloads'] ?></span>
                            <span class="label">Total Downloads</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-pill">
                            <div class="stat-icon" style="background:rgba(245,158,11,0.15); color:#fcd34d;">
                                <i class="fa-solid fa-book-open"></i>
                            </div>
                            <span class="number" id="count-courses" data-target="<?= $stats['courses'] ?>"><?= $stats['courses'] ?></span>
                            <span class="label">Nexus Courses</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-pill">
                            <div class="stat-icon" style="background:rgba(16,185,129,0.15); color:#6ee7b7;">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <span class="number" id="count-users" data-target="<?= $stats['users'] ?>"><?= $stats['users'] ?></span>
                            <span class="label">Active Scholars</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FLASH MESSAGES -->
<div class="container mt-4">
    <?php displayFlash(); ?>
</div>

<!-- CATEGORIES SECTION -->
<section class="py-5 bg-white border-bottom">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <span class="section-header-badge bg-primary-subtle text-primary" style="background:#e8f0fb;color:#003366;border:1px solid #b8d0e8;">
                    <i class="fa-solid fa-grid-2"></i> Browse by Type
                </span>
                <h3 class="mb-1">Resource Categories</h3>
                <p class="text-secondary small mb-0">Select a material category to explore specific study materials</p>
            </div>
            <a href="<?= BASE_URL ?>/explore.php" class="btn btn-outline-custom btn-sm">
                View All <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-3">
            <?php foreach ($categories as $cat): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="<?= BASE_URL ?>/explore.php?category=<?= $cat['category_id'] ?>" class="category-card text-decoration-none">
                        <div class="cat-icon">
                            <i class="fa-solid <?= htmlspecialchars($cat['icon']) ?>"></i>
                        </div>
                        <div class="cat-name"><?= htmlspecialchars($cat['name']) ?></div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- TOP RATED & FEATURED RESOURCES -->
<section class="py-5" style="background:#f5f7fa;">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <span class="section-header-badge" style="background:#e9f7ef;color:#1a7f37;border:1px solid #a3d9b1;display:inline-flex;align-items:center;gap:6px;font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:5px 14px;border-radius:9999px;margin-bottom:0.6rem;">
                    <i class="fa-solid fa-star"></i> Highly Rated
                </span>
                <h3 class="mb-1">Top-Rated Academic Notes</h3>
                <p class="text-secondary small mb-0">Aggregated via DBMS view <code>view_top_rated_resources</code></p>
            </div>
            <a href="<?= BASE_URL ?>/explore.php?sort=rating" class="btn btn-outline-custom btn-sm">
                Explore All <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php if (empty($topNotes)): ?>
                <div class="col-12">
                    <div class="card p-5 text-center border-0 rounded-4" style="background:#fff;box-shadow:0 2px 12px rgba(0,51,102,0.07);">
                        <i class="fa-solid fa-folder-open fa-3x text-muted mb-3"></i>
                        <h5>No resources published yet</h5>
                        <p class="text-secondary small">Database is ready. Be the first to share study notes!</p>
                        <?php if (isLoggedIn()): ?>
                            <div><a href="<?= BASE_URL ?>/faculty/upload.php" class="btn btn-primary-custom btn-sm">Upload Material</a></div>
                        <?php else: ?>
                            <div><a href="<?= BASE_URL ?>/login.php" class="btn btn-primary-custom btn-sm">Sign In to Upload</a></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($topNotes as $note): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="resource-card">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <div class="file-badge">
                                    <i class="fa-solid <?= getFileIconClass($note['file_type']) ?>"></i>
                                </div>
                                <span class="course-badge"><?= htmlspecialchars($note['course_code']) ?></span>
                            </div>

                            <h5 class="title">
                                <a href="<?= BASE_URL ?>/resource_details.php?id=<?= $note['resource_id'] ?>">
                                    <?= htmlspecialchars($note['title']) ?>
                                </a>
                            </h5>

                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="tag-chip">
                                    <i class="fa-solid fa-building-columns"></i> <?= htmlspecialchars($note['dept_code']) ?>
                                </span>
                                <span class="tag-chip">
                                    <i class="fa-solid fa-tag"></i> <?= htmlspecialchars($note['category_name']) ?>
                                </span>
                            </div>

                            <div class="mb-3">
                                <?= renderStarRating($note['avg_rating']) ?>
                                <span class="text-muted small ms-1">(<?= $note['rating_count'] ?> reviews)</span>
                            </div>

                            <div class="card-meta">
                                <span>
                                    <i class="fa-solid fa-user-pen me-1 text-muted"></i>
                                    <?= htmlspecialchars(getDisplayName($note['author_name'])) ?>
                                </span>
                                <span>
                                    <i class="fa-solid fa-download me-1 text-muted"></i>
                                    <?= $note['download_count'] ?> DLs
                                </span>
                                <a href="<?= BASE_URL ?>/resource_details.php?id=<?= $note['resource_id'] ?>" class="btn btn-primary-custom btn-sm">
                                    View Note
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- CALL TO ACTION BANNER -->
<section class="cta-section py-5 text-white text-center">
    <div class="container py-3 position-relative" style="z-index:2;">
        <i class="fa-solid fa-cloud-arrow-up fa-2x mb-3" style="color:#86efac;"></i>
        <h2 class="display-6 fw-bold mb-3 text-white">Have Great Lecture Notes or Lab Sheets?</h2>
        <p class="mx-auto mb-4" style="max-width:600px;color:#bfd7ea;font-size:1.05rem;">
            Help fellow students master challenging coursework. Upload your handwritten notes, study guides, and lab reports today.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <?php if (isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/faculty/upload.php" class="btn btn-accent-custom px-4 py-2">
                    <i class="fa-solid fa-cloud-arrow-up me-2"></i> Share a Note Now
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/register.php" class="btn btn-accent-custom px-4 py-2">
                    <i class="fa-solid fa-user-plus me-2"></i> Join CampusNotes
                </a>
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-light px-4 py-2">
                    <i class="fa-solid fa-right-to-bracket me-2"></i> Sign In
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

