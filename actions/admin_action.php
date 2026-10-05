<?php
/**
 * Administrator & Database Updater Operations Controller
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_middleware.php';

requireRole([ROLE_ADMIN, ROLE_MODERATOR]);
$pdo = Database::getConnection();
$user = getCurrentUser();
$action = $_POST['action'] ?? ($_GET['action'] ?? '');

// 1. UPDATE USER ROLE & STATUS (Admin only)
if ($action === 'update_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole(ROLE_ADMIN);

    $targetUserId = (int)($_POST['user_id'] ?? 0);
    $newRoleId = (int)($_POST['role_id'] ?? 4);
    $newStatus = $_POST['status'] ?? 'active';

    if (!in_array($newStatus, ['active', 'inactive', 'suspended'])) {
        $newStatus = 'active';
    }

    try {
        $stmt = $pdo->prepare("UPDATE `users` SET `role_id` = ?, `status` = ? WHERE `user_id` = ?");
        $stmt->execute([$newRoleId, $newStatus, $targetUserId]);

        Security::logActivity($pdo, $user['id'], 'USER_MODIFIED', 'users', $targetUserId, "Updated user ID $targetUserId role to $newRoleId, status to $newStatus");
        setFlash('success', 'User profile updated successfully.');
    } catch (Exception $e) {
        setFlash('error', 'Failed to update user: ' . $e->getMessage());
    }

    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

// 2. ADD DEPARTMENT
if ($action === 'add_department' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['dept_code'] ?? ''));
    $name = trim($_POST['dept_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (empty($code) || empty($name)) {
        setFlash('error', 'Department code and name are required.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO `departments` (`dept_code`, `dept_name`, `description`) VALUES (?, ?, ?)");
            $stmt->execute([$code, $name, $desc]);
            $deptId = $pdo->lastInsertId();

            Security::logActivity($pdo, $user['id'], 'DEPT_CREATED', 'departments', $deptId, "Added department $code");
            setFlash('success', "Department $code created successfully.");
        } catch (Exception $e) {
            setFlash('error', 'Error adding department: ' . $e->getMessage());
        }
    }
    header('Location: ' . BASE_URL . '/admin/departments.php');
    exit;
}

// 3. ADD COURSE
if ($action === 'add_course' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $deptId = (int)($_POST['dept_id'] ?? 0);
    $semesterId = (int)($_POST['semester_id'] ?? 1);
    $code = strtoupper(trim($_POST['course_code'] ?? ''));
    $title = trim($_POST['course_title'] ?? '');
    $credits = (float)($_POST['credit_hours'] ?? 3.0);
    $summary = trim($_POST['syllabus_summary'] ?? '');

    if (empty($code) || empty($title) || !$deptId) {
        setFlash('error', 'All course identification fields are required.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO `courses` (`dept_id`, `semester_id`, `course_code`, `course_title`, `credit_hours`, `syllabus_summary`) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$deptId, $semesterId, $code, $title, $credits, $summary]);
            $courseId = $pdo->lastInsertId();

            Security::logActivity($pdo, $user['id'], 'COURSE_CREATED', 'courses', $courseId, "Added course $code: $title");
            setFlash('success', "Course $code registered successfully.");
        } catch (Exception $e) {
            setFlash('error', 'Error adding course: ' . $e->getMessage());
        }
    }
    header('Location: ' . BASE_URL . '/admin/departments.php');
    exit;
}

// 4. RESOLVE CONTENT REPORT
if ($action === 'resolve_report' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportId = (int)($_POST['report_id'] ?? 0);
    $status = $_POST['status'] ?? 'resolved';
    $notes = trim($_POST['resolution_notes'] ?? '');

    try {
        $stmt = $pdo->prepare("UPDATE `reports` SET `status` = ?, `resolved_by` = ?, `resolution_notes` = ?, `resolved_at` = NOW() WHERE `report_id` = ?");
        $stmt->execute([$status, $user['id'], $notes, $reportId]);

        Security::logActivity($pdo, $user['id'], 'REPORT_RESOLVED', 'reports', $reportId, "Report resolved with status $status");
        setFlash('success', 'Report status updated.');
    } catch (Exception $e) {
        setFlash('error', 'Error updating report: ' . $e->getMessage());
    }

    header('Location: ' . BASE_URL . '/moderator/reported_items.php');
    exit;
}

// 5. DATABASE BACKUP / EXPORT (Admin only)
if ($action === 'export_db') {
    requireRole(ROLE_ADMIN);

    $tables = ['roles', 'departments', 'users', 'semesters', 'courses', 'categories', 'resources', 'reviews', 'comments', 'bookmarks', 'download_logs', 'activity_logs', 'reports'];
    
    $dump = "-- Campus Notes Sharing Portal Database Dump\n";
    $dump .= "-- Exported on: " . date('Y-m-d H:i:s') . "\n";
    $dump .= "-- Generated by: " . $user['name'] . "\n\n";

    foreach ($tables as $table) {
        $dump .= "-- -----------------------------------------------------\n";
        $dump .= "-- Table structure for `$table`\n";
        $dump .= "-- -----------------------------------------------------\n";
        
        $createStmt = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
        $dump .= $createStmt[1] . ";\n\n";

        // Dump data
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 0) {
            $dump .= "-- Dumping data for `$table`\n";
            foreach ($rows as $row) {
                $cols = array_map(function($val) use ($pdo) {
                    return $val === null ? 'NULL' : $pdo->quote($val);
                }, array_values($row));
                $dump .= "INSERT INTO `$table` VALUES (" . implode(', ', $cols) . ");\n";
            }
            $dump .= "\n";
        }
    }

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="campus_notes_backup_' . date('Y_m_d_His') . '.sql"');
    echo $dump;
    exit;
}
