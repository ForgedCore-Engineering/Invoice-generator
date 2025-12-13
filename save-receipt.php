<?php
/**
 * Save receipt data to database
 * Called via AJAX after PDF is generated
 */

// Suppress any output that might break JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

// Skip automatic DB init to avoid errors
define('SKIP_DB_INIT', true);
require_once __DIR__ . '/config.php';

// Clear any output
ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Get and sanitize form data
$name = isset($input['name']) ? trim($input['name']) : '';
$address = isset($input['address']) ? trim($input['address']) : '';
$contact = isset($input['contact']) ? trim($input['contact']) : '';
$description = isset($input['description']) ? trim($input['description']) : '';
$total = isset($input['total']) ? floatval($input['total']) : 0.0;
$paid = isset($input['paid']) ? floatval($input['paid']) : 0.0;
$date = isset($input['date']) ? trim($input['date']) : '';
$invoice_no = isset($input['invoice_no']) ? trim($input['invoice_no']) : '';

// Validate required fields
if (empty($name) || empty($invoice_no) || empty($date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$pdf_name = 'receipt_' . str_replace('/', '_', $invoice_no) . '.pdf';

// Save to database
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO clients (name, address, contact, description, total, paid, date, invoice_no, pdf_file)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $name,
        $address,
        $contact,
        $description,
        $total,
        $paid,
        $date,
        $invoice_no,
        $pdf_name
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Receipt saved successfully',
        'id' => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>

