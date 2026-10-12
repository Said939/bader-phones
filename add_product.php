<?php
// ============================================================================
// بدر للهواتف / Bader Phones - إضافة منتج ومواصفات تقنية متقدمة (add_product.php)
// Features: RBAC Guard ('admin' or 'partner' only), Persistent Bilingual Data
// (name_ar/en, desc_ar/en), Dynamic Category-Specific Tech Specs (Phones,
// Laptops, iPads, etc. including GPU), Visual Color Picker & Eyedropper Tool,
// 4 Image URLs with Live Preview, PDO utf8mb4 Insert
// ============================================================================

require_once __DIR__ . '/auth.php';

// RBAC Guard: Only 'admin' and 'partner' allowed
checkRole(['admin', 'partner'], 'login.php', 'index.php');

$pdo = getDBConnection();
$currentUser = getCurrentUser();
$currentRole = getCurrentRole();

$successMsg = null;
$errorMsg = null;
$newProductId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nameAr = trim($_POST['name_ar'] ?? '');
    $nameEn = trim($_POST['name_en'] ?? '');
    $category = trim($_POST['category'] ?? 'phones');
    $price = (float)($_POST['price'] ?? 0);
    $stock = max(0, (int)($_POST['stock'] ?? 10));

    // Structured specs
    $ram = trim($_POST['ram'] ?? '');
    $storage = trim($_POST['storage'] ?? '');
    $battery = trim($_POST['battery'] ?? '');
    $camera = trim($_POST['camera'] ?? '');
    $processor = trim($_POST['processor'] ?? '');
    $graphicsCard = trim($_POST['graphics_card'] ?? '');
    $screenSize = trim($_POST['screen_size'] ?? '');

    // Colors JSON payload
    $colorsRaw = trim($_POST['colors'] ?? '');
    $colorsToSave = null;
    if (!empty($colorsRaw)) {
        $decoded = json_decode($colorsRaw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $colorsToSave = json_encode($decoded, JSON_UNESCAPED_UNICODE);
        }
    }

    // Bilingual descriptions
    $descAr = trim($_POST['description_ar'] ?? '');
    $descEn = trim($_POST['description_en'] ?? '');

    // 4 Images
    $defaultLogo = 'assets/logo.png';
    $image1 = trim($_POST['image1'] ?? '') ?: $defaultLogo;
    $image2 = trim($_POST['image2'] ?? '') ?: $image1;
    $image3 = trim($_POST['image3'] ?? '') ?: $image1;
    $image4 = trim($_POST['image4'] ?? '') ?: $image1;

    // Backward compatibility general name/description
    $generalName = !empty($nameAr) ? ($nameAr . (!empty($nameEn) ? " ($nameEn)" : '')) : $nameEn;
    $generalDesc = !empty($descAr) ? $descAr : $descEn;

    if (empty($nameAr) && empty($nameEn)) {
        $errorMsg = "يرجى كتابة اسم المنتج بالعربية أو الإنجليزية على الأقل.";
    } elseif ($price <= 0) {
        $errorMsg = "يرجى تحديد سعر صالح أكبر من صفر.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO `products` (
                    `name_ar`, `name_en`, `name`, 
                    `description_ar`, `description_en`, `description`, 
                    `price`, `stock`, `category`, 
                    `ram`, `storage`, `battery`, `camera`, `processor`, `graphics_card`, `screen_size`, `colors`, 
                    `image1`, `image2`, `image3`, `image4`, `image`
                ) VALUES (
                    ?, ?, ?, 
                    ?, ?, ?, 
                    ?, ?, ?, 
                    ?, ?, ?, ?, ?, ?, ?, ?, 
                    ?, ?, ?, ?, ?
                )
            ");
            $stmt->execute([
                $nameAr ?: $nameEn,
                $nameEn ?: $nameAr,
                $generalName,
                $descAr,
                $descEn,
                $generalDesc,
                $price,
                $stock,
                $category,
                $ram,
                $storage,
                $battery,
                $camera,
                $processor,
                $graphicsCard,
                $screenSize,
                $colorsToSave,
                $image1,
                $image2,
                $image3,
                $image4,
                $image1
            ]);
            $newProductId = $pdo->lastInsertId();
            $successMsg = "تم حفظ ونشر المنتج (" . htmlspecialchars($nameAr ?: $nameEn) . ") بكافة مواصفاته وألوانه بنجاح!";
        } catch (PDOException $e) {
            error_log("Add Product Error: " . $e->getMessage());
            $errorMsg = "حدث خطأ أثناء حفظ المنتج: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="page-title" data-ar="إضافة منتج ومواصفات تقنية | بدر للهواتف" data-en="Add Product & Tech Specs | Bader Phones">إضافة منتج ومواصفات تقنية | بدر للهواتف</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #ff6600;
            --primary-dark: #e65c00;
            --primary-soft: rgba(255, 102, 0, 0.08);
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --header-bg: #ffffff;
            --text-color: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --input-bg: #ffffff;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            --radius-md: 10px;
            --radius-lg: 16px;
        }

        body.dark-mode {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --header-bg: #1e293b;
            --text-color: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --input-bg: #0f172a;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', 'Plus Jakarta Sans', sans-serif;
            transition: background-color 0.25s ease, color 0.25s ease, border-color 0.25s ease;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: var(--header-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .brand-logo {
            height: 38px;
            width: auto;
            object-fit: contain;
        }
        .brand-name {
            font-size: 20px;
            font-weight: 900;
            color: var(--primary-color);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .control-btn {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .control-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .nav-link-btn {
            background: var(--primary-soft);
            color: var(--primary-color);
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            border: 1px solid rgba(255,102,0,0.2);
        }
        .nav-link-btn:hover {
            background: var(--primary-color);
            color: #ffffff;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
            width: 100%;
            flex: 1;
        }

        .card-form {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 35px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }

        .form-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
            flex-wrap: wrap;
            gap: 10px;
        }
        .form-title {
            font-size: 22px;
            font-weight: 900;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .role-tag {
            background: var(--primary-soft);
            color: var(--primary-color);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
            border: 1px solid rgba(255, 102, 0, 0.2);
        }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .alert-success {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .form-section-card {
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 22px;
            margin-bottom: 25px;
        }
        .section-heading {
            font-size: 16px;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 8px;
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        .form-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-label {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-color);
        }
        .form-label-hint {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 400;
        }
        .form-input, .form-select, .form-textarea {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            width: 100%;
        }
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 102, 0, 0.15);
        }
        .form-textarea {
            resize: vertical;
            min-height: 85px;
        }

        /* Color Picker & Eyedropper Styles */
        .color-tool-box {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .color-inputs-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .color-picker-circle-wrap {
            position: relative;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--border-color);
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .color-picker-native {
            position: absolute;
            top: -10px;
            left: -10px;
            width: 64px;
            height: 64px;
            cursor: pointer;
            border: none;
            background: transparent;
        }
        .color-hex-input {
            width: 105px;
            text-transform: uppercase;
            font-family: monospace;
            font-weight: 700;
        }
        .btn-eyedropper {
            background: var(--primary-soft);
            color: var(--primary-color);
            border: 1px solid rgba(255, 102, 0, 0.3);
            padding: 9px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-eyedropper:hover {
            background: var(--primary-color);
            color: #ffffff;
        }
        .btn-add-color {
            background: #0f172a;
            color: #ffffff;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-add-color:hover {
            background: var(--primary-color);
        }
        body.dark-mode .btn-add-color {
            background: #334155;
        }
        body.dark-mode .btn-add-color:hover {
            background: var(--primary-color);
        }

        /* Preset color buttons */
        .preset-colors-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .preset-badge {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .preset-badge:hover {
            border-color: var(--primary-color);
            transform: translateY(-1px);
        }
        .preset-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 1px solid rgba(0,0,0,0.2);
            display: inline-block;
        }

        /* Selected Colors Chips */
        .selected-colors-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            min-height: 38px;
            padding: 10px;
            background: var(--input-bg);
            border-radius: 8px;
            border: 1px dashed var(--border-color);
        }
        .color-chip {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 4px 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .color-chip-circle {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 1px solid rgba(0,0,0,0.15);
            flex-shrink: 0;
        }
        .color-chip-remove {
            color: #ef4444;
            cursor: pointer;
            font-weight: 900;
            margin-right: 2px;
        }
        .color-chip-remove:hover {
            transform: scale(1.2);
        }

        /* 4-Image Inputs & Previews */
        .image-inputs-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        .image-item-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .preview-box {
            height: 105px;
            background: var(--input-bg);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px dashed var(--border-color);
            position: relative;
        }
        .preview-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Submit Button */
        .btn-submit {
            background: var(--primary-color);
            color: #ffffff;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(255, 102, 0, 0.25);
        }
        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(255, 102, 0, 0.35);
        }

        footer {
            background: var(--header-bg);
            border-top: 1px solid var(--border-color);
            padding: 20px;
            text-align: center;
            margin-top: 40px;
            font-size: 13px;
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            .form-grid-2, .form-grid-3, .image-inputs-grid {
                grid-template-columns: 1fr;
            }
            .card-form {
                padding: 20px;
            }
            .color-inputs-row {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>

    <header>
        <a href="index.php" class="brand">
            <img src="assets/logo.png" alt="بدر للهواتف" class="brand-logo" onerror="this.style.display='none'">
            <span class="brand-name" data-ar="بدر للهواتف" data-en="Bader Phones">بدر للهواتف</span>
        </a>

        <div class="header-actions">
            <button type="button" class="control-btn" id="lang-btn" onclick="toggleLanguage()" title="Change Language">
                🌐 <span id="lang-btn-text">EN</span>
            </button>
            <button type="button" class="control-btn" id="dark-mode-btn" onclick="toggleDarkMode()" title="Toggle Dark Mode">
                <span id="dark-mode-icon">🌙</span>
            </button>
            <?php if ($currentRole === 'admin'): ?>
                <a href="admin/dashboard.php" class="nav-link-btn" data-ar="لوحة التحكم" data-en="Dashboard">لوحة التحكم ⚙️</a>
            <?php else: ?>
                <a href="partner/dashboard.php" class="nav-link-btn" data-ar="بوابة الشريك" data-en="Partner Portal">بوابة الشريك 📦</a>
            <?php endif; ?>
            <a href="index.php" class="nav-link-btn" data-ar="المتجر" data-en="Store">المتجر 🏪</a>
        </div>
    </header>

    <main class="container">
        <div class="card-form">
            <div class="form-header">
                <h1 class="form-title">
                    <span>➕</span>
                    <span data-ar="إضافة جهاز وتخزين المواصفات الكاملة والألوان" data-en="Add Device, Tech Specs & Colors">إضافة جهاز وتخزين المواصفات الكاملة والألوان</span>
                </h1>
                <span class="role-tag">
                    👤 <?= htmlspecialchars($currentUser['name'] ?? 'User') ?> (<?= ucfirst($currentRole) ?>)
                </span>
            </div>

            <?php if ($successMsg): ?>
                <div class="alert alert-success">
                    <div>✅ <?= $successMsg ?></div>
                    <?php if ($newProductId): ?>
                        <a href="product.php?id=<?= $newProductId ?>" target="_blank" style="color: #15803d; font-weight: 800; text-decoration: underline;" data-ar="معاينة صفحة المنتج ↗" data-en="View Product Page ↗">
                            معاينة صفحة المنتج ↗
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMsg): ?>
                <div class="alert alert-error">
                    ⚠️ <?= $errorMsg ?>
                </div>
            <?php endif; ?>

            <form action="add_product.php" method="POST" id="addProductForm">
                <!-- 1. الأساسيات والأسماء ثنائية اللغة -->
                <div class="form-section-card">
                    <div class="section-heading">
                        <span>📝</span>
                        <span data-ar="1. البيانات الأساسية والأسماء ثنائية اللغة" data-en="1. Basic Info & Bilingual Names">1. البيانات الأساسية والأسماء ثنائية اللغة</span>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" data-ar="اسم المنتج بالعربية *" data-en="Product Name (Arabic) *">اسم المنتج بالعربية *</label>
                            <input type="text" name="name_ar" class="form-input" required placeholder="مثال: آيفون 18 برو ماكس">
                        </div>

                        <div class="form-group">
                            <label class="form-label" data-ar="اسم المنتج بالإنجليزية *" data-en="Product Name (English) *">اسم المنتج بالإنجليزية *</label>
                            <input type="text" name="name_en" class="form-input" required placeholder="e.g. iPhone 18 Pro Max">
                        </div>

                        <div class="form-group">
                            <label class="form-label" data-ar="التصنيف والقسم *" data-en="Category *">التصنيف والقسم *</label>
                            <select name="category" id="categorySelect" class="form-select" onchange="handleCategoryChange()">
                                <option value="phones" selected>هواتف ذكية (Smartphones)</option>
                                <option value="laptops">لابتوبات وكمبيوتر (Laptops)</option>
                                <option value="ipads">أجهزة لوحية وآيباد (iPads & Tablets)</option>
                                <option value="accessories">اكسسوارات وملحقات (Accessories)</option>
                                <option value="photography">كاميرات ومعدات تصوير (Photography)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" data-ar="السعر بالريال العُماني (OMR) *" data-en="Price (OMR) *">السعر بالريال العُماني (OMR) *</label>
                            <input type="number" step="0.001" name="price" class="form-input" required placeholder="مثال: 549.000">
                        </div>

                        <div class="form-group">
                            <label class="form-label" data-ar="الكمية المتوفرة بالمخزون" data-en="Stock Quantity">الكمية المتوفرة بالمخزون</label>
                            <input type="number" name="stock" class="form-input" value="10" min="0">
                        </div>
                    </div>
                </div>

                <!-- 2. المواصفات التقنية الديناميكية بحسب التصنيف -->
                <div class="form-section-card">
                    <div class="section-heading">
                        <div>
                            <span>⚙️</span>
                            <span data-ar="2. المواصفات التقنية المهيكلة (بحسب نوع القسم)" data-en="2. Structured Technical Specs (Category-Driven)">2. المواصفات التقنية المهيكلة (بحسب نوع القسم)</span>
                        </div>
                        <span id="categoryBadgeIndicator" style="font-size: 11px; background: var(--input-bg); border: 1px solid var(--border-color); padding: 3px 8px; border-radius: 6px; color: var(--text-muted);">هواتف ذكية</span>
                    </div>

                    <div class="form-grid-3" id="specsGrid">
                        <!-- RAM -->
                        <div class="form-group" id="groupRam">
                            <label class="form-label" id="labelRam" data-ar="الذاكرة العشوائية (RAM)" data-en="Memory (RAM)">الذاكرة العشوائية (RAM)</label>
                            <input type="text" name="ram" id="inputRam" class="form-input" placeholder="مثال: 16GB">
                        </div>

                        <!-- Storage -->
                        <div class="form-group" id="groupStorage">
                            <label class="form-label" id="labelStorage" data-ar="سعة التخزين (Storage)" data-en="Storage Capacity">سعة التخزين (Storage)</label>
                            <input type="text" name="storage" id="inputStorage" class="form-input" placeholder="مثال: 512GB أو 1TB">
                        </div>

                        <!-- Processor -->
                        <div class="form-group" id="groupProcessor">
                            <label class="form-label" id="labelProcessor" data-ar="المعالج (Processor)" data-en="Processor / Chip">المعالج (Processor)</label>
                            <input type="text" name="processor" id="inputProcessor" class="form-input" placeholder="مثال: Snapdragon 8 Gen 5 أو A19 Pro">
                        </div>

                        <!-- Graphics Card (GPU) -->
                        <div class="form-group" id="groupGpu">
                            <label class="form-label" id="labelGpu" data-ar="كرت الشاشة (GPU)" data-en="Graphics Card (GPU)">كرت الشاشة (GPU)</label>
                            <input type="text" name="graphics_card" id="inputGpu" class="form-input" placeholder="مثال: RTX 4070 8GB أو Apple 40-Core GPU">
                        </div>

                        <!-- Battery -->
                        <div class="form-group" id="groupBattery">
                            <label class="form-label" id="labelBattery" data-ar="سعة البطارية (Battery)" data-en="Battery Capacity">سعة البطارية (Battery)</label>
                            <input type="text" name="battery" id="inputBattery" class="form-input" placeholder="مثال: 5000mAh أو 100Wh">
                        </div>

                        <!-- Camera -->
                        <div class="form-group" id="groupCamera">
                            <label class="form-label" id="labelCamera" data-ar="الكاميرا (Camera)" data-en="Camera System">الكاميرا (Camera)</label>
                            <input type="text" name="camera" id="inputCamera" class="form-input" placeholder="مثال: 200MP + 50MP + 12MP">
                        </div>

                        <!-- Screen Size -->
                        <div class="form-group" id="groupScreen">
                            <label class="form-label" id="labelScreen" data-ar="حجم ونوع الشاشة" data-en="Screen Size / Display">حجم ونوع الشاشة</label>
                            <input type="text" name="screen_size" id="inputScreen" class="form-input" placeholder="مثال: 6.8 بوصة Dynamic AMOLED 2X">
                        </div>
                    </div>
                </div>

                <!-- 3. ألوان المنتج وأداة القطارة / Color Picker -->
                <div class="form-section-card">
                    <div class="section-heading">
                        <div>
                            <span>🎨</span>
                            <span data-ar="3. خيارات ألوان المنتج (أداة القطارة واللوحة الذكية)" data-en="3. Product Colors & Interactive Color Picker">3. خيارات ألوان المنتج (أداة القطارة واللوحة الذكية)</span>
                        </div>
                        <span style="font-size: 11px; color: var(--text-muted);" data-ar="تظهر كدوائر تفاعلية أنيقة للمشتري" data-en="Renders as clickable swatches on product page">تظهر كدوائر تفاعلية أنيقة للمشتري</span>
                    </div>

                    <div class="color-tool-box">
                        <div class="color-inputs-row">
                            <!-- Visual Color Circle Picker -->
                            <div class="color-picker-circle-wrap" id="pickerCircleWrap" title="انقر لاختيار اللون / Pick Color">
                                <input type="color" id="nativeColorPicker" class="color-picker-native" value="#1c1c1e" oninput="syncColorInput(this.value)">
                            </div>

                            <!-- Hex Input -->
                            <input type="text" id="hexColorInput" class="form-input color-hex-input" value="#1C1C1E" maxlength="7" oninput="syncColorInput(this.value)">

                            <!-- Eyedropper API Button -->
                            <button type="button" class="btn-eyedropper" onclick="openEyeDropper()" id="eyedropperBtn" title="استخراج اللون من أي مكان على الشاشة أو الصور">
                                <span>🎯</span>
                                <span data-ar="القطارة (EyeDropper)" data-en="EyeDropper">القطارة (EyeDropper)</span>
                            </button>

                            <!-- Arabic Color Name -->
                            <input type="text" id="colorNameArInput" class="form-input" style="flex: 1; min-width: 140px;" placeholder="اسم اللون بالعربية (مثال: تيتانيوم أسود)">

                            <!-- English Color Name -->
                            <input type="text" id="colorNameEnInput" class="form-input" style="flex: 1; min-width: 140px;" placeholder="Color name in English (e.g. Black Titanium)">

                            <!-- Add Button -->
                            <button type="button" class="btn-add-color" onclick="addColorChip()">
                                <span>➕</span>
                                <span data-ar="إضافة اللون" data-en="Add Color">إضافة اللون</span>
                            </button>
                        </div>

                        <!-- Quick Presets -->
                        <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-top: 4px;" data-ar="ألوان مقترحة سريعة للهواتف والأجهزة:" data-en="Popular Device Finishes:">
                            ألوان مقترحة سريعة للهواتف والأجهزة:
                        </div>
                        <div class="preset-colors-row">
                            <span class="preset-badge" onclick="applyPreset('#1C1C1E', 'تيتانيوم أسود', 'Black Titanium')">
                                <span class="preset-dot" style="background:#1C1C1E;"></span> تيتانيوم أسود
                            </span>
                            <span class="preset-badge" onclick="applyPreset('#C2BCAD', 'تيتانيوم صحراوي', 'Desert Titanium')">
                                <span class="preset-dot" style="background:#C2BCAD;"></span> تيتانيوم صحراوي
                            </span>
                            <span class="preset-badge" onclick="applyPreset('#9A958D', 'تيتانيوم طبيعي', 'Natural Titanium')">
                                <span class="preset-dot" style="background:#9A958D;"></span> تيتانيوم طبيعي
                            </span>
                            <span class="preset-badge" onclick="applyPreset('#F2F2F2', 'تيتانيوم أبيض', 'White Titanium')">
                                <span class="preset-dot" style="background:#F2F2F2;"></span> تيتانيوم أبيض
                            </span>
                            <span class="preset-badge" onclick="applyPreset('#2E3033', 'أسود فلكي', 'Space Black')">
                                <span class="preset-dot" style="background:#2E3033;"></span> أسود فلكي
                            </span>
                            <span class="preset-badge" onclick="applyPreset('#D8D9DD', 'فضي كلاسيكي', 'Silver')">
                                <span class="preset-dot" style="background:#D8D9DD;"></span> فضي
                            </span>
                            <span class="preset-badge" onclick="applyPreset('#385A4F', 'أخضر زمردي', 'Green')">
                                <span class="preset-dot" style="background:#385A4F;"></span> أخضر
                            </span>
                            <span class="preset-badge" onclick="applyPreset('#BE9182', 'ذهبي وردي', 'Pink Gold')">
                                <span class="preset-dot" style="background:#BE9182;"></span> ذهبي وردي
                            </span>
                            <span class="preset-badge" onclick="applyPreset('#B89F72', 'تيتانيوم أصفر', 'Titanium Yellow')">
                                <span class="preset-dot" style="background:#B89F72;"></span> تيتانيوم أصفر
                            </span>
                        </div>

                        <!-- Selected Colors Container -->
                        <div style="font-size: 13px; font-weight: 700; margin-top: 6px;">
                            <span data-ar="الألوان المضافة لهذا الجهاز:" data-en="Assigned Colors for this device:">الألوان المضافة لهذا الجهاز:</span>
                            <span id="colorsCount" style="color: var(--primary-color);">(0 ألوان)</span>
                        </div>
                        <div class="selected-colors-wrap" id="selectedColorsWrap">
                            <span style="color: var(--text-muted); font-size: 12px;" id="noColorsPlaceholder" data-ar="لم يتم إضافة ألوان بعد. استخدم الأداة أعلاه أو اختر من الألوان المقترحة." data-en="No colors added yet. Use the picker above or select a preset.">لم يتم إضافة ألوان بعد. استخدم الأداة أعلاه أو اختر من الألوان المقترحة.</span>
                        </div>

                        <!-- Hidden Input for Form Submission -->
                        <input type="hidden" name="colors" id="colorsJsonInput" value="">
                    </div>
                </div>

                <!-- 4. الوصف والمميزات باللغتين -->
                <div class="form-section-card">
                    <div class="section-heading">
                        <span>📄</span>
                        <span data-ar="4. الوصف والمميزات باللغتين" data-en="4. Bilingual Descriptions">4. الوصف والمميزات باللغتين</span>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label class="form-label" data-ar="الوصف بالعربية" data-en="Description (Arabic)">الوصف بالعربية</label>
                            <textarea name="description_ar" class="form-textarea" placeholder="اكتب وصفاً جذاباً للهاتف أو الجهاز بالعربية..."></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" data-ar="الوصف بالإنجليزية" data-en="Description (English)">الوصف بالإنجليزية</label>
                            <textarea name="description_en" class="form-textarea" placeholder="Write a compelling product description in English..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- 5. معرض الصور الأربع -->
                <div class="form-section-card">
                    <div class="section-heading">
                        <span>🖼️</span>
                        <span data-ar="5. معرض صور المنتج (4 صور تفاعلية)" data-en="5. Product Gallery (4 Interactive Images)">5. معرض صور المنتج (4 صور تفاعلية)</span>
                    </div>

                    <div class="image-inputs-grid">
                        <!-- Image 1 (Main) -->
                        <div class="image-item-card">
                            <label class="form-label" data-ar="الصورة 1 (الرئيسية)" data-en="Image 1 (Main)">الصورة 1 (الرئيسية)</label>
                            <input type="text" name="image1" id="imgInput1" class="form-input" placeholder="مسار الصورة (مثال: assets/iphone16.png)" oninput="updatePreview(1, this.value)">
                            <div class="preview-box">
                                <img src="assets/logo.png" id="previewImg1" class="preview-img" alt="Preview 1" onerror="this.src='assets/logo.png'">
                            </div>
                        </div>

                        <!-- Image 2 -->
                        <div class="image-item-card">
                            <label class="form-label" data-ar="الصورة 2 (الزاوية الجانبية)" data-en="Image 2 (Side Angle)">الصورة 2 (الزاوية الجانبية)</label>
                            <input type="text" name="image2" id="imgInput2" class="form-input" placeholder="مسار الصورة 2" oninput="updatePreview(2, this.value)">
                            <div class="preview-box">
                                <img src="assets/logo.png" id="previewImg2" class="preview-img" alt="Preview 2" onerror="this.src='assets/logo.png'">
                            </div>
                        </div>

                        <!-- Image 3 -->
                        <div class="image-item-card">
                            <label class="form-label" data-ar="الصورة 3 (الجهة الخلفية)" data-en="Image 3 (Back)">الصورة 3 (الجهة الخلفية)</label>
                            <input type="text" name="image3" id="imgInput3" class="form-input" placeholder="مسار الصورة 3" oninput="updatePreview(3, this.value)">
                            <div class="preview-box">
                                <img src="assets/logo.png" id="previewImg3" class="preview-img" alt="Preview 3" onerror="this.src='assets/logo.png'">
                            </div>
                        </div>

                        <!-- Image 4 -->
                        <div class="image-item-card">
                            <label class="form-label" data-ar="الصورة 4 (صندوق الجهاز أو الملحقات)" data-en="Image 4 (Accessories / Packaging)">الصورة 4 (صندوق الجهاز أو الملحقات)</label>
                            <input type="text" name="image4" id="imgInput4" class="form-input" placeholder="مسار الصورة 4" oninput="updatePreview(4, this.value)">
                            <div class="preview-box">
                                <img src="assets/logo.png" id="previewImg4" class="preview-img" alt="Preview 4" onerror="this.src='assets/logo.png'">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit" data-ar="حفظ ونشر المنتج بكافة المواصفات والألوان 🚀" data-en="Save & Publish Product 🚀">
                    حفظ ونشر المنتج بكافة المواصفات والألوان 🚀
                </button>
            </form>
        </div>
    </main>

    <footer>
        <p data-ar="لوحة إضافة المنتجات المعتمدة &copy; 2026 - بدر للهواتف (Bader Phones)" data-en="Official Add Product Panel &copy; 2026 - Bader Phones">
            لوحة إضافة المنتجات المعتمدة &copy; 2026 - بدر للهواتف (Bader Phones)
        </p>
    </footer>

    <script>
        // ====================================================================
        // A. Dynamic Category-Driven Specs Adjustment
        // ====================================================================
        function handleCategoryChange() {
            const cat = document.getElementById('categorySelect').value;
            const indicator = document.getElementById('categoryBadgeIndicator');
            
            const groupRam = document.getElementById('groupRam');
            const groupStorage = document.getElementById('groupStorage');
            const groupProcessor = document.getElementById('groupProcessor');
            const groupGpu = document.getElementById('groupGpu');
            const groupBattery = document.getElementById('groupBattery');
            const groupCamera = document.getElementById('groupCamera');
            const groupScreen = document.getElementById('groupScreen');

            const labelRam = document.getElementById('labelRam');
            const labelStorage = document.getElementById('labelStorage');
            const labelProcessor = document.getElementById('labelProcessor');
            const labelBattery = document.getElementById('labelBattery');
            const labelCamera = document.getElementById('labelCamera');
            const labelScreen = document.getElementById('labelScreen');

            if (cat === 'phones') {
                if (indicator) indicator.textContent = currentLang === 'ar' ? 'هواتف ذكية' : 'Smartphones';
                groupGpu.style.display = 'none';
                groupCamera.style.display = 'flex';
                groupBattery.style.display = 'flex';
                groupRam.style.display = 'flex';
                groupStorage.style.display = 'flex';
                groupProcessor.style.display = 'flex';
                groupScreen.style.display = 'flex';

                labelBattery.textContent = currentLang === 'ar' ? 'سعة البطارية (mAh)' : 'Battery Capacity (mAh)';
                labelCamera.textContent = currentLang === 'ar' ? 'كاميرا الهاتف (MP)' : 'Camera System (MP)';
                labelProcessor.textContent = currentLang === 'ar' ? 'معالج الهاتف (Chipset)' : 'Processor / Chip';
                document.getElementById('inputRam').placeholder = 'مثال: 12GB أو 16GB';
                document.getElementById('inputStorage').placeholder = 'مثال: 256GB أو 512GB';
            } else if (cat === 'laptops') {
                if (indicator) indicator.textContent = currentLang === 'ar' ? 'أجهزة لابتوب' : 'Laptops';
                groupGpu.style.display = 'flex';
                groupCamera.style.display = 'flex';
                groupBattery.style.display = 'flex';
                groupRam.style.display = 'flex';
                groupStorage.style.display = 'flex';
                groupProcessor.style.display = 'flex';
                groupScreen.style.display = 'flex';

                labelCamera.textContent = currentLang === 'ar' ? 'كاميرا الويب (Webcam)' : 'Webcam Camera';
                labelBattery.textContent = currentLang === 'ar' ? 'قدرة البطارية (Wh / مدة العمل)' : 'Battery Capacity (Wh)';
                labelProcessor.textContent = currentLang === 'ar' ? 'معالج اللابتوب (CPU)' : 'Laptop Processor (CPU)';
                document.getElementById('inputRam').placeholder = 'مثال: 32GB DDR5';
                document.getElementById('inputStorage').placeholder = 'مثال: 1TB NVMe SSD';
                document.getElementById('inputGpu').placeholder = 'مثال: RTX 4070 8GB GDDR6';
            } else if (cat === 'ipads') {
                if (indicator) indicator.textContent = currentLang === 'ar' ? 'آيباد وأجهزة لوحية' : 'iPads & Tablets';
                groupGpu.style.display = 'none';
                groupCamera.style.display = 'flex';
                groupBattery.style.display = 'flex';
                groupRam.style.display = 'flex';
                groupStorage.style.display = 'flex';
                groupProcessor.style.display = 'flex';
                groupScreen.style.display = 'flex';

                labelProcessor.textContent = currentLang === 'ar' ? 'شريحة المعالج (Apple M4 / Bionic)' : 'Tablet Chipset';
                labelScreen.textContent = currentLang === 'ar' ? 'مقاس الشاشة (بوصة)' : 'Screen Size (Inches)';
                document.getElementById('inputRam').placeholder = 'مثال: 8GB أو 16GB';
                document.getElementById('inputStorage').placeholder = 'مثال: 256GB';
            } else {
                // Accessories or Photography
                if (indicator) indicator.textContent = cat === 'photography' 
                    ? (currentLang === 'ar' ? 'كاميرات ومعدات' : 'Photography') 
                    : (currentLang === 'ar' ? 'ملحقات واكسسوارات' : 'Accessories');
                groupGpu.style.display = 'none';
                groupRam.style.display = cat === 'accessories' ? 'none' : 'flex';
                groupStorage.style.display = 'flex';
                groupBattery.style.display = 'flex';
                groupCamera.style.display = 'flex';
                groupProcessor.style.display = 'flex';
                groupScreen.style.display = 'flex';
            }
        }

        // ====================================================================
        // B. Interactive Colors Management & Eyedropper API
        // ====================================================================
        let assignedColors = [];

        function syncColorInput(val) {
            val = val.trim();
            if (!val.startsWith('#')) val = '#' + val;
            val = val.toUpperCase();
            
            document.getElementById('nativeColorPicker').value = val;
            document.getElementById('hexColorInput').value = val;
            document.getElementById('pickerCircleWrap').style.borderColor = val;
        }

        async function openEyeDropper() {
            if (!window.EyeDropper) {
                alert(currentLang === 'ar' 
                    ? 'أداة القطارة المدمجة تتطلب متصفحاً حديثاً مثل Google Chrome أو Microsoft Edge. يمكنك كتابة كود اللون أو اختياره من الدائرة.' 
                    : 'The EyeDropper tool is supported in modern Chrome & Edge. You can manually enter or pick the hex color.');
                return;
            }

            try {
                const eyeDropper = new EyeDropper();
                const result = await eyeDropper.open();
                if (result && result.sRGBHex) {
                    syncColorInput(result.sRGBHex);
                }
            } catch (err) {
                // User canceled eyedropper
            }
        }

        function applyPreset(hex, nameAr, nameEn) {
            syncColorInput(hex);
            document.getElementById('colorNameArInput').value = nameAr;
            document.getElementById('colorNameEnInput').value = nameEn;
        }

        function addColorChip() {
            const hex = document.getElementById('hexColorInput').value.trim().toUpperCase();
            const nameAr = document.getElementById('colorNameArInput').value.trim() || hex;
            const nameEn = document.getElementById('colorNameEnInput').value.trim() || nameAr;

            if (!hex || !/^#[0-9A-F]{6}$/i.test(hex)) {
                alert(currentLang === 'ar' ? 'يرجى إدخال كود لون سداسي عشري صحيح (مثال: #1C1C1E)' : 'Please enter a valid HEX code (e.g. #1C1C1E)');
                return;
            }

            // Check if already added
            if (assignedColors.some(c => c.hex.toUpperCase() === hex.toUpperCase())) {
                alert(currentLang === 'ar' ? 'هذا اللون مضاف مسبقاً لهذا الجهاز!' : 'This color is already in the list!');
                return;
            }

            assignedColors.push({ hex, name_ar: nameAr, name_en: nameEn });
            renderColorChips();

            // Reset inputs for next color
            document.getElementById('colorNameArInput').value = '';
            document.getElementById('colorNameEnInput').value = '';
        }

        function removeColorChip(index) {
            assignedColors.splice(index, 1);
            renderColorChips();
        }

        function renderColorChips() {
            const container = document.getElementById('selectedColorsWrap');
            const hiddenInput = document.getElementById('colorsJsonInput');
            const countEl = document.getElementById('colorsCount');

            if (assignedColors.length === 0) {
                container.innerHTML = `<span style="color: var(--text-muted); font-size: 12px;" id="noColorsPlaceholder">${currentLang === 'ar' ? 'لم يتم إضافة ألوان بعد. استخدم الأداة أعلاه أو اختر من الألوان المقترحة.' : 'No colors added yet.'}</span>`;
                hiddenInput.value = '';
                countEl.textContent = currentLang === 'ar' ? '(0 ألوان)' : '(0 Colors)';
                return;
            }

            hiddenInput.value = JSON.stringify(assignedColors);
            countEl.textContent = currentLang === 'ar' 
                ? `(${assignedColors.length} ألوان)` 
                : `(${assignedColors.length} Colors)`;

            let html = '';
            assignedColors.forEach((c, idx) => {
                const displayName = currentLang === 'ar' ? c.name_ar : c.name_en;
                html += `
                    <div class="color-chip">
                        <span class="color-chip-circle" style="background:${c.hex};"></span>
                        <span>${displayName} <small style="color:var(--text-muted);">(${c.hex})</small></span>
                        <span class="color-chip-remove" onclick="removeColorChip(${idx})" title="حذف">&times;</span>
                    </div>
                `;
            });
            container.innerHTML = html;
        }

        // ====================================================================
        // C. Live Image Previews
        // ====================================================================
        function updatePreview(index, url) {
            const img = document.getElementById(`previewImg${index}`);
            if (img) {
                img.src = url.trim() ? url.trim() : 'assets/logo.png';
            }
        }

        // ====================================================================
        // D. Theme & Language Controls
        // ====================================================================
        let currentLang = localStorage.getItem('bader_lang') || 'ar';

        function initTheme() {
            const savedTheme = localStorage.getItem('bader_theme') || 'light';
            if (savedTheme === 'dark') {
                document.body.classList.add('dark-mode');
                document.getElementById('dark-mode-icon').textContent = '☀️';
            } else {
                document.body.classList.remove('dark-mode');
                document.getElementById('dark-mode-icon').textContent = '🌙';
            }
        }

        function toggleDarkMode() {
            const isDark = document.body.classList.toggle('dark-mode');
            localStorage.setItem('bader_theme', isDark ? 'dark' : 'light');
            document.getElementById('dark-mode-icon').textContent = isDark ? '☀️' : '🌙';
        }

        function applyLanguage(lang) {
            currentLang = lang;
            localStorage.setItem('bader_lang', lang);
            document.cookie = `lang=${lang};path=/;max-age=2592000`;

            const html = document.getElementById('html-root');
            html.setAttribute('lang', lang);
            html.setAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr');

            const langBtnText = document.getElementById('lang-btn-text');
            if (langBtnText) langBtnText.textContent = lang === 'ar' ? 'EN' : 'عربي';

            document.querySelectorAll('[data-ar]').forEach(el => {
                const text = el.getAttribute(`data-${lang}`);
                if (text !== null) el.innerHTML = text;
            });

            handleCategoryChange();
            renderColorChips();
        }

        function toggleLanguage() {
            applyLanguage(currentLang === 'ar' ? 'en' : 'ar');
        }

        document.addEventListener('DOMContentLoaded', () => {
            initTheme();
            applyLanguage(currentLang);
            handleCategoryChange();
        });
    </script>
</body>
</html>
