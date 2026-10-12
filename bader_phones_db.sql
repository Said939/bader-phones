-- ============================================================================
-- Bader Phones / بدر للهواتف - Complete Production Database Schema
-- Database Name: bader_phones_db
-- Target DBMS: MySQL / MariaDB (XAMPP Local Server)
-- Flawless UTF-8 / utf8mb4 Support (Prevents Arabic Mojibake)
-- ============================================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS `bader_phones_db`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `bader_phones_db`;

SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
SET CHARACTER SET utf8mb4;

-- Drop existing tables safely to allow clean re-import
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. Table: users
-- Roles: 'admin' (Developer / Full Control), 'partner' (Shop Owner), 'customer' (Buyer)
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `email` VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
    `password` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `role` ENUM('admin', 'partner', 'customer') NOT NULL DEFAULT 'customer',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Table: products
-- Catalog items with stock quantities and categories
-- ----------------------------------------------------------------------------
CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name_ar` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `name_en` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `name` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `description_ar` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `description_en` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `description` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `price` DECIMAL(10, 3) NOT NULL,
    `stock` INT NOT NULL DEFAULT 10,
    `category` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'phones',
    `ram` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `storage` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `battery` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `camera` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `processor` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `graphics_card` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `screen_size` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `colors` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `image1` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'assets/logo.png',
    `image2` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'assets/logo.png',
    `image3` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'assets/logo.png',
    `image4` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'assets/logo.png',
    `image` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'assets/logo.png',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_products_category` (`category`),
    INDEX `idx_products_price` (`price`),
    INDEX `idx_products_stock` (`stock`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Table: orders
-- Customer orders with JSON order_items and WhatsApp status workflow
-- ----------------------------------------------------------------------------
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `total_amount` DECIMAL(10, 3) NOT NULL,
    `order_items` JSON NOT NULL,
    `status` ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_orders_user` (`user_id`),
    INDEX `idx_orders_status` (`status`),
    INDEX `idx_orders_created` (`created_at`),
    CONSTRAINT `fk_orders_user_id` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEED DATA: Pre-inserted Users
-- Passwords hashed using standard password_hash(PASSWORD_BCRYPT):
--   - admin123    => $2y$10$OJcWQQSX1rXPVVspK.Mwq.sOv9Ne8qLnNkyB2LDCPHfAgPuTEnMGK
--   - partner123  => $2y$10$srsnJxTz657jzKemDfVvceHuTOm77XiBeZQSgYCcvnK.wYphTRiH.
--   - customer123 => $2y$10$j1HNNyUckXqK6NBhbFhxqu.tYxM6dR0UGra0vYfs70DsNSnzFsGUe
-- ============================================================================
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`) VALUES
(1, 'سعيد العامري / Said Al-Amri', 'admin@baderphones.om', '$2y$10$OJcWQQSX1rXPVVspK.Mwq.sOv9Ne8qLnNkyB2LDCPHfAgPuTEnMGK', 'admin'),
(2, 'سعيد العامري (المطور) / Developer Reference', 'Ssaid9395212@gmail.com', '$2y$10$OJcWQQSX1rXPVVspK.Mwq.sOv9Ne8qLnNkyB2LDCPHfAgPuTEnMGK', 'admin'),
(3, 'سالم الشبيبي (شريك المتجر) / Salim Al-Shabibi', 'partner@baderphones.om', '$2y$10$srsnJxTz657jzKemDfVvceHuTOm77XiBeZQSgYCcvnK.wYphTRiH.', 'partner'),
(4, 'أحمد الحارثي / Ahmed Al-Harthi', 'customer@baderphones.om', '$2y$10$j1HNNyUckXqK6NBhbFhxqu.tYxM6dR0UGra0vYfs70DsNSnzFsGUe', 'customer');

