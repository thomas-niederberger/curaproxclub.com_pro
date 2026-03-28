<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['profile_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$profileId = $data['profile_id'] ?? $_SESSION['profile_id'];
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
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
