<?php
/**
 * Get payslip number and date for PDF generation
 * Called via AJAX before generating PDF
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

define('SKIP_DB_INIT', true);
require_once __DIR__ . '/functions.php';

ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$full_name = isset($input['full_name']) ? trim($input['full_name']) : '';

if ($full_name === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Full name is required']);
    exit;
}

try {
    $issue_date = formattedDate();
    $payslip_no = generatePayslipNumber($full_name);

    echo json_encode([
        'payslip_no' => $payslip_no,
        'issue_date'."     ". =>  $issue_date,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
