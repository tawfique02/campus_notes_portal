<?php
/**
 * Secure File Download Streamer with Trigger-based Logging
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

$pdo = Database::getConnection();
$resourceId = (int)($_GET['id'] ?? 0);

if (!$resourceId) {
    die("Invalid resource requested.");
}

try {
    // Fetch resource
    $stmt = $pdo->prepare("SELECT * FROM `resources` WHERE `resource_id` = ? AND `status` = 'approved'");
    $stmt->execute([$resourceId]);
    $resource = $stmt->fetch();

    if (!$resource) {
        die("Resource not found or pending moderation approval.");
    }

    $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    // Insert into download_logs -> Triggers MySQL Trigger: `trg_after_download_log_insert`
    $logStmt = $pdo->prepare("INSERT INTO `download_logs` (`resource_id`, `user_id`, `ip_address`, `user_agent`) VALUES (?, ?, ?, ?)");
    $logStmt->execute([$resourceId, $userId, $ip, $agent]);

    // Construct full disk path
    $filePath = ROOT_PATH . '/assets/' . $resource['file_path'];

    // Check if file exists on disk
    if (!file_exists($filePath)) {
        // Generate placeholder dummy file on-the-fly for seamless testing
        $dir = dirname($filePath);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        file_put_contents($filePath, "%PDF-1.4\n%CampusNotesDemo\nCourse: " . $resource['title'] . "\nGenerated download stream.");
    }

    // Clear output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Set download headers
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($resource['file_name']) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;

} catch (Exception $e) {
    die("Download processing error: " . $e->getMessage());
}
