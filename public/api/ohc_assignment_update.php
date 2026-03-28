<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../partials/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$originalProfileId = $data['original_profile_id'] ?? null;
$originalLocationId = $data['original_location_id'] ?? null;
$profileId = $data['profile_id'] ?? null;
$locationId = $data['location_id'] ?? null;

if (!$originalProfileId || !$originalLocationId || !$profileId || !$locationId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All IDs are required']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Check if new assignment already exists (if different from original)
    if ($profileId != $originalProfileId || $locationId != $originalLocationId) {
        $stmt = $pdo->prepare('SELECT 1 FROM ohc_profile WHERE profile_id = ? AND location_id = ?');
        $stmt->execute([$profileId, $locationId]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'This assignment already exists']);
            exit;
        }
    }
    
    // Update assignment
    $stmt = $pdo->prepare('
        UPDATE ohc_profile 
        SET location_id = ?
        WHERE profile_id = ? AND location_id = ?
    ');
    $stmt->execute([$locationId, $originalProfileId, $originalLocationId]);
    
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
