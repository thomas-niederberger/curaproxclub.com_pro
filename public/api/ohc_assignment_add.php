<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../partials/config.php';

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
    
    // Check if assignment already exists
    $stmt = $pdo->prepare('SELECT 1 FROM ohc_profile WHERE profile_id = ? AND location_id = ?');
    $stmt->execute([$profileId, $locationId]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'This assignment already exists']);
        exit;
    }
    
    // Insert new assignment
    $stmt = $pdo->prepare('INSERT INTO ohc_profile (profile_id, location_id) VALUES (?, ?)');
    $stmt->execute([$profileId, $locationId]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
