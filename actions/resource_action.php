<?php
/**
 * Resource Action Handler (Upload, Edit, Delete, Moderation)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireAuth();
$pdo = Database::getConnection();
$user = getCurrentUser();
$action = $_POST['action'] ?? ($_GET['action'] ?? '');

// 1. UPLOAD RESOURCE HANDLER
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token expired. Please re-submit.');
        header('Location: ' . BASE_URL . '/faculty/upload.php');
        exit;
    }

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $courseId = (int)($_POST['course_id'] ?? 0);
    $categoryId = (int)($_POST['category_id'] ?? 0);

    if (empty($title) || empty($courseId) || empty($categoryId)) {
        setFlash('error', 'Please fill in all mandatory fields.');
        header('Location: ' . BASE_URL . '/faculty/upload.php');
        exit;
    }

    if (!isset($_FILES['note_file']) || $_FILES['note_file']['error'] !== UPLOAD_ERR_OK) {
        setFlash('error', 'Please attach a valid file to upload.');
        header('Location: ' . BASE_URL . '/faculty/upload.php');
        exit;
    }

    $file = $_FILES['note_file'];
    $fileName = basename($file['name']);
    $fileSize = $file['size'];
    $fileTmpPath = $file['tmp_name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExt, ALLOWED_EXTENSIONS)) {
        setFlash('error', 'Invalid file type. Allowed formats: ' . implode(', ', ALLOWED_EXTENSIONS));
        header('Location: ' . BASE_URL . '/faculty/upload.php');
        exit;
    }

    if ($fileSize > MAX_FILE_SIZE) {
        setFlash('error', 'File size exceeds maximum limit of ' . formatBytes(MAX_FILE_SIZE));
        header('Location: ' . BASE_URL . '/faculty/upload.php');
        exit;
    }

    // Ensure upload folder exists
    $uploadDir = ROOT_PATH . '/assets/uploads/notes/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Unique secure filename
    $uniqueFileName = uniqid('note_', true) . '.' . $fileExt;
    $targetPath = $uploadDir . $uniqueFileName;
    $relPath = 'uploads/notes/' . $uniqueFileName;

    if (move_uploaded_file($fileTmpPath, $targetPath)) {
        try {
            // Auto-approve if uploaded by Admin or Faculty, pending if student
            $status = ($user['role_id'] == ROLE_STUDENT) ? 'pending' : 'approved';
            $moderator = ($status === 'approved') ? $user['id'] : null;

            $stmt = $pdo->prepare("INSERT INTO `resources` 
                (`title`, `description`, `course_id`, `category_id`, `uploaded_by`, `file_path`, `file_name`, `file_size`, `file_type`, `status`, `moderated_by`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $courseId, $categoryId, $user['id'], $relPath, $fileName, $fileSize, $fileExt, $status, $moderator]);
            $resourceId = $pdo->lastInsertId();

            Security::logActivity($pdo, $user['id'], 'RESOURCE_UPLOAD', 'resources', $resourceId, "Uploaded note '$title'");

            if ($status === 'pending') {
                setFlash('info', 'Your resource was uploaded and is queued for moderator review.');
            } else {
                setFlash('success', 'Resource uploaded and published successfully!');
            }

            if ($user['role_id'] == ROLE_ADMIN) {
                header('Location: ' . BASE_URL . '/admin/resources.php');
            } elseif ($user['role_id'] == ROLE_MODERATOR) {
                header('Location: ' . BASE_URL . '/moderator/index.php');
            } elseif ($user['role_id'] == ROLE_FACULTY) {
                header('Location: ' . BASE_URL . '/faculty/my_resources.php');
            } elseif ($user['role_id'] == ROLE_STUDENT) {
                header('Location: ' . BASE_URL . '/student/index.php');
            } else {
                header('Location: ' . BASE_URL . '/index.php');
            }
            exit;

        } catch (Exception $e) {
            if (file_exists($targetPath)) unlink($targetPath);
            logError($e, 'Resource Upload Error');
            setFlash('error', 'Database Error occurred during upload.');
            header('Location: ' . BASE_URL . '/faculty/upload.php');
            exit;
        }
    } else {
        setFlash('error', 'Failed to save uploaded file on server disk.');
        header('Location: ' . BASE_URL . '/faculty/upload.php');
        exit;
    }
}

// 2. MODERATION STATUS HANDLER (Approve / Reject)
if ($action === 'moderate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole([ROLE_ADMIN, ROLE_MODERATOR]);
    
    $resourceId = (int)($_POST['resource_id'] ?? 0);
    $status = $_POST['status'] ?? 'approved';
    $notes = trim($_POST['moderation_notes'] ?? '');

    if (!in_array($status, ['approved', 'rejected', 'pending'])) {
        $status = 'approved';
    }

    try {
        if ($status === 'approved') {
            // Call Stored Procedure sp_approve_resource
            $stmt = $pdo->prepare("CALL sp_approve_resource(?, ?, ?)");
            $stmt->execute([$resourceId, $user['id'], $notes]);
        } else {
            $stmt = $pdo->prepare("UPDATE `resources` SET `status` = ?, `moderated_by` = ?, `moderation_notes` = ? WHERE `resource_id` = ?");
            $stmt->execute([$status, $user['id'], $notes, $resourceId]);
            Security::logActivity($pdo, $user['id'], 'RESOURCE_REJECTED', 'resources', $resourceId, "Resource status updated to $status");
        }

        setFlash('success', "Resource status successfully updated to " . ucfirst($status));
    } catch (Exception $e) {
        logError($e, 'Resource Moderation Error');
        setFlash('error', 'Failed to update moderation state due to a system error.');
    }

    $redirect = $_POST['redirect_to'] ?? (BASE_URL . '/moderator/pending_reviews.php');
    header('Location: ' . $redirect);
    exit;
}

// 3. DELETE RESOURCE
if ($action === 'delete') {
    $resourceId = (int)($_GET['id'] ?? 0);

    try {
        // Fetch resource
        $stmt = $pdo->prepare("SELECT * FROM `resources` WHERE `resource_id` = ?");
        $stmt->execute([$resourceId]);
        $resource = $stmt->fetch();

        if (!$resource) {
            setFlash('error', 'Resource not found.');
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }

        // Permission check: Admin, Moderator or Original Uploader
        if ($user['role_id'] != ROLE_ADMIN && $user['role_id'] != ROLE_MODERATOR && $resource['uploaded_by'] != $user['id']) {
            setFlash('error', 'You are not authorized to delete this resource.');
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }

        // Delete from DB (Triggers and foreign keys handle cascades)
        $delStmt = $pdo->prepare("DELETE FROM `resources` WHERE `resource_id` = ?");
        $delStmt->execute([$resourceId]);

        Security::logActivity($pdo, $user['id'], 'RESOURCE_DELETED', 'resources', $resourceId, "Deleted resource: " . $resource['title']);

        setFlash('success', 'Resource permanently deleted.');
    } catch (Exception $e) {
        logError($e, 'Resource Deletion Error');
        setFlash('error', 'Error deleting resource due to a system error.');
    }

    $back = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/explore.php');
    header('Location: ' . $back);
    exit;
}
