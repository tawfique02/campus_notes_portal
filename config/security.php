<?php
/**
 * Security, CSRF Protection, and Data Sanitization Utilities
 */

require_once __DIR__ . '/config.php';

class Security {
    /**
     * Generate CSRF Token and store in session
     */
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Output CSRF Token Hidden Input HTML
     */
    public static function csrfField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Validate CSRF Token
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Sanitize string input
     */
    public static function cleanInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'cleanInput'], $data);
        }
        $data = trim($data);
        $data = stripslashes($data);
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Log system activity into DB
     */
    public static function logActivity($pdo, $userId, $action, $entityType, $entityId = null, $details = '') {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $pdo->prepare("INSERT INTO `activity_logs` (`user_id`, `action`, `entity_type`, `entity_id`, `details`, `ip_address`) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $action, $entityType, $entityId, $details, $ip]);
        } catch (Exception $e) {
            // Silently continue if log fails to not break user flow
        }
    }
}
