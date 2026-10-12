<?php
// ============================================================================
// Bader Phones / بدر للهواتف - Registration Page (register.php)
// Aesthetic: Modern White & Vibrant Tech Orange (#ff6600), Responsive UI
// ============================================================================

require_once __DIR__ . '/auth.php';

// If already logged in, redirect based on role
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

$error = $_SESSION['register_error'] ?? null;
unset($_SESSION['register_error']);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب جديد | بدر للهواتف (Bader Phones)</title>
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
            margin-bottom: 2rem;
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

        .alert-error {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #e11d48;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 0.88rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
            transition: color 0.2s;
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
            transition: color 0.2s;
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
                <p class="brand-subtitle">أنشئ حسابك للاستمتاع بأفضل أسعار الهواتف وتتبع طلباتك</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert-error">
                    <span>⚠️</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="register_process.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="name">الاسم الكامل *</label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-input" 
                        placeholder="مثال: أحمد الحارثي" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">البريد الإلكتروني *</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="name@example.com" 
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">كلمة المرور (6 خانات على الأقل) *</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="••••••••" 
                        required
                        minlength="6"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">تأكيد كلمة المرور *</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-input" 
                        placeholder="••••••••" 
                        required
                        minlength="6"
                    >
                </div>

                <button type="submit" class="btn-submit">
                    <span>إنشاء الحساب الآن 🚀</span>
                </button>
            </form>

            <div class="auth-footer">
                <p>لديك حساب بالفعل؟ <a href="login.php" class="auth-link">تسجيل الدخول</a></p>
                <div>
                    <a href="index.php" class="back-link">← العودة للمتجر الرئيسي (Bader Phones)</a>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
