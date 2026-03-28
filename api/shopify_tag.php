<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get current user's profile
    if (!isset($currentProfile) || !isset($currentProfile['id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'User not authenticated']);
        exit;
    }

    // Check if user has Shopify B2C ID
    if (!isset($currentProfile['id_shopify_b2c']) || empty($currentProfile['id_shopify_b2c'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No Shopify customer ID found']);
        exit;
    }

    $shopifyCustomerId = $currentProfile['id_shopify_b2c'];
    $userId = $currentProfile['id'];

    // Shopify GraphQL API configuration
    $shopifyUrl = 'https://curaproxclub.myshopify.com/admin/api/2024-01/graphql.json';
    $accessToken = $_ENV['shopifyToken'] ?? '';

    // GraphQL mutation to add "professional" tag
    $mutation = [
        'query' => 'mutation {
            tagsAdd(id: "gid://shopify/Customer/' . $shopifyCustomerId . '", tags: ["professional"]) {
                node {
                    id
                }
                userErrors {
                    field
                    message
                }
            }
        }',
        'variables' => new stdClass()
    ];

    // Make request to Shopify
    $ch = curl_init($shopifyUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($mutation));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Shopify-Access-Token: ' . $accessToken
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Shopify API request failed', 'http_code' => $httpCode]);
        exit;
    }

    $result = json_decode($response, true);

    // Check for errors in GraphQL response
    if (isset($result['data']['tagsAdd']['userErrors']) && !empty($result['data']['tagsAdd']['userErrors'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error' => 'Shopify tag addition failed',
            'details' => $result['data']['tagsAdd']['userErrors']
        ]);
        exit;
    }

    // Update database with shopify_tag timestamp
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('UPDATE profile SET shopify_tag = NOW() WHERE id = ?');
        $stmt->execute([$userId]);

        echo json_encode(['success' => true, 'message' => 'Professional tag added successfully']);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database update failed: ' . $e->getMessage()]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
exit;
