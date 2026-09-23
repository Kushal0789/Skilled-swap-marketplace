<?php
/**
 * Registration API Endpoint (Business Layer)
 * POST /api/auth/register.php
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/validation.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed. Use POST.', null, 405);
}

$input = get_json_input();
$name = trim($input['name'] ?? '');
$username = strtolower(trim($input['username'] ?? ''));
$email = strtolower(trim($input['email'] ?? ''));
$password = $input['password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

$errors = [];

// Name validation
if (empty($name)) {
    $errors['name'] = 'Full name is required.';
} elseif (strlen($name) < 2 || strlen($name) > 100) {
    $errors['name'] = 'Name must be between 2 and 100 characters.';
}

// Username validation
$usernameErrors = validate_username($username);
if (!empty($usernameErrors)) {
    $errors['username'] = $usernameErrors[0];
}

// Email validation
if (empty($email) || !validate_email($email)) {
    $errors['email'] = 'A valid email address is required.';
}

// Password validation
$passwordErrors = validate_password($password);
if (!empty($passwordErrors)) {
    $errors['password'] = $passwordErrors[0];
}

// Confirm password
if ($password !== $confirmPassword) {
    $errors['confirm_password'] = 'Passwords do not match.';
}

try {
    $pdo = get_db();

    // Check duplicate username
    if (empty($errors['username'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        if ($stmt->fetch()) {
            $errors['username'] = 'This username is already taken. Please choose another.';
        }
    }

    // Check duplicate email
    if (empty($errors['email'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            $errors['email'] = 'An account with this email already exists.';
        }
    }

    if (!empty($errors)) {
        json_response(false, 'Validation failed. Please correct the highlighted errors.', null, 422, $errors);
    }

    // Hash password with standard secure bcrypt algorithm
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $insertStmt = $pdo->prepare("
        INSERT INTO users (name, username, email, password, profile_image, role, status, created_at)
        VALUES (:name, :username, :email, :password, 'default-avatar.svg', 'user', 'active', NOW())
    ");
    $insertStmt->execute([
        ':name'     => $name,
        ':username' => $username,
        ':email'    => $email,
        ':password' => $passwordHash
    ]);

    $newUserId = (int)$pdo->lastInsertId();

    // Fetch created record
    $userStmt = $pdo->prepare("SELECT id, name, username, email, bio, location, profile_image, role, status FROM users WHERE id = :id");
    $userStmt->execute([':id' => $newUserId]);
    $newUser = $userStmt->fetch();

    // Auto-login newly registered user
    login_user($newUser);

    // Create a welcome notification
    create_notification(
        $pdo,
        $newUserId,
        'welcome',
        'Welcome to Skill Swap!',
        'Get started by adding skills you can offer and skills you want to learn in your profile.',
        $newUserId
    );

    json_response(true, 'Account created successfully! Welcome to Skill Swap.', [
        'user'        => $newUser,
        'redirect_to' => 'profile.php?welcome=1'
    ], 201);

} catch (Exception $e) {
    error_log('[Registration Error] ' . $e->getMessage());
    json_response(false, 'An unexpected server error occurred during registration.', null, 500);
}
