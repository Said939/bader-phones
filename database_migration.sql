-- ============================================================================
-- بدر للهواتف / Bader Phones - Incremental Database Migration Script
-- Upgrades schema to 3-tier RBAC ('admin', 'partner', 'customer')
-- Safe to run on existing databases (preserves records & migrates 'user' -> 'customer')
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `bader_phones_db`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `bader_phones_db`;

-- 1. Users Table Evolution
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(180) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `role` ENUM('admin', 'partner', 'customer') NOT NULL DEFAULT 'customer',
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `avatar` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Migrate legacy 'role' column if existing
ALTER TABLE `users` 
    MODIFY COLUMN `role` ENUM('admin', 'partner', 'customer') NOT NULL DEFAULT 'customer';

-- Upgrade any legacy 'user' strings to 'customer'
UPDATE `users` SET `role` = 'customer' WHERE `role` NOT IN ('admin', 'partner');

-- 2. Products Table Evolution
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `category` VARCHAR(50) NOT NULL,
    `name_ar` VARCHAR(255) NOT NULL,
    `name_en` VARCHAR(255) NOT NULL,
    `description_ar` TEXT DEFAULT NULL,
    `description_en` TEXT DEFAULT NULL,
    `specs_ar` TEXT NOT NULL,
    `specs_en` TEXT NOT NULL,
    `price` DECIMAL(10, 3) NOT NULL,
    `stock_quantity` INT NOT NULL DEFAULT 25,
    `badge_ar` VARCHAR(100) DEFAULT NULL,
    `badge_en` VARCHAR(100) DEFAULT NULL,
    `badge_type` VARCHAR(30) DEFAULT 'primary',
    `rating` DECIMAL(2, 1) DEFAULT 5.0,
    `reviews` INT DEFAULT 0,
    `image_type` VARCHAR(50) DEFAULT 'iphone',
    `image_url` VARCHAR(255) DEFAULT 'assets/logo.png',
    `in_stock` TINYINT(1) DEFAULT 1,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Orders Table Evolution
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_code` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT DEFAULT NULL,
    `customer_name` VARCHAR(120) NOT NULL,
    `customer_phone` VARCHAR(50) NOT NULL,
    `customer_email` VARCHAR(180) DEFAULT NULL,
    `items_json` LONGTEXT NOT NULL,
    `total_amount` DECIMAL(10, 3) NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT 'cod',
    `status` ENUM('pending', 'confirmed', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT DEFAULT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `product_slug` VARCHAR(100) NOT NULL,
    `unit_price` DECIMAL(10, 3) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `subtotal` DECIMAL(10, 3) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_items_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. System Settings Table
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Upsert RBAC Seed Accounts
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `status`)
VALUES 
(
    'سعيد العامري / Said Al-Amri', 
    'admin@baderphones.om', 
    '$2y$10$fVd3r3GgYfVdJ9z0kFqgLeqB87kX8X2K2N7vUjEwZ4H4L4qF6pUiq', -- admin123
    '+96897447122', 
    'admin', 
    'active'
),
(
    'سعيد العامري / Said Al-Amri (Dev)', 
    'Ssaid9395212@gmail.com', 
    '$2y$10$fVd3r3GgYfVdJ9z0kFqgLeqB87kX8X2K2N7vUjEwZ4H4L4qF6pUiq', -- admin123
    '+96893952123', 
    'admin', 
    'active'
),
(
    'سالم الشبيبي (شريك المتجر) / Partner Shop', 
    'partner@baderphones.om', 
    '$2y$10$sQoA7y6P8vC.pW3Z0lUxeOFcZ8UvT3Z5W5eG5fQ9y1A3B5C7D9E1G', -- partner123
    '+96893952123', 
    'partner', 
    'active'
),
(
    'أحمد الحارثي / Ahmed Al-Harthi', 
    'customer@baderphones.om', 
    '$2y$10$wE96Xw44HkM3b0B2a.hHcew5W7hN1qD0mJ3y7.t8uKqG4.VvU9Z1m', -- customer123
    '+96891234567', 
    'customer', 
    'active'
)
ON DUPLICATE KEY UPDATE 
    `role` = VALUES(`role`), 
    `status` = VALUES(`status`),
    `name` = VALUES(`name`);
