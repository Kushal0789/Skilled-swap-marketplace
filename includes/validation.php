<?php
/**
 * Validation & File Security (Application Layer)
 * Skill Swap Marketplace
 */

if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

/**
 * Validate email address.
 *
 * @param string $email
 * @return bool
 */
function validate_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate username format and length.
 *
 * @param string $username
 * @return array List of error messages (empty if valid)
 */
function validate_username(string $username): array {
    $errors = [];
    $len = strlen($username);

    if ($len < 3 || $len > 30) {
        $errors[] = 'Username must be between 3 and 30 characters.';
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username may only contain letters, numbers, and underscores.';
    }

    return $errors;
}

/**
 * Validate password requirements.
 *
 * @param string $password
 * @return array List of error messages (empty if valid)
 */
function validate_password(string $password): array {
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain both letters and numbers.';
    }

    return $errors;
}

/**
 * Validates and safely processes uploaded profile image.
 *
 * @param array $file $_FILES['profile_image']
 * @return array ['valid' => bool, 'filename' => string|null, 'error' => string|null]
 */
function handle_avatar_upload(array $file): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['valid' => false, 'filename' => null, 'error' => 'Invalid file upload parameter.'];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['valid' => false, 'filename' => null, 'error' => 'No image was selected for upload.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'filename' => null, 'error' => 'File upload failed with error code: ' . $file['error']];
    }

    // Limit maximum file size (2 MB)
    $maxBytes = 2 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        return ['valid' => false, 'filename' => null, 'error' => 'Image file size must be less than 2MB.'];
    }

    // Validate MIME type with finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!array_key_exists($mime, $allowedMimes)) {
        return ['valid' => false, 'filename' => null, 'error' => 'Only JPG, PNG, and WebP images are allowed.'];
    }

    $ext = $allowedMimes[$mime];
    // Generate cryptographically random filename to prevent collisions and directory traversal
    $newFilename = 'avatar_' . bin2hex(random_bytes(16)) . '.' . $ext;
    $targetDir = __DIR__ . '/../assets/uploads/avatars/';

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $destination = $targetDir . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['valid' => false, 'filename' => null, 'error' => 'Could not save uploaded image to destination.'];
    }

    return ['valid' => true, 'filename' => $newFilename, 'error' => null];
}
