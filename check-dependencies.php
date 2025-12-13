<?php
/**
 * Quick dependency checker
 * Run this to verify if Composer dependencies are installed
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dependency Check - ForgedCore</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; }
        .success { color: #2e7d32; padding: 15px; background: #e8f5e9; border-radius: 4px; margin: 10px 0; }
        .error { color: #d32f2f; padding: 15px; background: #ffebee; border-radius: 4px; margin: 10px 0; }
        .info { color: #1976d2; padding: 15px; background: #e3f2fd; border-radius: 4px; margin: 10px 0; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
        .command { background: #263238; color: #aed581; padding: 15px; border-radius: 4px; margin: 15px 0; }
        a { color: #1976d2; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Dependency Check</h1>
        
        <?php
        $autoloadPath = __DIR__ . '/vendor/autoload.php';
        $composerJson = __DIR__ . '/composer.json';
        $vendorDir = __DIR__ . '/vendor';
        
        $hasComposerJson = file_exists($composerJson);
        $hasVendorDir = is_dir($vendorDir);
        $hasAutoload = file_exists($autoloadPath);
        
        if ($hasAutoload) {
            echo '<div class="success">';
            echo '<strong>✓ Dependencies Installed</strong><br/>';
            echo 'The vendor directory exists and autoload.php is present.';
            echo '</div>';
            
            // Try to load and check TCPDF
            try {
                require_once $autoloadPath;
                if (class_exists('TCPDF')) {
                    echo '<div class="success">';
                    echo '<strong>✓ TCPDF Library Loaded</strong><br/>';
                    echo 'TCPDF class is available and ready to use.';
                    echo '</div>';
                } else {
                    echo '<div class="error">';
                    echo '<strong>✗ TCPDF Not Found</strong><br/>';
                    echo 'The TCPDF class is not available. Try running: <code>composer install</code>';
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<strong>✗ Error Loading Dependencies</strong><br/>';
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
            }
        } else {
            echo '<div class="error">';
            echo '<strong>✗ Dependencies Not Installed</strong><br/>';
            echo 'The vendor directory or autoload.php is missing.';
            echo '</div>';
            
            if ($hasComposerJson) {
                echo '<div class="info">';
                echo '<strong>Installation Instructions:</strong><br/><br/>';
                echo '1. Open Command Prompt in this directory:<br/>';
                echo '<div class="command">cd D:\\xampp\\htdocs\\invoice</div>';
                echo '2. Run Composer install:<br/>';
                echo '<div class="command">composer install</div>';
                echo '3. If you don\'t have Composer:<br/>';
                echo '<ul>';
                echo '<li>Download from: <a href="https://getcomposer.org/download/" target="_blank">https://getcomposer.org/download/</a></li>';
                echo '<li>Or use: <code>php -r "copy(\'https://getcomposer.org/installer\', \'composer-setup.php\');" && php composer-setup.php</code></li>';
                echo '</ul>';
                echo '</div>';
            } else {
                echo '<div class="error">';
                echo '<strong>✗ composer.json Not Found</strong><br/>';
                echo 'The composer.json file is missing. Please ensure all files are present.';
                echo '</div>';
            }
        }
        ?>
        
        <p style="margin-top: 30px;">
            <a href="index.php">← Back to Application</a> | 
            <a href="install.php">Full Installation Check</a>
        </p>
    </div>
</body>
</html>

