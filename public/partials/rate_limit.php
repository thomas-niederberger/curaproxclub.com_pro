<?php
/**
 * Rate Limiter
 * Prevents abuse by limiting requests per IP address
 * Uses APCu if available, falls back to database
 */

function checkRateLimit($identifier, $maxRequests = 5, $windowSeconds = 60) {
    // Use IP address as part of identifier for better security
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'rate_limit_' . $identifier . '_' . $ip;
    
    // Try APCu first (fastest)
    if (function_exists('apcu_enabled') && apcu_enabled()) {
        $current = apcu_fetch($key);
        
        if ($current === false) {
            // First request in window
            apcu_store($key, 1, $windowSeconds);
            return true;
        }
        
        if ($current >= $maxRequests) {
            // Rate limit exceeded
            return false;
        }
        
        // Increment counter
        apcu_inc($key);
        return true;
    }
    
    // Fallback to database
    try {
        $pdo = getDbConnection();
        
        // Clean up old entries first
        $stmt = $pdo->prepare('DELETE FROM rate_limit WHERE expires_at < NOW()');
        $stmt->execute();
        
        // Check current count
        $stmt = $pdo->prepare('
            SELECT COUNT(*) as count 
            FROM rate_limit 
            WHERE identifier = ? AND ip_address = ? AND expires_at > NOW()
        ');
        $stmt->execute([$identifier, $ip]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] >= $maxRequests) {
            return false;
        }
        
        // Add new entry
        $expiresAt = gmdate('Y-m-d H:i:s', time() + $windowSeconds);
        $stmt = $pdo->prepare('
            INSERT INTO rate_limit (identifier, ip_address, expires_at, created_at) 
            VALUES (?, ?, ?, NOW())
        ');
        $stmt->execute([$identifier, $ip, $expiresAt]);
        
        return true;
    } catch (PDOException $e) {
        // If database fails, allow the request (fail open for availability)
        error_log('Rate limit database error: ' . $e->getMessage());
        return true;
    }
}

function sendRateLimitResponse() {
    http_response_code(429);
    header('Retry-After: 60');
    echo json_encode([
        'success' => false,
        'error' => 'Too many requests. Please wait a moment and try again.'
    ]);
    exit;
}
