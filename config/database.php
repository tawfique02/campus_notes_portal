<?php
/**
 * Database Connection using PDO
 * Campus Academic Resource & Notes Sharing Portal
 */

class Database {
    private static $host = '127.0.0.1';
    private static $db_name = 'campus_notes_db';
    private static $username = 'root';
    private static $password = ''; // Default XAMPP MySQL password is empty
    private static $charset = 'utf8mb4';
    private static $pdo = null;

    /**
     * Get singleton PDO Database connection
     * @return PDO
     */
    public static function getConnection() {
        if (self::$pdo === null) {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=" . self::$charset;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . self::$charset
            ];

            try {
                self::$pdo = new PDO($dsn, self::$username, self::$password, $options);
            } catch (PDOException $e) {
                // If database doesn't exist yet, we can connect to MySQL server root to facilitate installation
                try {
                    $rootDsn = "mysql:host=" . self::$host . ";charset=" . self::$charset;
                    $tempPdo = new PDO($rootDsn, self::$username, self::$password, $options);
                    return $tempPdo;
                } catch (PDOException $e2) {
                    die("<div style='font-family:sans-serif;padding:30px;max-width:600px;margin:50px auto;border:1px solid #f5c6cb;background:#f8d7da;color:#721c24;border-radius:8px;'>
                        <h2 style='margin-top:0;'>⚠️ Database Connection Failed</h2>
                        <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                        <p>Please make sure <strong>XAMPP MySQL is running</strong> in your control panel.</p>
                        <p><a href='" . (defined('BASE_URL') ? BASE_URL : '') . "/database/db_setup.php' style='display:inline-block;padding:8px 16px;background:#0d6efd;color:white;text-decoration:none;border-radius:4px;'>Run DB Auto-Installer</a></p>
                    </div>");
                }
            }
        }
        return self::$pdo;
    }

    /**
     * Check if database table exists
     */
    public static function isDatabaseInstalled() {
        try {
            $pdo = self::getConnection();
            $stmt = $pdo->query("SELECT 1 FROM `roles` LIMIT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
