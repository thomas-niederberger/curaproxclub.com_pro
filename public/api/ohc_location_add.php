<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../partials/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$city = $data['city'] ?? null;
$state = $data['state'] ?? null;
$isVirtual = isset($data['is_virtual']) ? (int)$data['is_virtual'] : 0;
$isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

if (!$city || !$state) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'City and state are required']);
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
        INSERT INTO ohc_location (city, state, is_virtual, is_active, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ');
    $stmt->execute([$city, strtoupper($state), $isVirtual, $isActive]);
    
    echo json_encode([
        'success' => true,
        'id' => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