-- ============================================================================
-- SEED DATA: 8 Modern Electronics & Mobile Products (Full Specs & Bilingual)
-- ============================================================================
INSERT INTO `products` (`id`, `name_ar`, `name_en`, `name`, `description_ar`, `description_en`, `description`, `price`, `stock`, `category`, `ram`, `storage`, `battery`, `camera`, `processor`, `graphics_card`, `screen_size`, `colors`, `image1`, `image2`, `image3`, `image4`, `image`) VALUES
(1, 'آيفون 18 برو ماكس', 'iPhone 18 Pro Max', 'آيفون 18 برو ماكس (iPhone 18 Pro Max)', 'هاتف آبل الرائد بهيكل تيتانيوم مصقول، كاميرات احترافية، وشاشة خارقة تدعم 120Hz ProMotion مع أطول عمر بطارية في فئته.', 'Apple flagship smartphone featuring aerospace-grade titanium, pro triple camera system, and ultra-smooth 120Hz ProMotion display.', 'رام 16GB | سعة 512GB تيتانيوم | معالج A19 Pro Bionic | شاشة 6.9 بوصة ProMotion 120Hz', 549.000, 25, 'phones', '16GB', '512GB', '4850mAh', '48MP + 48MP + 48MP LiDAR', 'A19 Pro Bionic 3nm', 'Apple 6-Core GPU (Hardware Ray Tracing)', '6.9 بوصة OLED / 6.9" OLED', '[{"hex":"#1c1c1e","name_ar":"تيتانيوم أسود","name_en":"Black Titanium"},{"hex":"#c2bcad","name_ar":"تيتانيوم صحراوي","name_en":"Desert Titanium"},{"hex":"#9a958d","name_ar":"تيتانيوم طبيعي","name_en":"Natural Titanium"},{"hex":"#f2f2f2","name_ar":"تيتانيوم أبيض","name_en":"White Titanium"}]', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png'),
(2, 'سامسونج جالكسي S26 ألترا', 'Samsung Galaxy S26 Ultra', 'سامسونج جالكسي S26 ألترا (Galaxy S26 Ultra)', 'أقوى هواتف الأندرويد مع الذكاء الاصطناعي Galaxy AI المتقدم، قلم S-Pen مدمج، وكاميرا جبارة بدقة 200 ميجابكسل وزوم بصري 100x.', 'Ultimate Android flagship powered by Galaxy AI, integrated S-Pen stylus, 200MP quad camera setup, and 100x Space Zoom.', 'رام 24GB | سعة 1TB تيتانيوم | معالج Snapdragon 8 Gen 5 AI | قلم S-Pen مدمج | كاميرا 200MP', 495.000, 20, 'phones', '24GB', '1TB', '5500mAh', '200MP + 50MP + 50MP + 12MP', 'Snapdragon 8 Gen 5 AI', 'Adreno 840 Next-Gen GPU', '6.8 بوصة Dynamic AMOLED 2X', '[{"hex":"#1f2421","name_ar":"تيتانيوم أسود","name_en":"Titanium Black"},{"hex":"#505f56","name_ar":"تيتانيوم رمادي","name_en":"Titanium Gray"},{"hex":"#b89f72","name_ar":"تيتانيوم أصفر","name_en":"Titanium Yellow"},{"hex":"#534d66","name_ar":"تيتانيوم بنفسجي","name_en":"Titanium Violet"}]', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png'),
(3, 'شاومي ريدمي نوت 17 برو بلس', 'Xiaomi Redmi Note 17 Pro+', 'شاومي ريدمي نوت 17 برو بلس (Redmi Note 17 Pro+)', 'بطل الفئة المتوسطة العليا مع شحن فائق السرعة 210 واط يشحن الهاتف كاملاً في 9 دقائق فقط مع شاشة منحنية 1.5K فائقة السطوع.', 'Midrange flagship killer with blazing-fast 210W HyperCharge (0-100% in 9 mins) and crystal-clear 1.5K 120Hz curved display.', 'رام 16GB | سعة 512GB | كاميرا 200MP OIS | شحن خارق 210W HyperCharge', 169.000, 35, 'phones', '16GB', '512GB', '5100mAh', '200MP OIS + 8MP + 2MP', 'Dimensity 9300+ Ultra', 'Immortalis-G720 MC12', '6.67 بوصة AMOLED 120Hz', '[{"hex":"#111111","name_ar":"أسود منتصف الليل","name_en":"Midnight Black"},{"hex":"#d5e4ec","name_ar":"أبيض جليدي","name_en":"Glacier White"},{"hex":"#748b97","name_ar":"أزرق محيطي","name_en":"Ocean Blue"}]', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png'),
(4, 'ماك بوك برو 16 بوصة (M4 Max)', 'MacBook Pro 16" (M4 Max)', 'ماك بوك برو 16 بوصة (M4 Max)', 'محطة العمل المحمولة الأقوى للمطورين وصناع المحتوى، معالجة فيديو 8K في ثوانٍ مع بطارية أسطورية تدوم حتى 22 ساعة.', 'Ultimate workstation laptop for creators and engineers, powered by M4 Max chip with up to 22 hours of all-day battery life.', 'ذاكرة موحدة 36GB | قرص 1TB SSD | شاشة Liquid Retina XDR | بطارية تدوم حتى 22 ساعة', 980.000, 12, 'laptops', '36GB Unified Memory', '1TB SSD', '100Wh (22h Battery)', '1080p FaceTime HD AI', 'Apple M4 Max 16-Core', 'Apple M4 Max 40-Core GPU (Ray Tracing)', '16.2 بوصة Liquid Retina XDR', '[{"hex":"#2e3033","name_ar":"أسود فلكي","name_en":"Space Black"},{"hex":"#d8d9dd","name_ar":"فضي كلاسيكي","name_en":"Silver"}]', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png'),
(5, 'ديل XPS 16 شاشة لمس 4K OLED', 'Dell XPS 16 Touch 4K OLED', 'ديل XPS 16 شاشة لمس 4K OLED', 'تحفة ويندوز بتصميم معدني فائق النحافة، شاشة لمس 4K OLED غامرة، وكرت شاشة RTX 4070 المخصص للألعاب والتصميم الاحترافي.', 'Premium Windows powerhouse with infinity-edge 4K OLED touch panel, RTX 4070 GPU, and aerospace-grade aluminum chassis.', 'رام 32GB DDR5 | قرص 1TB NVMe SSD | كرت شاشة RTX 4070 8GB | معالج Intel Core Ultra 9', 840.000, 10, 'laptops', '32GB LPDDR5X', '1TB NVMe Gen4 SSD', '99.5Wh Fast-Charge', 'FHD Windows Hello IR', 'Intel Core Ultra 9 185H', 'NVIDIA GeForce RTX 4070 8GB GDDR6', '16.3 بوصة 4K OLED Touch', '[{"hex":"#2a2b2e","name_ar":"رمادي جرافيت","name_en":"Graphite"},{"hex":"#e2e4e6","name_ar":"بلاتينيوم أبيض","name_en":"Platinum"}]', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png'),
(6, 'بنك طاقة أنكر برايم 27,650mAh بقدرة 250W', 'Anker Prime Power Bank 27,650mAh (250W)', 'بنك طاقة أنكر برايم 27,650mAh بقدرة 250W', 'شاحن متنقل فائق القوة بقدرة إجمالية 250 واط مزود بشاشة رقمية تفاعلية وتطبيق بلوتوث ذكي لمراقبة سرعة وحالة الشحن.', 'High-capacity 250W multi-device fast power bank featuring smart digital display and Bluetooth app tracking.', 'شحن فائق 250 واط | شاشة رقمية ذكية | 3 منافذ شحن سريعة للابتوب والهواتف معاً', 48.000, 50, 'accessories', '-', '-', '27,650mAh (99.54Wh)', '-', 'ActiveShield 2.0 Smart IC', NULL, 'Smart Digital Display', '[{"hex":"#1a1d20","name_ar":"رمادي فضائي داكن","name_en":"Dark Space Gray"}]', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png'),
(7, 'كاميرا سوني الفا 7 IV فل فريم + عدسة 28-70mm', 'Sony Alpha 7 IV Full-Frame Camera + 28-70mm', 'كاميرا سوني الفا 7 IV فل فريم + عدسة 28-70mm', 'كاميرا فل فريم هجينة للمحترفين تجمع بين تصوير فوتوغرافي فائق الدقة 33MP وتسجيل سينمائي 4K 60p 10-Bit 4:2:2 مع تتبع ذكي بالذكاء الاصطناعي.', 'Professional hybrid full-frame camera with 33MP Exmor R sensor, 4K 60p 10-Bit video, and AI Real-Time Eye Autofocus.', 'مستشعر 33MP Exmor R | تصوير فيديو 4K 60p 10-Bit | تركيز بؤري ذكي بالذكاء الاصطناعي Real-Time Eye AF', 865.000, 8, 'photography', '-', 'Dual Slot (CFexpress A / SD)', 'NP-FZ100 (600+ Shots)', '33MP Full-Frame Sensor', 'BIONZ XR Dual Engine', NULL, '3.0 بوصة شاشة لمس متحركة', '[{"hex":"#111213","name_ar":"أسود احترافي غير لامع","name_en":"Matte Pro Black"}]', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png'),
(8, 'كاميرا دي جيه آي أوزمو بوكيت 3 كومبو للمبدعين', 'DJI Osmo Pocket 3 Creator Combo', 'كاميرا دي جيه آي أوزمو بوكيت 3 كومبو للمبدعين', 'كاميرا جيب سينمائية بمستشعر كبير 1 بوصة، مانع اهتزاز ميكانيكي 3 محاور، شاشة لمس قابلة للدوران 2 بوصة، ومايك لاسلكي احترافي.', 'Pocket gimbal camera with 1-inch CMOS sensor, 4K 120fps slow-motion, 2-inch rotatable OLED screen, and wireless DJI Mic.', 'مستشعر 1 بوصة CMOS | فيديو 4K 120fps | مانع اهتزاز ميكانيكي 3 محاور + مايك لاسلكي DJI Mic', 245.000, 15, 'photography', '-', 'MicroSD up to 512GB', '1300mAh (166 mins)', '1-inch CMOS 4K/120fps', 'ActiveTrack 6.0 AI', NULL, '2.0 بوصة OLED Rotatable', '[{"hex":"#1a1a1a","name_ar":"أسود كلاسيكي","name_en":"Classic Black"}]', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png'),
(9, 'سامسونج جالكسي S22 5G', 'Samsung Galaxy S22 5G', 'سامسونج جالكسي S22 5G', 'هاتف رائد بتصميم عصري وكاميرا احترافية بدقة 50 ميجابكسل وشاشة ديناميكية سريعة الاستجابة.', 'Compact flagship smartphone featuring 50MP triple camera system and vivid 120Hz Dynamic AMOLED display.', 'رام 8GB | سعة 128GB | كاميرا 50MP ثلاثية | معالج Snapdragon 8 Gen 1', 175.000, 20, 'phones', '8GB', '128GB', '3700mAh Fast Charging', '50MP + 10MP Telephoto + 12MP Ultra-Wide', 'Snapdragon 8 Gen 1 (4nm)', 'Adreno 730 GPU', '6.1" Dynamic AMOLED 2X 120Hz', '[{"hex":"#000000","name_ar":"أسود فانتوم","name_en":"Phantom Black"},{"hex":"#f5f5f7","name_ar":"أبيض ناصع","name_en":"Phantom White"},{"hex":"#385a4f","name_ar":"أخضر زمردي","name_en":"Green"},{"hex":"#be9182","name_ar":"ذهبي وردي","name_en":"Pink Gold"}]', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png', 'assets/logo.png');

-- ============================================================================
-- SEED DATA: Sample Orders
-- ============================================================================
INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `order_items`, `status`) VALUES
(1, 4, 549.000, '[{"id": 1, "name": "آيفون 18 برو ماكس (iPhone 18 Pro Max)", "price": 549.000, "quantity": 1}]', 'processing'),
(2, 4, 96.000, '[{"id": 6, "name": "بنك طاقة أنكر برايم 27,650mAh بقدرة 250W", "price": 48.000, "quantity": 2}]', 'completed');
