<?php
/**
 * Review, Rating, and Discussion Comment Action Handler
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireAuth();
$pdo = Database::getConnection();
$user = getCurrentUser();
$action = $_POST['action'] ?? '';

// 1. ADD / UPDATE RATING & REVIEW (Trigger will automatically recalculate avg_rating)
if ($action === 'submit_review' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token mismatch. Please retry.');
        header('Location: ' . BASE_URL . '/explore.php');
        exit;
    }

    $resourceId = (int)($_POST['resource_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 5);
    $reviewText = trim($_POST['review_text'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $rating = 5;
    }

    try {
        // Upsert review (Insert or Update on duplicate key)
        $stmt = $pdo->prepare("INSERT INTO `reviews` (`resource_id`, `user_id`, `rating`, `review_text`)
                               VALUES (?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE `rating` = VALUES(`rating`), `review_text` = VALUES(`review_text`), `created_at` = NOW()");
        $stmt->execute([$resourceId, $user['id'], $rating, $reviewText]);

        Security::logActivity($pdo, $user['id'], 'REVIEW_SUBMITTED', 'reviews', $resourceId, "Rated note $resourceId with $rating stars");

        setFlash('success', 'Your review and rating have been recorded!');
    } catch (Exception $e) {
        logError($e, 'Review Submission Error');
        setFlash('error', 'Failed to submit review due to a system error. Please try again.');
    }

    header('Location: ' . BASE_URL . '/resource_details.php?id=' . $resourceId);
    exit;
}

// 2. ADD COMMENT / DISCUSSION
if ($action === 'post_comment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token mismatch.');
        header('Location: ' . BASE_URL . '/explore.php');
        exit;
    }

    $resourceId = (int)($_POST['resource_id'] ?? 0);
    $commentText = trim($_POST['comment_text'] ?? '');
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    if (empty($commentText)) {
        setFlash('error', 'Comment cannot be empty.');
        header('Location: ' . BASE_URL . '/resource_details.php?id=' . $resourceId);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO `comments` (`resource_id`, `user_id`, `parent_id`, `comment_text`) VALUES (?, ?, ?, ?)");
        $stmt->execute([$resourceId, $user['id'], $parentId, $commentText]);

        Security::logActivity($pdo, $user['id'], 'COMMENT_POSTED', 'comments', $resourceId, "Posted discussion comment");
        setFlash('success', 'Your comment was posted.');
    } catch (Exception $e) {
        logError($e, 'Comment Posting Error');
        setFlash('error', 'Failed to post comment due to a system error.');
    }

    header('Location: ' . BASE_URL . '/resource_details.php?id=' . $resourceId);
    exit;
}
