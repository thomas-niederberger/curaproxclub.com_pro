<?php
/**
 * Rate Limiter
 * Prevents abuse by limiting requests per IP address
 * Uses APCu for high-performance in-memory caching
 */

function checkRateLimit($identifier, $maxRequests = 5, $windowSeconds = 60) {
    // Use IP address as part of identifier for better security
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'rate_limit_' . $identifier . '_' . $ip;
    
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
