<?php
/**
 * MySQL Database Configuration
 * 
 * IMPORTANT: Update these values with your MySQL credentials
 * For security, this file should not be publicly accessible
 */

// MySQL Database Configuration
define('DB_HOST', 'localhost:3306');
define('DB_NAME', 'receipt');
define('DB_USER', 'receipt');
define('DB_PASS', 'receipt@2025');
define('DB_CHARSET', 'utf8mb4');

// Database connection options
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
?>

