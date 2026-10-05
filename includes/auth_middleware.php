<?php
/**
 * Authentication and Role Middleware
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['full_name'] ?? 'User',
        'email' => $_SESSION['email'] ?? '',
        'role_id' => $_SESSION['role_id'] ?? 4,
        'role_name' => $_SESSION['role_name'] ?? 'student',
        'dept_id' => $_SESSION['dept_id'] ?? null,
        'avatar' => $_SESSION['avatar'] ?? 'default_avatar.png'
    ];
}

function hasRole($roles) {
    if (!isLoggedIn()) return false;
    $userRoleId = (int)($_SESSION['role_id'] ?? 0);
    $userRoleName = strtolower($_SESSION['role_name'] ?? '');

    if (is_array($roles)) {
        foreach ($roles as $r) {
            if (is_numeric($r) && (int)$r === $userRoleId) return true;
            if (is_string($r) && strtolower($r) === $userRoleName) return true;
        }
        return false;
    }
    if (is_numeric($roles)) return (int)$roles === $userRoleId;
    return strtolower($roles) === $userRoleName;
}

function requireAuth() {
    if (!isLoggedIn()) {
        $_SESSION['flash'] = [
            'type' => 'warning',
            'message' => 'Please sign in to access this page.'
        ];
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function requireRole($allowedRoles) {
    requireAuth();
    if (!hasRole($allowedRoles)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Access denied: You do not have permission to view that resource.'
        ];
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}
