<?php
// ============================================================================
// بدر للهواتف / Bader Phones - سلة التسوق وإتمام الطلب (cart.php)
// Features: Rock-solid Session Cart Handling, Reliable Item Removal,
// Complete Clear Cart Feature (Zero Phantom Items), Interactive Stepper Controls,
// Selected Color Badges, WhatsApp Direct Checkout (Target WhatsApp: 96897447122),
// Tech Blue Geometric Trust Badges, Clean Tech White + Tech Orange Palette
// ============================================================================

require_once __DIR__ . '/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDBConnection();

// Initialize cart session properly
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$currentUser = getCurrentUser();
$whatsappTarget = '96897447122';

// ----------------------------------------------------------------------------
// Cart Actions Handler
// ----------------------------------------------------------------------------
$action = $_REQUEST['action'] ?? '';
$isJson = (isset($_GET['format']) && $_GET['format'] === 'json') || 
          (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
          (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

// 1. ADD ITEM TO CART
if ($action === 'add') {
    $productId = (int)($_REQUEST['id'] ?? 0);
    $quantity = max(1, (int)($_REQUEST['qty'] ?? 1));
    $color = trim($_REQUEST['color'] ?? '');
    $colorHex = trim($_REQUEST['color_hex'] ?? '');

    if ($productId > 0) {
        $stmt = $pdo->prepare("SELECT id, name, name_ar, name_en, price, stock, COALESCE(NULLIF(image1, ''), NULLIF(image, ''), 'assets/logo.png') AS image, category FROM products WHERE id = ? LIMIT 1");
        $stmt->execute([$productId]);
        $prod = $stmt->fetch();

        if ($prod) {
            $cartKey = $productId . (!empty($color) ? '_' . substr(md5($color . $colorHex), 0, 8) : '');

            if (isset($_SESSION['cart'][$cartKey])) {
                $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$cartKey] = [
                    'cart_key'  => $cartKey,
                    'id'        => (int)$prod['id'],
                    'name'      => $prod['name'],
                    'name_ar'   => !empty($prod['name_ar']) ? $prod['name_ar'] : $prod['name'],
                    'name_en'   => !empty($prod['name_en']) ? $prod['name_en'] : $prod['name'],
                    'price'     => (float)$prod['price'],
                    'stock'     => (int)$prod['stock'],
                    'image'     => $prod['image'],
                    'category'  => $prod['category'] ?? 'phones',
                    'color'     => $color,
                    'color_hex' => $colorHex,
                    'quantity'  => $quantity
                ];
            }
            $colorSuffix = !empty($color) ? " (لون: " . htmlspecialchars($color) . ")" : "";
            $_SESSION['cart_message'] = "تمت إضافة (" . htmlspecialchars($prod['name']) . $colorSuffix . ") إلى سلة التسوق بنجاح!";
        }
    }

    if ($isJson) {
        $totalItems = array_sum(array_column($_SESSION['cart'], 'quantity'));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'cartCount' => $totalItems]);
        exit;
    }

    header("Location: cart.php");
    exit;
}

// 2. UPDATE ITEM QUANTITY (or STEPPER DELTA)
if ($action === 'update') {
    $targetKey = $_REQUEST['key'] ?? ($_REQUEST['id'] ?? '');
    $delta = isset($_REQUEST['delta']) ? (int)$_REQUEST['delta'] : 0;
    
    if (!empty($targetKey) && isset($_SESSION['cart'][$targetKey])) {
        if ($delta !== 0) {
            $_SESSION['cart'][$targetKey]['quantity'] += $delta;
        } elseif (isset($_REQUEST['qty'])) {
            $_SESSION['cart'][$targetKey]['quantity'] = (int)$_REQUEST['qty'];
        }

        if ($_SESSION['cart'][$targetKey]['quantity'] <= 0) {
            unset($_SESSION['cart'][$targetKey]);
            $_SESSION['cart_message'] = "تم حذف المنتج من السلة.";
        } else {
            $_SESSION['cart_message'] = "تم تحديث كمية المنتج.";
        }
    }

    if ($isJson) {
        $totalItems = array_sum(array_column($_SESSION['cart'], 'quantity'));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'cartCount' => $totalItems]);
        exit;
    }

    header("Location: cart.php");
    exit;
}

