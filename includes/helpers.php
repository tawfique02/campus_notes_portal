<?php
/**
 * Global Helper Functions & View Utilities
 */

require_once __DIR__ . '/../config/config.php';

// Flash message handling
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

// Log application exceptions securely without exposing details to UI
function logError(Exception $e, $context = '') {
    $logFile = __DIR__ . '/../logs/error.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $errorMessage = "[{$timestamp}] {$context} | " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . PHP_EOL;
    error_log($errorMessage, 3, $logFile);
}

function displayFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $icon = 'fa-info-circle';
        if ($flash['type'] === 'success') $icon = 'fa-check-circle';
        if ($flash['type'] === 'error') $icon = 'fa-exclamation-circle';
        if ($flash['type'] === 'warning') $icon = 'fa-triangle-exclamation';

        echo '<div class="alert alert-' . htmlspecialchars($flash['type']) . ' alert-dismissible fade show" role="alert">
                <i class="fa-solid ' . $icon . ' me-2"></i> ' . htmlspecialchars($flash['message']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
}

// Format bytes to human readable format
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Generate Star Rating HTML
function renderStarRating($rating, $maxStars = 5) {
    $html = '<div class="star-rating d-inline-flex align-items-center" title="' . number_format($rating, 1) . ' out of ' . $maxStars . '">';
    for ($i = 1; $i <= $maxStars; $i++) {
        if ($rating >= $i) {
            $html .= '<i class="fa-solid fa-star text-warning"></i>';
        } elseif ($rating >= ($i - 0.5)) {
            $html .= '<i class="fa-solid fa-star-half-stroke text-warning"></i>';
        } else {
            $html .= '<i class="fa-regular fa-star text-muted"></i>';
        }
    }
    $html .= ' <span class="ms-1 fw-bold text-dark font-sm">(' . number_format($rating, 1) . ')</span>';
    $html .= '</div>';
    return $html;
}

// File icon selector based on extension
function getFileIconClass($fileType) {
    switch (strtolower($fileType)) {
        case 'pdf':
            return 'fa-file-pdf text-danger';
        case 'doc':
        case 'docx':
            return 'fa-file-word text-primary';
        case 'ppt':
        case 'pptx':
            return 'fa-file-powerpoint text-warning';
        case 'zip':
        case 'rar':
            return 'fa-file-zipper text-secondary';
        case 'jpg':
        case 'png':
        case 'jpeg':
            return 'fa-file-image text-info';
        case 'txt':
            return 'fa-file-lines text-muted';
        default:
            return 'fa-file text-secondary';
    }
}

// Time Ago Formatter
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' days ago';
    return date('M d, Y', $time);
}

// Clean and format Author/User display names
function getDisplayName($fullName) {
    if (empty($fullName)) return 'User';
    // Remove trailing parentheses like (Admin), (Student)
    $clean = preg_replace('/\s*\(.*?\)\s*/', '', trim($fullName));
    $parts = explode(' ', $clean);
    if (count($parts) >= 2) {
        $prefix = strtolower($parts[0]);
        if (in_array($prefix, ['dr.', 'prof.', 'mr.', 'ms.', 'mrs.', 'engr.'])) {
            return $parts[0] . ' ' . $parts[1];
        }
        return $parts[0] . ' ' . $parts[1];
    }
    return $parts[0];
}

// Extract primary initial letter for avatars
function getAvatarLetter($fullName) {
    if (empty($fullName)) return 'U';
    $clean = preg_replace('/\s*\(.*?\)\s*/', '', trim($fullName));
    $parts = explode(' ', $clean);
    if (count($parts) >= 2 && in_array(strtolower($parts[0]), ['dr.', 'prof.', 'mr.', 'ms.', 'mrs.', 'engr.'])) {
        return strtoupper(substr($parts[1], 0, 1));
    }
    return strtoupper(substr($parts[0], 0, 1));
}
