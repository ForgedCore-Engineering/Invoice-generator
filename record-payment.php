<?php
/**
 * Record Payment API — ForgedCore Receipt & Payslip Manager
 *
 * POST JSON body:
 *   { "id": <int>, "type": "receipt"|"payslip", "new_payment": <float> }
 *
 * Adds new_payment to the existing paid/amount_paid column (cumulative),
 * capped to the outstanding balance. Returns the updated record totals.
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

$input       = json_decode(file_get_contents('php://input'), true);
$id          = isset($input['id'])          ? (int)$input['id']          : 0;
$type        = isset($input['type'])        ? trim($input['type'])        : '';
$new_payment = isset($input['new_payment']) ? (float)$input['new_payment'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ID']);
    exit;
}
if (!in_array($type, ['receipt', 'payslip'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid type. Must be "receipt" or "payslip"']);
    exit;
}
if ($new_payment <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Payment amount must be greater than zero']);
    exit;
}

try {
    $pdo = getDB();

    if ($type === 'receipt') {
        /* ── receipts live in the `clients` table ── */
        $stmt = $pdo->prepare("SELECT total, paid FROM clients WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'Receipt not found']);
            exit;
        }

        $total       = (float)$row['total'];
        $already_paid = (float)$row['paid'];
        $outstanding = $total - $already_paid;

        if ($outstanding <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'This receipt is already fully paid']);
            exit;
        }
        // Cap the payment to what's still outstanding
        $applied = min($new_payment, $outstanding);
        $new_paid = $already_paid + $applied;

        $upd = $pdo->prepare("UPDATE clients SET paid = ? WHERE id = ?");
        $upd->execute([$new_paid, $id]);

        echo json_encode([
            'success'      => true,
            'applied'      => round($applied, 2),
            'new_paid'     => round($new_paid, 2),
            'total'        => round($total, 2),
            'outstanding'  => round($total - $new_paid, 2),
            'capped'       => ($applied < $new_payment), // true if input was over-capped
            'message'      => 'Payment of GH₵ ' . number_format($applied, 2) . ' recorded successfully',
        ]);

    } else {
        /* ── payslips table ── */
        $stmt = $pdo->prepare("SELECT amount_due, amount_paid FROM payslips WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'Payslip not found']);
            exit;
        }

        $amount_due   = (float)$row['amount_due'];
        $already_paid = (float)$row['amount_paid'];
        $outstanding  = $amount_due - $already_paid;

        if ($outstanding <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'This payslip is already fully paid']);
            exit;
        }
        $applied  = min($new_payment, $outstanding);
        $new_paid = $already_paid + $applied;

        $upd = $pdo->prepare("UPDATE payslips SET amount_paid = ? WHERE id = ?");
        $upd->execute([$new_paid, $id]);

        echo json_encode([
            'success'      => true,
            'applied'      => round($applied, 2),
            'new_paid'     => round($new_paid, 2),
            'amount_due'   => round($amount_due, 2),
            'outstanding'  => round($amount_due - $new_paid, 2),
            'capped'       => ($applied < $new_payment),
            'message'      => 'Payment of GH₵ ' . number_format($applied, 2) . ' recorded successfully',
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
