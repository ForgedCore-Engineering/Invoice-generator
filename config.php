<?php
/**
 * Configuration file for ForgedCore Receipt Generator
 */

// Load database configuration
require_once __DIR__ . '/db_config.php';

// Directory paths
define('RECEIPT_DIR', __DIR__ . '/receipts');
define('STATIC_DIR', __DIR__ . '/receipts/static');

// Ensure directories exist
if (!file_exists(RECEIPT_DIR)) {
    mkdir(RECEIPT_DIR, 0755, true);
}
if (!file_exists(STATIC_DIR)) {
    mkdir(STATIC_DIR, 0755, true);
}

// Database connection
function getDB() {
    try {
        // Handle host:port format
        $host = DB_HOST;
        $port = '3306';
        if (strpos($host, ':') !== false) {
            list($host, $port) = explode(':', $host, 2);
        }
        $dsn = "mysql:host=" . $host . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
        return $pdo;
    } catch (PDOException $e) {
        // If database doesn't exist, try to create it
        if ($e->getCode() == 1049) {
            try {
                $host = DB_HOST;
                $port = '3306';
                if (strpos($host, ':') !== false) {
                    list($host, $port) = explode(':', $host, 2);
                }
                $pdo = new PDO("mysql:host=" . $host . ";port=" . $port . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, DB_OPTIONS);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $dsn = "mysql:host=" . $host . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $pdo = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
                initDB();
                return $pdo;
            } catch (PDOException $e2) {
                throw new Exception("Database connection failed: " . $e2->getMessage());
            }
        }
        throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

// Initialize database tables
function initDB() {
    try {
        $pdo = getDB();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS clients (
                id INT(11) NOT NULL AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                address TEXT,
                contact VARCHAR(255),
                description TEXT,
                total DECIMAL(10,2) DEFAULT 0.00,
                paid DECIMAL(10,2) DEFAULT 0.00,
                date VARCHAR(255),
                invoice_no VARCHAR(100),
                pdf_file VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                INDEX idx_invoice_no (invoice_no),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (PDOException $e) {
        // If getDB() fails, we can't initialize
        error_log("Database initialization error: " . $e->getMessage());
    }
}

// Initialize database on first load (only if connection succeeds)
// Don't initialize here if called from AJAX endpoints - let them handle errors
if (!defined('SKIP_DB_INIT')) {
    try {
        $testConnection = getDB();
        initDB();
    } catch (Exception $e) {
        // Connection will be attempted again when actually needed
        // Don't output anything - let calling code handle it
    }
}
?>

