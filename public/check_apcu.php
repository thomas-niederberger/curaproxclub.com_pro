<?php
/**
 * APCu Availability Check
 * Run this script to verify if APCu is enabled on your server
 */

header('Content-Type: text/plain');

echo "=== APCu Availability Check ===\n\n";

// Check if APCu extension is loaded
if (extension_loaded('apcu')) {
    echo "✓ APCu extension is LOADED\n";
    
    // Check if APCu is enabled
    if (function_exists('apcu_enabled') && apcu_enabled()) {
        echo "✓ APCu is ENABLED\n\n";
        
        // Test APCu functionality
        $testKey = 'apcu_test_' . time();
        $testValue = 'test_value';
        
        if (apcu_store($testKey, $testValue, 10)) {
            echo "✓ APCu WRITE test: SUCCESS\n";
            
            $retrieved = apcu_fetch($testKey);
            if ($retrieved === $testValue) {
                echo "✓ APCu READ test: SUCCESS\n";
                apcu_delete($testKey);
                echo "✓ APCu DELETE test: SUCCESS\n\n";
                
                echo "RESULT: APCu is fully functional!\n";
                echo "Rate limiting will use APCu (in-memory, high performance)\n";
                echo "Database table is NOT required.\n";
            } else {
                echo "✗ APCu READ test: FAILED\n\n";
                echo "RESULT: APCu has issues\n";
                echo "Database table IS required for rate limiting.\n";
            }
        } else {
            echo "✗ APCu WRITE test: FAILED\n\n";
            echo "RESULT: APCu has issues\n";
            echo "Database table IS required for rate limiting.\n";
        }
    } else {
        echo "✗ APCu is DISABLED\n\n";
        echo "RESULT: APCu is not enabled\n";
        echo "Database table IS required for rate limiting.\n";
    }
} else {
    echo "✗ APCu extension is NOT LOADED\n\n";
    echo "RESULT: APCu is not available\n";
    echo "Database table IS required for rate limiting.\n";
}

echo "\n=== PHP Configuration ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Loaded Extensions: " . (extension_loaded('apcu') ? 'apcu, ' : '') . implode(', ', array_slice(get_loaded_extensions(), 0, 10)) . "...\n";

if (extension_loaded('apcu')) {
    echo "\n=== APCu Configuration ===\n";
    echo "apcu.enabled: " . ini_get('apcu.enabled') . "\n";
    echo "apcu.shm_size: " . ini_get('apcu.shm_size') . "\n";
    echo "apcu.ttl: " . ini_get('apcu.ttl') . "\n";
}
