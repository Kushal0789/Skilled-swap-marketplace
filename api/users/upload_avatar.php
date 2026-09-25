<?php
/**
 * Upload Avatar API Endpoint
 * POST /api/users/upload_avatar.php (multipart/form-data with 'avatar' file)
 * Handles profile photo upload independently of other profile fields.
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/validation.php';

header('Content-Type: application/json; charset=utf-8');
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed. Use POST.', null, 405);
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
    json_response(false, 'No image file provided. Send a file with field name "avatar".', null, 422);
}

$userId = current_user_id();

try {
    $uploadRes = handle_avatar_upload($_FILES['avatar']);

    if (!$uploadRes['valid']) {
        json_response(false, $uploadRes['error'], null, 422);
    }

    $newFilename = $uploadRes['filename'];
    $pdo = get_db();

    // Delete old avatar file from disk
    $oldStmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = :id LIMIT 1");
    $oldStmt->execute([':id' => $userId]);
    $oldRow = $oldStmt->fetch();
    if ($oldRow && !empty($oldRow['profile_image']) && $oldRow['profile_image'] !== 'default-avatar.svg') {
        $oldPath = __DIR__ . '/../../assets/uploads/avatars/' . basename($oldRow['profile_image']);
        if (file_exists($oldPath)) {
            @unlink($oldPath);
        }
    }

    // Save new filename
    $stmt = $pdo->prepare("UPDATE users SET profile_image = :img, updated_at = NOW() WHERE id = :id");
    $stmt->execute([':img' => $newFilename, ':id' => $userId]);

    // Refresh session
    $userStmt = $pdo->prepare("SELECT id, name, username, email, bio, location, profile_image, role, status,
               discord, facebook, contact_email, github, linkedin, twitter, instagram, whatsapp, website
        FROM users WHERE id = :id");
    $userStmt->execute([':id' => $userId]);
    $updatedUser = $userStmt->fetch();
    $_SESSION['user'] = $updatedUser;

    json_response(true, 'Profile photo updated successfully!', [
        'avatar_url' => get_avatar_url($newFilename)
    ]);

} catch (Exception $e) {
    error_log('[Upload Avatar Error] ' . $e->getMessage());
    json_response(false, 'Failed to upload profile photo.', null, 500);
}
