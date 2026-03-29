<?php
header('Content-Type: application/json');
define('API_REQUEST', true);
require_once __DIR__ . '/../config/config.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$profileId = $currentProfileId;
$allowedFields = [
    'first_name', 'last_name', 'phone',
    'company_name', 'job_title', 'avatar', 'cal_token',
    'cal_webhook', 'cal_url', 'theme', 'licence_no', 'licence_state'
];

$updates = [];
$values = [];

foreach ($allowedFields as $field) {
    if (isset($input[$field])) {
        $updates[] = "$field = ?";
        $values[] = $input[$field];
    }
}

if (empty($updates)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No fields to update']);
    exit;
}

try {
    $pdo = getDbConnection();
    $values[] = $profileId;
    $sql = 'UPDATE profile SET ' . implode(', ', $updates) . ', updated_at = CURRENT_TIMESTAMP WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} catch (Exception $e) {
    error_log('settings_update error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An internal error occurred']);
}