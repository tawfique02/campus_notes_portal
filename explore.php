<?php
/**
 * Explore Catalog & Advanced Filter Page
 */

$pageTitle = "Explore Academic Resources & Notes";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$pdo = Database::getConnection();

// Capture Query Filters
$keyword = trim($_GET['q'] ?? '');
$deptId = !empty($_GET['dept']) ? (int)$_GET['dept'] : 0;
$courseId = !empty($_GET['course']) ? (int)$_GET['course'] : 0;
$categoryId = !empty($_GET['category']) ? (int)$_GET['category'] : 0;
$fileType = trim($_GET['file_type'] ?? '');
$sortBy = trim($_GET['sort'] ?? 'newest');

// Build Dynamic SQL Query with Prepared Statements
$whereClauses = ["r.`status` = 'approved'"];
$params = [];

if (!empty($keyword)) {
    $whereClauses[] = "(r.`title` LIKE ? OR r.`description` LIKE ? OR c.`course_code` LIKE ? OR c.`course_title` LIKE ?)";
    $searchWildcard = "%$keyword%";
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
    $params[] = $searchWildcard;
}

if ($deptId > 0) {
    $whereClauses[] = "c.`dept_id` = ?";
    $params[] = $deptId;
}

if ($courseId > 0) {
    $whereClauses[] = "r.`course_id` = ?";
    $params[] = $courseId;
}

if ($categoryId > 0) {
    $whereClauses[] = "r.`category_id` = ?";
    $params[] = $categoryId;
}

if (!empty($fileType)) {
    $whereClauses[] = "r.`file_type` = ?";
    $params[] = $fileType;
}

// Determine Order By Clause
$orderBy = "r.`created_at` DESC";
if ($sortBy === 'rating') {
    $orderBy = "r.`avg_rating` DESC, r.`rating_count` DESC";
} elseif ($sortBy === 'downloads') {
    $orderBy = "r.`download_count` DESC";
} elseif ($sortBy === 'oldest') {
    $orderBy = "r.`created_at` ASC";
}

$whereSql = implode(' AND ', $whereClauses);

$sql = "SELECT 
            r.*, 
            c.`course_code`, 
            c.`course_title`, 
            d.`dept_code`, 
            d.`dept_name`, 
            cat.`name` AS `category_name`, 
            cat.`icon` AS `category_icon`, 
            u.`full_name` AS `author_name`
        FROM `resources` r
        JOIN `courses` c ON r.`course_id` = c.`course_id`
        JOIN `departments` d ON c.`dept_id` = d.`dept_id`
        JOIN `categories` cat ON r.`category_id` = cat.`category_id`
        JOIN `users` u ON r.`uploaded_by` = u.`user_id`
        WHERE $whereSql
        ORDER BY $orderBy";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resources = $stmt->fetchAll();

    // Fetch Filter Options
    $departments = $pdo->query("SELECT * FROM `departments` ORDER BY `dept_name` ASC")->fetchAll();
    $categories = $pdo->query("SELECT * FROM `categories` ORDER BY `name` ASC")->fetchAll();
    $courses = $pdo->query("SELECT * FROM `courses` ORDER BY `course_code` ASC")->fetchAll();
} catch (Exception $e) {
    $resources = [];
    $departments = [];
    $categories = [];
    $courses = [];
}
?>

<div class="bg-white border-bottom py-4 mb-4">
    <div class="container">
        <h1 class="h3 font-weight-bold mb-1">Academic Resource Catalog</h1>
        <p class="text-secondary small mb-0">Discover verified semester notes, lecture handouts, and exam archives.</p>
    </div>
</div>

