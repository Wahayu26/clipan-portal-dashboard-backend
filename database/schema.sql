-- =========================================================================
-- DATABASE SCHEMA: SOC PORTAL BACKEND
-- Database Target: soc_dashboard (atau soc_portal)
-- Table Prefix: portal_*
-- =========================================================================

-- 1. Table Users untuk Portal Dashboard
CREATE TABLE IF NOT EXISTS `portal_users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(64) NOT NULL UNIQUE,
    `full_name` VARCHAR(128) NOT NULL,
    `email` VARCHAR(128) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` VARCHAR(64) NOT NULL DEFAULT 'Admin',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `failed_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` DATETIME NULL,
    `last_login_at` DATETIME NULL,
    `last_login_ip` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table Applications / Tools Registry
CREATE TABLE IF NOT EXISTS `portal_apps` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `app_id` VARCHAR(64) NOT NULL UNIQUE,
    `name` VARCHAR(128) NOT NULL,
    `description` TEXT NOT NULL,
    `url` VARCHAR(512) NOT NULL,
    `category` ENUM('security', 'network', 'monitoring', 'operations', 'other') NOT NULL DEFAULT 'other',
    `icon_name` VARCHAR(64) NOT NULL DEFAULT 'Layers',
    `badge` VARCHAR(32) NULL,
    `accent_color` VARCHAR(32) NOT NULL DEFAULT '#6366f1',
    `bg_gradient` VARCHAR(128) NOT NULL DEFAULT 'linear-gradient(135deg, #6366f1, #4f46e5)',
    `status` ENUM('online', 'maintenance', 'offline') NOT NULL DEFAULT 'online',
    `is_favorite` TINYINT(1) NOT NULL DEFAULT 0,
    `is_custom` TINYINT(1) NOT NULL DEFAULT 0,
    `launch_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `last_opened_at` BIGINT NULL,
    `created_by` VARCHAR(64) NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category` (`category`),
    INDEX `idx_is_favorite` (`is_favorite`),
    INDEX `idx_launch_count` (`launch_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table Active Sessions & Tokens
CREATE TABLE IF NOT EXISTS `portal_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `is_remember` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_active_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `portal_users`(`id`) ON DELETE CASCADE,
    INDEX `idx_token_hash` (`token_hash`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table Security Audit Logs
CREATE TABLE IF NOT EXISTS `portal_audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `username` VARCHAR(64) NOT NULL,
    `action` VARCHAR(64) NOT NULL,
    `description` VARCHAR(255) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `status` VARCHAR(32) NOT NULL DEFAULT 'SUCCESS',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table Deleted Apps Tracking (for cross-user deletion synchronization)
CREATE TABLE IF NOT EXISTS `portal_deleted_apps` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `app_id` VARCHAR(64) NOT NULL UNIQUE,
    `deleted_by` VARCHAR(64) NULL,
    `deleted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_deleted_app_id` (`app_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

