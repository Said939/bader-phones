<?php
// ============================================================================
// Bader Phones / بدر للهواتف - Partner Dashboard (partner/dashboard.php)
// Access: RESTRICTED TO ROLES 'admin' AND 'partner' (checkRole(['admin', 'partner']))
// Theme: Clean Tech White, Vibrant Tech Orange (#ff6600), Tech Blue (#0A66C2)
// ============================================================================

require_once __DIR__ . '/../auth.php';

// RBAC Guard: Allow 'admin' and 'partner'
checkRole(['admin', 'partner'], '../login.php', '../index.php');

$currentUser = getCurrentUser();
$pdo = getDBConnection();
$flashSuccess = $_SESSION['partner_success'] ?? null;
$flashError = $_SESSION['partner_error'] ?? null;
unset($_SESSION['partner_success'], $_SESSION['partner_error']);

// Handle Partner Actions (Stock & Orders)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Action 1: Update Stock
    if ($action === 'update_stock') {
        $id = (int)($_POST['id'] ?? 0);
        $stock = max(0, (int)($_POST['stock'] ?? 0));
        $price = max(0.0, (float)($_POST['price'] ?? 0));

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE products SET stock = ?, price = ? WHERE id = ?");
            $stmt->execute([$stock, $price, $id]);
            $_SESSION['partner_success'] = "تم تحديث المخزون والسعر بنجاح للمنتج #$id!";
        }
        header("Location: dashboard.php?tab=inventory");
        exit;
    }

    // Action 2: Update Order Status
    if ($action === 'update_status') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? 'pending';
        $allowed = ['pending', 'processing', 'completed', 'cancelled'];

        if ($orderId > 0 && in_array($status, $allowed)) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $orderId]);
            $_SESSION['partner_success'] = "تم تحديث حالة الطلب #$orderId إلى ($status)!";
        }
        header("Location: dashboard.php?tab=orders");
        exit;
    }
}

// Fetch Metrics for Partner
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStock = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 5")->fetchColumn();
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

