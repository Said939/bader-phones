<?php
// ============================================================================
// بدر للهواتف / Bader Phones - Authentication & Role-Based Access Control (RBAC)
// Module: includes/auth.php
// Architecture:
//   - Tier 1: 'admin'    -> Full system control, users, database, products, orders
//   - Tier 2: 'partner'  -> Shop owner/partner: products, stock, order statuses
//   - Tier 3: 'customer' -> Public customer: cart, checkout, view own orders
// ============================================================================

require_once __DIR__ . '/../config/db.php';

// Safe session startup
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ----------------------------------------------------------------------------
// 1. Role Definitions
// ----------------------------------------------------------------------------
if (!defined('ROLE_ADMIN')) {
    define('ROLE_ADMIN', 'admin');
    define('ROLE_PARTNER', 'partner');
    define('ROLE_CUSTOMER', 'customer');
}

/**
 * Normalizes role string to canonical form (handles legacy aliases).
 */
function normalizeRole($role) {
    $role = strtolower(trim((string)$role));
    if ($role === 'shop' || $role === 'vendor' || $role === 'merchant') {
        return ROLE_PARTNER;
    }
    if ($role === 'user' || $role === 'client' || $role === 'buyer') {
        return ROLE_CUSTOMER;
    }
    if (in_array($role, [ROLE_ADMIN, ROLE_PARTNER, ROLE_CUSTOMER])) {
        return $role;
    }
    return ROLE_CUSTOMER;
}

// ----------------------------------------------------------------------------
// 2. Authentication State Checks
// ----------------------------------------------------------------------------

/**
 * Check if a user is currently logged in.
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Get current authenticated user details.
 */
function getAuthUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id'    => (int)$_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => normalizeRole($_SESSION['user_role'] ?? ROLE_CUSTOMER),
        'phone' => $_SESSION['user_phone'] ?? ''
    ];
}

function getUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function getUserName(): string {
    return $_SESSION['user_name'] ?? '';
}

function getUserEmail(): string {
    return $_SESSION['user_email'] ?? '';
}

function getUserRole(): string {
    return normalizeRole($_SESSION['user_role'] ?? 'guest');
}

// ----------------------------------------------------------------------------
// 3. RBAC Permission Checks
// ----------------------------------------------------------------------------

/**
 * Check if the authenticated user has one of the allowed roles.
 * @param string|array $roles Single role or array of allowed roles
 */
function hasRole($roles): bool {
    if (!isLoggedIn()) {
        return false;
    }

    $currentRole = getUserRole();

    // Developer/Admin has super-user access to everything
    if ($currentRole === ROLE_ADMIN) {
        return true;
    }

    if (is_string($roles)) {
        $roles = [$roles];
    }

    $normalizedRoles = array_map('normalizeRole', $roles);
    return in_array($currentRole, $normalizedRoles, true);
}

function isAdmin(): bool {
    return getUserRole() === ROLE_ADMIN;
}

function isPartner(): bool {
    return getUserRole() === ROLE_PARTNER;
}

function isCustomer(): bool {
    return getUserRole() === ROLE_CUSTOMER;
}

/**
 * Granular Permission Helpers
 */
function canManageUsers(): bool {
    return isAdmin();
}

function canManageSettings(): bool {
    return isAdmin();
}

function canManageProducts(): bool {
    return isAdmin() || isPartner();
}

function canManageOrders(): bool {
    return isAdmin() || isPartner();
}

function canViewOrderHistory(): bool {
    return isLoggedIn();
}

// ----------------------------------------------------------------------------
// 4. Route Guard Middleware
// ----------------------------------------------------------------------------

/**
 * Guard: Requires the user to be authenticated.
 */
function requireLogin(string $redirectUrl = 'login.php'): void {
    if (!isLoggedIn()) {
        $_SESSION['auth_redirect'] = $_SERVER['REQUEST_URI'] ?? 'index.php';
        header("Location: $redirectUrl");
        exit;
    }
}

/**
 * Guard: Requires the user to possess specific role permissions.
 * Redirects unauthorized attempts back with a forbidden status code.
 */
function requireRole($roles, string $forbiddenRedirect = 'index.php'): void {
    requireLogin('login.php');

    if (!hasRole($roles)) {
        $_SESSION['flash_error'] = 'عذراً، لا تملك الصلاحيات الكافية للوصول إلى هذه الصفحة / Access Denied: Insufficient Permissions.';
        header("Location: $forbiddenRedirect?error=forbidden");
        exit;
    }
}

function requireAdmin(string $forbiddenRedirect = 'index.php'): void {
    requireRole([ROLE_ADMIN], $forbiddenRedirect);
}

function requirePartnerOrAdmin(string $forbiddenRedirect = 'index.php'): void {
    requireRole([ROLE_ADMIN, ROLE_PARTNER], $forbiddenRedirect);
}

// ----------------------------------------------------------------------------
// 5. Authentication Actions (Login, Register, Logout)
// ----------------------------------------------------------------------------

/**
 * Authenticates a user against database credentials using password_verify().
 */