<div class="container mb-5">
    <?php displayFlash(); ?>

    <div class="row g-4">
        <!-- FILTER SIDEBAR -->
        <div class="col-lg-3">
            <div class="card border rounded-4 shadow-sm p-4 bg-white sticky-top" style="top: 85px; z-index: 10;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="font-weight-bold mb-0"><i class="fa-solid fa-sliders text-primary me-2"></i> Filters</h5>
                    <a href="<?= BASE_URL ?>/explore.php" class="text-secondary small text-decoration-none">Reset All</a>
                </div>

                <form action="<?= BASE_URL ?>/explore.php" method="GET">
                    <!-- Keyword Search -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Search Keyword</label>
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="Title, code, keyword..." value="<?= htmlspecialchars($keyword) ?>">
                    </div>

                    <!-- Department Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Department</label>
                        <select name="dept" id="dept_select" class="form-select form-select-sm">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['dept_id'] ?>" <?= ($deptId == $d['dept_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['dept_code']) ?> - <?= htmlspecialchars($d['dept_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Course Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Course</label>
                        <select name="course" id="course_select" class="form-select form-select-sm">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['course_id'] ?>" data-dept="<?= $c['dept_id'] ?>" <?= ($courseId == $c['course_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['course_code']) ?>: <?= htmlspecialchars($c['course_title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Category</label>
                        <select name="category" class="form-select form-select-sm">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= ($categoryId == $cat['category_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- File Type Filter -->
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">File Format</label>
                        <select name="file_type" class="form-select form-select-sm">
                            <option value="">All Formats</option>
                            <option value="pdf" <?= ($fileType == 'pdf') ? 'selected' : '' ?>>PDF Document</option>
                            <option value="docx" <?= ($fileType == 'docx') ? 'selected' : '' ?>>Word (DOCX)</option>
                            <option value="pptx" <?= ($fileType == 'pptx') ? 'selected' : '' ?>>PowerPoint (PPTX)</option>
                            <option value="zip" <?= ($fileType == 'zip') ? 'selected' : '' ?>>ZIP Archive</option>
                        </select>
                    </div>

                    <!-- Sort Order -->
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Sort By</label>
                        <select name="sort" class="form-select form-select-sm">
                            <option value="newest" <?= ($sortBy == 'newest') ? 'selected' : '' ?>>Newest First</option>
                            <option value="rating" <?= ($sortBy == 'rating') ? 'selected' : '' ?>>Highest Rated</option>
                            <option value="downloads" <?= ($sortBy == 'downloads') ? 'selected' : '' ?>>Most Downloaded</option>
                            <option value="oldest" <?= ($sortBy == 'oldest') ? 'selected' : '' ?>>Oldest First</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary-custom w-100 btn-sm">
                        <i class="fa-solid fa-filter me-1"></i> Apply Filters
                    </button>
                </form>
            </div>
        </div>

        <!-- RESULTS GRID -->
        <div class="col-lg-9">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-secondary small font-weight-bold">
                    Found <strong><?= count($resources) ?></strong> verified academic resources
                </span>
            </div>

            <?php if (empty($resources)): ?>
                <div class="card border rounded-4 p-5 text-center bg-white shadow-sm">
                    <i class="fa-solid fa-magnifying-glass-chart fs-1 text-muted mb-3"></i>
                    <h5>No Matching Resources Found</h5>
                    <p class="text-secondary small">Try changing your filters or searching with different keywords.</p>
                    <div><a href="<?= BASE_URL ?>/explore.php" class="btn btn-outline-custom btn-sm">Clear All Filters</a></div>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($resources as $res): ?>
                        <div class="col-md-6">
                            <div class="resource-card shadow-sm">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div class="file-badge">
                                        <i class="fa-solid <?= getFileIconClass($res['file_type']) ?>"></i>
                                    </div>
                                    <span class="course-badge"><?= htmlspecialchars($res['course_code']) ?></span>
                                </div>

                                <h5 class="title">
                                    <a href="<?= BASE_URL ?>/resource_details.php?id=<?= $res['resource_id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($res['title']) ?>
                                    </a>
                                </h5>

                                <p class="desc"><?= htmlspecialchars($res['description']) ?></p>

                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="tag-chip">
                                        <i class="fa-solid fa-building-columns text-muted"></i> <?= htmlspecialchars($res['dept_code']) ?>
                                    </span>
                                    <span class="tag-chip">
                                        <i class="fa-solid fa-tag text-muted"></i> <?= htmlspecialchars($res['category_name']) ?>
                                    </span>
                                    <span class="tag-chip">
                                        <i class="fa-solid fa-hard-drive text-muted"></i> <?= formatBytes($res['file_size']) ?>
                                    </span>
                                </div>

                                <div class="mb-3">
                                    <?= renderStarRating($res['avg_rating']) ?>
                                    <span class="text-muted small ms-1">(<?= $res['rating_count'] ?> reviews)</span>
                                </div>

                                <div class="card-meta">
                                    <span>
                                        <i class="fa-solid fa-user-pen me-1 text-muted"></i> <?= htmlspecialchars(getDisplayName($res['author_name'])) ?>
                                    </span>
                                    <span>
                                        <i class="fa-solid fa-download me-1 text-muted"></i> <?= $res['download_count'] ?>
                                    </span>
                                    <a href="<?= BASE_URL ?>/resource_details.php?id=<?= $res['resource_id'] ?>" class="btn btn-primary-custom btn-sm py-1 px-3">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
