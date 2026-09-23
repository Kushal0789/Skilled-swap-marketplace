<?php
/**
 * Submit Review API Endpoint
 * POST /api/reviews/add.php
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

$reviewerId = current_user_id();
$input = get_json_input();

$reviewedUserId = (int)($input['reviewed_user_id'] ?? 0);
$rating = (int)($input['rating'] ?? 0);
$comment = trim($input['comment'] ?? '');

if ($reviewedUserId <= 0) {
    json_response(false, 'Target user is required.', null, 422);
}

if ($reviewedUserId === $reviewerId) {
    json_response(false, 'You cannot review yourself.', null, 422);
}

if ($rating < 1 || $rating > 5) {
    json_response(false, 'Rating must be between 1 and 5 stars.', null, 422);
}

if (empty($comment)) {
    json_response(false, 'Please write a brief feedback comment.', null, 422);
}

try {
    $pdo = get_db();

    // Verify reviewed user exists
    $uStmt = $pdo->prepare("SELECT id, name FROM users WHERE id = :id AND status = 'active'");
    $uStmt->execute([':id' => $reviewedUserId]);
    $targetUser = $uStmt->fetch();
    if (!$targetUser) {
        json_response(false, 'Target user not found.', null, 404);
    }

    // Check for duplicate review
    $checkStmt = $pdo->prepare("SELECT id FROM reviews WHERE reviewer_id = :r_id AND reviewed_user_id = :t_id LIMIT 1");
    $checkStmt->execute([':r_id' => $reviewerId, ':t_id' => $reviewedUserId]);
    if ($checkStmt->fetch()) {
        json_response(false, 'You have already reviewed this member.', null, 409);
    }

    // Insert review
    $insertStmt = $pdo->prepare("
        INSERT INTO reviews (reviewer_id, reviewed_user_id, rating, comment, created_at)
        VALUES (:rid, :tuid, :rating, :comment, NOW())
    ");
    $insertStmt->execute([
        ':rid'     => $reviewerId,
        ':tuid'    => $reviewedUserId,
        ':rating'  => $rating,
        ':comment' => $comment
    ]);

    $reviewId = (int)$pdo->lastInsertId();

    // Notify reviewed user
    $reviewer = current_user();
    create_notification(
        $pdo,
        $reviewedUserId,
        'new_review',
        'New Review Received!',
        "{$reviewer['name']} left you a {$rating}-star review.",
        $reviewId
    );

    json_response(true, 'Review submitted successfully! Thank you for supporting the community.', [
        'review_id' => $reviewId,
        'rating'    => $rating
    ], 201);

} catch (Exception $e) {
    error_log('[Submit Review Error] ' . $e->getMessage());
    json_response(false, 'Failed to submit review.', null, 500);
}
