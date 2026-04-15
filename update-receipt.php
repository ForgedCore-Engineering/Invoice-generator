<?php
/**
 * Update Receipt API — ForgedCore Receipt Manager
 * Accepts: POST JSON
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
$id    = isset($input['id']) ? (int)$input['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid receipt ID']);
    exit;
}

try {
    $pdo = getDB();
    
    $sql = "UPDATE clients SET 
            name = ?, 
            address = ?, 
            contact = ?, 
            description = ?, 
            total = ?, 
            paid = ?, 
            date = ? 
            WHERE id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $input['name'] ?? '',
        $input['address'] ?? '',
        $input['contact'] ?? '',
        $input['description'] ?? '',
        (float)($input['total'] ?? 0),
        (float)($input['paid'] ?? 0),
        $input['date'] ?? '',
        $id
    ]);

    echo json_encode(['success' => true, 'message' => 'Receipt updated successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
