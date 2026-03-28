-- Rate Limiting Table
-- Used as fallback when APCu is not available
-- Tracks request counts per IP address and identifier

CREATE TABLE IF NOT EXISTS rate_limit (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(100) NOT NULL COMMENT 'Action identifier (e.g., login, register)',
    ip_address VARCHAR(45) NOT NULL COMMENT 'IPv4 or IPv6 address',
    expires_at TIMESTAMP NOT NULL COMMENT 'When this rate limit entry expires',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier_ip (identifier, ip_address, expires_at),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
