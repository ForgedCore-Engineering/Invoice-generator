<?php
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
$id = isset($input['id']) ? (int)$input['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payslip ID']);
    exit;
}

$full_name = trim($input['full_name'] ?? '');
$service = trim($input['service'] ?? '');
$amount_due = (float)($input['amount_due'] ?? 0);
$amount_paid = (float)($input['amount_paid'] ?? 0);
$issue_date = trim($input['issue_date'] ?? '');

if ($full_name === '' || $service === '' || $issue_date === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}
if ($amount_due <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Amount supposed to be paid must be greater than 0']);
    exit;
}
if ($amount_paid < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Amount paid cannot be negative']);
    exit;
}
if ($amount_paid > $amount_due) {
    http_response_code(400);
    echo json_encode(['error' => 'Amount paid cannot be greater than amount supposed to be paid']);
    exit;
}

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        UPDATE payslips
        SET full_name = ?, service = ?, amount_due = ?, amount_paid = ?, issue_date = ?
        WHERE id = ?
    ");
    $stmt->execute([$full_name, $service, $amount_due, $amount_paid, $issue_date, $id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Payslip updated successfully']);
    } else {
        echo json_encode(['success' => true, 'message' => 'No changes made']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
