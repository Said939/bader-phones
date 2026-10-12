<?php
// ============================================================================
// بدر للهواتف / Bader Phones - Database Configuration & PDO Architecture
// Database: bader_phones_db
// Architecture: 3-Tier RBAC (admin, partner, customer), Products, Orders, Settings
// ============================================================================

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bader_phones_db');

/**
 * Returns a configured PDO database connection instance.
 * Automatically initializes database and tables if they do not exist.
 */
function getDB(): ?PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsnNoDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    try {
        // Step 1: Connect to MySQL server
        $conn = new PDO($dsnNoDb, DB_USER, DB_PASS, $options);
        
        // Step 2: Ensure primary database exists
        $conn->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Step 3: Connect to target database
        $dsnWithDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsnWithDb, DB_USER, DB_PASS, $options);

        // Step 4: Ensure Tables and Initial Seed exist
        initializeTablesAndSeed($pdo);

        return $pdo;
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        
        // Secondary attempt to connect to legacy database if available
        try {
            $legacyDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=bader_phones;charset=utf8mb4";
            $pdo = new PDO($legacyDsn, DB_USER, DB_PASS, $options);
            return $pdo;
        } catch (PDOException $e2) {
            error_log("Legacy Database Fallback Failed: " . $e2->getMessage());
            return null;
        }
    }
}

/**
 * Ensures schema tables and initial seed data are populated.
 */
