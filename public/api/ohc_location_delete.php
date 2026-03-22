<?php
require_once __DIR__ . '/../partials/config.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID is required']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Delete location (cascade will handle related records)
    $stmt = $pdo->prepare('DELETE FROM ohc_location WHERE id = ?');
    $stmt->execute([$id]);
    
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
