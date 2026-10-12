<?php
// ============================================================================
// Bader Phones / بدر للهواتف - Login Page (login.php)
// Features: White & Tech-Orange Aesthetic (#ff6600), 1-Click Role Fill Buttons,
// Flash Error Handling, Responsive Mobile/Desktop UI
// ============================================================================

require_once __DIR__ . '/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getCurrentRole();
    if ($role === 'admin') {
        header("Location: admin/dashboard.php");
    } elseif ($role === 'partner') {
        header("Location: partner/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

$loggedOut = isset($_GET['logged_out']);
$authRequired = (isset($_GET['error']) && $_GET['error'] === 'auth_required');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | بدر للهواتف (Bader Phones)</title>
    <link rel="icon" type="image/png" href="assets/logo.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-orange: #ff6600;
            --orange-hover: #e65c00;
            --orange-soft: rgba(255, 102, 0, 0.08);
            --bg-light: #f8fafc;
            --card-bg: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
            --shadow-hover: 0 20px 30px -10px rgba(255, 102, 0, 0.15);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Cairo', 'Plus Jakarta Sans', sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(255, 102, 0, 0.04) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(255, 102, 0, 0.05) 0%, transparent 40%);
        }

        .auth-container {
            width: 100%;
            max-width: 480px;
        }

        .auth-card {
            background: var(--card-bg);
            border-radius: 20px;
            border: 1px solid var(--border-color);
            padding: 2.5rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .auth-card:hover {
            box-shadow: var(--shadow-hover);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .brand-logo-img {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            object-fit: cover;
            margin-bottom: 0.75rem;
            box-shadow: 0 4px 15px rgba(255, 102, 0, 0.25);
        }

        .brand-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .brand-title span {
            color: var(--primary-orange);
        }

        .brand-subtitle {
            font-size: 0.88rem;
            color: var(--text-muted);
            margin-top: 0.35rem;
        }

        /* Demo Accounts Box */
        .demo-roles-box {
            background: #fffaf5;
            border: 1.5px dashed #fed7aa;
            border-radius: 14px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .demo-header {
            font-size: 0.8rem;
            font-weight: 800;
            color: var(--primary-orange);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .demo-buttons-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.45rem;
        }

        .demo-btn {
            background: #ffffff;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            padding: 0.45rem 0.25rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            font-family: inherit;
        }

        .demo-btn:hover {
            background: var(--primary-orange);
            color: #ffffff;
            border-color: var(--primary-orange);
            transform: translateY(-2px);
        }

        .demo-btn:hover span {
            color: #ffffff !important;
        }

        .demo-role-name {
            font-size: 0.75rem;
            font-weight: 800;
            color: var(--text-dark);
        }

        .demo-role-pass {
            font-size: 0.65rem;
            color: var(--text-muted);
            direction: ltr;
        }

        .alert-box {
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 0.88rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-error {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #e11d48;
        }

        .alert-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            height: 48px;
            background: #ffffff;
            border: 1.5px solid var(--border-color);
            border-radius: 12px;
            padding: 0 1rem;
            font-size: 0.95rem;
            font-family: inherit;
            color: var(--text-dark);
            transition: all 0.2s ease;
            outline: none;
        }

        .form-input:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 4px var(--orange-soft);
        }

        .btn-submit {
            width: 100%;
            height: 50px;
            background: var(--primary-orange);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 800;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 14px rgba(255, 102, 0, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .btn-submit:hover {
            background: var(--orange-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 102, 0, 0.45);
        }

        .auth-footer {
            margin-top: 1.75rem;
            text-align: center;
            font-size: 0.88rem;
            color: var(--text-muted);
        }

        .auth-link {
            color: var(--primary-orange);
            text-decoration: none;
            font-weight: 700;
        }

        .auth-link:hover {
            text-decoration: underline;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            margin-top: 1rem;
            color: var(--text-muted);
            font-size: 0.82rem;
            text-decoration: none;
        }

        .back-link:hover {
            color: var(--primary-orange);
        }
    </style>
</head>
<body>

    <div class="auth-container">
        <div class="auth-card">
            
            <div class="auth-header">
                <a href="index.php" style="text-decoration: none;">
                    <img src="assets/logo.png" alt="Bader Phones Logo" class="brand-logo-img" onerror="this.onerror=null; this.src='assets/logo.svg';">
                    <h1 class="brand-title">بدر <span>للهواتف</span></h1>
                </a>
                <p class="brand-subtitle">سجّل دخولك للوصول إلى لوحة التحكم أو متابعة سلة مشترياتك</p>
            </div>

            <!-- Demo Accounts 1-Click Fill -->
            <div class="demo-roles-box">
                <div class="demo-header">
                    <span>🔐</span>
                    <span>حسابات تجريبية سريعة (اضغط للتعبئة):</span>
                </div>
                <div class="demo-buttons-grid">
                    <button type="button" class="demo-btn" onclick="fillAuth('admin@baderphones.om', 'admin123')">
                        <span class="demo-role-name">🛡️ Admin</span>
                        <span class="demo-role-pass">admin123</span>
                    </button>
                    <button type="button" class="demo-btn" onclick="fillAuth('partner@baderphones.om', 'partner123')">
                        <span class="demo-role-name">🏪 Partner</span>
                        <span class="demo-role-pass">partner123</span>
                    </button>
                    <button type="button" class="demo-btn" onclick="fillAuth('customer@baderphones.om', 'customer123')">
                        <span class="demo-role-name">👤 Customer</span>
                        <span class="demo-role-pass">customer123</span>
                    </button>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert-box alert-error">
                    <span>⚠️</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($loggedOut): ?>
                <div class="alert-box alert-info">
                    <span>👋</span>
                    <span>تم تسجيل الخروج بنجاح. نراك قريباً!</span>
                </div>
            <?php elseif ($authRequired): ?>
                <div class="alert-box alert-info">
                    <span>🔒</span>
                    <span>يرجى تسجيل الدخول أولاً للوصول إلى هذه الصفحة.</span>
                </div>
            <?php endif; ?>

            <form action="login_process.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="login-email">البريد الإلكتروني *</label>
                    <input 
                        type="email" 
                        id="login-email" 
                        name="email" 
                        class="form-input" 
                        placeholder="admin@baderphones.om" 
                        value="admin@baderphones.om" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="login-pass">كلمة المرور *</label>
                    <input 
                        type="password" 
                        id="login-pass" 
                        name="password" 
                        class="form-input" 
                        placeholder="••••••••" 
                        value="admin123" 
                        required
                    >
                </div>

                <button type="submit" class="btn-submit">
                    <span>تسجيل الدخول 🚀</span>
                </button>
            </form>

            <div class="auth-footer">
                <p>ليس لديك حساب؟ <a href="register.php" class="auth-link">إنشاء حساب جديد</a></p>
                <div>
                    <a href="index.php" class="back-link">← العودة للمتجر الرئيسي (Bader Phones)</a>
                </div>
            </div>

        </div>
    </div>

    <script>
        function fillAuth(email, pass) {
            document.getElementById('login-email').value = email;
            document.getElementById('login-pass').value = pass;
        }
    </script>
</body>
</html>
