<?php
/**
 * User Profile API Endpoint
 * GET /api/users/profile.php?id=123 (or current logged-in user if id omitted)
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$requestedId = isset($_GET['id']) ? (int)$_GET['id'] : current_user_id();

if (!$requestedId) {
    json_response(false, 'User ID is required.', null, 400);
}

try {
    $pdo = get_db();

    // 1. Fetch user public profile
    $userStmt = $pdo->prepare("
        SELECT id, name, username, bio, location, profile_image, role, status, created_at,
               discord, facebook, contact_email, github, linkedin, twitter, instagram,  whatsapp, website
        FROM users
        WHERE id = :id AND status = 'active'
        LIMIT 1
    ");
    $userStmt->execute([':id' => $requestedId]);
    $user = $userStmt->fetch();

    if (!$user) {
        json_response(false, 'User profile not found.', null, 404);
    }

    $user['avatar_url'] = get_avatar_url($user['profile_image']);
    $user['member_since'] = date('F Y', strtotime($user['created_at']));

    // 2. Fetch skills offered
    $offeredStmt = $pdo->prepare("
        SELECT us.id, us.skill_id, us.proficiency, us.description, s.name AS skill_name, s.category
        FROM user_skills us
        INNER JOIN skills s ON us.skill_id = s.id
        WHERE us.user_id = :id AND us.skill_type = 'OFFER'
        ORDER BY s.name ASC
    ");
    $offeredStmt->execute([':id' => $requestedId]);
    $skillsOffered = $offeredStmt->fetchAll();

    // 3. Fetch skills wanted
    $wantedStmt = $pdo->prepare("
        SELECT us.id, us.skill_id, us.proficiency, us.description, s.name AS skill_name, s.category
        FROM user_skills us
        INNER JOIN skills s ON us.skill_id = s.id
        WHERE us.user_id = :id AND us.skill_type = 'WANT'
        ORDER BY s.name ASC
    ");
    $wantedStmt->execute([':id' => $requestedId]);
    $skillsWanted = $wantedStmt->fetchAll();

    // 4. Fetch reviews and calculate average rating
    $revStmt = $pdo->prepare("
        SELECT r.id, r.rating, r.comment, r.created_at,
               u.id AS reviewer_id, u.name AS reviewer_name, u.username AS reviewer_username, u.profile_image AS reviewer_avatar
        FROM reviews r
        INNER JOIN users u ON r.reviewer_id = u.id
        WHERE r.reviewed_user_id = :id
        ORDER BY r.created_at DESC
    ");
    $revStmt->execute([':id' => $requestedId]);
    $reviews = $revStmt->fetchAll();

    foreach ($reviews as &$rev) {
        $rev['reviewer_avatar_url'] = get_avatar_url($rev['reviewer_avatar']);
        $rev['time_ago'] = time_ago($rev['created_at']);
    }

    $avgRating = 0;
    $totalReviews = count($reviews);
    if ($totalReviews > 0) {
        $sum = array_sum(array_column($reviews, 'rating'));
        $avgRating = round($sum / $totalReviews, 1);
    }

    $isOwnProfile = (current_user_id() === $requestedId);

    json_response(true, 'User profile retrieved.', [
        'user'           => $user,
        'is_own_profile' => $isOwnProfile,
        'skills_offered' => $skillsOffered,
        'skills_wanted'  => $skillsWanted,
        'reviews'        => $reviews,
        'rating'         => [
            'average' => $avgRating,
            'count'   => $totalReviews
        ]
    ]);

} catch (Exception $e) {
    error_log('[User Profile Error] ' . $e->getMessage());
    json_response(false, 'Unable to load profile.', null, 500);
}
