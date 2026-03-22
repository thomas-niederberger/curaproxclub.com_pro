-- Create ohc_booking table for Brush & Learn bookings
CREATE TABLE `ohc_booking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `profile_id` int(11) NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `form_response_id` int(11) DEFAULT NULL,
  `cal_booking_id` varchar(255) DEFAULT NULL COMMENT 'Cal.com booking reference ID',
  `booking_date` datetime DEFAULT NULL COMMENT 'Selected appointment date and time',
  `status` enum('draft','pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'draft',
  `is_virtual` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_profile_id` (`profile_id`),
  KEY `idx_location_id` (`location_id`),
  KEY `idx_form_response_id` (`form_response_id`),
  KEY `idx_status` (`status`),
  KEY `idx_booking_date` (`booking_date`),
  CONSTRAINT `fk_booking_profile` FOREIGN KEY (`profile_id`) REFERENCES `profile` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_location` FOREIGN KEY (`location_id`) REFERENCES `ohc_location` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_booking_form_response` FOREIGN KEY (`form_response_id`) REFERENCES `form_response` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Brush & Learn booking records';
