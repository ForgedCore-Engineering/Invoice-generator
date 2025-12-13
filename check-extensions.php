<?php
/**
 * Check PHP extensions for image handling
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>PHP Extensions Check</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f5f5f5; }
        .container { max-width: 700px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #333; }
        .success { color: #2e7d32; padding: 15px; background: #e8f5e9; border-radius: 4px; margin: 10px 0; }
        .error { color: #d32f2f; padding: 15px; background: #ffebee; border-radius: 4px; margin: 10px 0; }
        .info { color: #1976d2; padding: 15px; background: #e3f2fd; border-radius: 4px; margin: 10px 0; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
        .command { background: #263238; color: #aed581; padding: 15px; border-radius: 4px; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="container">
        <h1>PHP Extensions Check</h1>
        
        <?php
        $gdLoaded = extension_loaded('gd');
        $imagickLoaded = extension_loaded('imagick');
        $hasImageSupport = $gdLoaded || $imagickLoaded;
        
        if ($hasImageSupport) {
            echo '<div class="success">';
            echo '<strong>✓ Image Extension Available</strong><br/>';
            if ($gdLoaded) {
                echo 'GD extension is loaded and ready to use.';
                if (function_exists('gd_info')) {
                    $gdInfo = gd_info();
                    echo '<br/><small>GD Version: ' . (isset($gdInfo['GD Version']) ? $gdInfo['GD Version'] : 'Unknown') . '</small>';
                }
            }
            if ($imagickLoaded) {
                echo ($gdLoaded ? '<br/>' : '') . 'Imagick extension is loaded and ready to use.';
            }
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '<strong>✗ No Image Extension Found</strong><br/>';
            echo 'Neither GD nor Imagick extension is loaded. TCPDF requires one of these to handle PNG images with alpha channel.';
            echo '</div>';
        }
        ?>
        
        <h2>Extension Status</h2>
        <table>
            <tr>
                <th>Extension</th>
                <th>Status</th>
                <th>Required For</th>
            </tr>
            <tr>
                <td><strong>GD</strong></td>
                <td><?php echo $gdLoaded ? '<span style="color: green;">✓ Loaded</span>' : '<span style="color: red;">✗ Not Loaded</span>'; ?></td>
                <td>PNG image handling in TCPDF</td>
            </tr>
            <tr>
                <td><strong>Imagick</strong></td>
                <td><?php echo $imagickLoaded ? '<span style="color: green;">✓ Loaded</span>' : '<span style="color: red;">✗ Not Loaded</span>'; ?></td>
                <td>PNG image handling in TCPDF (alternative)</td>
            </tr>
        </table>
        
        <?php if (!$hasImageSupport): ?>
        <div class="info">
            <h3>How to Enable GD Extension in XAMPP:</h3>
            <ol>
                <li>Open <code>php.ini</code> file:
                    <div class="command">D:\xampp\php\php.ini</div>
                </li>
                <li>Find the line (search for "extension=gd"):<br/>
                    <code>;extension=gd</code>
                </li>
                <li>Remove the semicolon (;) to uncomment it:<br/>
                    <code>extension=gd</code>
                </li>
                <li>Save the file</li>
                <li>Restart Apache in XAMPP Control Panel</li>
                <li>Refresh this page to verify</li>
            </ol>
            
            <p><strong>Note:</strong> If the line doesn't exist, add it under the "Dynamic Extensions" section:</p>
            <div class="command">extension=gd</div>
        </div>
        <?php endif; ?>
        
        <h2>PHP Information</h2>
        <p><a href="phpinfo.php" target="_blank">View phpinfo()</a> (opens in new tab)</p>
        
        <p style="margin-top: 30px;">
            <a href="index.php">← Back to Application</a>
        </p>
    </div>
</body>
</html>

