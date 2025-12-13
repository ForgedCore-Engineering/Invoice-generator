<?php
/**
 * Get invoice number and date for PDF generation
 * Called via AJAX before generating PDF
 */

// Suppress any output that might break JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

// Skip automatic DB init to avoid errors
define('SKIP_DB_INIT', true);
require_once __DIR__ . '/functions.php';

// Clear any output
ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$name = isset($input['name']) ? trim($input['name']) : '';

if (empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Client name is required']);
    exit;
}

try {
    $date = formattedDate();
    $invoice_no = generateInvoice($name);
    
    echo json_encode([
        'invoice_no' => $invoice_no,
        'date' => $date
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>

