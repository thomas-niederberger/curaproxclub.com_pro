<?php
header('Content-Type: application/json');
define('API_REQUEST', true);
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$profileId = $currentProfileId;
$calToken = $data['cal_token'] ?? '';

if (empty($calToken)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing cal_token']);
    exit;
}

try {
    // Verify Cal.com token by calling their API
    $ch = curl_init('https://api.cal.com/v2/me');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $calToken,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo json_encode(['success' => false, 'error' => 'Invalid Cal.com token or API error']);
        exit;
    }

    $calData = json_decode($response, true);

    if (!$calData || !isset($calData['data'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid response from Cal.com API']);
        exit;
    }

    $pdo = getDbConnection();
    $stmt = $pdo->prepare('UPDATE profile SET cal_confirmed = NOW() WHERE id = ?');
    $stmt->execute([$profileId]);

    echo json_encode([
        'success'  => true,
        'message'  => 'Cal.com token verified successfully',
        'cal_user' => $calData['data']['username'] ?? null
    ]);

} catch (Exception $e) {
    error_log('cal_verify error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An internal error occurred']);
}