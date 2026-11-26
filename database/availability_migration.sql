-- ========================================================
-- Availability Management System - Non-Destructive Migration
-- Skill Swap Marketplace
-- Target Database: skill_swap (MySQL 5.7+ / MariaDB 10.4+)
-- ========================================================

USE `skill_swap`;

-- 1. Safely add `availability_status` column to `users` if it does not exist
SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'availability_status'
);

SET @alter_sql := IF(
    @col_exists = 0,
    "ALTER TABLE `users` ADD COLUMN `availability_status` ENUM('available', 'busy', 'unavailable') NULL DEFAULT NULL AFTER `status`",
    "SELECT 'Column users.availability_status already exists' AS migration_info"
);

PREPARE stmt_alter_users FROM @alter_sql;
EXECUTE stmt_alter_users;
DEALLOCATE PREPARE stmt_alter_users;

-- 2. Create `availability` table for weekly time slots if it does not exist
CREATE TABLE IF NOT EXISTS `availability` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `day_of_week` ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_avail_unique_slot` (`user_id`, `day_of_week`, `start_time`, `end_time`),
  KEY `idx_avail_user_day` (`user_id`, `day_of_week`),
  CONSTRAINT `fk_availability_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
