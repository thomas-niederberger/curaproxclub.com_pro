<?php
header('Content-Type: application/json');
define('API_REQUEST', true);
require_once __DIR__ . '/../config/config.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['theme']) && in_array($input['theme'], ['light', 'dark'])) {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('UPDATE profile SET theme = ? WHERE id = ?');
        $stmt->execute([$input['theme'], $currentProfileId]);
        
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Invalid theme']);
exit;
