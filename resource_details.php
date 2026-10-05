<?php
/**
 * Detailed Resource Viewer, Rating, and Discussion Page
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth_middleware.php';
require_once __DIR__ . '/includes/helpers.php';

// Validate resource ID BEFORE any HTML is output
$resourceId = (int)($_GET['id'] ?? 0);
if (!$resourceId) {
    setFlash('error', 'Invalid resource requested.');
    header('Location: ' . BASE_URL . '/explore.php');
    exit;
}

$pdo = Database::getConnection();
$user = getCurrentUser();

try {
    // Increment View Count
    $pdo->prepare("UPDATE `resources` SET `view_count` = `view_count` + 1 WHERE `resource_id` = ?")->execute([$resourceId]);

    // Fetch Resource Info
    $stmt = $pdo->prepare("SELECT 
            r.*, 
            c.`dept_id`,
            c.`course_code`, 
            c.`course_title`, 
            c.`credit_hours`,
            d.`dept_code`, 
            d.`dept_name`, 
            cat.`name` AS `category_name`, 
            cat.`icon` AS `category_icon`, 
            u.`full_name` AS `author_name`,
            u.`email` AS `author_email`,
            u.`academic_id` AS `author_academic_id`
        FROM `resources` r
        JOIN `courses` c ON r.`course_id` = c.`course_id`
        JOIN `departments` d ON c.`dept_id` = d.`dept_id`
        JOIN `categories` cat ON r.`category_id` = cat.`category_id`
        JOIN `users` u ON r.`uploaded_by` = u.`user_id`
        WHERE r.`resource_id` = ?");
    $stmt->execute([$resourceId]);
    $resource = $stmt->fetch();

    if (!$resource) {
        setFlash('error', 'Resource not found or has been removed.');
        header('Location: ' . BASE_URL . '/explore.php');
        exit;
    }

    // Check if user has bookmarked this resource
    $isBookmarked = false;
    if ($user) {
        $bmStmt = $pdo->prepare("SELECT 1 FROM `bookmarks` WHERE `user_id` = ? AND `resource_id` = ?");
        $bmStmt->execute([$user['id'], $resourceId]);
        $isBookmarked = (bool)$bmStmt->fetch();
    }

    // Fetch Existing Reviews
    $revStmt = $pdo->prepare("SELECT rev.*, u.`full_name`, u.`avatar` 
                              FROM `reviews` rev 
                              JOIN `users` u ON rev.`user_id` = u.`user_id` 
                              WHERE rev.`resource_id` = ? 
                              ORDER BY rev.`created_at` DESC");
    $revStmt->execute([$resourceId]);
    $reviews = $revStmt->fetchAll();

    // Fetch Discussion Comments
    $commentStmt = $pdo->prepare("SELECT c.*, u.`full_name`, u.`avatar`, r.`role_name` 
                                  FROM `comments` c 
                                  JOIN `users` u ON c.`user_id` = u.`user_id` 
                                  JOIN `roles` r ON u.`role_id` = r.`role_id`
                                  WHERE c.`resource_id` = ? 
                                  ORDER BY c.`created_at` ASC");
    $commentStmt->execute([$resourceId]);
    $comments = $commentStmt->fetchAll();

    // Fetch Related Resources
    $relStmt = $pdo->prepare("SELECT * FROM `resources` WHERE `course_id` = ? AND `resource_id` != ? AND `status` = 'approved' LIMIT 3");
    $relStmt->execute([$resource['course_id'], $resourceId]);
    $relatedResources = $relStmt->fetchAll();

} catch (Exception $e) {
    logError($e, 'Resource Details Error');
    setFlash('error', 'A system error occurred while retrieving resource data.');
    header('Location: ' . BASE_URL . '/explore.php');
    exit;
}

$pageTitle = htmlspecialchars($resource['title']) . " - Campus Notes Portal";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="bg-white border-bottom py-3 mb-4">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/index.php" class="text-decoration-none">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/explore.php" class="text-decoration-none">Catalog</a></li>
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/explore.php?dept=<?= $resource['dept_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($resource['dept_code']) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($resource['course_code']) ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <?php displayFlash(); ?>

    <div class="row g-4">
        <!-- MAIN RESOURCE COLUMN -->
        <div class="col-lg-8">
            <!-- Resource Card Header -->
            <div class="card border rounded-4 p-4 bg-white shadow-sm mb-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="course-badge fs-6 px-3 py-1"><?= htmlspecialchars($resource['course_code']) ?></span>
                    <span class="tag-chip"><i class="fa-solid fa-building-columns"></i> <?= htmlspecialchars($resource['dept_name']) ?></span>
                    <span class="tag-chip"><i class="fa-solid fa-tag"></i> <?= htmlspecialchars($resource['category_name']) ?></span>
                </div>

                <h1 class="h3 font-weight-bold mb-3 text-dark"><?= htmlspecialchars($resource['title']) ?></h1>

                <div class="d-flex align-items-center gap-3 text-secondary small mb-4 flex-wrap">
                    <span><i class="fa-solid fa-user-circle me-1 text-primary"></i> Uploaded by <strong><?= htmlspecialchars($resource['author_name']) ?></strong></span>
                    <span><i class="fa-solid fa-calendar me-1"></i> <?= date('M d, Y', strtotime($resource['created_at'])) ?></span>
                    <span><i class="fa-solid fa-eye me-1"></i> <?= $resource['view_count'] ?> views</span>
                    <span><i class="fa-solid fa-download me-1"></i> <?= $resource['download_count'] ?> downloads</span>
                </div>

                <div class="p-3 bg-light rounded-3 mb-4">
                    <h6 class="font-weight-bold mb-2">Description & Syllabus Coverage:</h6>
                    <p class="mb-0 text-secondary" style="white-space: pre-line;"><?= htmlspecialchars($resource['description']) ?></p>
                </div>

                <!-- Download & Action Buttons -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-3 border-top">
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?= BASE_URL ?>/actions/download_action.php?id=<?= $resource['resource_id'] ?>" class="btn btn-primary-custom px-4 py-2">
                            <i class="fa-solid fa-cloud-arrow-down me-2"></i> Download File (<?= formatBytes($resource['file_size']) ?>)
                        </a>

                        <?php if ($user): ?>
                            <button class="btn btn-outline-custom px-3 py-2 btn-bookmark-toggle" data-resource-id="<?= $resource['resource_id'] ?>" data-url="<?= BASE_URL ?>/actions/bookmark_action.php">
                                <i class="<?= $isBookmarked ? 'fa-solid fa-bookmark text-danger' : 'fa-regular fa-bookmark' ?>"></i>
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($user && ($user['role_id'] == ROLE_ADMIN || $user['role_id'] == ROLE_MODERATOR || $user['id'] == $resource['uploaded_by'])): ?>
                        <div class="d-flex gap-2">
                            <a href="<?= BASE_URL ?>/actions/resource_action.php?action=delete&id=<?= $resource['resource_id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to permanently delete this resource?');">
                                <i class="fa-solid fa-trash me-1"></i> Delete
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- REVIEWS & RATINGS SECTION -->
            <div class="card border rounded-4 p-4 bg-white shadow-sm mb-4">
                <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                    <div>
                        <h4 class="font-weight-bold mb-1">Student Ratings & Reviews</h4>
                        <div class="d-flex align-items-center gap-2">
                            <?= renderStarRating($resource['avg_rating']) ?>
                            <span class="text-secondary small">Based on <?= $resource['rating_count'] ?> verified evaluations</span>
                        </div>
                    </div>
                </div>

                <!-- Review Form -->
                <?php if ($user): ?>
                    <form action="<?= BASE_URL ?>/actions/review_action.php" method="POST" class="mb-4 p-3 bg-light rounded-3">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="submit_review">
                        <input type="hidden" name="resource_id" value="<?= $resource['resource_id'] ?>">

                        <label class="form-label font-sm font-weight-bold mb-1">Leave your rating:</label>
                        <div class="interactive-star-rating mb-2">
                            <input type="hidden" name="rating" value="5">
                            <i class="fa-solid fa-star text-warning star-btn fs-5 me-1" style="cursor:pointer;"></i>
                            <i class="fa-solid fa-star text-warning star-btn fs-5 me-1" style="cursor:pointer;"></i>
                            <i class="fa-solid fa-star text-warning star-btn fs-5 me-1" style="cursor:pointer;"></i>
                            <i class="fa-solid fa-star text-warning star-btn fs-5 me-1" style="cursor:pointer;"></i>
                            <i class="fa-solid fa-star text-warning star-btn fs-5 me-1" style="cursor:pointer;"></i>
                        </div>

                        <textarea name="review_text" class="form-control mb-3" rows="2" placeholder="Write feedback about accuracy, clarity, and usefulness..." required></textarea>
                        <button type="submit" class="btn btn-primary-custom btn-sm"><i class="fa-solid fa-paper-plane me-1"></i> Submit Review</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-light border small text-center mb-4">
                        Please <a href="<?= BASE_URL ?>/login.php" class="fw-bold">Sign In</a> to rate or review this material.
                    </div>
                <?php endif; ?>

                <!-- List Reviews -->
                <div class="review-list">
                    <?php if (empty($reviews)): ?>
                        <p class="text-secondary small mb-0">No reviews submitted yet. Be the first to evaluate!</p>
                    <?php else: ?>
                        <?php foreach ($reviews as $rev): ?>
                            <div class="border-bottom py-3">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-avatar-circle" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                            <?= strtoupper(substr($rev['full_name'], 0, 1)) ?>
                                        </div>
                                        <span class="font-sm font-weight-bold text-dark"><?= htmlspecialchars($rev['full_name']) ?></span>
                                    </div>
                                    <span class="text-muted small"><?= timeAgo($rev['created_at']) ?></span>
                                </div>
                                <div class="mb-1"><?= renderStarRating($rev['rating']) ?></div>
                                <p class="text-secondary small mb-0"><?= htmlspecialchars($rev['review_text']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- DISCUSSION & Q&A THREAD -->
            <div class="card border rounded-4 p-4 bg-white shadow-sm">
                <h4 class="font-weight-bold mb-3 border-bottom pb-2">Academic Discussion & Q&A</h4>

                <?php if ($user): ?>
                    <form action="<?= BASE_URL ?>/actions/review_action.php" method="POST" class="mb-4">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="post_comment">
                        <input type="hidden" name="resource_id" value="<?= $resource['resource_id'] ?>">
                        <div class="d-flex gap-2">
                            <input type="text" name="comment_text" class="form-control" placeholder="Ask a question or discuss this topic with professors/peers..." required>
                            <button type="submit" class="btn btn-primary-custom px-4"><i class="fa-solid fa-comment-dots"></i></button>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="comments-thread">
                    <?php if (empty($comments)): ?>
                        <p class="text-secondary small mb-0">No questions or discussion threads yet.</p>
                    <?php else: ?>
                        <?php foreach ($comments as $com): ?>
                            <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                                <div class="user-avatar-circle" style="width: 36px; height: 36px; font-size: 0.85rem;">
                                    <?= strtoupper(substr($com['full_name'], 0, 1)) ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <div>
                                            <span class="font-sm font-weight-bold text-dark"><?= htmlspecialchars($com['full_name']) ?></span>
                                            <span class="badge bg-light text-secondary border ms-1 font-xs"><?= ucfirst($com['role_name']) ?></span>
                                        </div>
                                        <span class="text-muted small"><?= timeAgo($com['created_at']) ?></span>
                                    </div>
                                    <p class="text-secondary small mb-0"><?= htmlspecialchars($com['comment_text']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SIDEBAR METADATA COLUMN -->
        <div class="col-lg-4">
            <!-- File Info Box -->
            <div class="card border rounded-4 p-4 bg-white shadow-sm mb-4">
                <h5 class="font-weight-bold mb-3 border-bottom pb-2">Document Metadata</h5>
                <ul class="list-unstyled small mb-0">
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">File Format</span>
                        <span class="font-weight-bold text-uppercase text-dark"><?= htmlspecialchars($resource['file_type']) ?></span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">File Size</span>
                        <span class="font-weight-bold text-dark"><?= formatBytes($resource['file_size']) ?></span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Course Code</span>
                        <span class="font-weight-bold text-primary"><?= htmlspecialchars($resource['course_code']) ?></span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Credit Hours</span>
                        <span class="font-weight-bold text-dark"><?= number_format($resource['credit_hours'], 1) ?></span>
                    </li>
                    <li class="d-flex justify-content-between py-2">
                        <span class="text-secondary">Database Status</span>
                        <span class="badge-status badge-approved">3NF Verified</span>
                    </li>
                </ul>
            </div>

            <!-- Related Notes -->
            <?php if (!empty($relatedResources)): ?>
                <div class="card border rounded-4 p-4 bg-white shadow-sm">
                    <h5 class="font-weight-bold mb-3 border-bottom pb-2">More from this Course</h5>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($relatedResources as $rel): ?>
                            <a href="<?= BASE_URL ?>/resource_details.php?id=<?= $rel['resource_id'] ?>" class="text-decoration-none text-dark">
                                <div class="p-2 border rounded-3 hover-bg-light transition">
                                    <div class="font-sm font-weight-bold text-truncate mb-1"><?= htmlspecialchars($rel['title']) ?></div>
                                    <div class="d-flex align-items-center justify-content-between text-muted small">
                                        <span><i class="fa-solid fa-star text-warning"></i> <?= number_format($rel['avg_rating'], 1) ?></span>
                                        <span><i class="fa-solid fa-download"></i> <?= $rel['download_count'] ?></span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
