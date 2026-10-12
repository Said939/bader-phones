<?php
// ============================================================================
// Bader Phones / بدر للهواتف - RBAC & Authentication Middleware (auth.php)
// Features: Session Startup, Role Verification Guards, Reusable Helper Functions
// ============================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Check if a user is currently logged in.
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Get current authenticated user details from session.
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id'    => (int)$_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? 'Guest',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'customer'
    ];
}

/**
 * Get current user role ('admin', 'partner', 'customer', or 'guest').
 */
function getCurrentRole(): string {
    return $_SESSION['user_role'] ?? 'guest';
}

function isAdmin(): bool {
    return getCurrentRole() === 'admin';
}

function isPartner(): bool {
    return getCurrentRole() === 'partner';
}

function isCustomer(): bool {
    return getCurrentRole() === 'customer';
}

/**
 * RBAC Guard Middleware: Restricts page access to specific roles.
 * 
 * @param string|array $required_roles Role or array of allowed roles (e.g. 'admin' or ['admin', 'partner'])
 * @param string $loginRedirect URL to redirect unauthenticated users
 * @param string $unauthorizedRedirect URL to redirect users lacking permission
 */
function checkRole($required_roles, string $loginRedirect = '/elite-store/login.php', string $unauthorizedRedirect = '/elite-store/index.php'): void {
    if (!isLoggedIn()) {
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '/elite-store/index.php';
        header("Location: $loginRedirect?error=auth_required");
        exit;
    }

    if (is_string($required_roles)) {
        $required_roles = [$required_roles];
    }

    $currentRole = getCurrentRole();

    // Admins always have super-user access to all dashboards
    if ($currentRole === 'admin') {
        return;
    }

    if (!in_array($currentRole, $required_roles, true)) {
        header("Location: $unauthorizedRedirect?error=unauthorized");
        exit;
    }
}

/**
 * Initialize authenticated session variables safely.
 */
function initUserSession(int $id, string $name, string $email, string $role): void {
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
    $_SESSION['user_id'] = $id;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = $role;
    $_SESSION['logged_at'] = time();
}

/**
 * Safely destroy session and clear auth state.
 */
function destroyUserSession(): void {
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_role'], $_SESSION['logged_at']);
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}
