<?php
/**
 * Authentication & Session Management (Application Layer)
 * Skill Swap Marketplace
 */

if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

// Start session securely if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session cookie parameters
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    
    session_set_cookie_params([
        'lifetime' => 86400 * 7, // 7 days
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}

/**
 * Check if the current client is authenticated.
 *
 * @return bool
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user ID.
 *
 * @return int|null
 */
function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Get the currently authenticated user record from session/database.
 *
 * @return array|null
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    
    // Return cached session info or query fresh from DB if needed
    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    require_once __DIR__ . '/../config/db.php';
    $pdo = get_db();
    $stmt = $pdo->prepare("SELECT id, name, username, email, bio, location, profile_image, role, status, created_at FROM users WHERE id = :id AND status = 'active' LIMIT 1");
    $stmt->execute([':id' => current_user_id()]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user'] = $user;
        return $user;
    }

    // User was removed or suspended
    logout_user();
    return null;
}

/**
 * Check if current user has administrator role.
 *
 * @return bool
 */
function is_admin(): bool {
    $user = current_user();
    return $user && ($user['role'] ?? '') === 'admin';
}

/**
 * Require user to be authenticated. Redirects to login or responds with 401 JSON.
 */
function require_auth(string $redirect = 'login.php'): void {
    if (!is_logged_in()) {
        $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
        if ($isApi) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Authentication required. Please log in.',
                'errors'  => ['unauthorized']
            ]);
            exit;
        }

        // Return URL handling
        $currentUri = urlencode($_SERVER['REQUEST_URI'] ?? '');
        header("Location: {$redirect}?redirect={$currentUri}");
        exit;
    }
}

/**
 * Require user to have admin privileges.
 */
function require_admin(): void {
    require_auth();
    if (!is_admin()) {
        $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
        if ($isApi) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Access denied: Administrator role required.',
                'errors'  => ['forbidden']
            ]);
            exit;
        }

        http_response_code(403);
        die('Access denied: You do not have permission to view this page.');
    }
}

/**
 * Login a validated user into session.
 *
 * @param array $user
 */
function login_user(array $user): void {
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user'] = [
        'id'            => (int)$user['id'],
        'name'          => $user['name'],
        'username'      => $user['username'],
        'email'         => $user['email'],
        'bio'           => $user['bio'] ?? '',
        'location'      => $user['location'] ?? '',
        'profile_image' => $user['profile_image'] ?? 'default-avatar.svg',
        'role'          => $user['role'] ?? 'user',
        'status'        => $user['status'] ?? 'active'
    ];
}

/**
 * Safely destroy user session.
 */
function logout_user(): void {
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * Generate CSRF token for forms and AJAX headers.
 *
 * @return string
 */
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from POST body or X-CSRF-Token header.
 *
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token(?string $token = null): bool {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }

    $token = $token 
        ?? $_SERVER['HTTP_X_CSRF_TOKEN'] 
        ?? $_POST['csrf_token'] 
        ?? null;

    if (!$token) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}