// Fetch Data
$currentTab = $_GET['tab'] ?? 'inventory';
$products = $pdo->query("SELECT * FROM products ORDER BY stock ASC, id DESC")->fetchAll();
$orders = $pdo->query("SELECT o.*, u.name as customer_name, u.email as customer_email FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بوابة الشريك والموردين | بدر للهواتف (Partner Panel)</title>
    <link rel="icon" type="image/png" href="../assets/logo.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
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
        .partner-badge {
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

        .container {
            max-width: 1300px;
            margin: 1.5rem auto;
            padding: 0 1.5rem;
        }

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

        /* Banner */
        .partner-adv-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 16px;
            padding: 18px 24px;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            border-right: 5px solid var(--tech-blue);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
            gap: 15px;
            flex-wrap: wrap;
        }
        .partner-adv-title {
            font-size: 15px;
            font-weight: 900;
            margin-bottom: 4px;
        }
        .partner-adv-desc {
            font-size: 12.5px;
            color: #cbd5e1;
        }
        .btn-adv-partner {
            background: var(--tech-blue);
            color: #ffffff;
            padding: 9px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 800;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .btn-adv-partner:hover {
            background: #004182;
            transform: translateY(-2px);
        }

        /* KPI Grid */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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

        .btn-primary {
            background: var(--primary-orange);
            color: #ffffff;
            border: none;
            padding: 0.55rem 1rem;
            border-radius: 8px;
            font-weight: 800;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .btn-primary:hover { background: var(--orange-hover); }

        .table-responsive { width: 100%; overflow-x: auto; }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            text-align: right;
        }
        .data-table th {
            background: #f8fafc;
            padding: 0.85rem 1rem;
            font-weight: 800;
            color: var(--text-muted);
            border-bottom: 2px solid var(--border-color);
            white-space: nowrap;
        }
        .data-table td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }
        .data-table tr:hover { background: #fffaf5; }

        .status-pill {
            display: inline-block;
            padding: 0.25rem 0.65rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
        }
        .status-pending { background: #fef3c7; color: #b45309; }
        .status-processing { background: #e0f2fe; color: #0369a1; }
        .status-completed { background: #dcfce7; color: #15803d; }
        .status-cancelled { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>

    <header class="top-navbar">
        <div class="nav-wrapper">
            <a href="dashboard.php" class="brand-group">
                <img src="../assets/logo.png" alt="Logo" class="brand-logo-img" onerror="this.onerror=null; this.src='../assets/logo.svg';">
                <div>
                    <div class="brand-title">بدر <span>للهواتف</span></div>
                    <small style="color:var(--text-muted);">Partner Portal | بوابة الشريك والمخزون</small>
                </div>
                <span class="partner-badge">Partner</span>
            </a>

            <div class="nav-links">
                <span style="font-size:0.85rem; font-weight:700;">مرحباً، <?= htmlspecialchars($currentUser['name']) ?> 🏪</span>
                <?php if ($currentUser['role'] === 'admin'): ?>
                    <a href="../admin/dashboard.php" class="nav-btn" title="لوحة المدير">🛡️ لوحة المدير</a>
                <?php endif; ?>
                <a href="../add_product.php" class="nav-btn" style="color:var(--primary-orange); border-color:var(--primary-orange);" title="إضافة منتج متقدم">➕ إضافة منتج جديد</a>
                <a href="../index.php" class="nav-btn" target="_blank" title="المتجر الرئيسي">🛍️ المتجر</a>
                <a href="../logout.php" class="nav-btn danger" title="تسجيل الخروج">🚪 خروج</a>
            </div>
        </div>
    </header>

    <main class="container">

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

        <!-- Advanced Add Product Banner for Partner -->
        <div class="partner-adv-banner">
            <div>
                <div class="partner-adv-title">📦 إضافة كميات وموديلات جديدة بكافة المواصفات</div>
                <div class="partner-adv-desc">استخدم نموذج الإضافة الشامل لتسجيل الهواتف واللابتوبات بجميع ألوانها ومعالجاتها وصورها بدقة.</div>
            </div>
            <a href="../add_product.php" class="btn-adv-partner">
                <span>➕</span> فتح نموذج الإضافة
            </a>
        </div>

        <!-- KPI Cards -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon">📱</div>
                <div>
                    <div class="kpi-title">إجمالي المنتجات</div>
                    <div class="kpi-value"><?= number_format($totalProducts) ?></div>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon" style="color:#ef4444; background:#fff1f2;">⚠️</div>
                <div>
                    <div class="kpi-title">نقص المخزون (≤ 5 قطع)</div>
                    <div class="kpi-value" style="color:<?= ($lowStock > 0) ? '#dc2626' : '#16a34a' ?>;"><?= number_format($lowStock) ?></div>
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
                <div class="kpi-icon" style="color:#f59e0b; background:#fffbeb;">⏳</div>
                <div>
                    <div class="kpi-title">طلبات بانتظار التجهيز</div>
                    <div class="kpi-value"><?= number_format($pendingOrders) ?></div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="tabs-nav">
            <a href="dashboard.php?tab=inventory" class="tab-btn <?= ($currentTab === 'inventory') ? 'active' : '' ?>">
                📱 إدارة المخزون والأسعار
            </a>
            <a href="dashboard.php?tab=orders" class="tab-btn <?= ($currentTab === 'orders') ? 'active' : '' ?>">
                📦 معالجة وتجهيز الطلبات
            </a>
        </div>

        <!-- Tab 1: Inventory -->
        <?php if ($currentTab === 'inventory'): ?>
            <div class="dash-card">
                <div class="card-header">
                    <h2 class="card-title">📱 جرد المخزون وتحديث الكميات والأسعار (<?= count($products) ?>)</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th># ID</th>
                                <th>اسم المنتج</th>
                                <th>الألوان المتاحة</th>
                                <th>القسم</th>
                                <th>سعر البيع (ر.ع)</th>
                                <th>المخزون المتوفر</th>
                                <th>تحديث فوري</th>
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
                                        </strong>
                                        <?php if ((int)$p['stock'] <= 5): ?>
                                            <span style="color:#dc2626; font-size:0.75rem; font-weight:800;">(مخزون حرج!) ⚠️</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form action="dashboard.php" method="POST" style="display:flex; gap:0.4rem; align-items:center;">
                                            <input type="hidden" name="action" value="update_stock">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <label style="font-size:0.75rem; color:var(--text-muted);">سعر:</label>
                                            <input type="number" step="0.001" name="price" value="<?= (float)$p['price'] ?>" style="width:75px; height:34px; padding:0 0.35rem; border:1px solid #cbd5e1; border-radius:6px;" required>
                                            <label style="font-size:0.75rem; color:var(--text-muted);">كمية:</label>
                                            <input type="number" step="1" name="stock" value="<?= (int)$p['stock'] ?>" style="width:65px; height:34px; padding:0 0.35rem; border:1px solid #cbd5e1; border-radius:6px;" required>
                                            <button type="submit" class="btn-primary" style="padding:0.4rem 0.75rem; font-size:0.8rem;">حفظ</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tab 2: Orders -->
        <?php if ($currentTab === 'orders'): ?>
            <div class="dash-card">
                <div class="card-header">
                    <h2 class="card-title">📦 طلبات الزبائن للتجهيز والشحن (<?= count($orders) ?>)</h2>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th># الطلب</th>
                                <th>العميل</th>
                                <th>الأجهزة المطلوبة</th>
                                <th>الإجمالي</th>
                                <th>حالة الطلب</th>
                                <th>تحديث المعالجة</th>
                                <th>تواصل</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center; padding:2rem; color:var(--text-muted);">لا توجد طلبات واردة حتى الآن.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $ord): 
                                    $items = json_decode($ord['order_items'], true) ?: [];
                                ?>
                                    <tr>
                                        <td><strong>#<?= $ord['id'] ?></strong></td>
                                        <td>
                                            <div style="font-weight:700;"><?= htmlspecialchars($ord['customer_name'] ?? 'عميل الموقع') ?></div>
                                            <small style="color:var(--text-muted);"><?= htmlspecialchars($ord['customer_email'] ?? '-') ?></small>
                                        </td>
                                        <td>
                                            <?php foreach ($items as $item): ?>
                                                <div style="font-size:0.8rem; color:var(--text-dark); margin-bottom:2px;">
                                                    • <?= htmlspecialchars($item['name'] ?? 'جهاز') ?>
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
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                                <select name="status" style="height:34px; font-size:0.8rem; padding:0 0.5rem; border:1.5px solid var(--border-color); border-radius:6px; font-family:inherit;">
                                                    <option value="pending" <?= ($ord['status'] === 'pending') ? 'selected' : '' ?>>pending (قيد الانتظار)</option>
                                                    <option value="processing" <?= ($ord['status'] === 'processing') ? 'selected' : '' ?>>processing (قيد التجهيز)</option>
                                                    <option value="completed" <?= ($ord['status'] === 'completed') ? 'selected' : '' ?>>completed (تم التسليم)</option>
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

    </main>

</body>
</html>