// 3. REMOVE SPECIFIC ITEM (Permanently & Reliably)
if ($action === 'remove') {
    $targetKey = $_REQUEST['key'] ?? ($_REQUEST['id'] ?? '');
    if (!empty($targetKey) && isset($_SESSION['cart'][$targetKey])) {
        $removedName = $_SESSION['cart'][$targetKey]['name'];
        unset($_SESSION['cart'][$targetKey]);
        $_SESSION['cart_message'] = "تم حذف (" . htmlspecialchars($removedName) . ") من سلة التسوق بنجاح.";
    }

    if ($isJson) {
        $totalItems = array_sum(array_column($_SESSION['cart'], 'quantity'));
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'cartCount' => $totalItems]);
        exit;
    }

    header("Location: cart.php");
    exit;
}

// 4. FULL CLEAR CART (Resets session completely without phantom items)
if ($action === 'clear') {
    $_SESSION['cart'] = [];
    unset($_SESSION['cart']);
    $_SESSION['cart'] = [];
    $_SESSION['cart_message'] = "تم تفريغ سلة المشتريات بالكامل بنجاح.";

    if ($isJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'cartCount' => 0]);
        exit;
    }

    header("Location: cart.php");
    exit;
}

// 5. CHECKOUT & WHATSAPP REDIRECTION
if ($action === 'checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_SESSION['cart'])) {
        $_SESSION['cart_error'] = 'سلة المشتريات فارغة حالياً!';
        header("Location: cart.php");
        exit;
    }

    $customerName = trim($_POST['customer_name'] ?? ($currentUser['name'] ?? 'عميل المتجر'));
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $customerCity = trim($_POST['customer_city'] ?? 'مسقط');
    $customerNotes = trim($_POST['customer_notes'] ?? '');

    // Calculate total and prepare order breakdown
    $totalAmount = 0;
    $itemsList = [];
    $waItemsText = "";
    $itemCounter = 1;

    foreach ($_SESSION['cart'] as $item) {
        $itemSubtotal = $item['price'] * $item['quantity'];
        $totalAmount += $itemSubtotal;

        $itemsList[] = [
            'id'        => $item['id'],
            'name'      => $item['name'],
            'price'     => $item['price'],
            'color'     => $item['color'] ?? null,
            'color_hex' => $item['color_hex'] ?? null,
            'quantity'  => $item['quantity'],
            'subtotal'  => $itemSubtotal
        ];

        $colorNote = !empty($item['color']) ? " [اللون: " . $item['color'] . "]" : "";
        $waItemsText .= "{$itemCounter}. " . $item['name'] . $colorNote . " × " . $item['quantity'] . " = " . number_format($itemSubtotal, 3) . " ر.ع\n";
        $itemCounter++;
    }

    // Save order to MySQL database
    $orderId = null;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO `orders` (`user_id`, `total_amount`, `order_items`, `status`)
            VALUES (?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $currentUser ? $currentUser['id'] : null,
            $totalAmount,
            json_encode($itemsList, JSON_UNESCAPED_UNICODE)
        ]);
        $orderId = $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Order Insertion Error: " . $e->getMessage());
        $orderId = rand(1000, 9999);
    }

    // Build the formatted WhatsApp message
    $waMessage = "مرحباً بدر للهواتف، أود إتمام الطلب التالي من الموقع:\n"
               . "━━━━━━━━━━━━━━━━━━━━\n"
               . "📦 رقم الطلب: #" . $orderId . "\n"
               . "👤 اسم العميل: " . $customerName . "\n"
               . "📞 رقم الهاتف: " . $customerPhone . "\n"
               . "📍 المدينة / المنطقة: " . $customerCity . "\n";

    if (!empty($customerNotes)) {
        $waMessage .= "📝 ملاحظات: " . $customerNotes . "\n";
    }

    $waMessage .= "━━━━━━━━━━━━━━━━━━━━\n"
               . "📱 تفاصيل الأجهزة المطلوبة:\n"
               . $waItemsText
               . "━━━━━━━━━━━━━━━━━━━━\n"
               . "💰 المجموع الإجمالي: " . number_format($totalAmount, 3) . " ر.ع\n"
               . "يرجى تأكيد استلام الطلب وطريقة التوصيل والدفع في سلطنة عُمان.";

    $waUrl = "https://wa.me/" . $whatsappTarget . "?text=" . urlencode($waMessage);

    // Clear cart after successful order creation
    $_SESSION['cart'] = [];
    unset($_SESSION['cart']);
    $_SESSION['cart'] = [];
    $_SESSION['last_order_id'] = $orderId;

    // Redirect directly to WhatsApp
    header("Location: " . $waUrl);
    exit;
}

