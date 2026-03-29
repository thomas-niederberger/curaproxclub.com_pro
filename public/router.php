<?php
/**
 * PHP Built-in Dev Server Router
 * Usage: php -S localhost:8080 router.php  (from the public/ directory)
 *
 * Apache (.htaccess) handles this automatically in production.
 * This file is ONLY needed for the PHP built-in development server.
 */

$uri = $_SERVER['REQUEST_URI'];

// Route /api/*.php requests through the API proxy
if (preg_match('@^/api/[a-zA-Z0-9_\-]+\.php@', $uri)) {
    require __DIR__ . '/api.php';
    return true;
}

// Serve existing static files (css, js, images, etc.) directly
$filePath = __DIR__ . parse_url($uri, PHP_URL_PATH);
if (is_file($filePath)) {
    return false;
}

// Fall through to PHP pages
return false;
