<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;
$city = $data['city'] ?? null;
$state = $data['state'] ?? null;
$isVirtual = isset($data['is_virtual']) ? (int)$data['is_virtual'] : 0;
$isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

if (!$id || !$city || !$state) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID, city, and state are required']);
    exit;
}

if (strlen($state) !== 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'State must be a 2-letter code']);
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('
        UPDATE ohc_location 
        SET city = ?, state = ?, is_virtual = ?, is_active = ?, updated_at = NOW()
        WHERE id = ?
    ');
    $stmt->execute([$city, strtoupper($state), $isVirtual, $isActive, $id]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Location not found']);
        exit;
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
