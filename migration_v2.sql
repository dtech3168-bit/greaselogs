-- ============================================================
-- Greaselogs Greaselogs — Migration v2
-- Run this ONCE on your existing live database via phpMyAdmin
-- or `mysql -u user -p dbname < migration_v2.sql`
--
-- Adds: password_resets, settings, payment_settings,
-- product_pricing, and account-lockout security columns.
-- Safe to run on an existing DB (uses IF NOT EXISTS).
-- ============================================================

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL,
  `otp` VARCHAR(6) NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `attempts` INT NOT NULL DEFAULT 0,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_email` (`email`),
  KEY `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `api_key` VARCHAR(255) NULL,
  `api_enabled` TINYINT(1) DEFAULT 0,
  `markup` DECIMAL(10,2) DEFAULT 0.00,
  `maintenance_mode` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`id`, `api_key`, `api_enabled`, `markup`, `maintenance_mode`)
SELECT 1, '', 0, 0.00, 0
WHERE NOT EXISTS (SELECT 1 FROM `settings` WHERE `id` = 1);

CREATE TABLE IF NOT EXISTS `payment_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `gateway` VARCHAR(50) NOT NULL UNIQUE,
  `public_key` VARCHAR(255) NULL,
  `secret_key` VARCHAR(255) NULL,
  `enabled` TINYINT(1) DEFAULT 0,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `payment_settings` (`gateway`, `public_key`, `secret_key`, `enabled`) VALUES
('paystack', 'pk_test_xxx', 'sk_test_xxx', 0),
('cryptomus', NULL, NULL, 0),
('korapay', NULL, NULL, 0),
('nowpayments', NULL, NULL, 0);

CREATE TABLE IF NOT EXISTS `product_pricing` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` VARCHAR(100) NOT NULL UNIQUE,
  `custom_price` DECIMAL(10,2) NULL,
  `custom_markup` DECIMAL(10,2) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Account lockout columns (ignore errors here if they already exist
-- on older MySQL/MariaDB that doesn't support ADD COLUMN IF NOT EXISTS)
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `failed_attempts` INT NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `locked_until` DATETIME NULL;

ALTER TABLE `admin`
  ADD COLUMN IF NOT EXISTS `failed_attempts` INT NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `locked_until` DATETIME NULL;

-- ============================================================
-- ADDED: extra_key column on payment_settings
-- (used for NOWPayments IPN secret; Korapay doesn't need it —
-- its webhook signature uses the same secret_key)
-- ============================================================
ALTER TABLE `payment_settings`
  ADD COLUMN IF NOT EXISTS `extra_key` VARCHAR(255) NULL;

-- ============================================================
-- ADDED v3: listing image + admin roles (for Developer Dashboard)
-- ============================================================
ALTER TABLE `listings`
  ADD COLUMN IF NOT EXISTS `image` VARCHAR(500) NULL AFTER `description`;

ALTER TABLE `admin`
  ADD COLUMN IF NOT EXISTS `role` ENUM('admin','developer') NOT NULL DEFAULT 'admin' AFTER `username`;

-- Example: promote/create a developer account (edit email/password after import)
-- INSERT INTO `admin` (`username`, `email`, `password`, `role`) VALUES
-- ('dev', 'dev@greaselogs.com', '$2y$10$REPLACE_WITH_A_REAL_HASH', 'developer');
