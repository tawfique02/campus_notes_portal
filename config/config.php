<?php
/**
 * Global Application Configuration
 * Campus Academic Resource & Notes Sharing Portal
 */

// Prevent multiple session starts
if (session_status() === PHP_SESSION_NONE) {
    // Secure session cookies
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Timezone setup
date_default_timezone_set('Asia/Dhaka');

// Site Definition Constants - NexusNotes
define('APP_NAME', 'NexusNotes');
define('APP_FULL_NAME', 'Nexus Academic Resource & Notes Portal');
define('APP_TAGLINE', 'Centralized Academic & Exam Repository for Nexus Scholars');
define('UNIVERSITY_NAME', 'Nexus University');
define('APP_VERSION', '2.5.0');

// Determine Base URL dynamically
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

// Find root path
$docRoot = str_replace('\\', '/', __DIR__ . '/..');
define('ROOT_PATH', realpath($docRoot));

// If running in subfolder in XAMPP or custom path
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';
// Calculate base URL
define('BASE_URL', $protocol . $host . (strpos($_SERVER['REQUEST_URI'], '/campus_notes_portal') !== false ? '/campus_notes_portal' : ''));

// Uploads Directory
define('UPLOAD_DIR', ROOT_PATH . '/assets/uploads/notes/');
define('UPLOAD_URL', BASE_URL . '/assets/uploads/notes/');
define('MAX_FILE_SIZE', 25 * 1024 * 1024); // 25 MB max
define('ALLOWED_EXTENSIONS', ['pdf', 'docx', 'pptx', 'zip', 'txt', 'jpg', 'png', 'epub']);

// Role IDs
define('ROLE_ADMIN', 1);
define('ROLE_MODERATOR', 2);
define('ROLE_FACULTY', 3);
define('ROLE_STUDENT', 4);



