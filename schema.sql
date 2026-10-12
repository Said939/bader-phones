-- ============================================================================
-- بدر للهواتف / Bader Phones - Full E-Commerce Database Architecture
-- Database Name: bader_phones_db
-- Features: 3-Tier Role-Based Access Control (RBAC: Admin, Partner/Shop, Customer),
-- Products Catalog & Inventory, Orders & Normalized Order Items, System Settings
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `bader_phones_db`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `bader_phones_db`;

-- ----------------------------------------------------------------------------
-- 1. Table: users (3-Tier RBAC Architecture)
-- Roles:
--   - 'admin': Full Control (Developer & System Administrator)
--   - 'partner': Shop Owner / Business Partner (Products & Orders Management)
--   - 'customer': Regular Buyer (Browse, Cart, Order, View Profile)
-- ----------------------------------------------------------------------------
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
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Table: products (Catalog & Stock Inventory)
-- ----------------------------------------------------------------------------
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
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_products_category` (`category`),
    INDEX `idx_products_price` (`price`),
    INDEX `idx_products_stock` (`in_stock`, `stock_quantity`),
    CONSTRAINT `fk_products_created_by` FOREIGN KEY (`created_by`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Table: orders (Orders Linked to Customers & WhatsApp Checkout)
-- ----------------------------------------------------------------------------
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
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_orders_user` (`user_id`),
    INDEX `idx_orders_status` (`status`),
    INDEX `idx_orders_code` (`order_code`),
    CONSTRAINT `fk_orders_user_id` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. Table: order_items (Normalized Line Items for Reporting & Inventory)
-- ----------------------------------------------------------------------------
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
    INDEX `idx_items_order` (`order_id`),
    INDEX `idx_items_product` (`product_id`),
    CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) 
        REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) 
        REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. Table: system_settings (Admin-Restricted Site Configuration)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- SEED DATA: 3-Tier Users (Passwords hashed using standard bcrypt password_hash)
