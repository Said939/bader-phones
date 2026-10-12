<?php
// ============================================================================
// بدر للهواتف / Bader Phones - صفحة تفاصيل المنتج والمواصفات التقنية (product.php)
// Features: Full Bilingual Retrieval (_ar / _en), Category-Driven Tech Specs Grid
// (RAM, Storage, Battery, Camera, Processor, GPU, Screen), Interactive Colors
// Picker (Circular Badges with selection state), 4-Image Interactive Gallery,
// Stepper Qty Controls, Add to Cart with Color & Qty, WhatsApp Direct Checkout
// (Target WhatsApp: 96897447122), Clean Tech White + Tech Orange & Tech Blue Design
// ============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Validate Product ID
$productId = (int)($_GET['id'] ?? 0);
if ($productId <= 0) {
    header("Location: index.php");
    exit;
}

// Fetch all persistent attributes
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: index.php?error=product_not_found");
    exit;
}

// Live Cart Count
$cartCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));
}

// Bilingual attributes with graceful fallbacks
$nameAr = !empty($product['name_ar']) ? $product['name_ar'] : $product['name'];
$nameEn = !empty($product['name_en']) ? $product['name_en'] : $product['name'];
$descAr = !empty($product['description_ar']) ? $product['description_ar'] : ($product['description'] ?? '');
$descEn = !empty($product['description_en']) ? $product['description_en'] : ($product['description'] ?? '');

// Parse colors
$productColors = [];
if (!empty($product['colors'])) {
    $decodedColors = json_decode($product['colors'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedColors)) {
        $productColors = $decodedColors;
    }
}

// Category naming
$catMapAr = [
    'phones'      => 'هواتف ذكية',
    'smartphones' => 'هواتف ذكية',
    'ipads'       => 'أجهزة لوحية (آيباد)',
    'tablets'     => 'أجهزة لوحية (آيباد)',
    'laptops'     => 'أجهزة لابتوب وكمبيوتر',
    'accessories' => 'اكسسوارات وملحقات',
    'photography' => 'كاميرات ومعدات تصوير'
];
$catMapEn = [
    'phones'      => 'Smartphones',
    'smartphones' => 'Smartphones',
    'ipads'       => 'iPads & Tablets',
    'tablets'     => 'iPads & Tablets',
    'laptops'     => 'Laptops & Computers',
    'accessories' => 'Accessories',
    'photography' => 'Photography'
];
$rawCat = strtolower($product['category'] ?? 'phones');
$catNameAr = $catMapAr[$rawCat] ?? 'أجهزة ذكية';
$catNameEn = $catMapEn[$rawCat] ?? 'Smart Devices';

// 4 Images Setup
$defaultImg = 'assets/logo.png';
$image1 = !empty($product['image1']) ? $product['image1'] : (!empty($product['image']) ? $product['image'] : $defaultImg);
$image2 = !empty($product['image2']) ? $product['image2'] : $image1;
$image3 = !empty($product['image3']) ? $product['image3'] : $image1;
$image4 = !empty($product['image4']) ? $product['image4'] : $image1;
$galleryImages = [$image1, $image2, $image3, $image4];

// Price and Stock
$prodPrice = (float)$product['price'];
$prodStock = (int)($product['stock'] ?? 10);

// Default initial WhatsApp text
$firstColor = !empty($productColors[0]) ? $productColors[0] : null;
$waColorTxt = $firstColor ? "\n🎨 اللون: " . ($firstColor['name_ar'] ?? $firstColor['hex']) . " (" . $firstColor['hex'] . ")" : "";

$waText = "مرحباً بدر للهواتف، أود الاستفسار وطلب الجهاز التالي من الموقع:\n"
        . "━━━━━━━━━━━━━━━━━━━━\n"
        . "📱 اسم المنتج: " . $nameAr . " (" . $nameEn . ")\n"
        . "💰 السعر: " . number_format($prodPrice, 3) . " ر.ع\n"
        . "🆔 كود المنتج: #" . $productId
        . $waColorTxt . "\n";

