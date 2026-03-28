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

$data = json_decode(file_get_contents('php://input'), true);
$profileId = $currentProfileId;
$email = $data['email'] ?? '';

if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Email is required']);
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Use existing function to check and store all external IDs (HubSpot B2B, B2C, Shopify)
    checkAndStoreExternalIds($pdo, $profileId, $email);
    
    // Fetch the updated IDs from profile
    $stmt = $pdo->prepare('SELECT id_hubspot_b2b_contact, id_hubspot_b2b_company FROM profile WHERE id = ?');
    $stmt->execute([$profileId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'contact_id' => $profile['id_hubspot_b2b_contact'] ?? null,
        'company_id' => $profile['id_hubspot_b2b_company'] ?? null
    ]);
    
} catch (Exception $e) {
    error_log('sync_external_ids error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An internal error occurred']);
}