function authenticateUser(string $email, string $password): array {
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    if (empty($email) || empty($password)) {
        return [
            'success' => false,
            'message' => 'يرجى إدخال البريد الإلكتروني وكلمة المرور / Please enter both email and password.'
        ];
    }

    $pdo = getDB();

    // Check for developer/admin offline emergency fallback (Said Al-Amri)
    $isAdminEmail = in_array(strtolower($email), ['admin@baderphones.om', 'ssaid9395212@gmail.com']);
    $isAdminPass = in_array($password, ['admin123', 'password123']);

    if (!$pdo) {
        if ($isAdminEmail && $isAdminPass) {
            if (!headers_sent()) {
                session_regenerate_id(true);
            }
            $_SESSION['user_id'] = 1;
            $_SESSION['user_name'] = 'سعيد العامري / Said Al-Amri';
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = ROLE_ADMIN;
            $_SESSION['user_phone'] = '+96897447122';
            return [
                'success' => true,
                'message' => 'مرحباً بك يا مدير النظام (وضع عدم الاتصال) / Welcome Admin (Offline Mode)',
                'user' => getAuthUser()
            ];
        }
        return [
            'success' => false,
            'message' => 'تعذر الاتصال بقاعدة البيانات حالياً / Database connection is currently unavailable.'
        ];
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE LOWER(`email`) = LOWER(:email) LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        // Verify password hash or admin emergency pass
        $passwordValid = false;
        if ($user && password_verify($password, $user['password'])) {
            $passwordValid = true;
        } elseif ($isAdminEmail && $isAdminPass) {
            $passwordValid = true;
        }

        if (!$user || !$passwordValid) {
            return [
                'success' => false,
                'message' => 'بيانات الدخول غير صحيحة / Invalid email or password.'
            ];
        }

        // Check user active status
        if (isset($user['status']) && $user['status'] !== 'active') {
            return [
                'success' => false,
                'message' => 'هذا الحساب معطل حالياً، يرجى التواصل مع الدعم / Your account is inactive or suspended.'
            ];
        }

        // Secure Session Initialization
        if (!headers_sent()) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = normalizeRole($user['role'] ?? ROLE_CUSTOMER);
        $_SESSION['user_phone'] = $user['phone'] ?? '';
        $_SESSION['logged_at'] = time();

        return [
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح / Successfully logged in.',
            'user' => getAuthUser()
        ];

    } catch (PDOException $e) {
        error_log("Auth Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'خطأ أثناء تسجيل الدخول: ' . $e->getMessage()
        ];
    }
}

/**
 * Registers a new customer into the database with password_hash().
 */
function registerCustomer(string $name, string $email, string $password, string $phone = ''): array {
    $name = trim($name);
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    $phone = trim($phone);

    if (empty($name) || empty($email) || empty($password)) {
        return [
            'success' => false,
            'message' => 'يرجى ملء جميع الحقول المطلوبة / Please fill all required fields.'
        ];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => 'صيغة البريد الإلكتروني غير صالحة / Invalid email format.'
        ];
    }

    if (strlen($password) < 6) {
        return [
            'success' => false,
            'message' => 'كلمة المرور يجب أن لا تقل عن 6 أحرف / Password must be at least 6 characters.'
        ];
    }

    $pdo = getDB();
    if (!$pdo) {
        return [
            'success' => false,
            'message' => 'تعذر الاتصال بقاعدة البيانات / Database connection failure.'
        ];
    }

    try {
        // Check uniqueness
        $stmtCheck = $pdo->prepare("SELECT id FROM `users` WHERE LOWER(`email`) = LOWER(:email) LIMIT 1");
        $stmtCheck->execute(['email' => $email]);
        if ($stmtCheck->fetch()) {
            return [
                'success' => false,
                'message' => 'هذا البريد مسجل مسبقاً، يرجى تسجيل الدخول / Email is already registered.'
            ];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmtInsert = $pdo->prepare("
            INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `status`)
            VALUES (:name, :email, :password, :phone, :role, 'active')
        ");

        $stmtInsert->execute([
            'name'     => $name,
            'email'    => $email,
            'password' => $hash,
            'phone'    => $phone,
            'role'     => ROLE_CUSTOMER // Strictly enforce customer role for public registration
        ]);

        $newUserId = (int)$pdo->lastInsertId();

        // Auto login newly registered customer
        if (!headers_sent()) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = ROLE_CUSTOMER;
        $_SESSION['user_phone'] = $phone;
        $_SESSION['logged_at'] = time();

        return [
            'success' => true,
            'message' => 'تم إنشاء الحساب بنجاح / Account registered successfully.',
            'user_id' => $newUserId
        ];

    } catch (PDOException $e) {
        error_log("Registration Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'حدث خطأ في قاعدة البيانات أثناء إنشاء الحساب / Registration database error.'
        ];
    }
}

/**
 * Destroys authentication session safely while preserving user preferences (like language).
 */
function logoutUser(): void {
    $lang = $_SESSION['lang'] ?? ($_COOKIE['lang'] ?? 'ar');

    unset($_SESSION['user_id']);
    unset($_SESSION['user_name']);
    unset($_SESSION['user_email']);
    unset($_SESSION['user_role']);
    unset($_SESSION['user_phone']);
    unset($_SESSION['logged_at']);
    unset($_SESSION['auth_redirect']);

    $_SESSION['lang'] = $lang;
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
}
