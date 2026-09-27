<?php
/**
 * Search Outstanding Records API — ForgedCore
 *
 * GET  ?type=receipt&q=Kwame
 * GET  ?type=payslip&q=Kwame
 *
 * Returns matching records that still have an outstanding balance.
 */
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

define('SKIP_DB_INIT', true);
require_once __DIR__ . '/config.php';

ob_clean();
header('Content-Type: application/json');

$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$q    = isset($_GET['q'])    ? trim($_GET['q'])    : '';

if (!in_array($type, ['receipt', 'payslip'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid type']);
    exit;
}
if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

try {
    $pdo = getDB();

    if ($type === 'receipt') {
        $stmt = $pdo->prepare("
            SELECT id, name, address, contact, description, total, paid, invoice_no, date
            FROM clients
            WHERE name LIKE ?
              AND (total - paid) > 0
            ORDER BY created_at DESC
            LIMIT 8
        ");
        $stmt->execute(['%' . $q . '%']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = array_map(fn($r) => [
            'id'          => (int)$r['id'],
            'label'       => $r['name'] . ' — ' . $r['invoice_no'],
            'name'        => $r['name'],
            'address'     => $r['address'],
            'contact'     => $r['contact'],
            'description' => $r['description'],
            'total'       => (float)$r['total'],
            'paid'        => (float)$r['paid'],
            'outstanding' => round((float)$r['total'] - (float)$r['paid'], 2),
            'invoice_no'  => $r['invoice_no'],
            'date'        => $r['date'],
        ], $rows);

    } else {
        $stmt = $pdo->prepare("
            SELECT id, full_name, service, amount_due, amount_paid, payslip_no, issue_date
            FROM payslips
            WHERE full_name LIKE ?
              AND (amount_due - amount_paid) > 0
            ORDER BY created_at DESC
            LIMIT 8
        ");
        $stmt->execute(['%' . $q . '%']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = array_map(fn($r) => [
            'id'          => (int)$r['id'],
            'label'       => $r['full_name'] . ' — ' . $r['payslip_no'],
            'full_name'   => $r['full_name'],
            'service'     => $r['service'],
            'amount_due'  => (float)$r['amount_due'],
            'amount_paid' => (float)$r['amount_paid'],
            'outstanding' => round((float)$r['amount_due'] - (float)$r['amount_paid'], 2),
            'payslip_no'  => $r['payslip_no'],
            'issue_date'  => $r['issue_date'],
        ], $rows);
    }

    echo json_encode($results);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
