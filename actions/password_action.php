<?php
/**
 * Password Action Handler (Forgot Password, Reset Password)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = Database::getConnection();
$action = $_POST['action'] ?? '';

// FORGOT PASSWORD HANDLER
if ($action === 'forgot_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token. Please try again.');
        header('Location: ' . BASE_URL . '/forgot_password.php');
        exit;
    }

    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

    if (empty($email)) {
        setFlash('error', 'Please provide an email address.');
        header('Location: ' . BASE_URL . '/forgot_password.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT user_id, full_name FROM `users` WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $updateStmt = $pdo->prepare("UPDATE `users` SET reset_token = ?, reset_token_expires_at = ? WHERE user_id = ?");
            $updateStmt->execute([$token, $expires_at, $user['user_id']]);

            $resetLink = BASE_URL . "/reset_password.php?token=" . $token;

            // In a real application, you would send an email here.
            // For demo purposes, we will display the link in a flash message.
            setFlash('success', "A password reset link has been generated. <br><a href='$resetLink'><strong>Click here to reset your password (Demo)</strong></a>");
            
            Security::logActivity($pdo, $user['user_id'], 'PASSWORD_RESET_REQUESTED', 'users', $user['user_id'], 'User requested password reset');
        } else {
            // Do not reveal if the email exists or not for security
            setFlash('success', "If your email is registered, you will receive a reset link shortly.");
        }

        header('Location: ' . BASE_URL . '/forgot_password.php');
        exit;
    } catch (Exception $e) {
        logError($e, 'Forgot Password Error');
        setFlash('error', 'System error. Please try again later.');
        header('Location: ' . BASE_URL . '/forgot_password.php');
        exit;
    }
}

// RESET PASSWORD HANDLER
if ($action === 'reset_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid security token. Please try again.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($token) || empty($password) || empty($confirm_password)) {
        setFlash('error', 'All fields are required.');
        header('Location: ' . BASE_URL . '/reset_password.php?token=' . urlencode($token));
        exit;
    }

    if ($password !== $confirm_password) {
        setFlash('error', 'Passwords do not match.');
        header('Location: ' . BASE_URL . '/reset_password.php?token=' . urlencode($token));
        exit;
    }

    if (strlen($password) < 6) {
        setFlash('error', 'Password must be at least 6 characters.');
        header('Location: ' . BASE_URL . '/reset_password.php?token=' . urlencode($token));
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT user_id, reset_token_expires_at FROM `users` WHERE reset_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            if (strtotime($user['reset_token_expires_at']) < time()) {
                setFlash('error', 'Password reset link has expired. Please request a new one.');
                header('Location: ' . BASE_URL . '/forgot_password.php');
                exit;
            }

            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            
            $updateStmt = $pdo->prepare("UPDATE `users` SET password_hash = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE user_id = ?");
            $updateStmt->execute([$passwordHash, $user['user_id']]);

            Security::logActivity($pdo, $user['user_id'], 'PASSWORD_RESET_SUCCESS', 'users', $user['user_id'], 'User reset their password');
            
            setFlash('success', 'Your password has been successfully reset. You can now login.');
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        } else {
            setFlash('error', 'Invalid or expired password reset link.');
            header('Location: ' . BASE_URL . '/forgot_password.php');
            exit;
        }

    } catch (Exception $e) {
        logError($e, 'Reset Password Error');
        setFlash('error', 'System error. Please try again later.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}
