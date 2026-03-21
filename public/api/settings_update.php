<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../partials/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['profile_id'])) {
    echo json_encode(['success' => false, 'error' => 'Profile ID is required']);
    exit;
}

$profileId = (int) $input['profile_id'];

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare('SELECT * FROM profile WHERE id = ?');
    $stmt->execute([$profileId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        echo json_encode(['success' => false, 'error' => 'Profile not found']);
        exit;
    }

    $allowedFields = [
        'first_name', 'last_name', 'email', 'phone',
        'company_name', 'job_title', 'hubspot_company_id',
        'hubspot_contact_id', 'avatar', 'cal_token',
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
        echo json_encode(['success' => false, 'error' => 'No fields to update']);
        exit;
    }

    $values[] = $profileId;
    $sql = 'UPDATE profile SET ' . implode(', ', $updates) . ', updated_at = CURRENT_TIMESTAMP WHERE id = ?';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
