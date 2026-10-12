<?php
// ============================================================================
// بدر للهواتف / Bader Phones - المتجر الإلكتروني الرئيسي المعتمد (index.php)
// Refactored & Optimized by Senior Tech Lead, Business Analyst & Critical Customer
// Palette: Clean Tech White (#ffffff, #f8fafc), Brand Vibrant Orange (#ff6600),
// Tech Blue Accent (#0A66C2).
// Features: Instant Category Filter Tabs, Real-time Autocomplete Search with Colors
// and Specs, Multi-Color Dots Preview, AJAX Add to Cart, WhatsApp Direct Checkout
// (96897447122), Persistent Bilingual System & Dark Mode.
// ============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// Calculate live cart items count
$cartCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));
}

// User session and roles
$currentUser = getCurrentUser();
$currentRole = getCurrentRole();
$isAdmin = isAdmin();
$isPartner = isPartner();

// Fetch active catalog from MySQL with strict UTF-8
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Index products fetch error: " . $e->getMessage());
    $products = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="page-title" data-ar="بدر للهواتف | المتجر الإلكتروني المعتمد" data-en="Bader Phones | Official Tech E-Store">بدر للهواتف | المتجر الإلكتروني المعتمد</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Palette: Clean Tech White, Brand Vibrant Orange, Sharp Tech Blue */
            --brand-orange: #ff6600;
            --brand-orange-dark: #e65c00;
            --brand-orange-soft: rgba(255, 102, 0, 0.08);
            --brand-orange-glow: rgba(255, 102, 0, 0.25);
            
            --tech-blue: #0A66C2;
            --tech-blue-dark: #004182;
            --tech-blue-soft: rgba(10, 102, 194, 0.08);
            --tech-blue-border: rgba(10, 102, 194, 0.22);
            
            --whatsapp-green: #25D366;
            --whatsapp-dark: #128C7E;
            
            --bg-page: #f8fafc;
            --bg-card: #ffffff;
            --bg-header: #ffffff;
            --bg-input: #f1f5f9;
            
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.06);
            --shadow-hover: 0 10px 28px rgba(255, 102, 0, 0.14);
            
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 18px;
        }

        /* Persistent Dark Mode */
        body.dark-mode {
            --bg-page: #0f172a;
            --bg-card: #1e293b;
            --bg-header: #1e293b;
            --bg-input: #0f172a;
            
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #334155;
            
            --tech-blue-soft: rgba(10, 102, 194, 0.2);
            --tech-blue-border: rgba(10, 102, 194, 0.4);
            
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.2);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.3);
            --shadow-hover: 0 10px 28px rgba(255, 102, 0, 0.25);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', 'Plus Jakarta Sans', sans-serif;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease;
        }

        body {
            background-color: var(--bg-page);
            color: var(--text-main);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Header */
        header {
            background-color: var(--bg-header);
            box-shadow: var(--shadow-sm);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 2px solid var(--brand-orange);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .btn-hamburger {
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            width: 42px;
            height: 42px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 5px;
            cursor: pointer;
        }
        .btn-hamburger:hover {
            border-color: var(--brand-orange);
        }
        .btn-hamburger span {
            display: block;
            width: 20px;
            height: 2px;
            background-color: var(--text-main);
            border-radius: 2px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .brand-logo {
            height: 40px;
            width: auto;
            object-fit: contain;
        }
        .brand-text {
            display: flex;
            flex-direction: column;
        }
        .brand-name {
            font-size: 20px;
            font-weight: 900;
            color: var(--brand-orange);
            line-height: 1.1;
        }
        .brand-sub {
            font-size: 10px;
            font-weight: 800;
            color: var(--tech-blue);
            letter-spacing: 1px;
        }

        /* Live Autocomplete Search */
        .search-container {
            flex: 1;
            max-width: 500px;
            position: relative;
        }
        .search-input-box {
            position: relative;
            display: flex;
            align-items: center;
        }
        .search-input-box input {
            width: 100%;
            background: var(--bg-input);
            border: 1.5px solid var(--border-color);
            border-radius: 24px;
            padding: 10px 42px 10px 18px;
            font-size: 14px;
            color: var(--text-main);
            outline: none;
            transition: all 0.2s ease;
        }
        html[dir="ltr"] .search-input-box input {
            padding: 10px 18px 10px 42px;
        }
        .search-input-box input:focus {
            border-color: var(--tech-blue);
            box-shadow: 0 0 0 3px var(--tech-blue-soft);
            background: var(--bg-card);
        }
        .search-icon {
            position: absolute;
            right: 14px;
            color: var(--tech-blue);
            font-size: 16px;
            pointer-events: none;
        }
        html[dir="ltr"] .search-icon {
            right: auto;
            left: 14px;
        }

        .search-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            max-height: 380px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
        }
        .search-dropdown.active {
            display: block;
        }
        .search-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-bottom: 1px solid var(--border-color);
            text-decoration: none;
            color: var(--text-main);
            transition: background 0.15s;
        }
        .search-item:last-child {
            border-bottom: none;
        }
        .search-item:hover {
            background: var(--tech-blue-soft);
        }
        .search-item-img {
            width: 44px;
            height: 44px;
            object-fit: contain;
            border-radius: 6px;
            background: var(--bg-input);
            padding: 4px;
        }
        .search-item-info {
            flex: 1;
        }
        .search-item-name {
            font-size: 13px;
            font-weight: 800;
            color: var(--text-main);
        }
        .search-item-specs {
            font-size: 11px;
            color: var(--tech-blue);
            font-weight: 700;
        }
        .search-item-price {
            font-size: 13px;
            font-weight: 900;
            color: var(--brand-orange);
        }

        /* Header Right Controls */
        .header-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .control-btn {
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            padding: 8px 13px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .control-btn:hover {
            border-color: var(--brand-orange);
            color: var(--brand-orange);
        }

        .btn-cart {
            background: var(--brand-orange-soft);
            color: var(--brand-orange);
            border: 1px solid rgba(255, 102, 0, 0.3);
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
        }
        .btn-cart:hover {
            background: var(--brand-orange);
            color: #ffffff;
        }
        .cart-badge {
            background: #ef4444;
            color: #ffffff;
            font-size: 11px;
            font-weight: 900;
            padding: 2px 7px;
            border-radius: 12px;
            min-width: 18px;
            text-align: center;
        }

        /* Hero Banner with Tech Accents */
        .hero-banner {
            background: linear-gradient(135deg, #ffffff 0%, var(--bg-page) 60%, rgba(10, 102, 194, 0.05) 100%);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 32px 28px;
            margin: 24px 0;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }
        body.dark-mode .hero-banner {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }
        .hero-top-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--tech-blue-soft);
            color: var(--tech-blue);
            border: 1px solid var(--tech-blue-border);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
            margin-bottom: 12px;
        }
        .hero-title {
            font-size: 28px;
            font-weight: 900;
            color: var(--text-main);
            margin-bottom: 8px;
            line-height: 1.3;
        }
        .hero-title span {
            color: var(--brand-orange);
        }
        .hero-desc {
            font-size: 15px;
            color: var(--text-muted);
            max-width: 720px;
            margin-bottom: 20px;
        }

        /* 3 Geometric Trust Badges (Tech Blue Accent) */
        .trust-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }
        .trust-card {
            background: var(--bg-card);
            border: 1px solid var(--tech-blue-border);
            border-radius: var(--radius-md);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-sm);
        }
        .trust-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--tech-blue-soft);
            color: var(--tech-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .trust-text-box h4 {
            font-size: 13px;
            font-weight: 800;
            color: var(--text-main);
        }
        .trust-text-box p {
            font-size: 11px;
            color: var(--text-muted);
        }

        /* Category Filter Navigation Tabs */
        .category-filter-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            overflow-x: auto;
            padding: 6px 0 16px 0;
            margin-bottom: 18px;
            scrollbar-width: thin;
        }
        .cat-tab-btn {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            padding: 9px 18px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
        }
        .cat-tab-btn:hover {
            border-color: var(--tech-blue);
            color: var(--tech-blue);
        }
        .cat-tab-btn.active {
            background: var(--brand-orange);
            border-color: var(--brand-orange);
            color: #ffffff;
            box-shadow: 0 4px 12px var(--brand-orange-glow);
        }
        .cat-tab-btn .badge-count {
            background: rgba(0, 0, 0, 0.08);
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 11px;
        }
        .cat-tab-btn.active .badge-count {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        /* Products Grid Header */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 22px;
            font-weight: 900;
            color: var(--text-main);
            border-right: 4px solid var(--brand-orange);
            padding-right: 12px;
        }
        html[dir="ltr"] .section-title {
            border-right: none;
            border-left: 4px solid var(--brand-orange);
            padding-right: 0;
            padding-left: 12px;
        }
        .filter-counter {
            font-size: 13px;
            font-weight: 800;
            color: var(--tech-blue);
            background: var(--tech-blue-soft);
            padding: 4px 12px;
            border-radius: 20px;
            border: 1px solid var(--tech-blue-border);
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 22px;
        }

        .product-card {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
            border-color: rgba(255, 102, 0, 0.4);
        }

        .card-img-wrap {
            position: relative;
            background: var(--bg-input);
            height: 210px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            text-decoration: none;
        }
        .product-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: transform 0.3s ease;
        }
        .product-card:hover .product-img {
            transform: scale(1.06);
        }

        .stock-tag {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(15, 23, 42, 0.85);
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 6px;
        }
        html[dir="ltr"] .stock-tag {
            left: auto;
            right: 10px;
        }
        .cat-chip {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: var(--tech-blue-soft);
            color: var(--tech-blue);
            border: 1px solid var(--tech-blue-border);
            font-size: 10px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 4px;
        }
        html[dir="ltr"] .cat-chip {
            right: auto;
            left: 8px;
        }

        .product-info {
            padding: 16px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .product-title {
            font-size: 16px;
            font-weight: 900;
            margin-bottom: 6px;
            color: var(--text-main);
            line-height: 1.4;
            height: 44px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .product-title:hover {
            color: var(--brand-orange);
        }
        .product-desc {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 10px;
            height: 38px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        /* Color Preview Dots */
        .card-colors-row {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 10px;
            min-height: 16px;
        }
        .card-color-dot {
            width: 13px;
            height: 13px;
            border-radius: 50%;
            border: 1px solid rgba(0, 0, 0, 0.25);
            display: inline-block;
        }
        .card-color-more {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 800;
        }

        /* Mini Specs Chips */
        .product-mini-specs {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 12px;
            min-height: 24px;
        }
        .mini-spec-badge {
            background: var(--bg-input);
            color: var(--text-main);
            font-size: 11px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            white-space: nowrap;
        }

        .product-price-box {
            display: flex;
            align-items: baseline;
            gap: 5px;
            margin-bottom: 14px;
            margin-top: auto;
        }
        .product-price {
            font-size: 22px;
            font-weight: 900;
            color: var(--brand-orange);
        }
        .product-currency {
            font-size: 13px;
            font-weight: 800;
            color: var(--text-muted);
        }

        /* Actions: Dual Cart & WhatsApp */
        .card-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .btn-action {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 8px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-add-cart {
            background-color: var(--text-main);
            color: var(--bg-card);
        }
        .btn-add-cart:hover {
            background-color: var(--brand-orange);
            color: #ffffff;
        }
        .btn-whatsapp {
            background-color: var(--whatsapp-green);
            color: #ffffff;
        }
        .btn-whatsapp:hover {
            background-color: var(--whatsapp-dark);
        }

        /* Sidebar Navigation */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 998;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }
        .sidebar-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        .sidebar {
            position: fixed;
            top: 0;
            right: -320px;
            width: 300px;
            height: 100%;
            background: var(--bg-card);
            z-index: 999;
            box-shadow: -5px 0 25px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        html[dir="ltr"] .sidebar {
            right: auto;
            left: -320px;
            box-shadow: 5px 0 25px rgba(0, 0, 0, 0.15);
            transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar.active {
            right: 0;
        }
        html[dir="ltr"] .sidebar.active {
            left: 0;
        }
        .sidebar-header {
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border-color);
        }
        .btn-close-sidebar {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-main);
            cursor: pointer;
        }
        .sidebar-user-box {
            padding: 16px 20px;
            background: var(--bg-input);
            border-bottom: 1px solid var(--border-color);
        }
        .sidebar-nav-links {
            padding: 15px 0;
            flex: 1;
            overflow-y: auto;
        }
        .sidebar-nav-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: var(--text-main);
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
        }
        .sidebar-nav-links a:hover {
            background: var(--tech-blue-soft);
            color: var(--tech-blue);
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
            border-radius: var(--radius-sm);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            border-right: 4px solid var(--brand-orange);
            font-size: 14px;
            font-weight: 800;
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
            border-left: 4px solid var(--brand-orange);
        }
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        /* Footer */
        footer {
            background: var(--bg-header);
            border-top: 1px solid var(--border-color);
            padding: 30px 20px;
            margin-top: 50px;
            text-align: center;
        }
        .footer-brand {
            font-size: 18px;
            font-weight: 900;
            color: var(--brand-orange);
            margin-bottom: 6px;
        }
        .footer-contact {
            font-size: 14px;
            color: var(--text-muted);
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 8px;
            flex-wrap: wrap;
        }
        .footer-contact a {
            color: var(--tech-blue);
            text-decoration: none;
            font-weight: 700;
        }

        @media (max-width: 860px) {
            header { padding: 10px 14px; flex-wrap: wrap; }
            .brand-sub { display: none; }
            .search-container { max-width: 100%; order: 4; width: 100%; margin-top: 6px; }
            .trust-grid { grid-template-columns: 1fr; }
            .products-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 14px; }
            .card-img-wrap { height: 150px; }
            .product-desc { display: none; }
            .card-actions { grid-template-columns: 1fr; }
            .btn-cart span:not(.cart-badge) { display: none; }
        }
    </style>
</head>
<body>

    <!-- 1. Header Navigation -->
    <header>
        <div class="header-left">
            <button type="button" class="btn-hamburger" onclick="toggleSidebar(true)" title="فتح القائمة" aria-label="Open Navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <a href="index.php" class="brand">
                <img src="assets/logo.png" alt="شعار بدر للهواتف" class="brand-logo" onerror="this.style.display='none'">
                <div class="brand-text">
                    <span class="brand-name" data-ar="بدر للهواتف" data-en="Bader Phones">بدر للهواتف</span>
                    <span class="brand-sub">OFFICIAL E-STORE</span>
                </div>
            </a>
        </div>

        <!-- Live Autocomplete Search -->
        <div class="search-container">
            <div class="search-input-box">
                <input type="text" id="liveSearchInput" placeholder="ابحث عن هاتف، آيفون، لابتوب، لون..." data-placeholder-ar="ابحث عن هاتف، آيفون، لابتوب، لون..." data-placeholder-en="Search phones, laptops, colors..." autocomplete="off">
                <span class="search-icon">🔍</span>
            </div>
            <div class="search-dropdown" id="searchDropdown"></div>
        </div>

        <!-- Header Right Actions -->
        <div class="header-right">
            <!-- Language Toggle -->
            <button type="button" class="control-btn" id="lang-btn" onclick="toggleLanguage()" title="تغيير اللغة / Change Language">
                🌐 <span id="lang-btn-text">EN</span>
            </button>

            <!-- Dark Mode Toggle -->
            <button type="button" class="control-btn" id="dark-mode-btn" onclick="toggleDarkMode()" title="تغيير المظهر / Toggle Dark Mode">
                <span id="dark-mode-icon">🌙</span>
            </button>

            <!-- Direct Cart Button -->
            <a href="cart.php" class="btn-cart">
                <span data-ar="السلة" data-en="Cart">السلة</span> 🛒
                <span class="cart-badge" id="cartBadge"><?= (int)$cartCount ?></span>
            </a>
        </div>
    </header>

    <!-- 2. Slideout Sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar(false)"></div>
    <aside class="sidebar" id="sidebarNav">
        <div class="sidebar-header">
            <div class="brand">
                <img src="assets/logo.png" alt="بدر للهواتف" style="height: 32px;" onerror="this.style.display='none'">
                <span class="brand-name" style="font-size: 18px;" data-ar="بدر للهواتف" data-en="Bader Phones">بدر للهواتف</span>
            </div>
            <button type="button" class="btn-close-sidebar" onclick="toggleSidebar(false)">&times;</button>
        </div>

        <div class="sidebar-user-box">
            <?php if ($currentUser): ?>
                <div style="font-weight: 800; font-size: 15px;"><?= htmlspecialchars($currentUser['name']) ?></div>
                <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($currentUser['email']) ?></div>
                <div style="margin-top: 6px;">
                    <span style="background: var(--tech-blue-soft); color: var(--tech-blue); border: 1px solid var(--tech-blue-border); font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 12px;">
                        <?= ucfirst($currentRole) ?>
                    </span>
                </div>
            <?php else: ?>
                <div style="font-size: 13px; font-weight: 800;" data-ar="أهلاً بك في متجرنا 👋" data-en="Welcome to our store 👋">أهلاً بك في متجرنا 👋</div>
                <div style="font-size: 12px; color: var(--text-muted);" data-ar="سجّل دخولك لمتابعة طلباتك" data-en="Log in to manage orders">سجّل دخولك لمتابعة طلباتك</div>
            <?php endif; ?>
        </div>

        <nav class="sidebar-nav-links">
            <a href="index.php" data-ar="🏠 الرئيسية" data-en="🏠 Home">🏠 الرئيسية</a>
            <a href="cart.php" data-ar="🛒 سلة التسوق (<?= (int)$cartCount ?>)" data-en="🛒 Cart (<?= (int)$cartCount ?>)">🛒 سلة التسوق (<?= (int)$cartCount ?>)</a>

            <?php if ($currentUser): ?>
                <?php if ($isAdmin): ?>
                    <a href="admin/dashboard.php" style="color: var(--brand-orange);" data-ar="⚙️ لوحة تحكم المدير" data-en="⚙️ Admin Dashboard">⚙️ لوحة تحكم المدير</a>
                    <a href="add_product.php" style="color: var(--tech-blue);" data-ar="➕ إضافة جهاز ومواصفات" data-en="➕ Add Product & Specs">➕ إضافة جهاز ومواصفات</a>
                <?php elseif ($isPartner): ?>
                    <a href="partner/dashboard.php" style="color: var(--tech-blue);" data-ar="📦 بوابة الشريك والمخزون" data-en="📦 Partner Portal">📦 بوابة الشريك والمخزون</a>
                    <a href="add_product.php" style="color: var(--brand-orange);" data-ar="➕ إضافة منتج جديد" data-en="➕ Add Product">➕ إضافة منتج جديد</a>
                <?php endif; ?>
                <a href="logout.php" style="color: #ef4444;" data-ar="🚪 تسجيل الخروج" data-en="🚪 Logout">🚪 تسجيل الخروج</a>
            <?php else: ?>
                <a href="login.php" data-ar="🔑 تسجيل الدخول" data-en="🔑 Login">🔑 تسجيل الدخول</a>
                <a href="register.php" data-ar="✨ إنشاء حساب جديد" data-en="✨ Register">✨ إنشاء حساب جديد</a>
            <?php endif; ?>

            <hr style="margin: 10px 20px; border: none; border-top: 1px solid var(--border-color);">
            <a href="https://wa.me/96897447122" target="_blank" style="color: var(--whatsapp-green);" data-ar="💬 واتساب المتجر (96897447122)" data-en="💬 WhatsApp Support">💬 واتساب المتجر (96897447122)</a>
        </nav>
    </aside>

    <!-- 3. Main Content Container -->
    <main class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px; width: 100%; flex: 1;">
        <!-- Hero Banner with Tech Blue & Vibrant Orange -->
        <div class="hero-banner">
            <div class="hero-top-badge">
                <span>🛡️</span>
                <span data-ar="المتجر المعتمد للأجهزة الذكية والأصلية بالسلطنة" data-en="Authorized Tech & Smartphones Store in Oman">المتجر المعتمد للأجهزة الذكية والأصلية بالسلطنة</span>
            </div>
            
            <h1 class="hero-title" data-ar="مرحباً بك في متجر <span>بدر للهواتف</span>" data-en="Welcome to <span>Bader Phones</span> Tech Store">
                مرحباً بك في متجر <span>بدر للهواتف</span>
            </h1>
            
            <p class="hero-desc" data-ar="وجهتك الأولى في سلطنة عُمان لأحدث الهواتف الذكية واللابتوبات الأصلية بأفضل الأسعار مع ضمان شامل وتوصيل فوري معتمد." data-en="Your premier destination in Oman for authentic smartphones and laptops at competitive prices with full warranty and express delivery.">
                وجهتك الأولى في سلطنة عُمان لأحدث الهواتف الذكية واللابتوبات الأصلية بأفضل الأسعار مع ضمان شامل وتوصيل فوري معتمد.
            </p>

            <!-- 3 Geometric Trust Cards -->
            <div class="trust-grid">
                <div class="trust-card">
                    <div class="trust-icon">🛡️</div>
                    <div class="trust-text-box">
                        <h4 data-ar="أجهزة أصلية 100%" data-en="100% Genuine Devices">أجهزة أصلية 100%</h4>
                        <p data-ar="ضمان شامل ومعتمد" data-en="Official Brand Warranty">ضمان شامل ومعتمد</p>
                    </div>
                </div>
                <div class="trust-card">
                    <div class="trust-icon">⚡</div>
                    <div class="trust-text-box">
                        <h4 data-ar="توصيل سريع بعُمان" data-en="Fast Oman Delivery">توصيل سريع بعُمان</h4>
                        <p data-ar="تغطية لكافة المحافظات" data-en="All Governorates Covered">تغطية لكافة المحافظات</p>
                    </div>
                </div>
                <div class="trust-card">
                    <div class="trust-icon">💬</div>
                    <div class="trust-text-box">
                        <h4 data-ar="طلب مباشر بالواتساب" data-en="Direct WhatsApp Order">طلب مباشر بالواتساب</h4>
                        <p data-ar="خدمة فورية: 96897447122" data-en="Hotline: 96897447122">خدمة فورية: 96897447122</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Navigation Filter Tabs -->
        <div class="category-filter-bar">
            <button type="button" class="cat-tab-btn active" onclick="filterByCategory('all', this)">
                <span data-ar="✨ جميع الأجهزة" data-en="✨ All Devices">✨ جميع الأجهزة</span>
                <span class="badge-count" id="count-all"><?= count($products) ?></span>
            </button>
            <button type="button" class="cat-tab-btn" onclick="filterByCategory('phones', this)">
                <span data-ar="📱 هواتف ذكية" data-en="📱 Smartphones">📱 هواتف ذكية</span>
            </button>
            <button type="button" class="cat-tab-btn" onclick="filterByCategory('laptops', this)">
                <span data-ar="💻 لابتوبات" data-en="💻 Laptops">💻 لابتوبات</span>
            </button>
            <button type="button" class="cat-tab-btn" onclick="filterByCategory('ipads', this)">
                <span data-ar="📟 أجهزة لوحية" data-en="📟 Tablets & iPads">📟 أجهزة لوحية</span>
            </button>
            <button type="button" class="cat-tab-btn" onclick="filterByCategory('accessories', this)">
                <span data-ar="🔌 إكسسوارات" data-en="🔌 Accessories">🔌 إكسسوارات</span>
            </button>
            <button type="button" class="cat-tab-btn" onclick="filterByCategory('photography', this)">
                <span data-ar="📷 كاميرات وتصوير" data-en="📷 Photography">📷 كاميرات وتصوير</span>
            </button>
        </div>

        <!-- Section Header -->
        <div class="section-header">
            <h2 class="section-title" id="sectionTitleHeader" data-ar="أحدث الأجهزة المعروضة" data-en="Latest Tech Devices">أحدث الأجهزة المعروضة</h2>
            <span class="filter-counter" id="activeFilterCounter" data-ar="<?= count($products) ?> جهاز متوفر" data-en="<?= count($products) ?> Devices Available"><?= count($products) ?> جهاز متوفر</span>
        </div>

        <!-- Products Grid -->
        <div class="products-grid" id="productsGrid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                    <?php 
                        $prodId = (int)$product['id'];
                        $prodNameAr = !empty($product['name_ar']) ? $product['name_ar'] : $product['name'];
                        $prodNameEn = !empty($product['name_en']) ? $product['name_en'] : $product['name'];
                        $prodDescAr = !empty($product['description_ar']) ? $product['description_ar'] : ($product['description'] ?? '');
                        $prodDescEn = !empty($product['description_en']) ? $product['description_en'] : ($product['description'] ?? '');
                        $prodPrice = (float)$product['price'];
                        $prodImage = !empty($product['image1']) ? $product['image1'] : (!empty($product['image']) ? $product['image'] : 'assets/logo.png');
                        $prodStock = (int)($product['stock'] ?? 10);
                        $prodCategory = strtolower($product['category'] ?? 'phones');
                        
                        $cardColors = [];
                        if (!empty($product['colors'])) {
                            $cDec = json_decode($product['colors'], true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($cDec)) {
                                $cardColors = $cDec;
                            }
                        }

                        $waText = "مرحباً بدر للهواتف، أود الاستفسار والطلب للجهاز التالي:\n"
                                . "📱 المنتج: " . $prodNameAr . "\n"
                                . "💰 السعر: " . number_format($prodPrice, 3) . " ر.ع\n"
                                . "يرجى تأكيد التوفر وطريقة الاستلام بالسلطنة.";
                        $waUrl = "https://wa.me/96897447122?text=" . urlencode($waText);
                    ?>
                    <div class="product-card" id="product-card-<?= $prodId ?>" data-category="<?= htmlspecialchars($prodCategory) ?>">
                        <a href="product.php?id=<?= $prodId ?>" class="card-img-wrap" title="<?= htmlspecialchars($prodNameAr) ?>">
                            <span class="stock-tag" data-ar="<?= ($prodStock > 0) ? 'متوفر' : 'نفذت الكمية' ?>" data-en="<?= ($prodStock > 0) ? 'In Stock' : 'Out of Stock' ?>">
                                <?= ($prodStock > 0) ? 'متوفر' : 'نفذت الكمية' ?>
                            </span>
                            <span class="cat-chip"><?= ucfirst($prodCategory) ?></span>
                            <img src="<?= htmlspecialchars($prodImage) ?>" 
                                 alt="<?= htmlspecialchars($prodNameAr) ?>" 
                                 class="product-img" 
                                 onerror="this.src='assets/logo.png'">
                        </a>

                        <div class="product-info">
                            <a href="product.php?id=<?= $prodId ?>" style="text-decoration: none; color: inherit;">
                                <h3 class="product-title" data-ar="<?= htmlspecialchars($prodNameAr) ?>" data-en="<?= htmlspecialchars($prodNameEn) ?>" title="<?= htmlspecialchars($prodNameAr) ?>">
                                    <?= htmlspecialchars($prodNameAr) ?>
                                </h3>
                            </a>
                            <p class="product-desc" data-ar="<?= htmlspecialchars($prodDescAr) ?>" data-en="<?= htmlspecialchars($prodDescEn) ?>">
                                <?= htmlspecialchars($prodDescAr) ?>
                            </p>
                            
                            <!-- Available Color Badges -->
                            <?php if (!empty($cardColors)): ?>
                                <div class="card-colors-row">
                                    <?php foreach (array_slice($cardColors, 0, 4) as $c): ?>
                                        <span class="card-color-dot" style="background: <?= htmlspecialchars($c['hex'] ?? '#000') ?>;" title="<?= htmlspecialchars($c['name_ar'] ?? '') ?>"></span>
                                    <?php endforeach; ?>
                                    <?php if (count($cardColors) > 4): ?>
                                        <span class="card-color-more">+<?= count($cardColors) - 4 ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Structured Mini-Specs Chips -->
                            <?php if ((!empty($product['ram']) && $product['ram'] !== '-') || (!empty($product['storage']) && $product['storage'] !== '-') || (!empty($product['processor']) && $product['processor'] !== '-') || (!empty($product['graphics_card']) && $product['graphics_card'] !== '-')): ?>
                                <div class="product-mini-specs">
                                    <?php if (!empty($product['ram']) && $product['ram'] !== '-'): ?>
                                        <?php 
                                            $rVal = $product['ram'];
                                            $rClean = (stripos($rVal, 'GB') !== false) ? $rVal : $rVal . 'GB';
                                        ?>
                                        <span class="mini-spec-badge">💾 <?= htmlspecialchars($rClean) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($product['storage']) && $product['storage'] !== '-'): ?>
                                        <span class="mini-spec-badge">💽 <?= htmlspecialchars($product['storage']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($product['processor']) && $product['processor'] !== '-'): ?>
                                        <span class="mini-spec-badge">⚡ <?= htmlspecialchars($product['processor']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($product['graphics_card']) && $product['graphics_card'] !== '-'): ?>
                                        <span class="mini-spec-badge">🎮 <?= htmlspecialchars($product['graphics_card']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="product-price-box">
                                <span class="product-price"><?= number_format($prodPrice, 3) ?></span>
                                <span class="product-currency" data-ar="ر.ع" data-en="OMR">ر.ع</span>
                            </div>

                            <div class="card-actions">
                                <button type="button" 
                                        class="btn-action btn-add-cart" 
                                        onclick="addToCart(<?= $prodId ?>, '<?= htmlspecialchars(addslashes($prodNameAr)) ?>')">
                                    <span data-ar="إضافة للسلة" data-en="Add to Cart">إضافة للسلة</span> 🛒
                                </button>
                                
                                <a href="<?= $waUrl ?>" target="_blank" class="btn-action btn-whatsapp" title="WhatsApp Order">
                                    <span data-ar="أطلب بالواتساب" data-en="WhatsApp">أطلب بالواتساب</span> 💬
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; color: var(--text-muted); padding: 50px; background: var(--bg-card); border-radius: 12px;">
                    <h3 data-ar="لا توجد أجهزة متوفرة حالياً" data-en="No devices currently available">لا توجد أجهزة متوفرة حالياً</h3>
                    <p data-ar="يمكنك إضافة منتجات جديدة من خلال لوحة التحكم." data-en="You can add devices from the dashboard.">يمكنك إضافة منتجات جديدة من خلال لوحة التحكم.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Floating Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Footer -->
    <footer>
        <div class="footer-brand">بدر للهواتف / Bader Phones</div>
        <div class="footer-contact">
            <span data-ar="📍 سلطنة عُمان" data-en="📍 Sultanate of Oman">📍 سلطنة عُمان</span>
            <span>📞 <a href="https://wa.me/96897447122" target="_blank">96897447122</a></span>
            <span>📧 <a href="mailto:admin@baderphones.om">admin@baderphones.om</a></span>
        </div>
        <p style="font-size: 12px; color: var(--text-muted); margin-top: 15px;">
            <span data-ar="جميع الحقوق محفوظة &copy; 2026 - بدر للهواتف (Bader Phones)" data-en="All rights reserved &copy; 2026 - Bader Phones">جميع الحقوق محفوظة &copy; 2026 - بدر للهواتف (Bader Phones)</span>
        </p>
    </footer>

    <!-- JavaScript Application Logic -->
    <script>
        const storeProducts = <?= json_encode(array_map(function($p) {
            $img = !empty($p['image1']) ? $p['image1'] : (!empty($p['image']) ? $p['image'] : 'assets/logo.png');
            $nameAr = !empty($p['name_ar']) ? $p['name_ar'] : $p['name'];
            $nameEn = !empty($p['name_en']) ? $p['name_en'] : $p['name'];
            $descAr = !empty($p['description_ar']) ? $p['description_ar'] : ($p['description'] ?? '');
            $descEn = !empty($p['description_en']) ? $p['description_en'] : ($p['description'] ?? '');
            $specs = [];
            if (!empty($p['ram'])) $specs[] = $p['ram'] . 'GB RAM';
            if (!empty($p['storage'])) $specs[] = $p['storage'];
            if (!empty($p['processor'])) $specs[] = $p['processor'];
            if (!empty($p['graphics_card'])) $specs[] = $p['graphics_card'];

            $colorsStr = '';
            if (!empty($p['colors'])) {
                $cArr = json_decode($p['colors'], true);
                if (is_array($cArr)) {
                    $colorsStr = implode(' ', array_column($cArr, 'name_ar')) . ' ' . implode(' ', array_column($cArr, 'name_en'));
                }
            }

            return [
                'id'       => (int)$p['id'],
                'category' => strtolower($p['category'] ?? 'phones'),
                'name'     => $nameAr,
                'name_ar'  => $nameAr,
                'name_en'  => $nameEn,
                'price'    => number_format((float)$p['price'], 3),
                'desc_ar'  => $descAr,
                'desc_en'  => $descEn,
                'specs'    => implode(' • ', $specs),
                'colors'   => $colorsStr,
                'image'    => $img,
                'stock'    => (int)($p['stock'] ?? 10)
            ];
        }, $products), JSON_UNESCAPED_UNICODE) ?>;

        // ====================================================================
        // A. Category Filter Tabs
        // ====================================================================
        function filterByCategory(category, btn) {
            document.querySelectorAll('.cat-tab-btn').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');

            const cards = document.querySelectorAll('.product-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const cardCat = card.getAttribute('data-category');
                if (category === 'all' || cardCat === category || (category === 'phones' && cardCat === 'smartphones')) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            const counter = document.getElementById('activeFilterCounter');
            if (counter) {
                counter.textContent = currentLang === 'ar' 
                    ? `${visibleCount} جهاز متوفر` 
                    : `${visibleCount} Devices Available`;
            }
        }

        // ====================================================================
        // B. Dark Mode & Theme Management
        // ====================================================================
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

        // ====================================================================
        // C. Bilingual Switching
        // ====================================================================
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

            const searchInput = document.getElementById('liveSearchInput');
            if (searchInput) {
                const placeholder = searchInput.getAttribute(`data-placeholder-${lang}`);
                if (placeholder) searchInput.setAttribute('placeholder', placeholder);
            }

            const titleEl = document.getElementById('page-title');
            if (titleEl) {
                document.title = titleEl.getAttribute(`data-${lang}`) || document.title;
            }
        }

        function toggleLanguage() {
            applyLanguage(currentLang === 'ar' ? 'en' : 'ar');
        }

        // ====================================================================
        // D. Sidebar Toggle
        // ====================================================================
        function toggleSidebar(open) {
            const sidebar = document.getElementById('sidebarNav');
            const overlay = document.getElementById('sidebarOverlay');
            if (open) {
                sidebar.classList.add('active');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            } else {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') toggleSidebar(false);
        });

        // ====================================================================
        // E. Toast Notifications
        // ====================================================================
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
            }, 2500);
        }

        // ====================================================================
        // F. AJAX Add to Cart
        // ====================================================================
        function addToCart(productId, productName) {
            fetch(`cart.php?action=add&id=${productId}`, {
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
                    const msg = currentLang === 'ar' 
                        ? `تمت إضافة <strong>${productName}</strong> إلى السلة!` 
                        : `Added <strong>${productName}</strong> to cart!`;
                    showToast(msg);
                } else {
                    window.location.href = `cart.php?action=add&id=${productId}`;
                }
            })
            .catch(() => {
                window.location.href = `cart.php?action=add&id=${productId}`;
            });
        }

        // ====================================================================
        // G. Live Search Autocomplete with Colors & Specs
        // ====================================================================
        const searchInput = document.getElementById('liveSearchInput');
        const searchDropdown = document.getElementById('searchDropdown');

        if (searchInput && searchDropdown) {
            searchInput.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();
                if (query.length === 0) {
                    searchDropdown.innerHTML = '';
                    searchDropdown.classList.remove('active');
                    return;
                }

                const matched = storeProducts.filter(item => {
                    return item.name_ar.toLowerCase().includes(query) || 
                           item.name_en.toLowerCase().includes(query) ||
                           item.desc_ar.toLowerCase().includes(query) ||
                           item.desc_en.toLowerCase().includes(query) ||
                           item.specs.toLowerCase().includes(query) ||
                           (item.colors && item.colors.toLowerCase().includes(query));
                });

                if (matched.length > 0) {
                    let html = '';
                    matched.slice(0, 6).forEach(item => {
                        const displayName = currentLang === 'ar' ? item.name_ar : item.name_en;
                        const viewText = currentLang === 'ar' ? 'عرض 👈' : 'View 👉';
                        const currText = currentLang === 'ar' ? 'ر.ع' : 'OMR';
                        const specsBadge = item.specs ? `<div class="search-item-specs">${item.specs}</div>` : '';
                        html += `
                            <a href="product.php?id=${item.id}" class="search-item">
                                <img src="${item.image}" alt="${displayName}" class="search-item-img" onerror="this.src='assets/logo.png'">
                                <div class="search-item-info">
                                    <div class="search-item-name">${displayName}</div>
                                    ${specsBadge}
                                    <div class="search-item-price">${item.price} ${currText}</div>
                                </div>
                                <span style="font-size: 11px; color: var(--brand-orange); font-weight: 800;">${viewText}</span>
                            </a>
                        `;
                    });
                    searchDropdown.innerHTML = html;
                    searchDropdown.classList.add('active');
                } else {
                    const noRes = currentLang === 'ar' 
                        ? `لا توجد أجهزة مطابقة للبحث "${query}"` 
                        : `No devices found matching "${query}"`;
                    searchDropdown.innerHTML = `<div style="padding:15px;text-align:center;color:var(--text-muted);font-size:13px;">${noRes}</div>`;
                    searchDropdown.classList.add('active');
                }
            });

            document.addEventListener('click', e => {
                if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                    searchDropdown.classList.remove('active');
                }
            });
        }

        // Initialize on DOM load
        document.addEventListener('DOMContentLoaded', () => {
            initTheme();
            applyLanguage(currentLang);
        });
    </script>
</body>
</html>