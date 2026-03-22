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
$profileId = $data['profile_id'] ?? null;
$locationId = $data['location_id'] ?? null;

if (!$profileId || !$locationId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Profile ID and Location ID are required']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Delete assignment
    $stmt = $pdo->prepare('DELETE FROM ohc_profile WHERE profile_id = ? AND location_id = ?');
    $stmt->execute([$profileId, $locationId]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Assignment not found']);
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
