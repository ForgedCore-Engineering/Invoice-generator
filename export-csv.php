<?php
/**
 * Export Receipts to CSV — ForgedCore Receipt Manager
 */
require_once __DIR__ . '/config.php';

$search = trim($_GET['search'] ?? '');
$status = $_GET['status']  ?? 'all';
$from   = $_GET['from']    ?? '';
$to     = $_GET['to']      ?? '';

$where  = [];
$params = [];

if ($search) {
    $like    = "%$search%";
    $where[] = "(name LIKE ? OR invoice_no LIKE ? OR contact LIKE ? OR address LIKE ?)";
    array_push($params, $like, $like, $like, $like);
}
switch ($status) {
    case 'paid':    $where[] = "(total - paid) <= 0";                break;
    case 'partial': $where[] = "paid > 0 AND (total - paid) > 0";   break;
    case 'unpaid':  $where[] = "paid = 0";                           break;
}
if ($from) { $where[] = "DATE(created_at) >= ?"; $params[] = $from; }
if ($to)   { $where[] = "DATE(created_at) <= ?"; $params[] = $to; }

$wc = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT id, name, address, contact, invoice_no, total, paid, (total - paid) as balance, date, created_at
        FROM clients $wc ORDER BY id DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare CSV header
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="receipts_export_' . date('Y-m-d_His') . '.csv"');

    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Headers
    fputcsv($output, ['ID', 'Client Name', 'Address', 'Contact', 'Invoice No', 'Total Amount', 'Amount Paid', 'Balance', 'Receipt Date', 'Created At']);

    foreach ($rows as $row) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['address'],
            $row['contact'],
            $row['invoice_no'],
            number_format($row['total'], 2, '.', ''),
            number_format($row['paid'], 2, '.', ''),
            number_format($row['balance'], 2, '.', ''),
            $row['date'],
            $row['created_at']
        ]);
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    die("Export failed: " . $e->getMessage());
}
