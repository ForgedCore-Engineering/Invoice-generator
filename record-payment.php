<?php
require_once __DIR__ . '/auth.php';
fc_require_login();
/**
 * Record Payment API â€” ForgedCore Receipt & Payslip Manager
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
    $pdo=getDB();
    $result=fc_activity_change($pdo,$type,$id,'payment',function($row) use ($pdo,$type,$id,$new_payment) {
        $dueKey=$type==='receipt'?'total':'amount_due';$paidKey=$type==='receipt'?'paid':'amount_paid';
        $table=$type==='receipt'?'clients':'payslips';
        $total=(float)$row[$dueKey];$already_paid=(float)$row[$paidKey];$outstanding=$total-$already_paid;
        if($outstanding<=0)throw new DomainException('This record is already fully paid');
        $applied=min($new_payment,$outstanding);$new_paid=$already_paid+$applied;
        $pdo->prepare("UPDATE $table SET $paidKey=? WHERE id=?")->execute([$new_paid,$id]);
        return ['success'=>true,'applied'=>round($applied,2),'new_paid'=>round($new_paid,2),$dueKey=>round($total,2),'outstanding'=>round($total-$new_paid,2),'capped'=>$applied<$new_payment,'message'=>'Payment of GH₵ '.number_format($applied,2).' recorded successfully'];
    });
    echo json_encode($result);
} catch(OutOfBoundsException $e) {
    http_response_code(404);echo json_encode(['error'=>'Record not found']);
} catch(DomainException $e) {
    http_response_code(400);echo json_encode(['error'=>$e->getMessage()]);
} catch(Throwable $e) {
    error_log('Record payment failed: '.$e->getMessage());
    http_response_code(500);echo json_encode(['error'=>'Could not record the payment. No changes were saved.']);
}
