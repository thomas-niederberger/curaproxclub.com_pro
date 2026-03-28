<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../partials/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$profileId = $data['profile_id'] ?? '';
$calToken = $data['cal_token'] ?? '';

if (empty($profileId) || empty($calToken)) {
    echo json_encode(['success' => false, 'error' => 'Missing profile_id or cal_token']);
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
    
    // Check if the API call was successful
    if ($httpCode !== 200) {
        echo json_encode(['success' => false, 'error' => 'Invalid Cal.com token or API error']);
        exit;
    }
    
    $calData = json_decode($response, true);
    
    // Verify we got valid data back
    if (!$calData || !isset($calData['data'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid response from Cal.com API']);
        exit;
    }
    
    // Token is valid, update the database
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('UPDATE profile SET cal_confirmed = NOW() WHERE id = ?');
    $stmt->execute([$profileId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cal.com token verified successfully',
        'cal_user' => $calData['data']['username'] ?? null
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
