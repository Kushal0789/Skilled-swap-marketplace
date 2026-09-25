<?php
/**
 * Update Profile API Endpoint
 * POST /api/users/update_profile.php (Supports Multipart Form Data)
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

$userId = current_user_id();

// Supports regular POST or JSON
$name         = trim($_POST['name'] ?? '');
$bio          = trim($_POST['bio'] ?? '');
$location     = trim($_POST['location'] ?? '');
$discord      = trim($_POST['discord'] ?? '');
$facebook     = trim($_POST['facebook'] ?? '');
$contactEmail = trim($_POST['contact_email'] ?? '');
$github       = trim($_POST['github'] ?? '');
$linkedin     = trim($_POST['linkedin'] ?? '');
$twitter      = trim($_POST['twitter'] ?? '');
$instagram    = trim($_POST['instagram'] ?? '');
$whatsapp     = trim($_POST['whatsapp'] ?? '');
$website      = trim($_POST['website'] ?? '');

if (empty($name)) {
    // If sent as raw JSON
    $input        = get_json_input();
    $name         = trim($input['name'] ?? '');
    $bio          = trim($input['bio'] ?? '');
    $location     = trim($input['location'] ?? '');
    $discord      = trim($input['discord'] ?? '');
    $facebook     = trim($input['facebook'] ?? '');
    $contactEmail = trim($input['contact_email'] ?? '');
    $github       = trim($input['github'] ?? '');
    $linkedin     = trim($input['linkedin'] ?? '');
    $twitter      = trim($input['twitter'] ?? '');
    $instagram    = trim($input['instagram'] ?? '');
    $whatsapp     = trim($input['whatsapp'] ?? '');
    $website      = trim($input['website'] ?? '');
}

if (empty($name)) {
    json_response(false, 'Name cannot be empty.', null, 422, ['name' => 'Name is required.']);
}

try {
    $pdo = get_db();

    // Check if new profile image is uploaded
    $avatarFilename = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadRes = handle_avatar_upload($_FILES['profile_image']);
        if (!$uploadRes['valid']) {
            json_response(false, $uploadRes['error'], null, 422, ['profile_image' => $uploadRes['error']]);
        }
        $avatarFilename = $uploadRes['filename'];
    }

    $params = [
        ':name'          => $name,
        ':bio'           => $bio,
        ':location'      => $location,
        ':discord'       => !empty($discord) ? $discord : null,
        ':facebook'      => !empty($facebook) ? $facebook : null,
        ':contact_email' => !empty($contactEmail) ? $contactEmail : null,
        ':github'        => !empty($github) ? $github : null,
        ':linkedin'      => !empty($linkedin) ? $linkedin : null,
        ':twitter'       => !empty($twitter) ? $twitter : null,
        ':instagram'     => !empty($instagram) ? $instagram : null,
        ':whatsapp'      => !empty($whatsapp) ? $whatsapp : null,
        ':website'       => !empty($website) ? $website : null,
        ':id'            => $userId
    ];

    if ($avatarFilename !== null) {
        $params[':img'] = $avatarFilename;
        $stmt = $pdo->prepare("
            UPDATE users
            SET name = :name, 
                bio = :bio, 
                location = :location, 
                discord = :discord, 
                facebook = :facebook, 
                contact_email = :contact_email, 
                github = :github,
                linkedin = :linkedin,
                twitter = :twitter,
                instagram = :instagram,
                whatsapp = :whatsapp,
                website = :website,
                profile_image = :img, 
                updated_at = NOW()
            WHERE id = :id
        ");
    } else {
        $stmt = $pdo->prepare("
            UPDATE users
            SET name = :name, 
                bio = :bio, 
                location = :location, 
                discord = :discord, 
                facebook = :facebook, 
                contact_email = :contact_email, 
                github = :github,
                linkedin = :linkedin,
                twitter = :twitter,
                instagram = :instagram,
                whatsapp = :whatsapp,
                website = :website,
                updated_at = NOW()
            WHERE id = :id
        ");
    }
    $stmt->execute($params);

    // Refresh session data
    $userStmt = $pdo->prepare("
        SELECT id, name, username, email, bio, location, profile_image, role, status,
               discord, facebook, contact_email, github, linkedin, twitter, instagram, whatsapp, website 
        FROM users WHERE id = :id
    ");
    $userStmt->execute([':id' => $userId]);
    $updatedUser = $userStmt->fetch();

    $_SESSION['user'] = $updatedUser;

    json_response(true, 'Profile and social links updated successfully!', [
        'user'       => $updatedUser,
        'avatar_url' => get_avatar_url($updatedUser['profile_image'])
    ]);

} catch (Exception $e) {
    error_log('[Profile Update Error] ' . $e->getMessage());
    json_response(false, 'Failed to update profile.', null, 500);
}