<?php
/**
 * Non-Destructive Database Migration Runner: Availability System
 * Idempotent: safe to run multiple times without affecting existing data.
 */

if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

require_once __DIR__ . '/../config/db.php';

/**
 * Ensures availability schema additions exist in MySQL.
 */
function run_availability_migration(PDO $pdo): array {
    $actions = [];

    // 1. Check and add `availability_status` to `users`
    $colStmt = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'availability_status'");
    if ($colStmt->rowCount() === 0) {
        $pdo->exec("
            ALTER TABLE `users`
            ADD COLUMN `availability_status` ENUM('available', 'busy', 'unavailable') NULL DEFAULT NULL AFTER `status`
        ");
        $actions[] = 'Added users.availability_status column.';
    } else {
        $actions[] = 'users.availability_status column already exists.';
    }

    // 2. Check and create `availability` table
    $tblStmt = $pdo->query("SHOW TABLES LIKE 'availability'");
    if ($tblStmt->rowCount() === 0) {
        $pdo->exec("
            CREATE TABLE `availability` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $actions[] = 'Created availability table.';
    } else {
        $actions[] = 'availability table already exists.';
    }

    return $actions;
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $pdo = get_db();
    foreach (run_availability_migration($pdo) as $msg) {
        echo "[Migration] {$msg}\n";
    }
}
