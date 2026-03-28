<?php
header('Content-Type: application/json');
define('API_REQUEST', true);
require_once __DIR__ . '/../config/config.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$profileId  = $data['profile_id'] ?? null;
$locationId = $data['location_id'] ?? null;

if (!$profileId || !$locationId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Profile ID and Location ID are required']);
    exit;
}

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare('SELECT 1 FROM ohc_profile WHERE profile_id = ? AND location_id = ?');
    $stmt->execute([$profileId, $locationId]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'This assignment already exists']);
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO ohc_profile (profile_id, location_id) VALUES (?, ?)');
    $stmt->execute([$profileId, $locationId]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('ohc_assignment_add error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An internal error occurred']);
}