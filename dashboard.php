<?php
// ============================================================================
// بدر للهواتف / Bader Phones - Management & Operations Portal (dashboard.php)
// Features: 3-Tier RBAC Access Control (Admin & Partner), Multi-Language (AR/EN),
// Products & Stock Management, Order Processing & WhatsApp Sync, User Roles (Admin Only),
// Store Settings (Admin Only), Responsive White & Vibrant Orange Tech UI
// ============================================================================

require_once __DIR__ . '/includes/auth.php';

// Strict RBAC Guard: Only Tier 1 (Admin) and Tier 2 (Partner) are permitted
requireRole([ROLE_ADMIN, ROLE_PARTNER], 'index.php');

$currentUser = getAuthUser();
$isAdmin = isAdmin();
$isPartner = isPartner();
$currentRole = getUserRole();

// Base URI calculation
$baseUri = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
if ($baseUri === '/' || empty($baseUri)) {
    $baseUri = '/elite-store/';
}

// Language Handling
$allowedLangs = ['ar', 'en'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowedLangs)) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, time() + (86400 * 30), '/');
} elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $allowedLangs)) {
    $lang = $_SESSION['lang'];
} elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $allowedLangs)) {
    $lang = $_COOKIE['lang'];
} else {
    $lang = 'ar';
}

$isRtl = ($lang === 'ar');
$dir = $isRtl ? 'rtl' : 'ltr';
$otherLang = ($lang === 'ar') ? 'en' : 'ar';
$otherLangName = ($lang === 'ar') ? 'English' : 'العربية';
$otherLangFlag = ($lang === 'ar') ? '🇬🇧' : '🇴🇲';

// Active Tab Handling
$tab = $_GET['tab'] ?? 'overview';
$allowedTabs = ['overview', 'products', 'orders'];
if ($isAdmin) {
    $allowedTabs[] = 'users';
    $allowedTabs[] = 'settings';
}

if (!in_array($tab, $allowedTabs)) {
    $tab = 'overview';
}

$pdo = getDB();
$flashSuccess = $_SESSION['dash_success'] ?? null;
$flashError = $_SESSION['dash_error'] ?? null;
unset($_SESSION['dash_success'], $_SESSION['dash_error']);

// ----------------------------------------------------------------------------
// POST Actions Handler (Stock, Orders, Users, Settings)
// ----------------------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';

    // Action 1: Update Product Stock & Price (Admin & Partner)
    if ($action === 'update_product' && canManageProducts()) {
        $prodId = (int)($_POST['product_id'] ?? 0);
        $stock = max(0, (int)($_POST['stock_quantity'] ?? 0));
        $price = max(0.0, (float)($_POST['price'] ?? 0));

        if ($prodId > 0) {
            $stmt = $pdo->prepare("UPDATE `products` SET `stock_quantity` = :stock, `price` = :price WHERE `id` = :id");
            $stmt->execute(['stock' => $stock, 'price' => $price, 'id' => $prodId]);
            $_SESSION['dash_success'] = ($lang === 'ar') ? "تم تحديث بيانات المنتج ومخزونه بنجاح!" : "Product stock and price updated successfully!";
        }
        header("Location: dashboard.php?tab=products");
        exit;
    }

    // Action 2: Update Order Status (Admin & Partner)
    if ($action === 'update_order_status' && canManageOrders()) {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $allowedStatuses = ['pending', 'confirmed', 'processing', 'completed', 'cancelled'];

        if ($orderId > 0 && in_array($newStatus, $allowedStatuses)) {
            updateOrderStatus($orderId, $newStatus);
            $_SESSION['dash_success'] = ($lang === 'ar') ? "تم تحديث حالة الطلب #{$orderId} إلى '{$newStatus}'!" : "Order #{$orderId} status changed to '{$newStatus}'!";
        }
        header("Location: dashboard.php?tab=orders");
        exit;
    }

    // Action 3: Update User Role (Admin Only!)
    if ($action === 'update_user_role' && canManageUsers()) {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $targetRole = trim($_POST['role'] ?? '');
        $allowedRoles = [ROLE_ADMIN, ROLE_PARTNER, ROLE_CUSTOMER];

        // Safeguard root admin account from accidental self-lockout
        if ($targetUserId === (int)$currentUser['id'] && $targetRole !== ROLE_ADMIN) {
            $_SESSION['dash_error'] = ($lang === 'ar') ? "لا يمكنك تغيير أو إلغاء صلاحية حسابك الخاص!" : "You cannot change or revoke your own root admin role!";
        } elseif ($targetUserId > 0 && in_array($targetRole, $allowedRoles)) {
            updateUserRole($targetUserId, $targetRole);
            $_SESSION['dash_success'] = ($lang === 'ar') ? "تم تحديث صلاحية ورتبة المستخدم بنجاح!" : "User role and access tier updated successfully!";
        }
        header("Location: dashboard.php?tab=users");
        exit;
    }

    // Action 4: Update System Settings (Admin Only!)
    if ($action === 'update_settings' && canManageSettings()) {
        $settingsToSave = [
            'store_name_ar'   => trim($_POST['store_name_ar'] ?? 'بدر للهواتف'),
            'store_name_en'   => trim($_POST['store_name_en'] ?? 'Bader Phones'),
            'whatsapp_number' => preg_replace('/\D/', '', $_POST['whatsapp_number'] ?? '96897447122'),
            'partner_whatsapp'=> preg_replace('/\D/', '', $_POST['partner_whatsapp'] ?? '96893952123'),
            'currency'        => trim($_POST['currency'] ?? 'OMR')
        ];

        $stmtSet = $pdo->prepare("
            INSERT INTO `system_settings` (`setting_key`, `setting_value`) 
            VALUES (:k, :v) 
            ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)
        ");

        foreach ($settingsToSave as $k => $v) {
            $stmtSet->execute(['k' => $k, 'v' => $v]);
        }

        $_SESSION['dash_success'] = ($lang === 'ar') ? "تم حفظ إعدادات المتجر العامة بنجاح!" : "Store settings saved successfully!";
        header("Location: dashboard.php?tab=settings");
        exit;
    }
}

