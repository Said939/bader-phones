<?php
// ============================================================================
// Bader Phones / بدر للهواتف - Admin Dashboard (admin/dashboard.php)
// Access: STRICTLY RESTRICTED TO ROLE 'admin' (checkRole('admin'))
// Theme: Clean Tech White, Vibrant Tech Orange (#ff6600), Tech Blue (#0A66C2)
// ============================================================================

require_once __DIR__ . '/../auth.php';

// Strict RBAC Guard: Only 'admin' role allowed
checkRole('admin', '../login.php', '../index.php');

$currentUser = getCurrentUser();
$pdo = getDBConnection();
$flashSuccess = $_SESSION['admin_success'] ?? null;
$flashError = $_SESSION['admin_error'] ?? null;
unset($_SESSION['admin_success'], $_SESSION['admin_error']);

// Handle Admin POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action 1: Add New Product (Quick)
    if ($action === 'add_product') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? 'phones');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        if (!empty($name) && $price > 0) {
            $stmt = $pdo->prepare("INSERT INTO products (name, name_ar, name_en, category, price, stock, description, description_ar, description_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $name, $name, $category, $price, $stock, $description, $description, $description]);
            $_SESSION['admin_success'] = "تمت إضافة المنتج بنجاح! / Product added successfully.";
        } else {
            $_SESSION['admin_error'] = "يرجى إدخال اسم المنتج وسعر صالح.";
        }
        header("Location: dashboard.php?tab=products");
        exit;
    }

    // Action 2: Update Product Stock & Price
    if ($action === 'update_product') {
        $id = (int)($_POST['id'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE products SET price = ?, stock = ? WHERE id = ?");
            $stmt->execute([$price, $stock, $id]);
            $_SESSION['admin_success'] = "تم تحديث سعر ومخزون المنتج #$id بنجاح!";
        }
        header("Location: dashboard.php?tab=products");
        exit;
    }

    // Action 3: Delete Product
    if ($action === 'delete_product') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['admin_success'] = "تم حذف المنتج بنجاح.";
        }
        header("Location: dashboard.php?tab=products");
        exit;
    }

    // Action 4: Update Order Status
    if ($action === 'update_order_status') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? 'pending';
        $allowed = ['pending', 'processing', 'completed', 'cancelled'];

        if ($orderId > 0 && in_array($status, $allowed)) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $orderId]);
            $_SESSION['admin_success'] = "تم تحديث حالة الطلب #$orderId إلى ($status)!";
        }
        header("Location: dashboard.php?tab=orders");
        exit;
    }

    // Action 5: Update User Role
    if ($action === 'update_user_role') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $newRole = $_POST['role'] ?? 'customer';
        $allowedRoles = ['admin', 'partner', 'customer'];

        if ($userId === (int)$currentUser['id'] && $newRole !== 'admin') {
            $_SESSION['admin_error'] = "لا يمكنك إلغاء رتبة الإدارة لحسابك الحالي!";
        } elseif ($userId > 0 && in_array($newRole, $allowedRoles)) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $userId]);
            $_SESSION['admin_success'] = "تم تحديث صلاحية ورتبة المستخدم بنجاح!";
        }
        header("Location: dashboard.php?tab=users");
        exit;
    }
}

