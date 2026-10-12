<?php
// ============================================================================
// Bader Phones / بدر للهواتف - Login Processor (login_process.php)
// Features: Credential Verification (password_verify), Session Initialization,
// Strict Role-Based Redirection:
//   - 'admin'    -> admin/dashboard.php
//   - 'partner'  -> partner/dashboard.php
//   - 'customer' -> index.php
// ============================================================================

require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['login_error'] = 'يرجى إدخال البريد الإلكتروني وكلمة المرور / Please provide email and password.';
    header("Location: login.php");
    exit;
}

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Successful login
        initUserSession((int)$user['id'], $user['name'], $user['email'], $user['role']);

        // Check if there was an intended URL prior to login
        $intendedUrl = $_SESSION['intended_url'] ?? null;
        unset($_SESSION['intended_url']);

        if ($intendedUrl && !preg_match('/login|register/i', $intendedUrl)) {
            header("Location: " . $intendedUrl);
            exit;
        }

        // Strict Role-based routing
        if ($user['role'] === 'admin') {
            header("Location: admin/dashboard.php");
        } elseif ($user['role'] === 'partner') {
            header("Location: partner/dashboard.php");
        } else {
            header("Location: index.php?logged_in=1");
        }
        exit;

    } else {
        $_SESSION['login_error'] = 'بيانات الدخول غير صحيحة. يرجى التحقق من البريد أو كلمة المرور / Invalid email or password.';
        header("Location: login.php");
        exit;
    }

} catch (PDOException $e) {
    error_log("Login Exception: " . $e->getMessage());
    $_SESSION['login_error'] = 'تعذر الاتصال بقاعدة البيانات. يرجى المحاولة لاحقاً / Database connection error.';
    header("Location: login.php");
    exit;
}