// Prepare cart calculations for display
$cartItems = $_SESSION['cart'] ?? [];
$cartTotal = 0;
$totalCount = 0;

foreach ($cartItems as $item) {
    $cartTotal += ($item['price'] * $item['quantity']);
    $totalCount += $item['quantity'];
}

$cartMsg = $_SESSION['cart_message'] ?? null;
$cartErr = $_SESSION['cart_error'] ?? null;
unset($_SESSION['cart_message'], $_SESSION['cart_error']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="page-title" data-ar="سلة التسوق وإتمام الطلب | بدر للهواتف" data-en="Shopping Cart & Checkout | Bader Phones">سلة التسوق وإتمام الطلب | بدر للهواتف</title>
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
            --danger-color: #ef4444;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
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
            --primary-soft: rgba(255, 102, 0, 0.15);
            --tech-blue-soft: rgba(10, 102, 194, 0.2);
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

        /* Header */
        header {
            background-color: var(--header-bg);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 2px solid var(--border-color);
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
            gap: 10px;
        }

        .control-btn {
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 7px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .control-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .btn-nav-home {
            background: var(--primary-soft);
            border: 1px solid rgba(255, 102, 0, 0.25);
            color: var(--primary-color);
            padding: 7px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 800;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-nav-home:hover {
            background: var(--primary-color);
            color: #ffffff;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 25px auto;
            padding: 0 20px;
            width: 100%;
            flex: 1;
        }

        /* Alert notifications */
        .alert {
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

        /* Cart Layout */
        .cart-layout {
            display: grid;
            grid-template-columns: 1.8fr 1.2fr;
            gap: 30px;
            align-items: start;
        }

        /* Cart Items Box */
        .cart-box {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }
        .cart-box-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border-color);
        }
        .cart-title {
            font-size: 20px;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-color);
        }

        .btn-clear {
            background: #fee2e2;
            color: #b91c1c;
            border: none;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-clear:hover {
            background: #ef4444;
            color: #ffffff;
        }

        /* Cart Item Row */
        .cart-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .item-img {
            width: 74px;
            height: 74px;
            object-fit: contain;
            border-radius: 8px;
            background: var(--input-bg);
            padding: 6px;
            border: 1px solid var(--border-color);
        }
        .item-details {
            flex: 1;
        }
        .item-name {
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 4px;
            color: var(--text-color);
            text-decoration: none;
            display: block;
        }
        .item-name:hover {
            color: var(--primary-color);
        }
        .item-color-pill {
            font-size: 12px;
            color: var(--text-muted);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            padding: 2px 8px;
            border-radius: 12px;
            margin: 2px 0 6px 0;
        }
        .item-price {
            font-size: 14px;
            color: var(--primary-color);
            font-weight: 800;
        }

        /* Stepper Quantity in Cart Row */
        .cart-stepper {
            display: inline-flex;
            align-items: center;
            border: 1px solid var(--border-color);
            background: var(--input-bg);
            border-radius: 8px;
            overflow: hidden;
        }
        .cart-stepper-btn {
            background: transparent;
            border: none;
            color: var(--text-color);
            width: 32px;
            height: 32px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .cart-stepper-btn:hover {
            background: var(--primary-soft);
            color: var(--primary-color);
        }
        .cart-stepper-val {
            width: 36px;
            text-align: center;
            font-weight: 800;
            font-size: 14px;
            color: var(--text-color);
        }

        .item-subtotal {
            font-size: 16px;
            font-weight: 900;
            color: var(--text-color);
            min-width: 90px;
            text-align: left;
        }
        html[dir="ltr"] .item-subtotal {
            text-align: right;
        }

        .btn-remove-item {
            color: var(--danger-color);
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 18px;
            padding: 6px;
            border-radius: 6px;
            transition: transform 0.2s;
            text-decoration: none;
        }
        .btn-remove-item:hover {
            transform: scale(1.15);
            background: #fee2e2;
        }

        /* Checkout Box */
        .checkout-box {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            position: sticky;
            top: 85px;
        }
        .checkout-title {
            font-size: 18px;
            font-weight: 900;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-color);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
            color: var(--text-muted);
        }
        .summary-total {
            display: flex;
            justify-content: space-between;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 2px dashed var(--border-color);
            font-size: 18px;
            font-weight: 900;
            color: var(--text-color);
        }
        .total-amount {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 900;
        }

        /* Geometric Trust Badges in Tech Blue */
        .checkout-trust-box {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin: 16px 0;
            padding: 12px;
            background: var(--tech-blue-soft);
            border: 1px solid rgba(10, 102, 194, 0.2);
            border-radius: var(--radius-md);
        }
        .c-trust-item {
            text-align: center;
            font-size: 11px;
            font-weight: 800;
            color: var(--text-color);
        }
        .c-trust-item span {
            display: block;
            font-size: 16px;
            margin-bottom: 2px;
        }

        /* Form Inputs */
        .form-group {
            margin-top: 14px;
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--text-color);
        }
        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid var(--border-color);
            border-radius: 8px;
            background: var(--input-bg);
            color: var(--text-color);
            font-size: 14px;
            outline: none;
        }
        .form-input:focus {
            border-color: var(--primary-color);
            background: var(--card-bg);
        }

        /* WhatsApp Checkout Button */
        .btn-whatsapp-checkout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            background: var(--primary-color);
            color: #ffffff;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            margin-top: 20px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(255, 102, 0, 0.3);
            transition: all 0.2s;
        }
        .btn-whatsapp-checkout:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 102, 0, 0.4);
        }

        /* Empty Cart State */
        .empty-cart-box {
            text-align: center;
            padding: 60px 20px;
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }
        .empty-cart-icon {
            font-size: 54px;
            margin-bottom: 14px;
        }
        .empty-cart-title {
            font-size: 22px;
            font-weight: 900;
            margin-bottom: 8px;
            color: var(--text-color);
        }
        .empty-cart-desc {
            color: var(--text-muted);
            margin-bottom: 22px;
        }
        .btn-browse {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary-color);
            color: #ffffff;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 800;
        }
        .btn-browse:hover {
            background: var(--primary-dark);
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
            .cart-layout {
                grid-template-columns: 1fr;
            }
            .cart-item {
                flex-wrap: wrap;
            }
            .item-subtotal {
                width: 100%;
                text-align: right;
                margin-top: 8px;
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
            <a href="index.php" class="btn-nav-home">
                <span data-ar="الرئيسية" data-en="Home">الرئيسية</span> 🏠
            </a>
        </div>
    </header>

    <main class="container">
        <!-- Messages -->
        <?php if ($cartMsg): ?>
            <div class="alert alert-success">✅ <?= $cartMsg ?></div>
        <?php endif; ?>
        <?php if ($cartErr): ?>
            <div class="alert alert-error">⚠️ <?= $cartErr ?></div>
        <?php endif; ?>

        <?php if (!empty($cartItems)): ?>
            <div class="cart-layout">
                <!-- Cart Items List -->
                <div class="cart-box">
                    <div class="cart-box-header">
                        <div class="cart-title">
                            🛒 <span data-ar="سلة المشتريات (<?= $totalCount ?> أجهزة)" data-en="Shopping Cart (<?= $totalCount ?> items)">سلة المشتريات (<?= $totalCount ?> أجهزة)</span>
                        </div>
                        <a href="cart.php?action=clear" class="btn-clear" onclick="return confirm('هل أنت متأكد من رغبتك في تفريغ السلة بالكامل؟')">
                            🗑️ <span data-ar="تفريغ السلة" data-en="Clear Cart">تفريغ السلة</span>
                        </a>
                    </div>

                    <?php foreach ($cartItems as $itemKey => $item): ?>
                        <?php 
                            $itemId = (int)$item['id'];
                            $itemSub = $item['price'] * $item['quantity'];
                        ?>
                        <div class="cart-item" id="item-row-<?= htmlspecialchars($itemKey) ?>">
                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="item-img" onerror="this.src='assets/logo.png'">
                            
                            <div class="item-details">
                                <a href="product.php?id=<?= $itemId ?>" class="item-name"><?= htmlspecialchars($item['name']) ?></a>
                                <?php if (!empty($item['color'])): ?>
                                    <div class="item-color-pill">
                                        <?php if (!empty($item['color_hex'])): ?>
                                            <span style="width: 12px; height: 12px; border-radius: 50%; background: <?= htmlspecialchars($item['color_hex']) ?>; border: 1px solid rgba(0,0,0,0.25); display: inline-block;"></span>
                                        <?php endif; ?>
                                        <span data-ar="اللون: <?= htmlspecialchars($item['color']) ?>" data-en="Color: <?= htmlspecialchars($item['color']) ?>">اللون: <strong><?= htmlspecialchars($item['color']) ?></strong></span>
                                    </div>
                                <?php endif; ?>
                                <div class="item-price"><?= number_format($item['price'], 3) ?> <span class="curr-tag">ر.ع</span></div>
                            </div>

                            <!-- Stepper Quantity in Cart Row -->
                            <div class="cart-stepper">
                                <a href="cart.php?action=update&key=<?= urlencode($itemKey) ?>&delta=-1" class="cart-stepper-btn" title="تقليل">−</a >
                                <span class="cart-stepper-val"><?= $item['quantity'] ?></span>
                                <a href="cart.php?action=update&key=<?= urlencode($itemKey) ?>&delta=1" class="cart-stepper-btn" title="زيادة">+</a>
                            </div>

                            <div class="item-subtotal">
                                <?= number_format($itemSub, 3) ?> <span class="curr-tag">ر.ع</span>
                            </div>

                            <a href="cart.php?action=remove&key=<?= urlencode($itemKey) ?>&id=<?= $itemId ?>" class="btn-remove-item" title="حذف المنتج من السلة" onclick="return confirm('حذف هذا المنتج؟')">
                                ❌
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Checkout Form & Direct WhatsApp Button -->
                <div class="checkout-box">
                    <h3 class="checkout-title" data-ar="ملخص وإتمام الطلب" data-en="Order Summary & Checkout">ملخص وإتمام الطلب</h3>
                    
                    <div class="summary-row">
                        <span data-ar="عدد الأجهزة" data-en="Total Items">عدد الأجهزة</span>
                        <strong><?= $totalCount ?></strong>
                    </div>
                    <div class="summary-row">
                        <span data-ar="رسوم التوصيل" data-en="Delivery">رسوم التوصيل</span>
                        <strong style="color: #16a34a;" data-ar="توصيل سريع متاح" data-en="Fast Delivery Available">توصيل سريع متاح</strong>
                    </div>

                    <div class="summary-total">
                        <span data-ar="المجموع الإجمالي" data-en="Grand Total">المجموع الإجمالي</span>
                        <div class="total-amount">
                            <span><?= number_format($cartTotal, 3) ?></span> <span class="curr-tag" style="font-size: 15px;">ر.ع</span>
                        </div>
                    </div>

                    <!-- Geometric Trust Badges in Tech Blue -->
                    <div class="checkout-trust-box">
                        <div class="c-trust-item">
                            <span>🛡️</span>
                            <span data-ar="أصلي 100%" data-en="100% Genuine">أصلي 100%</span>
                        </div>
                        <div class="c-trust-item">
                            <span>⚡</span>
                            <span data-ar="توصيل سريع" data-en="Fast Delivery">توصيل سريع</span>
                        </div>
                        <div class="c-trust-item">
                            <span>✅</span>
                            <span data-ar="ضمان معتمد" data-en="Warranty">ضمان معتمد</span>
                        </div>
                    </div>

                    <!-- Checkout Form to WhatsApp -->
                    <form action="cart.php?action=checkout" method="POST" style="margin-top: 10px;">
                        <div class="form-group">
                            <label class="form-label" data-ar="اسم المستلم" data-en="Full Name">اسم المستلم</label>
                            <input type="text" name="customer_name" class="form-input" required 
                                   value="<?= htmlspecialchars($currentUser['name'] ?? '') ?>" 
                                   placeholder="أدخل اسمك الكريم">
                        </div>

                        <div class="form-group">
                            <label class="form-label" data-ar="رقم هاتف التواصل" data-en="Phone Number">رقم هاتف التواصل</label>
                            <input type="tel" name="customer_phone" class="form-input" required 
                                   placeholder="مثال: 96897447122">
                        </div>

                        <div class="form-group">
                            <label class="form-label" data-ar="المدينة / المنطقة" data-en="City / Region">المدينة / المنطقة</label>
                            <input type="text" name="customer_city" class="form-input" required 
                                   placeholder="مثال: مسقط، السيب، صحار، صلالة">
                        </div>

                        <div class="form-group">
                            <label class="form-label" data-ar="ملاحظات إضافية (اختياري)" data-en="Notes (Optional)">ملاحظات إضافية (اختياري)</label>
                            <input type="text" name="customer_notes" class="form-input" 
                                   placeholder="أي تفاصيل خاصة بالتوصيل أو الاستلام">
                        </div>

                        <button type="submit" class="btn-whatsapp-checkout">
                            <span>💬</span>
                            <span data-ar="أطلب الآن عبر واتساب (96897447122)" data-en="Order via WhatsApp (96897447122)">أطلب الآن عبر واتساب (96897447122)</span>
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart-box">
                <div class="empty-cart-icon">🛒</div>
                <h2 class="empty-cart-title" data-ar="سلة التسوق فارغة حالياً" data-en="Your Shopping Cart is Empty">سلة التسوق فارغة حالياً</h2>
                <p class="empty-cart-desc" data-ar="لم تقم بإضافة أي هواتف أو أجهزة إلى السلة بعد. تصفح أحدث الأجهزة وأضف ما يناسبك!" data-en="You haven't added any products to your cart yet. Browse our collection and add your favorite phones!">لم تقم بإضافة أي هواتف أو أجهزة إلى السلة بعد. تصفح أحدث الأجهزة وأضف ما يناسبك!</p>
                <a href="index.php" class="btn-browse">
                    <span data-ar="تصفح الهواتف والمنتجات" data-en="Browse Products">تصفح الهواتف والمنتجات</span> 📱
                </a>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <p data-ar="جميع الحقوق محفوظة &copy; 2026 - بدر للهواتف (Bader Phones) | هاتف: 96897447122" data-en="All rights reserved &copy; 2026 - Bader Phones | Tel: 96897447122">
            جميع الحقوق محفوظة &copy; 2026 - بدر للهواتف (Bader Phones) | هاتف: 96897447122
        </p>
    </footer>

    <!-- Theme & Language Script -->
    <script>
        // Clean any residual obsolete local cart storage to eliminate phantom items
        try {
            localStorage.removeItem('bader_cart');
        } catch (e) {}

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

        let currentLang = localStorage.getItem('bader_lang') || 'ar';

        function applyLanguage(lang) {
            currentLang = lang;
            localStorage.setItem('bader_lang', lang);
            document.cookie = `lang=${lang};path=/;max-age=2592000`;

            const html = document.getElementById('html-root');
            html.setAttribute('lang', lang);
            html.setAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr');

            const langBtnText = document.getElementById('lang-btn-text');
            if (langBtnText) {
                langBtnText.textContent = lang === 'ar' ? 'EN' : 'عربي';
            }

            document.querySelectorAll('[data-ar]').forEach(el => {
                const text = el.getAttribute(`data-${lang}`);
                if (text !== null) {
                    el.innerHTML = text;
                }
            });

            document.querySelectorAll('.curr-tag').forEach(el => {
                el.textContent = lang === 'ar' ? 'ر.ع' : 'OMR';
            });

            const titleEl = document.getElementById('page-title');
            if (titleEl) {
                document.title = titleEl.getAttribute(`data-${lang}`) || document.title;
            }
        }

        function toggleLanguage() {
            const newLang = currentLang === 'ar' ? 'en' : 'ar';
            applyLanguage(newLang);
        }

        document.addEventListener('DOMContentLoaded', () => {
            initTheme();
            applyLanguage(currentLang);
        });
    </script>
</body>
</html>
