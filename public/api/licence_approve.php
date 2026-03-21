<?php
require_once __DIR__ . '/../partials/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['user_id']) && is_numeric($input['user_id'])) {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare('UPDATE profile SET licence_verified = NOW() WHERE id = ?');
            $stmt->execute([$input['user_id']]);

            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
exit;