// ----------------------------------------------------------------------------
// Fetch Metrics & KPI Stats
// ----------------------------------------------------------------------------
$totalProducts = 0;
$lowStockCount = 0;
$totalOrders = 0;
$totalRevenue = 0.0;
$totalUsers = 0;

if ($pdo) {
    try {
        $totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM `products`")->fetchColumn();
        $lowStockCount = (int)$pdo->query("SELECT COUNT(*) FROM `products` WHERE `stock_quantity` <= 5")->fetchColumn();
        $totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM `orders`")->fetchColumn();
        $totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(`total_price`), 0) FROM `orders` WHERE `status` != 'cancelled'")->fetchColumn();
        if ($isAdmin) {
            $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
        }
    } catch (PDOException $e) {
        error_log("Dashboard KPI Error: " . $e->getMessage());
    }
}

// Multi-language Strings
$i18n = [
    'ar' => [
        'portal_title'   => 'بوابة الإدارة والعمليات | بدر للهواتف',
        'shop_name'      => 'بدر للهواتف',
        'tagline'        => 'نظام التحكم وإدارة المبيعات المتطور',
        'badge_admin'    => 'مدير النظام (تحكم شامل)',
        'badge_partner'  => 'شريك المتجر (المنتجات والطلبات)',
        'back_to_store'  => 'المتجر الرئيسي',
        'logout'         => 'خروج',
        'tab_overview'   => '📊 نظرة عامة',
        'tab_products'   => '📱 المنتجات والمخزون',
        'tab_orders'     => '📦 معالجة الطلبات',
        'tab_users'      => '👥 المستخدمين والصلاحيات',
        'tab_settings'   => '⚙️ إعدادات المتجر',
        'kpi_prod'       => 'إجمالي المنتجات',
        'kpi_low_stock'  => 'تنبيه نقص المخزون',
        'kpi_orders'     => 'إجمالي الطلبات',
        'kpi_revenue'    => 'إجمالي المبيعات المؤكدة',
        'kpi_users'      => 'المستخدمين المسجلين',
        'welcome_msg'    => 'أهلاً وسهلاً بك،',
        'role_perm_desc' => 'أنت مسجل بصلاحيات المستوى:',
        'quick_save'     => 'حفظ',
        'filter_all'     => 'الكل',
        'in_stock'       => 'متوفر',
        'low_stock'      => 'مخزون حرج',
        'out_stock'      => 'نفد المخزون',
        'chat_customer'  => 'تواصل عبر واتساب',
        'save_changes'   => 'حفظ التغييرات',
        'admin_only_tag' => 'خاص بالمدير فقط'
    ],
    'en' => [
        'portal_title'   => 'Operations & Management Portal | Bader Phones',
        'shop_name'      => 'Bader Phones',
        'tagline'        => 'Advanced Store Management & Operations Portal',
        'badge_admin'    => 'System Administrator (Full Access)',
        'badge_partner'  => 'Store Partner (Inventory & Orders)',
        'back_to_store'  => 'Storefront Catalog',
        'logout'         => 'Logout',
        'tab_overview'   => '📊 Overview',
        'tab_products'   => '📱 Products & Stock',
        'tab_orders'     => '📦 Orders Processing',
        'tab_users'      => '👥 Users & RBAC Roles',
        'tab_settings'   => '⚙️ Store Settings',
        'kpi_prod'       => 'Total Products',
        'kpi_low_stock'  => 'Low Stock Alert',
        'kpi_orders'     => 'Total Orders',
        'kpi_revenue'    => 'Confirmed Revenue',
        'kpi_users'      => 'Registered Users',
        'welcome_msg'    => 'Welcome back,',
        'role_perm_desc' => 'Logged in with access level:',
        'quick_save'     => 'Save',
        'filter_all'     => 'All',
        'in_stock'       => 'In Stock',
        'low_stock'      => 'Low Stock',
        'out_stock'      => 'Out of Stock',
        'chat_customer'  => 'WhatsApp Customer',
        'save_changes'   => 'Save Settings',
        'admin_only_tag' => 'Admin Only'
    ]
];

