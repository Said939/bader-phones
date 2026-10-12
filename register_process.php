<?php
// ============================================================================
// Bader Phones / بدر للهواتف - Registration Processor (register_process.php)
// Features: Input Sanitization, Email Uniqueness, Password Hashing (password_hash),
// Default 'customer' Role Assignment, Safe Session Initialization
// ============================================================================

require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit;
}

$name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
    $_SESSION['register_error'] = 'يرجى ملء جميع الحقول المطلوبة / Please fill all required fields.';
    header("Location: register.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_error'] = 'صيغة البريد الإلكتروني غير صالحة / Invalid email address.';
    header("Location: register.php");
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['register_error'] = 'كلمة المرور يجب أن لا تقل عن 6 خانات / Password must be at least 6 characters.';
    header("Location: register.php");
    exit;
}

if ($password !== $confirm_password) {
    $_SESSION['register_error'] = 'كلمتا المرور غير متطابقتين / Passwords do not match.';
    header("Location: register.php");
    exit;
}

try {
    $pdo = getDBConnection();

    // Check if email already registered
    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
    $stmtCheck->execute(['email' => $email]);
    if ($stmtCheck->fetch()) {
        $_SESSION['register_error'] = 'هذا البريد الإلكتروني مسجل بالفعل، يرجى تسجيل الدخول / Email already registered. Please log in.';
        header("Location: register.php");
        exit;
    }

    // Securely hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user strictly with 'customer' role
    $stmtInsert = $pdo->prepare("
        INSERT INTO users (name, email, password, role) 
        VALUES (:name, :email, :password, 'customer')
    ");
    $stmtInsert->execute([
        'name'     => $name,
        'email'    => $email,
        'password' => $hashedPassword
    ]);

    $newUserId = (int)$pdo->lastInsertId();

    // Auto-login new customer
    initUserSession($newUserId, $name, $email, 'customer');

    // Redirect to store catalog
    header("Location: index.php?registered=1");
    exit;

} catch (PDOException $e) {
    error_log("Registration Exception: " . $e->getMessage());
    $_SESSION['register_error'] = 'حدث خطأ في النظام أثناء إنشاء الحساب، يرجى المحاولة لاحقاً.';
    header("Location: register.php");
    exit;
}
