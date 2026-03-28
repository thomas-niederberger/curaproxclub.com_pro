<?php
/**
 * API Router
 * Routes /api/* requests to the actual API folder outside public directory
 * This maintains backward compatibility with existing frontend fetch calls
 */

// Get the requested API endpoint from the URL
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Extract the API endpoint path
// Example: /api/brushlearn-book_confirm.php -> brushlearn-book_confirm.php
if (preg_match('#/api/([a-zA-Z0-9_\-]+\.php)#', $requestUri, $matches)) {
    $apiFile = $matches[1];
    $apiPath = __DIR__ . '/../api/' . $apiFile;
    
    // Security: Only allow .php files and prevent directory traversal
    if (strpos($apiFile, '..') !== false || !preg_match('/^[a-zA-Z0-9_\-]+\.php$/', $apiFile)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid API endpoint']);
        exit;
    }
    
    // Check if the API file exists
    if (file_exists($apiPath)) {
        // Include and execute the API file
        require $apiPath;
        exit;
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'API endpoint not found']);
        exit;
    }
} else {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid API request']);
    exit;
}
