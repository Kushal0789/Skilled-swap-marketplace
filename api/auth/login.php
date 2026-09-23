<?php
/**
 * Login API Endpoint (Business Layer)
 * POST /api/auth/login.php
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed. Use POST.', null, 405);
}

$input = get_json_input();
$identity = trim($input['identity'] ?? '');
$password = $input['password'] ?? '';

$errors = [];

if (empty($identity)) {
    $errors['identity'] = 'Please enter your email or username.';
}

if (empty($password)) {
    $errors['password'] = 'Please enter your password.';
}

if (!empty($errors)) {
    json_response(false, 'Validation failed. Please correct errors.', null, 422, $errors);
}

try {
    $pdo = get_db();
    $stmt = $pdo->prepare("
        SELECT id, name, username, email, password, bio, location, profile_image, role, status, created_at
        FROM users
        WHERE email = :identity_email OR username = :identity_username
        LIMIT 1
    ");
    $stmt->execute([
        ':identity_email'    => $identity,
        ':identity_username' => $identity
    ]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        json_response(false, 'Invalid credentials. Please verify your email/username and password.', null, 401, ['credentials' => 'Invalid email/username or password.']);
    }

    if ($user['status'] === 'suspended') {
        json_response(false, 'Your account has been suspended by an administrator. Please contact support.', null, 403, ['account' => 'Account suspended.']);
    }

    // Set user session
    login_user($user);

    // Remove password hash from response
    unset($user['password']);

    $redirectUrl = ($user['role'] === 'admin' && isset($input['admin_redirect'])) ? 'admin.php' : 'dashboard.php';

    json_response(true, 'Login successful! Welcome back, ' . $user['name'] . '.', [
        'user'        => $user,
        'redirect_to' => $redirectUrl
    ]);

} catch (Exception $e) {
    error_log('[Login Error] ' . $e->getMessage());
    json_response(false, 'A server error occurred during login. Please try again.', null, 500);
}
