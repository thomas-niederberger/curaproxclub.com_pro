<?php
header('Content-Type: application/json');
define('API_REQUEST', true);
require_once __DIR__ . '/../config/config.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$contactId = $currentProfile['id_hubspot_b2b_contact'] ?? null;

if (empty($contactId)) {
    echo json_encode(['success' => false, 'error' => 'No HubSpot contact linked to this profile']);
    exit;
}

$data = getHubSpotB2BData($contactId);

if (!$data) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch HubSpot data']);
    exit;
}

echo json_encode(['success' => true, 'data' => $data]);