if (!empty($product['ram']) && $product['ram'] !== '-') $waText .= "💾 الرام: " . $product['ram'] . "\n";
if (!empty($product['storage']) && $product['storage'] !== '-') $waText .= "💽 السعة: " . $product['storage'] . "\n";
if (!empty($product['processor']) && $product['processor'] !== '-') $waText .= "⚡ المعالج: " . $product['processor'] . "\n";
if (!empty($product['graphics_card']) && $product['graphics_card'] !== '-') $waText .= "🎮 كرت الشاشة: " . $product['graphics_card'] . "\n";

$waText .= "━━━━━━━━━━━━━━━━━━━━\n"
        . "يرجى تأكيد التوفر والتوصيل في سلطنة عُمان.";
$waUrl = "https://wa.me/96897447122?text=" . urlencode($waText);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="page-title" data-ar="<?= htmlspecialchars($nameAr) ?> | بدر للهواتف" data-en="<?= htmlspecialchars($nameEn) ?> | Bader Phones"><?= htmlspecialchars($nameAr) ?> | بدر للهواتف</title>
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
            --tech-blue: #0A66C2;
            --tech-blue-dark: #004182;
            --tech-blue-soft: rgba(10, 102, 194, 0.08);
            --whatsapp-color: #25D366;
            --whatsapp-dark: #128C7E;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --header-bg: #ffffff;
            --text-color: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --input-bg: #f1f5f9;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            --shadow-hover: 0 10px 30px rgba(255, 102, 0, 0.15);
            --radius-md: 12px;
            --radius-lg: 18px;
        }

        body.dark-mode {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --header-bg: #1e293b;
            --text-color: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --input-bg: #0f172a;
            --primary-soft: rgba(255, 102, 0, 0.15);
            --tech-blue-soft: rgba(10, 102, 194, 0.2);
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.35);
            --shadow-hover: 0 10px 30px rgba(255, 102, 0, 0.25);
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

        /* Header */
        header {
            background: var(--header-bg);
            border-bottom: 2px solid var(--border-color);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
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

        .btn-cart {
            background: var(--primary-soft);
            color: var(--primary-color);
            border: 1px solid rgba(255, 102, 0, 0.3);
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
        }
        .btn-cart:hover {
            background: var(--primary-color);
            color: #ffffff;
        }
        .cart-badge {
            background: var(--primary-color);
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 12px;
            min-width: 18px;
            text-align: center;
        }

        /* Breadcrumb */
        .breadcrumb-box {
            padding: 16px 0 0 0;
            font-size: 13px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .breadcrumb-box a {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 600;
        }
        .breadcrumb-box a:hover {
            color: var(--primary-color);
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            width: 100%;
            flex: 1;
        }

        /* Product Main Details Grid */
        .product-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 36px;
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 35px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            margin: 20px 0 30px 0;
        }

        /* Gallery System (4 Images) */
        .gallery-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .main-img-box {
            background: var(--input-bg);
            border-radius: var(--radius-md);
            height: 380px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid var(--border-color);
            position: relative;
            padding: 20px;
        }
        .main-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }
        .main-img:hover {
            transform: scale(1.06);
        }
        .thumbnails-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        .thumb-box {
            background: var(--input-bg);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            overflow: hidden;
            padding: 6px;
            transition: all 0.2s;
        }
        .thumb-box:hover, .thumb-box.active {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 102, 0, 0.2);
            transform: translateY(-2px);
        }
        .thumb-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Product Info Column */
        .product-meta-col {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .category-pill {
            display: inline-block;
            background: var(--tech-blue-soft);
            color: var(--tech-blue);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
            width: fit-content;
            margin-bottom: 10px;
            border: 1px solid rgba(10, 102, 194, 0.25);
        }
        .product-title-large {
            font-size: 26px;
            font-weight: 900;
            line-height: 1.35;
            margin-bottom: 12px;
            color: var(--text-color);
        }
        .product-price-section {
            display: flex;
            align-items: baseline;
            gap: 6px;
            margin-bottom: 16px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border-color);
        }
        .price-val {
            font-size: 32px;
            font-weight: 900;
            color: var(--primary-color);
        }
        .price-currency {
            font-size: 16px;
            font-weight: 800;
            color: var(--text-muted);
        }

        /* Description Box */
        .product-description-box {
            margin-bottom: 16px;
        }
        .description-title {
            font-size: 14px;
            font-weight: 800;
            color: var(--text-color);
            margin-bottom: 6px;
        }
        .description-text {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.7;
        }

        /* Interactive Colors Section */
        .product-colors-box {
            background: var(--input-bg);
            border-radius: var(--radius-md);
            padding: 16px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }
        .colors-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 13px;
            font-weight: 800;
        }
        .selected-color-name {
            color: var(--primary-color);
            font-weight: 900;
        }
        .colors-swatches-grid {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .color-swatch-badge {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            border: 2px solid transparent;
            padding: 3px;
            background: transparent;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        .color-swatch-badge:hover {
            transform: scale(1.15);
        }
        .color-swatch-badge.active {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px var(--card-bg), 0 0 0 4px var(--primary-color);
            transform: scale(1.15);
        }
        .swatch-circle {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            display: block;
            box-shadow: inset 0 0 0 1px rgba(0,0,0,0.25);
        }

        /* Actions: Quantity Stepper, Add to Cart, WhatsApp Order */
        .order-actions-box {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 10px;
        }
        .qty-row {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .qty-label {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-color);
        }
        .stepper-box {
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--border-color);
            background: var(--input-bg);
            border-radius: 8px;
            overflow: hidden;
        }
        .stepper-btn {
            background: transparent;
            border: none;
            color: var(--text-color);
            width: 36px;
            height: 36px;
            font-size: 18px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .stepper-btn:hover {
            background: var(--primary-soft);
            color: var(--primary-color);
        }
        .stepper-input {
            width: 44px;
            height: 36px;
            border: none;
            background: transparent;
            text-align: center;
            font-weight: 800;
            font-size: 15px;
            color: var(--text-color);
            outline: none;
        }

        .buttons-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .btn-main-action {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 13px 20px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-add-cart-large {
            background: #0f172a;
            color: #ffffff;
            border: 1px solid #0f172a;
        }
        .btn-add-cart-large:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 102, 0, 0.25);
        }
        body.dark-mode .btn-add-cart-large {
            background: #334155;
            border-color: #475569;
        }
        body.dark-mode .btn-add-cart-large:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-whatsapp-large {
            background: var(--primary-color);
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(255, 102, 0, 0.25);
        }
        .btn-whatsapp-large:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 102, 0, 0.35);
        }

        /* 3 Geometric Trust Cards in Tech Blue */
        .trust-banner-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            padding-top: 18px;
            border-top: 1px solid var(--border-color);
            margin-top: 10px;
        }
        .trust-badge-card {
            background: var(--tech-blue-soft);
            border: 1px solid rgba(10, 102, 194, 0.2);
            border-radius: var(--radius-md);
            padding: 12px 10px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .trust-badge-card .t-icon {
            font-size: 20px;
            color: var(--tech-blue);
        }
        .trust-badge-card .t-title {
            font-size: 12px;
            font-weight: 800;
            color: var(--text-color);
        }
        .trust-badge-card .t-sub {
            font-size: 10px;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* Structured Technical Specs Breakdown Section */
        .specs-section {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 35px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
        }
        .specs-section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }
        .specs-header-icon {
            width: 36px;
            height: 36px;
            background: var(--tech-blue-soft);
            color: var(--tech-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 18px;
        }
        .specs-section-title {
            font-size: 20px;
            font-weight: 900;
            color: var(--text-color);
        }

        .specs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }
        .spec-card {
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: transform 0.2s, border-color 0.2s;
        }
        .spec-card:hover {
            border-color: var(--tech-blue);
            transform: translateY(-2px);
        }
        .spec-icon {
            font-size: 22px;
            background: var(--tech-blue-soft);
            color: var(--tech-blue);
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: 1px solid rgba(10, 102, 194, 0.2);
            flex-shrink: 0;
        }
        .spec-meta {
            display: flex;
            flex-direction: column;
        }
        .spec-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 2px;
        }
        .spec-val {
            font-size: 15px;
            font-weight: 800;
            color: var(--text-color);
        }

        /* Floating Toast */
        .toast-container {
            position: fixed;
            bottom: 25px;
            left: 25px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }
        html[dir="ltr"] .toast-container {
            left: auto;
            right: 25px;
        }
        .toast {
            background: #0f172a;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border-right: 4px solid var(--primary-color);
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            transform: translateY(20px);
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: auto;
        }
        html[dir="ltr"] .toast {
            border-right: none;
            border-left: 4px solid var(--primary-color);
        }
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        /* Footer */
        footer {
            background: var(--header-bg);
            border-top: 1px solid var(--border-color);
            padding: 24px;
            margin-top: 50px;
            text-align: center;
            font-size: 13px;
            color: var(--text-muted);
        }

        @media (max-width: 860px) {
            .product-details-grid {
                grid-template-columns: 1fr;
                padding: 20px;
            }
            .main-img-box {
                height: 280px;
            }
            .buttons-grid {
                grid-template-columns: 1fr;
            }
            .specs-grid {
                grid-template-columns: 1fr;
            }
            .trust-banner-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Header -->
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
            <a href="cart.php" class="btn-cart">
                <span data-ar="السلة" data-en="Cart">السلة</span> 🛒
                <span class="cart-badge" id="cartBadge"><?= (int)$cartCount ?></span>
            </a>
        </div>
    </header>

    <main class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb-box">
            <a href="index.php" data-ar="الرئيسية" data-en="Home">الرئيسية</a>
            <span>/</span>
            <span id="breadcrumbCat" data-ar="<?= htmlspecialchars($catNameAr) ?>" data-en="<?= htmlspecialchars($catNameEn) ?>"><?= htmlspecialchars($catNameAr) ?></span>
            <span>/</span>
            <span id="breadcrumbTitle" data-ar="<?= htmlspecialchars($nameAr) ?>" data-en="<?= htmlspecialchars($nameEn) ?>"><?= htmlspecialchars($nameAr) ?></span>
        </div>

        <!-- Main Product Section -->
        <div class="product-details-grid">
            <!-- 1. Gallery System (4 Images) -->
            <div class="gallery-container">
                <div class="main-img-box">
                    <img src="<?= htmlspecialchars($image1) ?>" id="mainDisplayImg" class="main-img" alt="<?= htmlspecialchars($nameAr) ?>" onerror="this.src='assets/logo.png'">
                </div>

                <div class="thumbnails-row">
                    <?php foreach ($galleryImages as $index => $img): ?>
                        <div class="thumb-box <?= $index === 0 ? 'active' : '' ?>" onclick="switchImage('<?= htmlspecialchars(addslashes($img)) ?>', this)">
                            <img src="<?= htmlspecialchars($img) ?>" class="thumb-img" alt="Thumbnail <?= $index + 1 ?>" onerror="this.src='assets/logo.png'">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 2. Product Information & Actions -->
            <div class="product-meta-col">
                <div>
                    <span class="category-pill" id="catPill" data-ar="<?= htmlspecialchars($catNameAr) ?>" data-en="<?= htmlspecialchars($catNameEn) ?>"><?= htmlspecialchars($catNameAr) ?></span>
                    
                    <h1 class="product-title-large" id="productTitle" data-ar="<?= htmlspecialchars($nameAr) ?>" data-en="<?= htmlspecialchars($nameEn) ?>">
                        <?= htmlspecialchars($nameAr) ?>
                    </h1>

                    <div class="product-price-section">
                        <span class="price-val"><?= number_format($prodPrice, 3) ?></span>
                        <span class="price-currency" data-ar="ر.ع" data-en="OMR">ر.ع</span>
                    </div>

                    <div class="product-description-box">
                        <h3 class="description-title" data-ar="المواصفات والتفاصيل:" data-en="Specifications & Details:">المواصفات والتفاصيل:</h3>
                        <p class="description-text" id="productDesc" data-ar="<?= htmlspecialchars($descAr) ?>" data-en="<?= htmlspecialchars($descEn) ?>">
                            <?= htmlspecialchars($descAr) ?>
                        </p>
                    </div>

                    <!-- Interactive Color Swatches Picker -->
                    <?php if (!empty($productColors)): ?>
                        <div class="product-colors-box">
                            <div class="colors-header">
                                <span data-ar="🎨 اللون المختار:" data-en="🎨 Selected Color:">🎨 اللون المختار:</span>
                                <span class="selected-color-name" id="selectedColorNameDisplay">
                                    <?= htmlspecialchars(!empty($productColors[0]['name_ar']) ? $productColors[0]['name_ar'] : $productColors[0]['hex']) ?>
                                </span>
                            </div>
                            <div class="colors-swatches-grid" id="colorSwatchesContainer">
                                <?php foreach ($productColors as $index => $c): ?>
                                    <?php 
                                        $cHex = $c['hex'] ?? '#000000';
                                        $cNameAr = $c['name_ar'] ?? $cHex;
                                        $cNameEn = $c['name_en'] ?? $cNameAr;
                                    ?>
                                    <button type="button" 
                                            class="color-swatch-badge <?= $index === 0 ? 'active' : '' ?>" 
                                            data-hex="<?= htmlspecialchars($cHex) ?>" 
                                            data-name-ar="<?= htmlspecialchars($cNameAr) ?>" 
                                            data-name-en="<?= htmlspecialchars($cNameEn) ?>" 
                                            title="<?= htmlspecialchars($cNameAr) ?> (<?= htmlspecialchars($cHex) ?>)"
                                            onclick="selectProductColor(this, '<?= htmlspecialchars(addslashes($cHex)) ?>', '<?= htmlspecialchars(addslashes($cNameAr)) ?>', '<?= htmlspecialchars(addslashes($cNameEn)) ?>')">
                                        <span class="swatch-circle" style="background-color: <?= htmlspecialchars($cHex) ?>;"></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Actions: Stepper Quantity, Add to Cart, WhatsApp Order -->
                <div class="order-actions-box">
                    <div class="qty-row">
                        <label class="qty-label" data-ar="الكمية المطلوبة:" data-en="Quantity:">الكمية المطلوبة:</label>
                        <div class="stepper-box">
                            <button type="button" class="stepper-btn" onclick="stepQty(-1)">−</button>
                            <input type="text" id="productQty" class="stepper-input" value="1" readonly>
                            <button type="button" class="stepper-btn" onclick="stepQty(1)">+</button>
                        </div>
                    </div>

                    <div class="buttons-grid">
                        <button type="button" class="btn-main-action btn-add-cart-large" onclick="handleAddCart()">
                            <span>🛒</span>
                            <span data-ar="إضافة إلى السلة" data-en="Add to Cart">إضافة إلى السلة</span>
                        </button>

                        <a href="<?= $waUrl ?>" target="_blank" class="btn-main-action btn-whatsapp-large" id="whatsappOrderBtn">
                            <span>💬</span>
                            <span data-ar="أطلب الآن بالواتساب" data-en="Order via WhatsApp">أطلب الآن بالواتساب</span>
                        </a>
                    </div>

                    <!-- 3 Geometric Trust Cards in Tech Blue -->
                    <div class="trust-banner-cards">
                        <div class="trust-badge-card">
                            <span class="t-icon">🛡️</span>
                            <span class="t-title" data-ar="أجهزة أصلية 100%" data-en="100% Genuine">أجهزة أصلية 100%</span>
                            <span class="t-sub" data-ar="فحص وجودة معتمدة" data-en="Certified Quality">فحص وجودة معتمدة</span>
                        </div>
                        <div class="trust-badge-card">
                            <span class="t-icon">⚡</span>
                            <span class="t-title" data-ar="توصيل سريع بعُمان" data-en="Fast Oman Delivery">توصيل سريع بعُمان</span>
                            <span class="t-sub" data-ar="شحن آمن لكافة المحافظات" data-en="All Governorates">شحن آمن لكافة المحافظات</span>
                        </div>
                        <div class="trust-badge-card">
                            <span class="t-icon">✅</span>
                            <span class="t-title" data-ar="ضمان معتمد فوري" data-en="Warranty Included">ضمان معتمد فوري</span>
                            <span class="t-sub" data-ar="خدمة دعم ما بعد البيع" data-en="After-sales Support">خدمة دعم ما بعد البيع</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Structured Technical Specs Breakdown Section -->
        <div class="specs-section">
            <div class="specs-section-header">
                <div class="specs-header-icon">⚙️</div>
                <h2 class="specs-section-title" data-ar="المواصفات التقنية التفصيلية (للمقارنة والتقييم)" data-en="Detailed Technical Specifications">
                    المواصفات التقنية التفصيلية (للمقارنة والتقييم)
                </h2>
            </div>

            <div class="specs-grid">
                <!-- RAM -->
                <?php if (!empty($product['ram']) && $product['ram'] !== '-'): ?>
                    <div class="spec-card">
                        <div class="spec-icon">💾</div>
                        <div class="spec-meta">
                            <span class="spec-label" data-ar="الذاكرة العشوائية (RAM)" data-en="Memory (RAM)">الذاكرة العشوائية (RAM)</span>
                            <span class="spec-val"><?= htmlspecialchars($product['ram']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Storage -->
                <?php if (!empty($product['storage']) && $product['storage'] !== '-'): ?>
                    <div class="spec-card">
                        <div class="spec-icon">💽</div>
                        <div class="spec-meta">
                            <span class="spec-label" data-ar="سعة التخزين (Storage)" data-en="Storage Capacity">سعة التخزين (Storage)</span>
                            <span class="spec-val"><?= htmlspecialchars($product['storage']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Processor -->
                <?php if (!empty($product['processor']) && $product['processor'] !== '-'): ?>
                    <div class="spec-card">
                        <div class="spec-icon">⚡</div>
                        <div class="spec-meta">
                            <span class="spec-label" data-ar="المعالج (Processor)" data-en="Processor / Chipset">المعالج (Processor)</span>
                            <span class="spec-val"><?= htmlspecialchars($product['processor']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Graphics Card (GPU) -->
                <?php if (!empty($product['graphics_card']) && $product['graphics_card'] !== '-'): ?>
                    <div class="spec-card">
                        <div class="spec-icon">🎮</div>
                        <div class="spec-meta">
                            <span class="spec-label" data-ar="كرت الشاشة (GPU)" data-en="Graphics Card (GPU)">كرت الشاشة (GPU)</span>
                            <span class="spec-val"><?= htmlspecialchars($product['graphics_card']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Battery -->
                <?php if (!empty($product['battery']) && $product['battery'] !== '-'): ?>
                    <div class="spec-card">
                        <div class="spec-icon">🔋</div>
                        <div class="spec-meta">
                            <span class="spec-label" data-ar="البطارية (Battery)" data-en="Battery Capacity">البطارية (Battery)</span>
                            <span class="spec-val"><?= htmlspecialchars($product['battery']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Camera -->
                <?php if (!empty($product['camera']) && $product['camera'] !== '-'): ?>
                    <div class="spec-card">
                        <div class="spec-icon">📸</div>
                        <div class="spec-meta">
                            <span class="spec-label" data-ar="الكاميرا (Camera)" data-en="Camera System">الكاميرا (Camera)</span>
                            <span class="spec-val"><?= htmlspecialchars($product['camera']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Screen Size -->
                <?php if (!empty($product['screen_size']) && $product['screen_size'] !== '-'): ?>
                    <div class="spec-card">
                        <div class="spec-icon">📱</div>
                        <div class="spec-meta">
                            <span class="spec-label" data-ar="الشاشة (Display)" data-en="Display & Screen Size">الشاشة (Display)</span>
                            <span class="spec-val"><?= htmlspecialchars($product['screen_size']) ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Available Colors Overview in Specs -->
                <?php if (!empty($productColors)): ?>
                    <div class="spec-card">
                        <div class="spec-icon">🎨</div>
                        <div class="spec-meta">
                            <span class="spec-label" data-ar="خيارات الألوان المتاحة" data-en="Available Color Finishes">خيارات الألوان المتاحة</span>
                            <div style="display:flex; gap:6px; margin-top:5px; align-items:center;">
                                <?php foreach ($productColors as $c): ?>
                                    <span style="width:14px; height:14px; border-radius:50%; background:<?= htmlspecialchars($c['hex'] ?? '#000') ?>; border:1px solid rgba(0,0,0,0.2); display:inline-block;" title="<?= htmlspecialchars($c['name_ar'] ?? '') ?>"></span>
                                <?php endforeach; ?>
                                <span class="spec-val" style="font-size:12px; margin-right:4px;">(<?= count($productColors) ?> ألوان)</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Category -->
                <div class="spec-card">
                    <div class="spec-icon">🏷️</div>
                    <div class="spec-meta">
                        <span class="spec-label" data-ar="التصنيف والقسم" data-en="Category">التصنيف والقسم</span>
                        <span class="spec-val" id="specCatVal" data-ar="<?= htmlspecialchars($catNameAr) ?>" data-en="<?= htmlspecialchars($catNameEn) ?>"><?= htmlspecialchars($catNameAr) ?></span>
                    </div>
                </div>

                <!-- Stock Available -->
                <div class="spec-card">
                    <div class="spec-icon">📦</div>
                    <div class="spec-meta">
                        <span class="spec-label" data-ar="حالة التوفر" data-en="Availability">حالة التوفر</span>
                        <span class="spec-val" style="color: <?= $prodStock > 0 ? '#16a34a' : '#dc2626' ?>;" data-ar="<?= $prodStock > 0 ? 'متوفر بالمخزون (' . $prodStock . ' قطعة)' : 'نفذت الكمية' ?>" data-en="<?= $prodStock > 0 ? 'In Stock (' . $prodStock . ' units)' : 'Out of Stock' ?>">
                            <?= $prodStock > 0 ? 'متوفر بالمخزون (' . $prodStock . ' قطعة)' : 'نفذت الكمية' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Floating Toast -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Footer -->
    <footer>
        <p data-ar="بدر للهواتف / Bader Phones &copy; 2026 - سلطنة عُمان | هاتف: 96897447122" data-en="Bader Phones &copy; 2026 - Sultanate of Oman | Tel: 96897447122">
            بدر للهواتف / Bader Phones &copy; 2026 - سلطنة عُمان | هاتف: 96897447122
        </p>
    </footer>

    <script>
        const productId = <?= $productId ?>;
        const productPrice = <?= number_format($prodPrice, 3, '.', '') ?>;
        const maxStock = <?= max(1, $prodStock) ?>;
        const productBaseNameAr = <?= json_encode($nameAr, JSON_UNESCAPED_UNICODE) ?>;
        const productBaseNameEn = <?= json_encode($nameEn, JSON_UNESCAPED_UNICODE) ?>;
        const productSpecsList = {
            ram: <?= json_encode($product['ram'] ?? '') ?>,
            storage: <?= json_encode($product['storage'] ?? '') ?>,
            processor: <?= json_encode($product['processor'] ?? '') ?>,
            gpu: <?= json_encode($product['graphics_card'] ?? '') ?>
        };

        let selectedColor = <?= !empty($productColors) ? json_encode($productColors[0], JSON_UNESCAPED_UNICODE) : 'null' ?>;

        // 1. Gallery Switcher Function
        function switchImage(imgSrc, element) {
            const mainImg = document.getElementById('mainDisplayImg');
            if (mainImg) mainImg.src = imgSrc;
            document.querySelectorAll('.thumb-box').forEach(b => b.classList.remove('active'));
            if (element) element.classList.add('active');
        }

        // 2. Color Swatch Selector
        function selectProductColor(btn, hex, nameAr, nameEn) {
            document.querySelectorAll('.color-swatch-badge').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selectedColor = { hex: hex, name_ar: nameAr, name_en: nameEn };

            const nameDisplay = document.getElementById('selectedColorNameDisplay');
            if (nameDisplay) {
                nameDisplay.textContent = currentLang === 'ar' ? nameAr : nameEn;
            }

            updateOrderUrls();
        }

        // 3. Stepper Quantity
        function stepQty(delta) {
            const qtyInput = document.getElementById('productQty');
            if (!qtyInput) return;
            let current = parseInt(qtyInput.value, 10) || 1;
            current = Math.max(1, Math.min(maxStock, current + delta));
            qtyInput.value = current;
            updateOrderUrls();
        }

        // 4. Dynamic WhatsApp URL Updater
        function updateOrderUrls() {
            const qty = document.getElementById('productQty') ? document.getElementById('productQty').value : 1;
            const colorTxt = selectedColor 
                ? (currentLang === 'ar' ? `\n🎨 اللون المختار: ${selectedColor.name_ar} (${selectedColor.hex})` : `\n🎨 Selected Color: ${selectedColor.name_en} (${selectedColor.hex})`)
                : '';
            
            let specsTxt = '';
            if (productSpecsList.ram && productSpecsList.ram !== '-') specsTxt += `\n💾 الرام: ${productSpecsList.ram}`;
            if (productSpecsList.storage && productSpecsList.storage !== '-') specsTxt += `\n💽 السعة: ${productSpecsList.storage}`;
            if (productSpecsList.processor && productSpecsList.processor !== '-') specsTxt += `\n⚡ المعالج: ${productSpecsList.processor}`;
            if (productSpecsList.gpu && productSpecsList.gpu !== '-') specsTxt += `\n🎮 كرت الشاشة: ${productSpecsList.gpu}`;

            let text = currentLang === 'ar'
                ? `مرحباً بدر للهواتف، أود الاستفسار وطلب الجهاز التالي من الموقع:\n━━━━━━━━━━━━━━━━━━━━\n📱 المنتج: ${productBaseNameAr}\n💰 السعر: ${productPrice} ر.ع\n🔢 الكمية: ${qty}${colorTxt}${specsTxt}\n━━━━━━━━━━━━━━━━━━━━\nيرجى تأكيد التوفر والتوصيل في سلطنة عُمان.`
                : `Hello Bader Phones, I would like to order this device:\n━━━━━━━━━━━━━━━━━━━━\n📱 Product: ${productBaseNameEn}\n💰 Price: ${productPrice} OMR\n🔢 Quantity: ${qty}${colorTxt}${specsTxt}\n━━━━━━━━━━━━━━━━━━━━\nPlease confirm availability and delivery in Oman.`;

            const waBtn = document.getElementById('whatsappOrderBtn');
            if (waBtn) {
                waBtn.href = `https://wa.me/96897447122?text=${encodeURIComponent(text)}`;
            }
        }

        // 5. Add to Cart with selected quantity & selected color
        function handleAddCart() {
            const qty = document.getElementById('productQty').value || 1;
            const prodName = currentLang === 'ar' ? productBaseNameAr : productBaseNameEn;
            
            let url = `cart.php?action=add&id=${productId}&qty=${qty}`;
            if (selectedColor) {
                const colorParam = currentLang === 'ar' ? selectedColor.name_ar : selectedColor.name_en;
                url += `&color=${encodeURIComponent(colorParam)}&color_hex=${encodeURIComponent(selectedColor.hex)}`;
            }

            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById('cartBadge');
                    if (badge) badge.textContent = data.cartCount;
                    
                    const colorNote = selectedColor ? ` (${currentLang === 'ar' ? selectedColor.name_ar : selectedColor.name_en})` : '';
                    const msg = currentLang === 'ar'
                        ? `تمت إضافة (${qty}) من <strong>${prodName}${colorNote}</strong> إلى السلة بنجاح!`
                        : `Added (${qty}) <strong>${prodName}${colorNote}</strong> to cart!`;
                    showToast(msg);
                } else {
                    window.location.href = url;
                }
            })
            .catch(() => {
                window.location.href = url;
            });
        }

        // 6. Toast Notifications
        function showToast(message) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.innerHTML = `<span>✅</span> <div>${message}</div>`;
            container.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 2800);
        }

        // 7. Dark Mode & Theme
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

        // 8. Bilingual Language System
        let currentLang = localStorage.getItem('bader_lang') || 'ar';

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

            document.querySelectorAll('.price-currency').forEach(el => {
                el.textContent = lang === 'ar' ? 'ر.ع' : 'OMR';
            });

            if (selectedColor) {
                const nameDisplay = document.getElementById('selectedColorNameDisplay');
                if (nameDisplay) {
                    nameDisplay.textContent = lang === 'ar' ? selectedColor.name_ar : selectedColor.name_en;
                }
            }

            updateOrderUrls();
        }

        function toggleLanguage() {
            applyLanguage(currentLang === 'ar' ? 'en' : 'ar');
        }

        document.addEventListener('DOMContentLoaded', () => {
            initTheme();
            applyLanguage(currentLang);
        });
    </script>
</body>
</html>
