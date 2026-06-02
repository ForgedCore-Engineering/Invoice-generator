<?php
/**
 * Save payslip data to database
 * Called via AJAX after PDF is generated
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

define('SKIP_DB_INIT', true);
require_once __DIR__ . '/config.php';

ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$full_name = isset($input['full_name']) ? trim($input['full_name']) : '';
$service = isset($input['service']) ? trim($input['service']) : '';
$amount_due = isset($input['amount_due']) ? floatval($input['amount_due']) : 0.0;
$amount_paid = isset($input['amount_paid']) ? floatval($input['amount_paid']) : 0.0;
$issue_date = isset($input['issue_date']) ? trim($input['issue_date']) : '';
$payslip_no = isset($input['payslip_no']) ? trim($input['payslip_no']) : '';

if ($full_name === '' || $service === '' || $payslip_no === '' || $issue_date === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

if ($amount_due <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Amount due must be greater than 0']);
    exit;
}

if ($amount_paid < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Amount paid cannot be negative']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO payslips (full_name, service, amount_due, amount_paid, payslip_no, issue_date, pdf_file)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $pdf_name = 'payslip_' . str_replace('/', '_', $payslip_no) . '.pdf';
    $stmt->execute([
        $full_name,
        $service,
        $amount_due,
        $amount_paid,
        $payslip_no,
        $issue_date,
        $pdf_name
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Payslip saved successfully',
        'id' => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