$t = $i18n[$lang];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>" dir="<?= htmlspecialchars($dir) ?>" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= htmlspecialchars($baseUri) ?>">
    <title><?= htmlspecialchars($t['portal_title']) ?></title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">

    <script>
        const savedTheme = localStorage.getItem('bader_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);

        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme') || 'dark';
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('bader_theme', next);
        }
    </script>
</head>
<body>

    <!-- Header Navigation -->
    <header class="dash-header">
        <div class="dash-header-inner">
            <a href="dashboard.php" class="brand-section">
                <img src="assets/logo.png" alt="Bader Phones Logo" class="brand-logo-img" onerror="this.onerror=null; this.src='assets/logo.svg';">
                <div class="brand-text-col">
                    <div class="brand-title">
                        <span><?= htmlspecialchars($t['shop_name']) ?></span>
                        <span class="brand-portal-badge"><?= $isAdmin ? 'Admin Portal' : 'Partner Portal' ?></span>
                    </div>
                    <span class="brand-subtitle"><?= htmlspecialchars($t['tagline']) ?></span>
                </div>
            </a>

            <div class="header-user-actions">
                <!-- User Status Badge -->
                <div class="user-badge-card">
                    <div class="user-avatar-circle">
                        <?= mb_substr($currentUser['name'] ?? 'U', 0, 1, 'UTF-8') ?>
                    </div>
                    <div class="user-meta">
                        <span class="user-name-label"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></span>
                        <span class="role-tag <?= $currentRole ?>">
                            <?= $isAdmin ? '🛡️ ' . $t['badge_admin'] : '🏪 ' . $t['badge_partner'] ?>
                        </span>
                    </div>
                </div>

                <!-- Language Toggle -->
                <a href="dashboard.php?tab=<?= urlencode($tab) ?>&lang=<?= $otherLang ?>" class="header-btn" title="Switch Language">
                    <?= $otherLangFlag ?> <?= $otherLangName ?>
                </a>

                <!-- Theme Toggle -->
                <button type="button" class="header-btn" onclick="toggleTheme()" title="Toggle Dark/Light Mode">
                    🌓
                </button>

                <!-- Storefront Link -->
                <a href="index.php" class="header-btn" target="_blank" title="View Store">
                    🛍️ <?= htmlspecialchars($t['back_to_store']) ?>
                </a>

                <!-- Logout -->
                <a href="logout.php" class="header-btn danger" title="Sign Out">
                    🚪 <?= htmlspecialchars($t['logout']) ?>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="dash-container">

        <!-- Flash Messages -->
        <?php if (!empty($flashSuccess)): ?>
            <div class="flash-alert success">
                <span>✅ <?= htmlspecialchars($flashSuccess) ?></span>
                <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:inherit; cursor:pointer;">✕</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($flashError)): ?>
            <div class="flash-alert danger">
                <span>⚠️ <?= htmlspecialchars($flashError) ?></span>
                <button type="button" onclick="this.parentElement.remove()" style="background:none; border:none; color:inherit; cursor:pointer;">✕</button>
            </div>
        <?php endif; ?>

        <!-- Role Permissions Banner -->
        <div style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 14px; padding: 0.85rem 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <span style="font-size: 0.88rem; color: var(--text-secondary);"><?= $t['welcome_msg'] ?> <strong><?= htmlspecialchars($currentUser['name']) ?></strong>. <?= $t['role_perm_desc'] ?></span>
                <span class="role-tag <?= $currentRole ?>" style="font-size: 0.85rem; font-weight: 800; margin-inline-start: 0.35rem;">
                    <?= $isAdmin ? 'Tier 1: Administrator (كامل الصلاحيات)' : 'Tier 2: Business Partner (المنتجات والمخزون والطلبات)' ?>
                </span>
            </div>
            <?php if ($isPartner): ?>
                <span style="font-size: 0.78rem; color: #94a3b8; background: rgba(56, 189, 248, 0.1); padding: 0.25rem 0.6rem; border-radius: 6px; border: 1px solid rgba(56, 189, 248, 0.25);">
                    🔒 تم تقييد إعدادات النظام وقائمة المستخدمين للإدارة العليا فقط
                </span>
            <?php endif; ?>
        </div>

        <!-- Dashboard Navigation Tabs -->
        <nav class="dash-nav-bar">
            <a href="dashboard.php?tab=overview" class="nav-tab-btn <?= ($tab === 'overview') ? 'active' : '' ?>">
                <?= $t['tab_overview'] ?>
            </a>
            <a href="dashboard.php?tab=products" class="nav-tab-btn <?= ($tab === 'products') ? 'active' : '' ?>">
                <?= $t['tab_products'] ?>
            </a>
            <a href="dashboard.php?tab=orders" class="nav-tab-btn <?= ($tab === 'orders') ? 'active' : '' ?>">
                <?= $t['tab_orders'] ?>
            </a>
            <?php if ($isAdmin): ?>
                <a href="dashboard.php?tab=users" class="nav-tab-btn <?= ($tab === 'users') ? 'active' : '' ?>">
                    <?= $t['tab_users'] ?>
                </a>
                <a href="dashboard.php?tab=settings" class="nav-tab-btn <?= ($tab === 'settings') ? 'active' : '' ?>">
                    <?= $t['tab_settings'] ?>
                </a>
            <?php else: ?>
                <span class="nav-tab-btn" style="opacity: 0.45; cursor: not-allowed;" title="Admin Exclusive">
                    👥 <?= ($lang === 'ar') ? 'المستخدمين' : 'Users' ?> <span class="badge-restricted">🔒 <?= $t['admin_only_tag'] ?></span>
                </span>
                <span class="nav-tab-btn" style="opacity: 0.45; cursor: not-allowed;" title="Admin Exclusive">
                    ⚙️ <?= ($lang === 'ar') ? 'الإعدادات' : 'Settings' ?> <span class="badge-restricted">🔒 <?= $t['admin_only_tag'] ?></span>
                </span>
            <?php endif; ?>
        </nav>

        <!-- ================================================================== -->
        <!-- TAB 1: OVERVIEW & KPIS                                             -->
        <!-- ================================================================== -->
        <?php if ($tab === 'overview'): ?>
            <div class="kpi-grid">
                <!-- Products Count -->
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-orange">📱</div>
                    <div class="kpi-content">
                        <span class="kpi-title"><?= $t['kpi_prod'] ?></span>
                        <span class="kpi-value"><?= number_format($totalProducts) ?></span>
                        <span class="kpi-meta">هواتف، لابتوبات وإكسسوارات</span>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-amber">⚠️</div>
                    <div class="kpi-content">
                        <span class="kpi-title"><?= $t['kpi_low_stock'] ?></span>
                        <span class="kpi-value"><?= number_format($lowStockCount) ?></span>
                        <span class="kpi-meta"><?= ($lowStockCount > 0) ? 'منتجات بحاجة للتزويد فوراً' : 'المخزون ممتاز' ?></span>
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-blue">📦</div>
                    <div class="kpi-content">
                        <span class="kpi-title"><?= $t['kpi_orders'] ?></span>
                        <span class="kpi-value"><?= number_format($totalOrders) ?></span>
                        <span class="kpi-meta">طلبات واتساب وموقع</span>
                    </div>
                </div>

                <!-- Confirmed Revenue -->
                <div class="kpi-card">
                    <div class="kpi-icon-box kpi-icon-green">💰</div>
                    <div class="kpi-content">
                        <span class="kpi-title"><?= $t['kpi_revenue'] ?></span>
                        <span class="kpi-value"><?= number_format($totalRevenue, 2) ?> <small style="font-size:0.9rem;">ر.ع</small></span>
                        <span class="kpi-meta">الطلبات النشطة والمسلّمة</span>
                    </div>
                </div>

                <!-- Registered Users (Admin only) -->
                <?php if ($isAdmin): ?>
                    <div class="kpi-card">
                        <div class="kpi-icon-box kpi-icon-purple">👥</div>
                        <div class="kpi-content">
                            <span class="kpi-title"><?= $t['kpi_users'] ?></span>
                            <span class="kpi-value"><?= number_format($totalUsers) ?></span>
                            <span class="kpi-meta">إداريين، شركاء وعملاء</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Orders Preview -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div class="card-title-group">
                        <span class="card-title-icon">🕒</span>
                        <div>
                            <h2 class="dash-card-title"><?= ($lang === 'ar') ? 'أحدث الطلبات الواردة' : 'Recent Incoming Orders' ?></h2>
                            <p class="dash-card-subtitle"><?= ($lang === 'ar') ? 'نظرة سريعة على طلبات العملاء عبر واتساب والموقع' : 'Quick view of latest customer WhatsApp orders' ?></p>
                        </div>
                    </div>
                    <a href="dashboard.php?tab=orders" class="btn-action-sm">
                        <?= ($lang === 'ar') ? 'عرض جميع الطلبات ←' : 'View All Orders →' ?>
                    </a>
                </div>

                <?php
                $recentOrders = getAllOrders(5);
                ?>
                <div class="table-responsive">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th># ID</th>
                                <th><?= ($lang === 'ar') ? 'العميل' : 'Customer' ?></th>
                                <th><?= ($lang === 'ar') ? 'رقم الهاتف' : 'Phone' ?></th>
                                <th><?= ($lang === 'ar') ? 'الإجمالي' : 'Total Price' ?></th>
                                <th><?= ($lang === 'ar') ? 'الحالة' : 'Status' ?></th>
                                <th><?= ($lang === 'ar') ? 'التاريخ' : 'Date' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentOrders)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                        <?= ($lang === 'ar') ? 'لا توجد طلبات مسجلة بعد.' : 'No orders recorded yet.' ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentOrders as $ro): ?>
                                    <tr>
                                        <td><strong>#<?= (int)$ro['id'] ?></strong></td>
                                        <td><?= htmlspecialchars($ro['customer_name'] ?? 'Guest') ?></td>
                                        <td>
                                            <a href="https://wa.me/<?= preg_replace('/\D/', '', $ro['customer_phone'] ?? '96897447122') ?>" target="_blank" style="color: #25D366; text-decoration: none; font-weight: 700;">
                                                <?= htmlspecialchars($ro['customer_phone'] ?? '-') ?> 💬
                                            </a>
                                        </td>
                                        <td><strong><?= number_format((float)($ro['total_price'] ?? 0), 2) ?> ر.ع</strong></td>
                                        <td>
                                            <?php
                                            $stClass = 'info';
                                            if ($ro['status'] === 'completed') $stClass = 'success';
                                            elseif ($ro['status'] === 'cancelled') $stClass = 'danger';
                                            elseif ($ro['status'] === 'pending') $stClass = 'warning';
                                            ?>
                                            <span class="status-pill <?= $stClass ?>"><?= htmlspecialchars($ro['status']) ?></span>
                                        </td>
                                        <td style="color: var(--text-muted); font-size: 0.8rem;"><?= htmlspecialchars($ro['created_at'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- ================================================================== -->
        <!-- TAB 2: PRODUCTS & STOCK MANAGEMENT                                 -->
        <!-- ================================================================== -->
        <?php if ($tab === 'products'): ?>
            <?php
            $productsList = fetchCatalogProducts();
            ?>
            <div class="dash-card">
                <div class="dash-card-header">
                    <div class="card-title-group">
                        <span class="card-title-icon">📱</span>
                        <div>
                            <h2 class="dash-card-title"><?= ($lang === 'ar') ? 'إدارة المنتجات وتحديث المخزون والأسعار' : 'Products, Stock & Price Management' ?></h2>
                            <p class="dash-card-subtitle"><?= ($lang === 'ar') ? 'تعديل مباشر وفوري لكميات المخزون وأسعار البيع بالريال العماني' : 'Direct live inventory and pricing updates in OMR' ?></p>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th># ID</th>
                                <th><?= ($lang === 'ar') ? 'المنتج' : 'Product' ?></th>
                                <th><?= ($lang === 'ar') ? 'التصنيف' : 'Category' ?></th>
                                <th><?= ($lang === 'ar') ? 'السعر الحالي (ر.ع)' : 'Price (OMR)' ?></th>
                                <th><?= ($lang === 'ar') ? 'المخزون المتوفر' : 'Stock Qty' ?></th>
                                <th><?= ($lang === 'ar') ? 'حالة التوفر' : 'Stock Status' ?></th>
                                <th><?= ($lang === 'ar') ? 'تحديث فوري' : 'Quick Update' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productsList as $p): ?>
                                <?php
                                $stockQty = (int)($p['stock_quantity'] ?? 25);
                                $statusClass = 'success';
                                $statusLabel = $t['in_stock'];

                                if ($stockQty <= 0) {
                                    $statusClass = 'danger';
                                    $statusLabel = $t['out_stock'];
                                } elseif ($stockQty <= 5) {
                                    $statusClass = 'warning';
                                    $statusLabel = $t['low_stock'];
                                }
                                ?>
                                <tr>
                                    <td><strong>#<?= (int)$p['id'] ?></strong></td>
                                    <td>
                                        <div class="product-cell">
                                            <div class="product-thumb-box">
                                                <?php
                                                $imgType = $p['image_type'] ?? 'iphone';
                                                $emoji = '📱';
                                                if (strpos($imgType, 'mac') !== false || strpos($imgType, 'dell') !== false) $emoji = '💻';
                                                elseif (strpos($imgType, 'anker') !== false) $emoji = '🔋';
                                                elseif (strpos($imgType, 'sony') !== false || strpos($imgType, 'dji') !== false) $emoji = '📷';
                                                echo $emoji;
                                                ?>
                                            </div>
                                            <div class="product-meta-names">
                                                <span class="product-name-primary"><?= htmlspecialchars($p['name_' . $lang] ?? $p['name_ar']) ?></span>
                                                <span class="product-name-secondary"><?= htmlspecialchars($p['specs_' . $lang] ?? $p['specs_ar']) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.78rem; text-transform: capitalize; background: var(--bg-surface-elevated); padding: 0.2rem 0.5rem; border-radius: 6px; border: 1px solid var(--border-color);">
                                            <?= htmlspecialchars($p['category']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong style="color: var(--primary-orange);"><?= number_format((float)$p['price'], 3) ?> ر.ع</strong>
                                    </td>
                                    <td>
                                        <strong><?= $stockQty ?></strong> قطعة
                                    </td>
                                    <td>
                                        <span class="status-pill <?= $statusClass ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <td>
                                        <!-- Inline Fast Update Form -->
                                        <form action="dashboard.php" method="POST" class="inline-edit-form">
                                            <input type="hidden" name="action" value="update_product">
                                            <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                            
                                            <label style="font-size:0.75rem; color:var(--text-muted);"><?= ($lang === 'ar') ? 'سعر:' : 'Price:' ?></label>
                                            <input 
                                                type="number" 
                                                step="0.001" 
                                                name="price" 
                                                value="<?= (float)$p['price'] ?>" 
                                                class="form-input-compact" 
                                                style="width: 75px;" 
                                                required
                                            >

                                            <label style="font-size:0.75rem; color:var(--text-muted);"><?= ($lang === 'ar') ? 'مخزون:' : 'Stock:' ?></label>
                                            <input 
                                                type="number" 
                                                step="1" 
                                                name="stock_quantity" 
                                                value="<?= $stockQty ?>" 
                                                class="form-input-compact" 
                                                style="width: 65px;" 
                                                required
                                            >

                                            <button type="submit" class="btn-action-sm" title="<?= $t['quick_save'] ?>">
                                                💾 <?= $t['quick_save'] ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- ================================================================== -->
        <!-- TAB 3: ORDERS PROCESSING                                           -->
        <!-- ================================================================== -->
        <?php if ($tab === 'orders'): ?>
            <?php
            $ordersList = getAllOrders(100);
            ?>
            <div class="dash-card">
                <div class="dash-card-header">
                    <div class="card-title-group">
                        <span class="card-title-icon">📦</span>
                        <div>
                            <h2 class="dash-card-title"><?= ($lang === 'ar') ? 'إدارة ومعالجة طلبات العملاء' : 'Customer Orders Processing & Dispatch' ?></h2>
                            <p class="dash-card-subtitle"><?= ($lang === 'ar') ? 'متابعة الطلبات وتحديث حالاتها والتواصل المباشر مع العميل عبر واتساب' : 'Track orders, modify statuses, and contact customers directly via WhatsApp' ?></p>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th># ID</th>
                                <th><?= ($lang === 'ar') ? 'العميل' : 'Customer' ?></th>
                                <th><?= ($lang === 'ar') ? 'رقم الهاتف' : 'Phone' ?></th>
                                <th><?= ($lang === 'ar') ? 'العنوان / الملاحظات' : 'Address / Notes' ?></th>
                                <th><?= ($lang === 'ar') ? 'المبلغ الإجمالي' : 'Total' ?></th>
                                <th><?= ($lang === 'ar') ? 'الحالة الحالية' : 'Current Status' ?></th>
                                <th><?= ($lang === 'ar') ? 'تعديل الحالة' : 'Update Status' ?></th>
                                <th><?= ($lang === 'ar') ? 'واتساب' : 'WhatsApp' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ordersList)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                                        <?= ($lang === 'ar') ? 'لا توجد طلبات مسجلة في قاعدة البيانات حالياً.' : 'No orders recorded yet.' ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ordersList as $o): ?>
                                    <?php
                                    $oStatus = $o['status'] ?? 'pending';
                                    $stClass = 'info';
                                    if ($oStatus === 'completed') $stClass = 'success';
                                    elseif ($oStatus === 'cancelled') $stClass = 'danger';
                                    elseif ($oStatus === 'pending') $stClass = 'warning';
                                    
                                    $cleanPhone = preg_replace('/\D/', '', $o['customer_phone'] ?? '');
                                    $waMsg = urlencode("مرحباً بك عزيزي {$o['customer_name']}، بخصوص طلبك رقم #{$o['id']} من متجر بدر للهواتف 📱");
                                    ?>
                                    <tr>
                                        <td><strong>#<?= (int)$o['id'] ?></strong></td>
                                        <td>
                                            <div style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($o['customer_name'] ?? 'Guest') ?></div>
                                            <div style="font-size: 0.72rem; color: var(--text-muted);"><?= htmlspecialchars($o['customer_email'] ?? '') ?></div>
                                        </td>
                                        <td>
                                            <span style="direction: ltr; font-weight: 600;"><?= htmlspecialchars($o['customer_phone'] ?? '-') ?></span>
                                        </td>
                                        <td style="max-width: 220px; font-size: 0.8rem; color: var(--text-secondary);">
                                            <?= htmlspecialchars($o['shipping_address'] ?? 'مسقط، سلطنة عُمان') ?>
                                        </td>
                                        <td>
                                            <strong style="color: var(--primary-orange);"><?= number_format((float)($o['total_price'] ?? 0), 2) ?> ر.ع</strong>
                                        </td>
                                        <td>
                                            <span class="status-pill <?= $stClass ?>"><?= htmlspecialchars($oStatus) ?></span>
                                        </td>
                                        <td>
                                            <form action="dashboard.php" method="POST" class="inline-edit-form">
                                                <input type="hidden" name="action" value="update_order_status">
                                                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                                
                                                <select name="status" class="form-input-compact" style="font-weight: 600;">
                                                    <option value="pending" <?= ($oStatus === 'pending') ? 'selected' : '' ?>>قيد الانتظار (Pending)</option>
                                                    <option value="confirmed" <?= ($oStatus === 'confirmed') ? 'selected' : '' ?>>مؤكد (Confirmed)</option>
                                                    <option value="processing" <?= ($oStatus === 'processing') ? 'selected' : '' ?>>قيد التجهيز (Processing)</option>
                                                    <option value="completed" <?= ($oStatus === 'completed') ? 'selected' : '' ?>>مكتمل / تم التسليم (Completed)</option>
                                                    <option value="cancelled" <?= ($oStatus === 'cancelled') ? 'selected' : '' ?>>ملغي (Cancelled)</option>
                                                </select>

                                                <button type="submit" class="btn-action-sm">
                                                    <?= $t['quick_save'] ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <?php if (!empty($cleanPhone)): ?>
                                                <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= $waMsg ?>" target="_blank" class="btn-whatsapp-chat" title="Chat on WhatsApp">
                                                    💬 <?= $t['chat_customer'] ?>
                                                </a>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted); font-size: 0.75rem;">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- ================================================================== -->
        <!-- TAB 4: USERS & RBAC PERMISSIONS (ADMIN ONLY)                       -->
        <!-- ================================================================== -->
        <?php if ($tab === 'users' && $isAdmin): ?>
            <?php
            $usersList = getAllUsers();
            ?>
            <div class="dash-card">
                <div class="dash-card-header">
                    <div class="card-title-group">
                        <span class="card-title-icon">👥</span>
                        <div>
                            <h2 class="dash-card-title"><?= ($lang === 'ar') ? 'إدارة المستخدمين وصلاحيات الأدوار (RBAC)' : 'User Management & Role Permissions (RBAC)' ?></h2>
                            <p class="dash-card-subtitle"><?= ($lang === 'ar') ? 'التحكم في رتب المستخدمين: مدير النظام (Admin)، شريك المتجر (Partner)، والعملاء (Customer)' : 'Assign 3-tier user access: Admin, Partner, Customer' ?></p>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th># ID</th>
                                <th><?= ($lang === 'ar') ? 'الاسم' : 'Name' ?></th>
                                <th><?= ($lang === 'ar') ? 'البريد الإلكتروني' : 'Email' ?></th>
                                <th><?= ($lang === 'ar') ? 'الهاتف' : 'Phone' ?></th>
                                <th><?= ($lang === 'ar') ? 'الرتبة الحالية' : 'Current Role' ?></th>
                                <th><?= ($lang === 'ar') ? 'الحالة' : 'Status' ?></th>
                                <th><?= ($lang === 'ar') ? 'تعديل الصلاحية' : 'Change Role' ?></th>
                                <th><?= ($lang === 'ar') ? 'تاريخ التسجيل' : 'Registered' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usersList as $u): ?>
                                <?php
                                $uRole = normalizeRole($u['role'] ?? 'customer');
                                $isSelf = ((int)$u['id'] === (int)$currentUser['id']);
                                ?>
                                <tr>
                                    <td><strong>#<?= (int)$u['id'] ?></strong></td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--text-primary);">
                                            <?= htmlspecialchars($u['name']) ?>
                                            <?php if ($isSelf): ?>
                                                <span style="font-size: 0.68rem; background: var(--primary-orange); color:#fff; padding:0.1rem 0.35rem; border-radius:4px; margin-inline-start:0.3rem;">أنت / You</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><span style="font-family: monospace; font-size: 0.85rem;"><?= htmlspecialchars($u['email']) ?></span></td>
                                    <td style="direction: ltr;"><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                                    <td>
                                        <span class="role-tag <?= $uRole ?>">
                                            <?php
                                            if ($uRole === ROLE_ADMIN) echo '🛡️ Admin (مدير)';
                                            elseif ($uRole === ROLE_PARTNER) echo '🏪 Partner (شريك)';
                                            else echo '👤 Customer (عميل)';
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-pill <?= (($u['status'] ?? 'active') === 'active') ? 'success' : 'danger' ?>">
                                            <?= htmlspecialchars($u['status'] ?? 'active') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($isSelf): ?>
                                            <span style="font-size: 0.75rem; color: var(--text-muted);">حسابك الأساسي (محمي)</span>
                                        <?php else: ?>
                                            <form action="dashboard.php" method="POST" class="inline-edit-form">
                                                <input type="hidden" name="action" value="update_user_role">
                                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">

                                                <select name="role" class="form-input-compact" style="font-weight: 600;">
                                                    <option value="admin" <?= ($uRole === 'admin') ? 'selected' : '' ?>>🛡️ Admin (مدير النظام)</option>
                                                    <option value="partner" <?= ($uRole === 'partner') ? 'selected' : '' ?>>🏪 Partner (شريك المتجر)</option>
                                                    <option value="customer" <?= ($uRole === 'customer') ? 'selected' : '' ?>>👤 Customer (عميل)</option>
                                                </select>

                                                <button type="submit" class="btn-action-sm">
                                                    <?= $t['quick_save'] ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: var(--text-muted); font-size: 0.8rem;"><?= htmlspecialchars($u['created_at'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- ================================================================== -->
        <!-- TAB 5: SYSTEM SETTINGS (ADMIN ONLY)                                -->
        <!-- ================================================================== -->
        <?php if ($tab === 'settings' && $isAdmin): ?>
            <?php
            $storeNameAr = getSystemSetting('store_name_ar', 'بدر للهواتف');
            $storeNameEn = getSystemSetting('store_name_en', 'Bader Phones');
            $waPrimary = getSystemSetting('whatsapp_number', '96897447122');
            $waPartner = getSystemSetting('partner_whatsapp', '96893952123');
            $storeCurr = getSystemSetting('currency', 'OMR');
            ?>
            <div class="dash-card">
                <div class="dash-card-header">
                    <div class="card-title-group">
                        <span class="card-title-icon">⚙️</span>
                        <div>
                            <h2 class="dash-card-title"><?= ($lang === 'ar') ? 'إعدادات المتجر وقنوات الاتصال' : 'Store Settings & Communication Channels' ?></h2>
                            <p class="dash-card-subtitle"><?= ($lang === 'ar') ? 'تعديل رقم واتساب المعتمد للطلبات (96897447122) ورقم الشريك والعملة' : 'Configure official WhatsApp order lines and store parameters' ?></p>
                        </div>
                    </div>
                </div>

                <form action="dashboard.php" method="POST">
                    <input type="hidden" name="action" value="update_settings">

                    <div class="settings-grid">
                        <div class="setting-item">
                            <label class="setting-label"><?= ($lang === 'ar') ? 'اسم المتجر الرسمي (بالعربية)' : 'Store Name (Arabic)' ?></label>
                            <span class="setting-desc">الاسم المعتمد للعلامة التجارية</span>
                            <input type="text" name="store_name_ar" class="setting-input" value="<?= htmlspecialchars($storeNameAr) ?>" required>
                        </div>

                        <div class="setting-item">
                            <label class="setting-label"><?= ($lang === 'ar') ? 'اسم المتجر الرسمي (بالإنجليزية)' : 'Store Name (English)' ?></label>
                            <span class="setting-desc">Official English branding</span>
                            <input type="text" name="store_name_en" class="setting-input" value="<?= htmlspecialchars($storeNameEn) ?>" required>
                        </div>

                        <div class="setting-item">
                            <label class="setting-label"><?= ($lang === 'ar') ? 'رقم واتساب المعتمد لاستقبال الطلبات' : 'Official WhatsApp Line for Orders' ?></label>
                            <span class="setting-desc">المفتاح الدولي بدون + (افتراضي: 96897447122)</span>
                            <input type="text" name="whatsapp_number" class="setting-input" value="<?= htmlspecialchars($waPrimary) ?>" required>
                        </div>

                        <div class="setting-item">
                            <label class="setting-label"><?= ($lang === 'ar') ? 'رقم واتساب الشريك / التجهيز الميداني' : 'Partner / Supplier WhatsApp Line' ?></label>
                            <span class="setting-desc">خط تواصل الشريك والموردين (96893952123)</span>
                            <input type="text" name="partner_whatsapp" class="setting-input" value="<?= htmlspecialchars($waPartner) ?>" required>
                        </div>

                        <div class="setting-item">
                            <label class="setting-label"><?= ($lang === 'ar') ? 'عملة المتجر الافتراضية' : 'Store Currency' ?></label>
                            <span class="setting-desc">الريال العماني (OMR)</span>
                            <input type="text" name="currency" class="setting-input" value="<?= htmlspecialchars($storeCurr) ?>" required>
                        </div>
                    </div>

                    <div style="margin-top: 2rem;">
                        <button type="submit" class="btn-action-sm" style="padding: 0.75rem 1.75rem; font-size: 0.95rem;">
                            💾 <?= $t['save_changes'] ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    </main>

</body>
</html>
