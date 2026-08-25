-- DeluxeSocial Database Schema
-- Created for cPanel Deployment

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

-- Table structure for `users`
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(255) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `wallet_balance` DECIMAL(10, 2) DEFAULT 0.00,
  `email_verified_at` DATETIME NULL,
  `activation_token_hash` CHAR(64) NULL,
  `activation_expires_at` DATETIME NULL,
  `otp_code` VARCHAR(6) NULL,
  `otp_expires_at` DATETIME NULL,
  `session_token` VARCHAR(255) NULL,
  `session_expires_at` DATETIME NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `admin`
CREATE TABLE `admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role` ENUM('admin','developer') NOT NULL DEFAULT 'admin',
  `username` VARCHAR(255) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin (password: admin123)
INSERT INTO `admin` (`username`, `email`, `password`) VALUES
('admin', 'admin@deluxesocial.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Table structure for `listings`
CREATE TABLE `listings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `platform` ENUM('Instagram', 'Facebook', 'TikTok', 'Twitter/X', 'YouTube') NOT NULL,
  `followers_count` INT NOT NULL,
  `engagement_rate` DECIMAL(5, 2) NULL,
  `niche` VARCHAR(255) NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `description` TEXT NULL,
  `image` VARCHAR(500) NULL,
  `source` ENUM('api', 'manual') NOT NULL DEFAULT 'manual',
  `account_username` VARCHAR(255) NULL,
  `account_password` VARCHAR(255) NULL,
  `account_email` VARCHAR(255) NULL,
  `status` ENUM('available', 'sold') DEFAULT 'available',
  `is_featured` BOOLEAN DEFAULT FALSE,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `orders`
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `listing_id` INT NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `status` ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
  `order_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`listing_id`) REFERENCES `listings`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `transactions`
CREATE TABLE `transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `type` ENUM('deposit', 'purchase', 'withdrawal') NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `reference` VARCHAR(255) NOT NULL UNIQUE,
  `status` ENUM('pending', 'success', 'failed') DEFAULT 'pending',
  `payment_gateway` VARCHAR(255) NULL,
  `gateway_response` TEXT NULL,
  `transaction_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `wallet_logs`
CREATE TABLE `wallet_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `transaction_id` INT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `type` ENUM('credit', 'debit') NOT NULL,
  `description` TEXT NULL,
  `balance_after` DECIMAL(10, 2) NOT NULL,
  `log_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table structure for `coupons`
CREATE TABLE `coupons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(255) NOT NULL UNIQUE,
  `discount_type` ENUM('percentage', 'fixed') NOT NULL,
  `discount_value` DECIMAL(10, 2) NOT NULL,
  `min_purchase_amount` DECIMAL(10, 2) DEFAULT 0.00,
  `usage_limit` INT DEFAULT NULL,
  `used_count` INT DEFAULT 0,
  `expires_at` DATETIME NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;

-- ============================================================
-- ADDED: Missing tables required by application code
-- (password resets, settings, payment gateway config, pricing)
-- ============================================================

CREATE TABLE `password_resets` (
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

CREATE TABLE `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `api_key` VARCHAR(255) NULL,
  `api_enabled` TINYINT(1) DEFAULT 0,
  `markup` DECIMAL(10,2) DEFAULT 0.00,
  `maintenance_mode` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`id`, `api_key`, `api_enabled`, `markup`, `maintenance_mode`)
VALUES (1, '', 0, 0.00, 0);

CREATE TABLE `payment_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `gateway` VARCHAR(50) NOT NULL UNIQUE,
  `public_key` VARCHAR(255) NULL,
  `secret_key` VARCHAR(255) NULL,
  `enabled` TINYINT(1) DEFAULT 0,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `payment_settings` (`gateway`, `public_key`, `secret_key`, `enabled`) VALUES
('paystack', 'pk_test_xxx', 'sk_test_xxx', 0),
('cryptomus', NULL, NULL, 0),
('korapay', NULL, NULL, 0),
('nowpayments', NULL, NULL, 0);

CREATE TABLE `product_pricing` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` VARCHAR(100) NOT NULL UNIQUE,
  `custom_price` DECIMAL(10,2) NULL,
  `custom_markup` DECIMAL(10,2) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ADDED: Brute-force / account lockout security columns
-- ============================================================

ALTER TABLE `users`
  ADD COLUMN `failed_attempts` INT NOT NULL DEFAULT 0,
  ADD COLUMN `locked_until` DATETIME NULL;

ALTER TABLE `admin`
  ADD COLUMN `failed_attempts` INT NOT NULL DEFAULT 0,
  ADD COLUMN `locked_until` DATETIME NULL;

ALTER TABLE `payment_settings`
  ADD COLUMN `extra_key` VARCHAR(255) NULL;
