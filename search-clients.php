<?php
/**
 * Client search for autocomplete (new receipt form)
 * GET ?q=search_term  — min 2 characters
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

define('SKIP_DB_INIT', true);
require_once __DIR__ . '/config.php';

ob_clean();
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $pdo  = getDB();
    $like = '%' . $q . '%';

    $stmt = $pdo->prepare("
        SELECT c.name, c.contact, c.address, c.description
        FROM clients c
        INNER JOIN (
            SELECT MAX(id) AS max_id
            FROM clients
            WHERE name LIKE ? OR contact LIKE ?
            GROUP BY LOWER(TRIM(name))
        ) t ON c.id = t.max_id
        ORDER BY c.name ASC
        LIMIT 10
    ");
    $stmt->execute([$like, $like]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(array_map(function ($r) {
        return [
            'name'        => $r['name'],
            'contact'     => $r['contact'] ?? '',
            'address'     => $r['address'] ?? '',
            'description' => $r['description'] ?? '',
        ];
    }, $rows));
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
