<?php
/**
 * AJAX Bookmark Toggle Action
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Please log in to bookmark resources.']);
    exit;
}

$pdo = Database::getConnection();
$user = getCurrentUser();
$resourceId = (int)($_POST['resource_id'] ?? 0);

if (!$resourceId) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid resource identifier.']);
    exit;
}

try {
    // Check if currently bookmarked
    $stmt = $pdo->prepare("SELECT bookmark_id FROM `bookmarks` WHERE `user_id` = ? AND `resource_id` = ?");
    $stmt->execute([$user['id'], $resourceId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Remove bookmark
        $del = $pdo->prepare("DELETE FROM `bookmarks` WHERE `bookmark_id` = ?");
        $del->execute([$existing['bookmark_id']]);
        echo json_encode(['status' => 'success', 'action' => 'unbookmarked', 'message' => 'Removed from saved notes.']);
    } else {
        // Add bookmark
        $ins = $pdo->prepare("INSERT INTO `bookmarks` (`user_id`, `resource_id`) VALUES (?, ?)");
        $ins->execute([$user['id'], $resourceId]);
        echo json_encode(['status' => 'success', 'action' => 'bookmarked', 'message' => 'Saved to your bookmarks!']);
    }
} catch (Exception $e) {
    require_once __DIR__ . '/../includes/helpers.php';
    logError($e, 'Bookmark Error');
    echo json_encode(['status' => 'error', 'message' => 'System error processing bookmark.']);
}
