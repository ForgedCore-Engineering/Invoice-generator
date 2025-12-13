<?php
/**
 * MySQL Database Setup Script
 * Run this once to set up the database and tables
 */

require_once __DIR__ . '/db_config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Database Setup - ForgedCore</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f5f5f5; }
        .container { max-width: 700px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; }
        .success { color: #2e7d32; padding: 15px; background: #e8f5e9; border-radius: 4px; margin: 10px 0; }
        .error { color: #d32f2f; padding: 15px; background: #ffebee; border-radius: 4px; margin: 10px 0; }
        .info { color: #1976d2; padding: 15px; background: #e3f2fd; border-radius: 4px; margin: 10px 0; }
        .warning { color: #f57c00; padding: 15px; background: #fff3e0; border-radius: 4px; margin: 10px 0; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
        .button { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>MySQL Database Setup</h1>
        
        <?php
        $errors = [];
        $success = [];
        
        // Display current configuration
        echo '<div class="info">';
        echo '<strong>Current Configuration:</strong><br/>';
        echo 'Host: <code>' . htmlspecialchars(DB_HOST) . '</code><br/>';
        echo 'Database: <code>' . htmlspecialchars(DB_NAME) . '</code><br/>';
        echo 'User: <code>' . htmlspecialchars(DB_USER) . '</code><br/>';
        echo 'Password: ' . (DB_PASS ? '***' : '<em>Not set</em>') . '<br/>';
        echo '</div>';
        
        // Test MySQL connection
        try {
            // Handle host:port format
            $host = DB_HOST;
            $port = '3306';
            if (strpos($host, ':') !== false) {
                list($host, $port) = explode(':', $host, 2);
            }
            $pdo = new PDO("mysql:host=" . $host . ";port=" . $port . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, DB_OPTIONS);
            $success[] = "MySQL connection: Successful ✓";
            
            // Check if database exists
            $stmt = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
            $dbExists = $stmt->rowCount() > 0;
            
            if (!$dbExists) {
                // Create database
                try {
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $success[] = "Database created: " . DB_NAME . " ✓";
                } catch (PDOException $e) {
                    $errors[] = "Error creating database: " . $e->getMessage();
                }
            } else {
                $success[] = "Database exists: " . DB_NAME . " ✓";
            }
            
            // Connect to the database
            // Handle host:port format
            $host = DB_HOST;
            $port = '3306';
            if (strpos($host, ':') !== false) {
                list($host, $port) = explode(':', $host, 2);
            }
            $dsn = "mysql:host=" . $host . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
            
            // Create table
            try {
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
                $success[] = "Table 'clients' created/verified ✓";
                
                // Check table structure
                $stmt = $pdo->query("DESCRIBE clients");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $success[] = "Table has " . count($columns) . " columns";
                
            } catch (PDOException $e) {
                $errors[] = "Error creating table: " . $e->getMessage();
            }
            
            // Check if table has data
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM clients");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $count = $result['count'];
                if ($count > 0) {
                    $success[] = "Table contains $count record(s)";
                } else {
                    $success[] = "Table is empty (ready for new records)";
                }
            } catch (PDOException $e) {
                $errors[] = "Error checking table: " . $e->getMessage();
            }
            
        } catch (PDOException $e) {
            $errors[] = "MySQL connection failed: " . $e->getMessage();
            $errors[] = "Please check your MySQL credentials in <code>db_config.php</code>";
        }
        
        // Display results
        if (!empty($success)) {
            echo '<div class="success">';
            echo '<strong>Success:</strong><ul>';
            foreach ($success as $msg) {
                echo '<li>' . htmlspecialchars($msg) . '</li>';
            }
            echo '</ul></div>';
        }
        
        if (!empty($errors)) {
            echo '<div class="error">';
            echo '<strong>Errors:</strong><ul>';
            foreach ($errors as $msg) {
                echo '<li>' . htmlspecialchars($msg) . '</li>';
            }
            echo '</ul></div>';
            
            echo '<div class="warning">';
            echo '<strong>How to Fix:</strong><br/>';
            echo '1. Open <code>db_config.php</code> and update the MySQL credentials<br/>';
            echo '2. Make sure MySQL is running in XAMPP<br/>';
            echo '3. Verify the database user has CREATE DATABASE privileges<br/>';
            echo '4. Refresh this page';
            echo '</div>';
        } else {
            echo '<div class="success">';
            echo '<strong>✓ Database setup completed successfully!</strong><br/>';
            echo 'Your MySQL database is ready to use.';
            echo '</div>';
        }
        ?>
        
        <h2>Table Structure</h2>
        <?php
        try {
            $pdo = getDB();
            $stmt = $pdo->query("DESCRIBE clients");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($columns)) {
                echo '<table>';
                echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>';
                foreach ($columns as $col) {
                    echo '<tr>';
                    echo '<td><strong>' . htmlspecialchars($col['Field']) . '</strong></td>';
                    echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
                    echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
        } catch (Exception $e) {
            echo '<div class="error">Could not retrieve table structure: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <p style="margin-top: 30px;">
            <a href="index.php" class="button">Go to Application</a>
            <a href="install.php" class="button">Full Installation Check</a>
        </p>
    </div>
</body>
</html>

