<?php
/**
 * Helper script to check and provide instructions for enabling GD extension
 */
$phpIniPath = 'D:\xampp\php\php.ini';
$backupPath = 'D:\xampp\php\php.ini.backup';

// Check if GD is already enabled
$gdEnabled = extension_loaded('gd');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>GD Extension Helper</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f5f5f5; }
        .container { max-width: 700px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; }
        .success { color: #2e7d32; padding: 15px; background: #e8f5e9; border-radius: 4px; margin: 10px 0; }
        .error { color: #d32f2f; padding: 15px; background: #ffebee; border-radius: 4px; margin: 10px 0; }
        .info { color: #1976d2; padding: 15px; background: #e3f2fd; border-radius: 4px; margin: 10px 0; }
        .warning { color: #f57c00; padding: 15px; background: #fff3e0; border-radius: 4px; margin: 10px 0; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
        .command { background: #263238; color: #aed581; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .button { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .button:hover { background: #0056b3; }
        .button-danger { background: #dc3545; }
        .button-danger:hover { background: #c82333; }
    </style>
</head>
<body>
    <div class="container">
        <h1>GD Extension Helper</h1>
        
        <?php if ($gdEnabled): ?>
            <div class="success">
                <strong>✓ GD Extension is Already Enabled!</strong><br/>
                Your PHP installation has the GD extension loaded and ready to use.
            </div>
            <p><a href="index.php" class="button">Go to Application</a></p>
        <?php else: ?>
            <div class="error">
                <strong>✗ GD Extension is Not Enabled</strong><br/>
                You need to enable it to use PNG images in PDF generation.
            </div>
            
            <?php
            // Check if php.ini exists and is readable
            if (!file_exists($phpIniPath)) {
                echo '<div class="error">';
                echo '<strong>Error:</strong> php.ini file not found at: ' . htmlspecialchars($phpIniPath);
                echo '</div>';
            } else if (!is_writable($phpIniPath)) {
                echo '<div class="warning">';
                echo '<strong>Warning:</strong> php.ini file is not writable. You will need to edit it manually.';
                echo '</div>';
                
                // Read the file to check current state
                $phpIniContent = file_get_contents($phpIniPath);
                $hasGdLine = preg_match('/^\s*;?\s*extension\s*=\s*gd\s*$/mi', $phpIniContent);
                
                if ($hasGdLine) {
                    echo '<div class="info">';
                    echo '<strong>Found:</strong> The <code>extension=gd</code> line exists in php.ini but is commented out.';
                    echo '</div>';
                } else {
                    echo '<div class="info">';
                    echo '<strong>Note:</strong> The <code>extension=gd</code> line was not found. You may need to add it manually.';
                    echo '</div>';
                }
            } else {
                // File is writable, we can offer to enable it
                $phpIniContent = file_get_contents($phpIniPath);
                $hasGdLine = preg_match('/^\s*;?\s*extension\s*=\s*gd\s*$/mi', $phpIniContent);
                
                if ($hasGdLine) {
                    // Check if it's commented
                    if (preg_match('/^\s*;\s*extension\s*=\s*gd\s*$/mi', $phpIniContent)) {
                        echo '<div class="info">';
                        echo '<strong>Ready to Enable:</strong> Found commented GD extension line.';
                        echo '</div>';
                        
                        if (isset($_POST['enable_gd']) && $_POST['enable_gd'] === 'yes') {
                            // Create backup first
                            copy($phpIniPath, $backupPath);
                            
                            // Uncomment the line
                            $newContent = preg_replace('/^\s*;\s*extension\s*=\s*gd\s*$/mi', 'extension=gd', $phpIniContent);
                            
                            if (file_put_contents($phpIniPath, $newContent)) {
                                echo '<div class="success">';
                                echo '<strong>✓ Success!</strong> GD extension has been enabled in php.ini.<br/>';
                                echo 'A backup was created at: ' . htmlspecialchars($backupPath) . '<br/><br/>';
                                echo '<strong>IMPORTANT:</strong> You must restart Apache in XAMPP Control Panel for the changes to take effect.';
                                echo '</div>';
                            } else {
                                echo '<div class="error">';
                                echo '<strong>Error:</strong> Could not write to php.ini file. Please edit it manually.';
                                echo '</div>';
                            }
                        } else {
                            echo '<form method="POST" onsubmit="return confirm(\'This will modify your php.ini file. A backup will be created. Continue?\');">';
                            echo '<input type="hidden" name="enable_gd" value="yes">';
                            echo '<button type="submit" class="button">Enable GD Extension Automatically</button>';
                            echo '</form>';
                            echo '<p><small>Note: A backup of php.ini will be created before making changes.</small></p>';
                        }
                    } else {
                        echo '<div class="info">';
                        echo '<strong>Found:</strong> GD extension line exists and appears to be enabled, but PHP is not loading it.';
                        echo 'You may need to restart Apache or check for other issues.';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="info">';
                    echo '<strong>Not Found:</strong> The extension=gd line was not found. You may need to add it manually.';
                    echo '</div>';
                }
            }
            ?>
            
            <div class="info" style="margin-top: 20px;">
                <h3>Manual Instructions:</h3>
                <ol>
                    <li>Open <code>D:\xampp\php\php.ini</code> in a text editor (as Administrator if needed)</li>
                    <li>Search for <code>extension=gd</code> (press Ctrl+F)</li>
                    <li>If you find <code>;extension=gd</code>, remove the semicolon to make it <code>extension=gd</code></li>
                    <li>If the line doesn't exist, add <code>extension=gd</code> in the "Dynamic Extensions" section</li>
                    <li>Save the file</li>
                    <li><strong>Restart Apache</strong> in XAMPP Control Panel</li>
                    <li>Refresh this page to verify</li>
                </ol>
            </div>
        <?php endif; ?>
        
        <p style="margin-top: 30px;">
            <a href="check-extensions.php" class="button">Check Extensions</a>
            <a href="index.php" class="button">Back to Application</a>
        </p>
    </div>
</body>
</html>

