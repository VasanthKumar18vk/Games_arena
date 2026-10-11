<?php
/**
 * Games Arena - Authentication & Security Module
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Returns current CSRF token
 */
function csrf_token(): string {
    return $_SESSION['csrf_token'] ?? '';
}

/**
 * Output hidden input with CSRF token
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Validate CSRF token from POST request
 */
function validate_csrf(): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
    return true;
}

/**
 * Check if user is logged in
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is administrator
 */
function is_admin(): bool {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if user is customer
 */
function is_customer(): bool {
    return is_logged_in() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer';
}

/**
 * Get current logged in user ID
 */
function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Get current user data array
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    $db = get_db();
    if (!$db) return null;

    $stmt = $db->prepare("SELECT user_id, name, email, phone, profile_image, role, language, theme, status, created_at FROM users WHERE user_id = ?");
    $stmt->execute([current_user_id()]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/**
 * Route protection: require login
 */
function require_login(string $redirectUrl = 'login.php'): void {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please log in to continue.';
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';
        header("Location: " . BASE_URL . "/" . ltrim($redirectUrl, '/'));
        exit;
    }

    // Check if user is blocked
    $user = current_user();
    if ($user && $user['status'] === 'blocked') {
        session_destroy();
        session_start();
        $_SESSION['flash_error'] = 'Your account has been suspended. Please contact support.';
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }
}

/**
 * Route protection: require admin role
 */
function require_admin(): void {
    require_login('login.php');
    if (!is_admin()) {
        $_SESSION['flash_error'] = 'Access denied. Administrator privileges required.';
        header("Location: " . BASE_URL . "/customer/dashboard.php");
        exit;
    }
}

/**
 * Route protection: require customer role (or allow admin to preview)
 */
function require_customer(): void {
    require_login('login.php');
    // Allow customer or redirect admin to admin dashboard if accessing customer-only endpoints
    if (is_admin()) {
        // Admin can browse customer view or redirect as desired
    }
}

/**
 * Sanitize string output
 */
function e($string): string {
    return htmlspecialchars((string)($string ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize string input
 */
function sanitize(string $data): string {
    return trim(strip_tags($data));
}
