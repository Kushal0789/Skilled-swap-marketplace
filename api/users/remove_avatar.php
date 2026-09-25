<?php
/**
 * Remove Avatar API Endpoint
 * POST /api/users/remove_avatar.php
 * Clears the user's profile_image, deletes the file, returns default avatar URL.
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Method not allowed. Use POST.', null, 405);
}

$userId = current_user_id();

try {
    $pdo = get_db();

    // Fetch current avatar filename
    $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch();

    if ($row && !empty($row['profile_image']) && $row['profile_image'] !== 'default-avatar.svg') {
        // Delete the physical file
        $filePath = __DIR__ . '/../../assets/uploads/avatars/' . basename($row['profile_image']);
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    // Clear from DB
    $update = $pdo->prepare("UPDATE users SET profile_image = NULL, updated_at = NOW() WHERE id = :id");
    $update->execute([':id' => $userId]);

    // Refresh session
    $userStmt = $pdo->prepare("SELECT id, name, username, email, bio, location, profile_image, role, status,
               discord, facebook, contact_email, github, linkedin, twitter, instagram, whatsapp, website
        FROM users WHERE id = :id");
    $userStmt->execute([':id' => $userId]);
    $updatedUser = $userStmt->fetch();
    $_SESSION['user'] = $updatedUser;

    json_response(true, 'Profile photo removed.', [
        'avatar_url' => get_avatar_url(null)
    ]);

} catch (Exception $e) {
    error_log('[Remove Avatar Error] ' . $e->getMessage());
    json_response(false, 'Failed to remove profile photo.', null, 500);
}