function initializeTablesAndSeed(PDO $pdo): void {
    // 1. Users Table (3-Tier RBAC)
    $pdo->exec("
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
            INDEX `idx_users_role` (`role`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Products Table
    $pdo->exec("
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
            INDEX `idx_products_category` (`category`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Orders Table
    $pdo->exec("
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
            INDEX `idx_orders_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 4. Order Items Table
    $pdo->exec("
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
    ");

    // 5. System Settings Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `system_settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(100) NOT NULL UNIQUE,
            `setting_value` TEXT DEFAULT NULL,
            `description` VARCHAR(255) DEFAULT NULL,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Seed 3-Tier RBAC Default Accounts if missing
    $checkUsers = $pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
    if ($checkUsers == 0) {
        $stmtUsers = $pdo->prepare("
            INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `status`)
            VALUES (:name, :email, :password, :phone, :role, 'active')
        ");

        $seedUsers = [
            [
                'name'     => 'سعيد العامري / Said Al-Amri',
                'email'    => 'admin@baderphones.om',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'phone'    => '+96897447122',
                'role'     => 'admin'
            ],
            [
                'name'     => 'سعيد العامري / Developer Reference',
                'email'    => 'Ssaid9395212@gmail.com',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'phone'    => '+96893952123',
                'role'     => 'admin'
            ],
            [
                'name'     => 'سالم الشبيبي (شريك المتجر) / Salim Al-Shabibi',
                'email'    => 'partner@baderphones.om',
                'password' => password_hash('partner123', PASSWORD_DEFAULT),
                'phone'    => '+96893952123',
                'role'     => 'partner'
            ],
            [
                'name'     => 'أحمد الحارثي / Ahmed Al-Harthi',
                'email'    => 'customer@baderphones.om',
                'password' => password_hash('customer123', PASSWORD_DEFAULT),
                'phone'    => '+96891234567',
                'role'     => 'customer'
            ],
            [
                'name'     => 'حساب تجريبي / Demo User',
                'email'    => 'demo@baderphones.om',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'phone'    => '+96897447122',
                'role'     => 'customer'
            ]
        ];

        foreach ($seedUsers as $u) {
            $stmtUsers->execute($u);
        }
    }

    // Seed Products Catalog if empty
    $checkProd = $pdo->query("SELECT COUNT(*) FROM `products`")->fetchColumn();
    if ($checkProd == 0) {
        $initialProducts = getFallbackProductsList();
        $stmtProd = $pdo->prepare("
            INSERT INTO `products` 
            (`slug`, `category`, `name_ar`, `name_en`, `specs_ar`, `specs_en`, `price`, `stock_quantity`, `badge_ar`, `badge_en`, `badge_type`, `rating`, `reviews`, `image_type`)
            VALUES 
            (:slug, :category, :name_ar, :name_en, :specs_ar, :specs_en, :price, 25, :badge_ar, :badge_en, :badge_type, :rating, :reviews, :image_type)
        ");

        foreach ($initialProducts as $item) {
            $stmtProd->execute($item);
        }
    }

    // Seed System Settings if empty
    $checkSettings = $pdo->query("SELECT COUNT(*) FROM `system_settings`")->fetchColumn();
    if ($checkSettings == 0) {
        $settings = [
            ['store_name_ar', 'بدر للهواتف', 'Official Arabic Store Name'],
            ['store_name_en', 'Bader Phones', 'Official English Store Name'],
            ['whatsapp_number', '96897447122', 'Primary WhatsApp Direct Order Line'],
            ['partner_whatsapp', '96893952123', 'Partner & Supplier WhatsApp Dispatch Line'],
            ['currency', 'OMR', 'Store Currency'],
            ['maintenance_mode', '0', 'Store Maintenance Mode (0=Active, 1=Maintenance)']
        ];

        $stmtSet = $pdo->prepare("INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES (?, ?, ?)");
        foreach ($settings as $s) {
            $stmtSet->execute($s);
        }
    }
}

/**
 * Fetch all catalog products from MySQL or fallback array
 */
function fetchCatalogProducts(?string $category = null): array {
    $pdo = getDB();
    if ($pdo !== null) {
        try {
            if ($category && $category !== 'all') {
                $stmt = $pdo->prepare("SELECT * FROM `products` WHERE `in_stock` = 1 AND `category` = :cat ORDER BY `id` ASC");
                $stmt->execute(['cat' => $category]);
            } else {
                $stmt = $pdo->query("SELECT * FROM `products` WHERE `in_stock` = 1 ORDER BY `id` ASC");
            }
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                return $rows;
            }
        } catch (PDOException $e) {
            error_log("Query Catalog Error: " . $e->getMessage());
        }
    }

    $all = getFallbackProductsList();
    if ($category && $category !== 'all') {
        return array_values(array_filter($all, fn($p) => $p['category'] === $category));
    }
    return $all;
}

/**
 * Retrieve product by slug
 */
function findProductBySlug(string $slug): ?array {
    $pdo = getDB();
    if ($pdo !== null) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM `products` WHERE `slug` = :slug LIMIT 1");
            $stmt->execute(['slug' => $slug]);
            $p = $stmt->fetch();
            if ($p) return $p;
        } catch (PDOException $e) {
            error_log("findProductBySlug Error: " . $e->getMessage());
        }
    }

    foreach (getFallbackProductsList() as $p) {
        if ($p['slug'] === $slug) return $p;
    }
    return null;
}

/**
 * Save an e-commerce order into database linked to customer
 */
function saveOrder(array $orderData, array $items = []): ?int {
    $pdo = getDB();
    if (!$pdo) return null;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO `orders` 
            (`order_code`, `user_id`, `customer_name`, `customer_phone`, `customer_email`, `items_json`, `total_amount`, `payment_method`, `status`, `notes`)
            VALUES
            (:order_code, :user_id, :customer_name, :customer_phone, :customer_email, :items_json, :total_amount, :payment_method, :status, :notes)
        ");

        $stmt->execute([
            'order_code'     => $orderData['order_code'] ?? ('ORD-' . date('Ymd') . '-' . rand(1000, 9999)),
            'user_id'        => $orderData['user_id'] ?? null,
            'customer_name'  => $orderData['customer_name'] ?? 'Guest Customer',
            'customer_phone' => $orderData['customer_phone'] ?? '',
            'customer_email' => $orderData['customer_email'] ?? null,
            'items_json'     => is_array($orderData['items_json']) ? json_encode($orderData['items_json'], JSON_UNESCAPED_UNICODE) : $orderData['items_json'],
            'total_amount'   => (float)($orderData['total_amount'] ?? 0),
            'payment_method' => $orderData['payment_method'] ?? 'whatsapp_cod',
            'status'         => $orderData['status'] ?? 'pending',
            'notes'          => $orderData['notes'] ?? null
        ]);

        $orderId = (int)$pdo->lastInsertId();

        // Populate order items if provided
        if (!empty($items) && is_array($items)) {
            $stmtItem = $pdo->prepare("
                INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `product_slug`, `unit_price`, `quantity`, `subtotal`)
                VALUES (:order_id, :product_id, :product_name, :product_slug, :unit_price, :quantity, :subtotal)
            ");

            foreach ($items as $it) {
                $stmtItem->execute([
                    'order_id'     => $orderId,
                    'product_id'   => $it['product_id'] ?? null,
                    'product_name' => $it['name'] ?? 'Product',
                    'product_slug' => $it['slug'] ?? 'prod',
                    'unit_price'   => (float)($it['price'] ?? 0),
                    'quantity'     => (int)($it['qty'] ?? 1),
                    'subtotal'     => (float)(($it['price'] ?? 0) * ($it['qty'] ?? 1))
                ]);
            }
        }

        $pdo->commit();
        return $orderId;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Save Order Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Fetch orders for a specific user (Customer order history)
 */
function getUserOrders(int $userId): array {
    $pdo = getDB();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->prepare("SELECT * FROM `orders` WHERE `user_id` = :uid ORDER BY `id` DESC");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("getUserOrders Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Fetch all orders (Admin / Partner dashboard)
 */
function getAllOrders(int $limit = 50): array {
    $pdo = getDB();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->prepare("SELECT * FROM `orders` ORDER BY `id` DESC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("getAllOrders Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Update order status (Admin / Partner)
 */
function updateOrderStatus(int $orderId, string $status): bool {
    $pdo = getDB();
    if (!$pdo) return false;

    $allowed = ['pending', 'confirmed', 'processing', 'completed', 'cancelled'];
    if (!in_array($status, $allowed)) return false;

    try {
        $stmt = $pdo->prepare("UPDATE `orders` SET `status` = :st WHERE `id` = :id");
        return $stmt->execute(['st' => $status, 'id' => $orderId]);
    } catch (PDOException $e) {
        error_log("updateOrderStatus Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetch all users (Admin only)
 */
function getAllUsers(): array {
    $pdo = getDB();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->query("SELECT `id`, `name`, `email`, `phone`, `role`, `status`, `created_at` FROM `users` ORDER BY `id` ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("getAllUsers Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Update a user's role (Admin only)
 */
function updateUserRole(int $userId, string $newRole): bool {
    $pdo = getDB();
    if (!$pdo) return false;

    $allowedRoles = ['admin', 'partner', 'customer'];
    if (!in_array($newRole, $allowedRoles)) return false;

    try {
        $stmt = $pdo->prepare("UPDATE `users` SET `role` = :role WHERE `id` = :id");
        return $stmt->execute(['role' => $newRole, 'id' => $userId]);
    } catch (PDOException $e) {
        error_log("updateUserRole Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Retrieve system setting value
 */
function getSystemSetting(string $key, ?string $default = null): ?string {
    $pdo = getDB();
    if (!$pdo) return $default;

    try {
        $stmt = $pdo->prepare("SELECT `setting_value` FROM `system_settings` WHERE `setting_key` = :k LIMIT 1");
        $stmt->execute(['k' => $key]);
        $val = $stmt->fetchColumn();
        return ($val !== false) ? (string)$val : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Default fallback products array
 */
function getFallbackProductsList(): array {
    return [
        [
            'id'          => 1,
            'slug'        => 'iphone-18-pro-max',
            'category'    => 'smartphones',
            'name_ar'     => 'آيفون 18 برو ماكس (iPhone 18 Pro Max)',
            'name_en'     => 'Apple iPhone 18 Pro Max',
            'specs_ar'    => 'رام 16GB | سعة 512GB تيتانيوم | معالج A19 Pro Bionic | شاشة 120Hz',
            'specs_en'    => '16GB RAM | 512GB Natural Titanium | A19 Pro Chip | 6.9" ProMotion',
            'price'       => 549.000,
            'badge_ar'    => 'الأعلى طلباً ⭐',
            'badge_en'    => 'Top Flagship ⭐',
            'badge_type'  => 'primary',
            'rating'      => 4.9,
            'reviews'     => 142,
            'image_type'  => 'iphone'
        ],
        [
            'id'          => 2,
            'slug'        => 'samsung-s26-ultra',
            'category'    => 'smartphones',
            'name_ar'     => 'سامسونج جالكسي S26 ألترا (Galaxy S26 Ultra)',
            'name_en'     => 'Samsung Galaxy S26 Ultra',
            'specs_ar'    => 'رام 24GB | سعة 1TB | معالج Snapdragon 8 Gen 5 AI | قلم S-Pen مدمج',
            'specs_en'    => '24GB RAM | 1TB Titanium Black | Snapdragon 8 Gen 5 AI | S-Pen Included',
            'price'       => 495.000,
            'badge_ar'    => 'ذكاء اصطناعي فائق 🤖',
            'badge_en'    => 'Galaxy AI 2.0 🤖',
            'badge_type'  => 'accent',
            'rating'      => 4.9,
            'reviews'     => 98,
            'image_type'  => 'samsung'
        ],
        [
            'id'          => 3,
            'slug'        => 'redmi-note-17-pro-plus',
            'category'    => 'smartphones',
            'name_ar'     => 'شاومي ريدمي نوت 17 برو بلس (Redmi Note 17 Pro+)',
            'name_en'     => 'Xiaomi Redmi Note 17 Pro+',
            'specs_ar'    => 'رام 16GB | سعة 512GB | كاميرا 200MP | شحن خارق 210W HyperCharge',
            'specs_en'    => '16GB RAM | 512GB Storage | 200MP OIS Camera | 210W HyperCharge',
            'price'       => 169.000,
            'badge_ar'    => 'أفضل قيمة مقابل سعر ⚡',
            'badge_en'    => 'Best Value Deal ⚡',
            'badge_type'  => 'success',
            'rating'      => 4.8,
            'reviews'     => 215,
            'image_type'  => 'xiaomi'
        ],
        [
            'id'          => 4,
            'slug'        => 'macbook-pro-m4',
            'category'    => 'laptops',
            'name_ar'     => 'ماك بوك برو 16 بوصة (M4 Max)',
            'name_en'     => 'Apple MacBook Pro 16" (M4 Max)',
            'specs_ar'    => 'ذاكرة موحدة 36GB | قرص 1TB SSD | شاشة Liquid Retina XDR | بطارية 22 ساعة',
            'specs_en'    => '36GB Unified Memory | 1TB SSD | Liquid Retina XDR Display | 22h Battery',
            'price'       => 980.000,
            'badge_ar'    => 'قوة احترافية للمصممين 💻',
            'badge_en'    => 'Pro Powerhouse 💻',
            'badge_type'  => 'primary',
            'rating'      => 5.0,
            'reviews'     => 64,
            'image_type'  => 'macbook'
        ],
        [
            'id'          => 5,
            'slug'        => 'dell-xps-16-oled',
            'category'    => 'laptops',
            'name_ar'     => 'ديل XPS 16 شاشة لمس 4K OLED',
            'name_en'     => 'Dell XPS 16 OLED Touch Ultra',
            'specs_ar'    => 'رام 32GB DDR5 | قرص 1TB NVMe | كرت شاشة RTX 4070 8GB | Core Ultra 9',
            'specs_en'    => '32GB DDR5 RAM | 1TB NVMe SSD | RTX 4070 8GB | Intel Core Ultra 9',
            'price'       => 840.000,
            'badge_ar'    => 'صناع المحتوى والألعاب 🎮',
            'badge_en'    => 'Creator & Gaming 🎮',
            'badge_type'  => 'accent',
            'rating'      => 4.8,
            'reviews'     => 42,
            'image_type'  => 'dell'
        ],
        [
            'id'          => 6,
            'slug'        => 'anker-prime-250w',
            'category'    => 'accessories',
            'name_ar'     => 'بنك طاقة أنكر برايم 27,650mAh بقدرة 250W',
            'name_en'     => 'Anker Prime 27,650mAh (250W) PowerBank',
            'specs_ar'    => 'شحن فائق 250 واط | شاشة رقمية ذكية | 3 منافذ شحن للابتوب والهواتف معاً',
            'specs_en'    => '250W Total Output | Smart Digital App Screen | Multi-Device Fast Charge',
            'price'       => 48.000,
            'badge_ar'    => 'ضروري للسفر والعمل 🔋',
            'badge_en'    => 'Travel Essential 🔋',
            'badge_type'  => 'warning',
            'rating'      => 4.9,
            'reviews'     => 318,
            'image_type'  => 'anker'
        ],
        [
            'id'          => 7,
            'slug'        => 'sony-a7m4-kit',
            'category'    => 'photography',
            'name_ar'     => 'كاميرا سوني الفا 7 IV فل فريم + عدسة 28-70mm',
            'name_en'     => 'Sony Alpha 7 IV Full-Frame Kit',
            'specs_ar'    => 'مستشعر 33MP Exmor R | تصوير فيديو 4K 60p 10-Bit | تركيز بؤري ذكي بالذكاء الاصطناعي',
            'specs_en'    => '33MP BSI CMOS Sensor | 4K 60p 10-Bit Video | AI Real-Time Eye AF',
            'price'       => 865.000,
            'badge_ar'    => 'الخيار السينمائي المفضل 🎥',
            'badge_en'    => 'Cinema Grade 🎥',
            'badge_type'  => 'primary',
            'rating'      => 4.9,
            'reviews'     => 56,
            'image_type'  => 'sony'
        ],
        [
            'id'          => 8,
            'slug'        => 'dji-osmo-pocket-3',
            'category'    => 'photography',
            'name_ar'     => 'كاميرا دي جيه آي أوزمو بوكيت 3 كومبو للمبدعين',
            'name_en'     => 'DJI Osmo Pocket 3 Creator Combo',
            'specs_ar'    => 'مستشعر 1 بوصة CMOS | فيديو 4K 120fps | مانع اهتزاز ميكانيكي 3 محاور + مايك لاسلكي',
            'specs_en'    => '1-inch CMOS Sensor | 4K 120fps | 3-Axis Gimbal + Wireless Mic Transmitter',
            'price'       => 245.000,
            'badge_ar'    => 'الأفضل للفلوج وصناع المحتوى 🎙️',
            'badge_en'    => 'Vlogger Champion 🎙️',
            'badge_type'  => 'accent',
            'rating'      => 4.9,
            'reviews'     => 192,
            'image_type'  => 'dji'
        ]
    ];
}