-- Passwords:
--   - admin@baderphones.om  / Ssaid9395212@gmail.com => admin123
--   - partner@baderphones.om => partner123
--   - customer@baderphones.om / demo@baderphones.om => customer123 / password123
-- ----------------------------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`, `status`)
VALUES
(
    1,
    'سعيد العامري / Said Al-Amri',
    'admin@baderphones.om',
    '$2y$10$OJcWQQSX1rXPVVspK.Mwq.sOv9Ne8qLnNkyB2LDCPHfAgPuTEnMGK', -- admin123
    '+96897447122',
    'admin',
    'active'
),
(
    2,
    'سعيد العامري / Developer Reference',
    'Ssaid9395212@gmail.com',
    '$2y$10$OJcWQQSX1rXPVVspK.Mwq.sOv9Ne8qLnNkyB2LDCPHfAgPuTEnMGK', -- admin123
    '+96893952123',
    'admin',
    'active'
),
(
    3,
    'سالم الشبيبي (شريك المتجر) / Salim Al-Shabibi',
    'partner@baderphones.om',
    '$2y$10$srsnJxTz657jzKemDfVvceHuTOm77XiBeZQSgYCcvnK.wYphTRiH.', -- partner123
    '+96893952123',
    'partner',
    'active'
),
(
    4,
    'أحمد الحارثي / Ahmed Al-Harthi',
    'customer@baderphones.om',
    '$2y$10$j1HNNyUckXqK6NBhbFhxqu.tYxM6dR0UGra0vYfs70DsNSnzFsGUe', -- customer123
    '+96891234567',
    'customer',
    'active'
),
(
    5,
    'حساب تجريبي / Demo User',
    'demo@baderphones.om',
    '$2y$10$oOQXyi59GecjjFzz2rpr7ONKfeWa0eptY6VrW8uLQFPxcsnS7N6uq', -- password123
    '+96897447122',
    'customer',
    'active'
)
ON DUPLICATE KEY UPDATE 
    `role` = VALUES(`role`), 
    `status` = VALUES(`status`),
    `name` = VALUES(`name`);

-- ----------------------------------------------------------------------------
-- SEED DATA: 8 Featured Products with Stock Quantities & Full Descriptions
-- ----------------------------------------------------------------------------
INSERT INTO `products` (`id`, `slug`, `category`, `name_ar`, `name_en`, `description_ar`, `description_en`, `specs_ar`, `specs_en`, `price`, `stock_quantity`, `badge_ar`, `badge_en`, `badge_type`, `rating`, `reviews`, `image_type`, `in_stock`, `created_by`)
VALUES
(
    1,
    'iphone-18-pro-max',
    'smartphones',
    'آيفون 18 برو ماكس (iPhone 18 Pro Max)',
    'Apple iPhone 18 Pro Max',
    'الهاتف الرائد الأقوى من آبل لعام 2026 بهيكل مصنوع من التيتانيوم الطبيعي المطور وكاميرا تقريب بصري خارقة.',
    'The pinnacle of mobile engineering with titanium frame, A19 Pro Bionic, and unmatched periscope zoom.',
    'رام 16GB | سعة 512GB تيتانيوم | معالج A19 Pro Bionic | شاشة 120Hz',
    '16GB RAM | 512GB Natural Titanium | A19 Pro Chip | 6.9" ProMotion',
    549.000,
    30,
    'الأعلى طلباً ⭐',
    'Top Flagship ⭐',
    'primary',
    4.9,
    142,
    'iphone',
    1,
    1
),
(
    2,
    'samsung-s26-ultra',
    'smartphones',
    'سامسونج جالكسي S26 ألترا (Galaxy S26 Ultra)',
    'Samsung Galaxy S26 Ultra',
    'الهاتف الذكي الأكثر ذكاءً مع الجيل الثاني من Galaxy AI وقلم S-Pen وشاشة مذهلة بدقة 2K.',
    'Empowered by Next-Gen Galaxy AI, Snapdragon 8 Gen 5, and built-in precision stylus.',
    'رام 24GB | سعة 1TB | معالج Snapdragon 8 Gen 5 AI | قلم S-Pen مدمج',
    '24GB RAM | 1TB Titanium Black | Snapdragon 8 Gen 5 AI | S-Pen Included',
    495.000,
    25,
    'ذكاء اصطناعي فائق 🤖',
    'Galaxy AI 2.0 🤖',
    'accent',
    4.9,
    98,
    'samsung',
    1,
    1
),
(
    3,
    'redmi-note-17-pro-plus',
    'smartphones',
    'شاومي ريدمي نوت 17 برو بلس (Redmi Note 17 Pro+)',
    'Xiaomi Redmi Note 17 Pro+',
    'أفضل هاتف من الفئة المتوسطة العليا مع شاحن صاروخي بقوة 210 واط يشحن الهاتف كاملاً في 9 دقائق.',
    'Best value performance powerhouse featuring 200MP OIS camera and ultra-rapid 210W HyperCharge.',
    'رام 16GB | سعة 512GB | كاميرا 200MP | شحن خارق 210W HyperCharge',
    '16GB RAM | 512GB Storage | 200MP OIS Camera | 210W HyperCharge',
    169.000,
    45,
    'أفضل قيمة مقابل سعر ⚡',
    'Best Value Deal ⚡',
    'success',
    4.8,
    215,
    'xiaomi',
    1,
    1
),
(
    4,
    'macbook-pro-m4',
    'laptops',
    'ماك بوك برو 16 بوصة (M4 Max)',
    'Apple MacBook Pro 16" (M4 Max)',
    'محطة عمل محمولة فائقة السرعة لصناع المحتوى والمطورين مع شاشة Liquid Retina XDR وبطارية تدوم 22 ساعة.',
    'Extreme mobile workstation powered by Apple M4 Max with Liquid Retina XDR display and 22-hour battery.',
    'ذاكرة موحدة 36GB | قرص 1TB SSD | شاشة Liquid Retina XDR | بطارية 22 ساعة',
    '36GB Unified Memory | 1TB SSD | Liquid Retina XDR Display | 22h Battery',
    980.000,
    12,
    'قوة احترافية للمصممين 💻',
    'Pro Powerhouse 💻',
    'primary',
    5.0,
    64,
    'macbook',
    1,
    1
),
(
    5,
    'dell-xps-16-oled',
    'laptops',
    'ديل XPS 16 شاشة لمس 4K OLED',
    'Dell XPS 16 OLED Touch Ultra',
    'لابتوب ويندوز فائق الأناقة مع شاشة OLED بدقة 4K ولمس كامل وكرت شاشة RTX 4070 المخصص للألعاب والمونتاج.',
    'Sophisticated engineering with 4K InfinityEdge OLED touch panel, Intel Core Ultra 9, and RTX 4070 GPU.',
    'رام 32GB DDR5 | قرص 1TB NVMe | كرت شاشة RTX 4070 8GB | Core Ultra 9',
    '32GB DDR5 RAM | 1TB NVMe SSD | RTX 4070 8GB | Intel Core Ultra 9',
    840.000,
    10,
    'صناع المحتوى والألعاب 🎮',
    'Creator & Gaming 🎮',
    'accent',
    4.8,
    42,
    'dell',
    1,
    1
),
(
    6,
    'anker-prime-250w',
    'accessories',
    'بنك طاقة أنكر برايم 27,650mAh بقدرة 250W',
    'Anker Prime 27,650mAh (250W) PowerBank',
    'بنك الطاقة الأقوى للشحن فائق السرعة يدعم شحن لابتوب وهاتفين معاً بشاشة رقمية تعرض سرعة كل منفذ.',
    'Airline-approved 27,650mAh multi-device fast battery with smart digital display and 250W total output.',
    'شحن فائق 250 واط | شاشة رقمية ذكية | 3 منافذ شحن للابتوب والهواتف معاً',
    '250W Total Output | Smart Digital App Screen | Multi-Device Fast Charge',
    48.000,
    60,
    'ضروري للسفر والعمل 🔋',
    'Travel Essential 🔋',
    'warning',
    4.9,
    318,
    'anker',
    1,
    1
),
(
    7,
    'sony-a7m4-kit',
    'photography',
    'كاميرا سوني الفا 7 IV فل فريم + عدسة 28-70mm',
    'Sony Alpha 7 IV Full-Frame Kit',
    'كاميرا ميرورليس احترافية للمصورين وصناع الأفلام بدقة 33 ميجابكسل وتصوير فيديو 4K بمعدل 60 إطار في الثانية.',
    'Hybrid creator flagship with 33MP Exmor R sensor, 4K 60p 10-bit recording, and next-level autofocus.',
    'مستشعر 33MP Exmor R | تصوير فيديو 4K 60p 10-Bit | تركيز بؤري ذكي بالذكاء الاصطناعي',
    '33MP BSI CMOS Sensor | 4K 60p 10-Bit Video | AI Real-Time Eye AF',
    865.000,
    8,
    'الخيار السينمائي المفضل 🎥',
    'Cinema Grade 🎥',
    'primary',
    4.9,
    56,
    'sony',
    1,
    1
),
(
    8,
    'dji-osmo-pocket-3',
    'photography',
    'كاميرا دي جيه آي أوزمو بوكيت 3 كومبو للمبدعين',
    'DJI Osmo Pocket 3 Creator Combo',
    'كاميرا الجيب السينمائية مع مانع اهتزاز ميكانيكي 3 محاور ومستشعر 1 بوصة وميكروفون لاسلكي فائق النقاء.',
    'Ultra-portable pocket vlogging stabilizer with 1-inch CMOS, rotating 2-inch OLED screen, and wireless audio.',
    'مستشعر 1 بوصة CMOS | فيديو 4K 120fps | مانع اهتزاز ميكانيكي 3 محاور + مايك لاسلكي',
    '1-inch CMOS Sensor | 4K 120fps | 3-Axis Gimbal + Wireless Mic Transmitter',
    245.000,
    20,
    'الأفضل للفلوج وصناع المحتوى 🎙️',
    'Vlogger Champion 🎙️',
    'accent',
    4.9,
    192,
    'dji',
    1,
    1
)
ON DUPLICATE KEY UPDATE 
    `name_ar` = VALUES(`name_ar`),
    `specs_ar` = VALUES(`specs_ar`),
    `price` = VALUES(`price`),
    `stock_quantity` = VALUES(`stock_quantity`);

-- ----------------------------------------------------------------------------
-- SEED DATA: Sample Orders demonstrating Linked Customer Purchases
-- ----------------------------------------------------------------------------
INSERT INTO `orders` (`id`, `order_code`, `user_id`, `customer_name`, `customer_phone`, `customer_email`, `items_json`, `total_amount`, `payment_method`, `status`, `notes`)
VALUES
(
    1,
    'ORD-2026-8801',
    4,
    'أحمد الحارثي / Ahmed Al-Harthi',
    '+96891234567',
    'customer@baderphones.om',
    '[{"slug":"iphone-18-pro-max","name":"Apple iPhone 18 Pro Max","price":549.000,"qty":1}]',
    549.000,
    'whatsapp_cod',
    'confirmed',
    'توصيل سريع - ولاية السيب / مسقط'
),
(
    2,
    'ORD-2026-8802',
    5,
    'محمد البلوشي',
    '+96898765432',
    'demo@baderphones.om',
    '[{"slug":"anker-prime-250w","name":"Anker Prime 27,650mAh (250W)","price":48.000,"qty":2}]',
    96.000,
    'whatsapp_cod',
    'completed',
    'تم الاستلام والتأكيد'
)
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

-- Order Line Items
INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `product_slug`, `unit_price`, `quantity`, `subtotal`)
VALUES
(1, 1, 1, 'Apple iPhone 18 Pro Max', 'iphone-18-pro-max', 549.000, 1, 549.000),
(2, 2, 6, 'Anker Prime 27,650mAh (250W) PowerBank', 'anker-prime-250w', 48.000, 2, 96.000)
ON DUPLICATE KEY UPDATE `quantity` = VALUES(`quantity`);

-- ----------------------------------------------------------------------------
-- SEED DATA: System Settings
-- ----------------------------------------------------------------------------
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`)
VALUES
('store_name_ar', 'بدر للهواتف', 'Official Arabic Store Name'),
('store_name_en', 'Bader Phones', 'Official English Store Name'),
('store_phone', '+968 9744 7122', 'Primary Hotline Phone'),
('whatsapp_number', '96897447122', 'Primary WhatsApp Direct Order Number'),
('partner_whatsapp', '96893952123', 'Partner & Supplier WhatsApp Dispatch Line'),
('currency', 'OMR', 'Default Store Currency'),
('maintenance_mode', '0', 'Store Maintenance Mode (0=Live, 1=Maintenance)')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
