<?php
header('Content-Type: application/json');
define('API_REQUEST', true);
require_once __DIR__ . '/../config/config.php';

requireAuth();
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['user_id']) || !is_numeric($input['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('UPDATE profile SET licence_verified = NOW() WHERE id = ?');
    $stmt->execute([$input['user_id']]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('licence_approve error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An internal error occurred']);
}