<?php
/**
 * Handle PDF file downloads
 */

require_once __DIR__ . '/config.php';

// Get filename from query parameter
$filename = isset($_GET['file']) ? basename($_GET['file']) : '';

if (empty($filename)) {
    http_response_code(400);
    die("Filename is required");
}

// Security: Only allow PDF files
if (pathinfo($filename, PATHINFO_EXTENSION) !== 'pdf') {
    http_response_code(400);
    die("Invalid file type");
}

// Construct full path
$filepath = RECEIPT_DIR . '/' . $filename;

// Check if file exists
if (!file_exists($filepath)) {
    http_response_code(404);
    die("File not found");
}

// Send file to browser
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;
?>

