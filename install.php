<?php
/**
 * Installation script for ForgedCore Receipt Generator
 * Run this once to set up the application
 */

require_once __DIR__ . '/config.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>Installation - ForgedCore Receipt Generator</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; }
        .success { color: green; padding: 10px; background: #e8f5e9; border-radius: 4px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffebee; border-radius: 4px; margin: 10px 0; }
        .info { color: #666; padding: 10px; background: #e3f2fd; border-radius: 4px; margin: 10px 0; }
        ul { line-height: 1.8; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>ForgedCore Receipt Generator - Installation</h1>";

$errors = [];
$warnings = [];
$success = [];

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    $errors[] = "PHP 7.4 or higher is required. Current version: " . PHP_VERSION;
} else {
    $success[] = "PHP version: " . PHP_VERSION . " ✓";
}

// Check MySQL/PDO extension
if (!extension_loaded('pdo_mysql')) {
    $errors[] = "PDO MySQL extension is not enabled";
} else {
    $success[] = "PDO MySQL extension: Enabled ✓";
}

// Check if Composer dependencies are installed
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    $warnings[] = "Composer dependencies not installed. Run: <code>composer install</code>";
} else {
    $success[] = "Composer dependencies: Installed ✓";
}

// Check directory permissions
$dirs = [
    RECEIPT_DIR => 'Receipts directory',
    STATIC_DIR => 'Static files directory'
];

foreach ($dirs as $dir => $name) {
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0755, true)) {
            $errors[] = "Cannot create directory: $name ($dir)";
        } else {
            $success[] = "Created directory: $name ✓";
        }
    } else {
        if (!is_writable($dir)) {
            $warnings[] = "Directory not writable: $name ($dir)";
        } else {
            $success[] = "Directory writable: $name ✓";
        }
    }
}

// Check static files
$staticFiles = [
    STATIC_DIR . '/logo.png' => 'Logo image',
    STATIC_DIR . '/signature.png' => 'Signature image'
];

foreach ($staticFiles as $file => $name) {
    if (!file_exists($file)) {
        $warnings[] = "Missing: $name ($file)";
    } else {
        $success[] = "Found: $name ✓";
    }
}

// Test database connection
try {
    require_once __DIR__ . '/db_config.php';
    // Handle host:port format
    $host = DB_HOST;
    $port = '3306';
    if (strpos($host, ':') !== false) {
        list($host, $port) = explode(':', $host, 2);
    }
    $dsn = "mysql:host=" . $host . ";port=" . $port . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
    $success[] = "MySQL connection: Successful ✓";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    if ($stmt->rowCount() > 0) {
        $success[] = "Database '" . DB_NAME . "' exists ✓";
    } else {
        $warnings[] = "Database '" . DB_NAME . "' does not exist. Run <a href='setup-database.php'>setup-database.php</a> to create it.";
    }
    
    // Test table creation
    try {
        initDB();
        $success[] = "Database tables: Created/Verified ✓";
    } catch (Exception $e) {
        $warnings[] = "Table initialization: " . $e->getMessage();
    }
} catch (PDOException $e) {
    $errors[] = "MySQL connection error: " . $e->getMessage();
    $errors[] = "Please check your MySQL credentials in <code>db_config.php</code>";
    $errors[] = "Make sure MySQL is running in XAMPP Control Panel";
}

// Display results
if (!empty($success)) {
    echo "<div class='success'><strong>Success:</strong><ul>";
    foreach ($success as $msg) {
        echo "<li>$msg</li>";
    }
    echo "</ul></div>";
}

if (!empty($warnings)) {
    echo "<div class='info'><strong>Warnings:</strong><ul>";
    foreach ($warnings as $msg) {
        echo "<li>$msg</li>";
    }
    echo "</ul></div>";
}

if (!empty($errors)) {
    echo "<div class='error'><strong>Errors:</strong><ul>";
    foreach ($errors as $msg) {
        echo "<li>$msg</li>";
    }
    echo "</ul></div>";
    echo "<p><strong>Please fix the errors above before using the application.</strong></p>";
} else {
    echo "<div class='success'><strong>Installation completed successfully!</strong></div>";
    echo "<p><a href='index.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;'>Go to Application</a></p>";
}

echo "    </div>
</body>
</html>";
?>

