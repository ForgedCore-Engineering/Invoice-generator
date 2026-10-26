<?php
require_once __DIR__ . '/auth.php';
fc_require_login();
/**
 * Legacy form handler - redirects to new JavaScript-based system
 * This file is kept for backward compatibility
 */

header('Location: index.php');
exit;
?>