// Fetch Metrics
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStockCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 5")->fetchColumn();
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Fetch Data Lists
$currentTab = $_GET['tab'] ?? 'products';
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
$orders = $pdo->query("SELECT o.*, u.name as customer_name, u.email as customer_email FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.id DESC")->fetchAll();
$users = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم مدير النظام | بدر للهواتف (Admin Panel)</title>
    <link rel="icon" type="image/png" href="../assets/logo.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-orange: #ff6600;
            --orange-hover: #e65c00;
            --orange-soft: rgba(255, 102, 0, 0.08);
            --tech-blue: #0A66C2;
            --tech-blue-soft: rgba(10, 102, 194, 0.08);
            --bg-light: #f8fafc;
            --card-bg: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Cairo', 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            min-height: 100vh;
            padding-bottom: 3rem;
        }

        /* Top Navbar */
        .top-navbar {
            background: #ffffff;
            border-bottom: 2px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }
        .nav-wrapper {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0.85rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .brand-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: inherit;
        }
        .brand-logo-img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }
        .brand-title {
            font-size: 1.2rem;
            font-weight: 900;
        }
        .brand-title span { color: var(--primary-orange); }
        .admin-badge {
            background: var(--tech-blue);
            color: #fff;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 0.2rem 0.55rem;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .nav-btn {
            background: #ffffff;
            border: 1px solid var(--border-color);
            padding: 0.45rem 0.85rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-dark);
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .nav-btn:hover {
            border-color: var(--primary-orange);
            color: var(--primary-orange);
            background: var(--orange-soft);
        }
        .nav-btn.danger:hover {
            border-color: #ef4444;
            color: #ef4444;
            background: #fff1f2;
        }

        /* Container */
        .container {
            max-width: 1300px;
            margin: 1.5rem auto;
            padding: 0 1.5rem;
        }

        /* Flash message */
        .flash-alert {
            padding: 0.85rem 1.25rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .flash-alert.success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
        .flash-alert.error { background: #fff1f2; border: 1px solid #fecdd3; color: #b91c1c; }

        /* Advanced Banner */
        .advanced-cta-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 16px;
            padding: 20px 24px;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            border-right: 5px solid var(--primary-orange);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            gap: 15px;
            flex-wrap: wrap;
        }
        .advanced-cta-title {
            font-size: 16px;
            font-weight: 900;
            margin-bottom: 4px;
        }
        .advanced-cta-desc {
            font-size: 13px;
            color: #cbd5e1;
        }
        .btn-adv-cta {
            background: var(--primary-orange);
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 800;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-adv-cta:hover {
            background: var(--orange-hover);
            transform: translateY(-2px);
        }

        /* KPI Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        .kpi-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.35rem;
            display: flex;
            align-items: center;
            gap: 1.15rem;
            box-shadow: var(--shadow);
            transition: transform 0.2s ease;
        }
        .kpi-card:hover { transform: translateY(-3px); }
        .kpi-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: var(--orange-soft);
            color: var(--primary-orange);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            flex-shrink: 0;
        }
        .kpi-icon.blue {
            background: var(--tech-blue-soft);
            color: var(--tech-blue);
        }
        .kpi-title { font-size: 0.82rem; color: var(--text-muted); font-weight: 700; }
        .kpi-value { font-size: 1.6rem; font-weight: 900; color: var(--text-dark); margin-top: 0.2rem; }

        /* Tabs Nav */
        .tabs-nav {
            display: flex;
            gap: 0.5rem;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 1.5rem;
        }
        .tab-btn {
            padding: 0.75rem 1.25rem;
            font-weight: 800;
            font-size: 0.95rem;
            text-decoration: none;
            color: var(--text-muted);
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        .tab-btn:hover { color: var(--primary-orange); }
        .tab-btn.active {
            color: var(--primary-orange);
            border-bottom-color: var(--primary-orange);
        }

        /* Card Section */
        .dash-card {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 18px;
            padding: 1.75rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .card-title { font-size: 1.25rem; font-weight: 800; }

        /* Form elements */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .form-group { display: flex; flex-direction: column; gap: 0.35rem; }
        .form-label { font-size: 0.82rem; font-weight: 700; color: var(--text-dark); }
        .form-input, .form-select {
            height: 44px;
            padding: 0 0.85rem;
            border: 1.5px solid var(--border-color);
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.9rem;
            outline: none;
        }
        .form-input:focus, .form-select:focus {
            border-color: var(--primary-orange);
        }
        .btn-primary {
            background: var(--primary-orange);
            color: #ffffff;
            border: none;
            padding: 0.65rem 1.25rem;
            border-radius: 10px;
            font-weight: 800;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary:hover { background: var(--orange-hover); }

        /* Table */
        .table-responsive { width: 100%; overflow-x: auto; }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            text-align: right;
            font-size: 0.88rem;
        }
        .data-table th {
            background: #f8fafc;
            padding: 0.85rem 1rem;
            font-weight: 800;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border-color);
        }
        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        .data-table tr:hover { background: #fdfbf7; }

        .status-pill {
            display: inline-block;
            padding: 0.25rem 0.65rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: capitalize;
        }
        .status-pending { background: #fef3c7; color: #b45309; }
        .status-processing { background: #e0f2fe; color: #0369a1; }
        .status-completed { background: #dcfce7; color: #15803d; }
        .status-cancelled { background: #fee2e2; color: #b91c1c; }

        .role-pill {
            display: inline-block;
            padding: 0.25rem 0.65rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 800;
        }
        .role-admin { background: #fee2e2; color: #dc2626; }
        .role-partner { background: #e0f2fe; color: #0284c7; }
        .role-customer { background: #dcfce7; color: #16a34a; }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <header class="top-navbar">
        <div class="nav-wrapper">
            <a href="dashboard.php" class="brand-group">
                <img src="../assets/logo.png" alt="Logo" class="brand-logo-img" onerror="this.onerror=null; this.src='../assets/logo.svg';">
                <div>
                    <div class="brand-title">بدر <span>للهواتف</span></div>
                    <small style="color:var(--text-muted);">Admin Panel | لوحة تحكم مدير النظام</small>
                </div>
                <span class="admin-badge">Admin</span>
            </a>

            <div class="nav-links">
                <span style="font-size:0.85rem; font-weight:700;">مرحباً، <?= htmlspecialchars($currentUser['name']) ?> 👋</span>
                <a href="../add_product.php" class="nav-btn" style="color:var(--primary-orange); border-color:var(--primary-orange);" title="إضافة منتج متقدم">➕ إضافة منتج تفصيلي</a>
                <a href="../partner/dashboard.php" class="nav-btn" title="لوحة الشريك">🏪 بوابة الشريك</a>
                <a href="../index.php" class="nav-btn" target="_blank" title="المتجر الرئيسي">🛍️ المتجر</a>
                <a href="../logout.php" class="nav-btn danger" title="تسجيل الخروج">🚪 خروج</a>
            </div>
        </div>
    </header>

    <main class="container">

        <!-- Flash messages -->
        <?php if (!empty($flashSuccess)): ?>
            <div class="flash-alert success">
                <span>✅ <?= htmlspecialchars($flashSuccess) ?></span>
                <span style="cursor:pointer;" onclick="this.parentElement.remove()">✕</span>
            </div>
        <?php endif; ?>

        <?php if (!empty($flashError)): ?>
            <div class="flash-alert error">
                <span>⚠️ <?= htmlspecialchars($flashError) ?></span>
                <span style="cursor:pointer;" onclick="this.parentElement.remove()">✕</span>
            </div>
        <?php endif; ?>

        <!-- Advanced Add Product CTA Banner -->
        <div class="advanced-cta-banner">
            <div>
                <div class="advanced-cta-title">✨ نظام إضافة وتوصيف المنتجات المتقدم (Bilingual & Category Specs)</div>
                <div class="advanced-cta-desc">أضف المنتجات بكافة المواصفات (المعالج، كرت الشاشة، الرام، الألوان المحددة بأداة القطارة واللغتين العربية والإنجليزية).</div>
            </div>
            <a href="../add_product.php" class="btn-adv-cta">
                <span>➕</span> فتح نموذج الإضافة الشامل
            </a>
        </div>

        <!-- KPI Cards Grid -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon">📱</div>
                <div>
                    <div class="kpi-title">إجمالي المنتجات</div>
                    <div class="kpi-value"><?= number_format($totalProducts) ?></div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon <?= $lowStockCount > 0 ? '' : 'blue' ?>">⚠️</div>
                <div>
                    <div class="kpi-title">تنبيهات انخفاض المخزون</div>
                    <div class="kpi-value" style="color: <?= $lowStockCount > 0 ? '#dc2626' : '#16a34a' ?>;"><?= number_format($lowStockCount) ?></div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon">📦</div>
                <div>
                    <div class="kpi-title">إجمالي الطلبات</div>
                    <div class="kpi-value"><?= number_format($totalOrders) ?></div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon blue">💰</div>
                <div>
                    <div class="kpi-title">المبيعات المؤكدة</div>
                    <div class="kpi-value"><?= number_format($totalRevenue, 3) ?> <small style="font-size:0.8rem;">ر.ع</small></div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon">👥</div>
                <div>
                    <div class="kpi-title">المستخدمين المسجلين</div>
                    <div class="kpi-value"><?= number_format($totalUsers) ?></div>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="tabs-nav">
            <a href="dashboard.php?tab=products" class="tab-btn <?= ($currentTab === 'products') ? 'active' : '' ?>">
                📱 إدارة المنتجات والمخزون
            </a>
            <a href="dashboard.php?tab=orders" class="tab-btn <?= ($currentTab === 'orders') ? 'active' : '' ?>">
                📦 متابعة ومعالجة الطلبات
            </a>
            <a href="dashboard.php?tab=users" class="tab-btn <?= ($currentTab === 'users') ? 'active' : '' ?>">
                👥 المستخدمين وتعيين الأدوار (RBAC)
            </a>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 1: PRODUCTS                                                -->
        <!-- ============================================================== -->
        <?php if ($currentTab === 'products'): ?>
            <!-- Quick Add Product Card -->
            <div class="dash-card">
                <div class="card-header">
                    <h2 class="card-title">⚡ إضافة سريعة لمنتج (أو استخدم النموذج المتقدم أعلاه)</h2>
                </div>
                <form action="dashboard.php" method="POST">
                    <input type="hidden" name="action" value="add_product">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">اسم المنتج *</label>
                            <input type="text" name="name" class="form-input" placeholder="مثال: iPhone 18 Pro Max" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">القسم *</label>
                            <select name="category" class="form-select">
                                <option value="phones">الهواتف الذكية (Smartphones)</option>
                                <option value="laptops">اللابتوبات والكمبيوتر (Laptops)</option>
                                <option value="ipads">الأجهزة اللوحية (iPads)</option>
                                <option value="accessories">الاكسسوارات العامة (Accessories)</option>
                                <option value="photography">اكسسوارات التصوير (Photography)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">السعر (ر.ع) *</label>
                            <input type="number" step="0.001" name="price" class="form-input" placeholder="0.000" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">المخزون المتوفر *</label>
                            <input type="number" step="1" name="stock" class="form-input" placeholder="10" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <label class="form-label">الوصف والمواصفات السريعة</label>
                        <input type="text" name="description" class="form-input" placeholder="مواصفات المعالج، الرام، الشاشة، والضمان...">
                    </div>
                    <button type="submit" class="btn-primary">حفظ وإضافة المنتج 🚀</button>
                </form>
            </div>

            <!-- Products List Card -->
            <div class="dash-card">
                <div class="card-header">
                    <h2 class="card-title">📋 قائمة المنتجات الحالية (<?= count($products) ?>)</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th># ID</th>
                                <th>اسم المنتج</th>
                                <th>الألوان</th>
                                <th>القسم</th>
                                <th>السعر الحالي</th>
                                <th>المخزون</th>
                                <th>تعديل سريع</th>
                                <th>إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                                <?php 
                                    $pColors = [];
                                    if (!empty($p['colors'])) {
                                        $decoded = json_decode($p['colors'], true);
                                        if (is_array($decoded)) $pColors = $decoded;
                                    }
                                    $pName = !empty($p['name_ar']) ? $p['name_ar'] : $p['name'];
                                ?>
                                <tr>
                                    <td><strong>#<?= $p['id'] ?></strong></td>
                                    <td>
                                        <div style="font-weight:800; color:var(--text-dark);">
                                            <a href="../product.php?id=<?= $p['id'] ?>" target="_blank" style="color:inherit; text-decoration:none;">
                                                <?= htmlspecialchars($pName) ?> 🔗
                                            </a>
                                        </div>
                                        <small style="color:var(--text-muted);"><?= htmlspecialchars(mb_substr($p['description_ar'] ?? ($p['description'] ?? ''), 0, 45)) ?>...</small>
                                    </td>
                                    <td>
                                        <?php if (!empty($pColors)): ?>
                                            <div style="display:flex; gap:4px; align-items:center;">
                                                <?php foreach ($pColors as $c): ?>
                                                    <span style="width:14px; height:14px; border-radius:50%; background:<?= htmlspecialchars($c['hex'] ?? '#000') ?>; border:1px solid rgba(0,0,0,0.2);" title="<?= htmlspecialchars($c['name_ar'] ?? '') ?>"></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <small style="color:var(--text-muted);">-</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><span style="background:var(--tech-blue-soft); color:var(--tech-blue); padding:0.2rem 0.5rem; border-radius:6px; font-weight:700;"><?= htmlspecialchars($p['category']) ?></span></td>
                                    <td><strong style="color:var(--primary-orange);"><?= number_format((float)$p['price'], 3) ?> ر.ع</strong></td>
                                    <td>
                                        <strong style="color: <?= ((int)$p['stock'] <= 5) ? '#dc2626' : '#16a34a' ?>;">
                                            <?= (int)$p['stock'] ?> قطعة
                                            <?= ((int)$p['stock'] <= 5) ? ' ⚠️' : '' ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <form action="dashboard.php" method="POST" style="display:flex; gap:0.4rem; align-items:center;">
                                            <input type="hidden" name="action" value="update_product">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <input type="number" step="0.001" name="price" value="<?= (float)$p['price'] ?>" style="width:75px; height:34px; padding:0 0.35rem; border:1px solid #cbd5e1; border-radius:6px;" required title="السعر">
                                            <input type="number" step="1" name="stock" value="<?= (int)$p['stock'] ?>" style="width:65px; height:34px; padding:0 0.35rem; border:1px solid #cbd5e1; border-radius:6px;" required title="المخزون">
                                            <button type="submit" class="btn-primary" style="padding:0.4rem 0.6rem; font-size:0.75rem;">تحديث</button>
                                        </form>
                                    </td>
                                    <td>
                                        <form action="dashboard.php" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا المنتج؟');">
                                            <input type="hidden" name="action" value="delete_product">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" style="background:none; border:none; color:#dc2626; font-weight:700; cursor:pointer;">🗑️ حذف</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- ============================================================== -->
        <!-- TAB 2: ORDERS                                                  -->
        <!-- ============================================================== -->
        <?php if ($currentTab === 'orders'): ?>
            <div class="dash-card">
                <div class="card-header">
                    <h2 class="card-title">📦 طلبات العملاء عبر واتساب والموقع (<?= count($orders) ?>)</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>العميل</th>
                                <th>عناصر الطلب</th>
                                <th>الإجمالي</th>
                                <th>الحالة</th>
                                <th>تحديث الحالة</th>
                                <th>تواصل</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding:2rem; color:var(--text-muted);">لا توجد طلبات مسجلة بعد.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $ord): 
                                    $items = json_decode($ord['order_items'], true) ?: [];
                                ?>
                                    <tr>
                                        <td><strong>#<?= $ord['id'] ?></strong></td>
                                        <td>
                                            <div style="font-weight:700;"><?= htmlspecialchars($ord['customer_name'] ?? 'عميل زائر') ?></div>
                                            <small style="color:var(--text-muted);"><?= htmlspecialchars($ord['customer_email'] ?? '-') ?></small>
                                        </td>
                                        <td>
                                            <?php foreach ($items as $item): ?>
                                                <div style="font-size:0.8rem; color:var(--text-dark); margin-bottom:2px;">
                                                    • <?= htmlspecialchars($item['name'] ?? 'منتج') ?>
                                                    <?php if (!empty($item['color'])): ?>
                                                        <span style="font-size:11px; color:var(--primary-orange);">(<?= htmlspecialchars($item['color']) ?>)</span>
                                                    <?php endif; ?>
                                                    <strong>(×<?= $item['quantity'] ?? 1 ?>)</strong>
                                                </div>
                                            <?php endforeach; ?>
                                        </td>
                                        <td><strong style="color:var(--primary-orange);"><?= number_format((float)$ord['total_amount'], 3) ?> ر.ع</strong></td>
                                        <td>
                                            <span class="status-pill status-<?= $ord['status'] ?>"><?= $ord['status'] ?></span>
                                        </td>
                                        <td>
                                            <form action="dashboard.php" method="POST" style="display:flex; gap:0.4rem; align-items:center;">
                                                <input type="hidden" name="action" value="update_order_status">
                                                <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                                <select name="status" class="form-select" style="height:34px; font-size:0.8rem; padding:0 0.5rem;">
                                                    <option value="pending" <?= ($ord['status'] === 'pending') ? 'selected' : '' ?>>pending (قيد الانتظار)</option>
                                                    <option value="processing" <?= ($ord['status'] === 'processing') ? 'selected' : '' ?>>processing (قيد التجهيز)</option>
                                                    <option value="completed" <?= ($ord['status'] === 'completed') ? 'selected' : '' ?>>completed (مكتمل)</option>
                                                    <option value="cancelled" <?= ($ord['status'] === 'cancelled') ? 'selected' : '' ?>>cancelled (ملغي)</option>
                                                </select>
                                                <button type="submit" class="btn-primary" style="padding:0.4rem 0.6rem; font-size:0.75rem;">تحديث</button>
                                            </form>
                                        </td>
                                        <td>
                                            <a href="https://wa.me/96897447122?text=<?= urlencode('مرحباً بخصوص الطلب #' . $ord['id']) ?>" target="_blank" style="text-decoration:none; font-size:12px; font-weight:700; color:#16a34a; background:#dcfce7; padding:4px 8px; border-radius:6px; display:inline-flex; align-items:center; gap:3px;">
                                                💬 واتساب
                                            </a>
                                        </td>
                                        <td style="color:var(--text-muted); font-size:0.8rem;"><?= $ord['created_at'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- ============================================================== -->
        <!-- TAB 3: USERS & RBAC                                            -->
        <!-- ============================================================== -->
        <?php if ($currentTab === 'users'): ?>
            <div class="dash-card">
                <div class="card-header">
                    <h2 class="card-title">👥 إدارة المستخدمين وصلاحيات الأدوار (RBAC)</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th># ID</th>
                                <th>الاسم</th>
                                <th>البريد الإلكتروني</th>
                                <th>الرتبة الحالية</th>
                                <th>تعديل الرتبة</th>
                                <th>تاريخ الانضمام</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><strong>#<?= $u['id'] ?></strong></td>
                                    <td>
                                        <strong style="color:var(--text-dark);"><?= htmlspecialchars($u['name']) ?></strong>
                                        <?php if ((int)$u['id'] === (int)$currentUser['id']): ?>
                                            <small style="background:var(--primary-orange); color:#fff; padding:0.1rem 0.35rem; border-radius:4px;">حسابك الحالي</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><span style="font-family:monospace;"><?= htmlspecialchars($u['email']) ?></span></td>
                                    <td>
                                        <span class="role-pill role-<?= $u['role'] ?>"><?= $u['role'] ?></span>
                                    </td>
                                    <td>
                                        <?php if ((int)$u['id'] === (int)$currentUser['id']): ?>
                                            <small style="color:var(--text-muted);">حساب الإدارة الأساسي</small>
                                        <?php else: ?>
                                            <form action="dashboard.php" method="POST" style="display:flex; gap:0.4rem; align-items:center;">
                                                <input type="hidden" name="action" value="update_user_role">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <select name="role" class="form-select" style="height:34px; font-size:0.8rem; padding:0 0.5rem;">
                                                    <option value="admin" <?= ($u['role'] === 'admin') ? 'selected' : '' ?>>🛡️ admin (مدير النظام)</option>
                                                    <option value="partner" <?= ($u['role'] === 'partner') ? 'selected' : '' ?>>🏪 partner (شريك المتجر)</option>
                                                    <option value="customer" <?= ($u['role'] === 'customer') ? 'selected' : '' ?>>👤 customer (عميل)</option>
                                                </select>
                                                <button type="submit" class="btn-primary" style="padding:0.4rem 0.6rem; font-size:0.75rem;">تعيين</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color:var(--text-muted); font-size:0.8rem;"><?= $u['created_at'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </main>

</body>
</html>
