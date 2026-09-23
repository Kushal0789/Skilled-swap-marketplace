<?php
/**
 * User & Skill Discovery Search API Endpoint
 * GET /api/users/search.php
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$query = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$skillId = !empty($_GET['skill_id']) ? (int)$_GET['skill_id'] : null;
$skillType = strtoupper(trim($_GET['skill_type'] ?? ''));
$proficiency = trim($_GET['proficiency'] ?? '');
$location = trim($_GET['location'] ?? '');
$currentUid = current_user_id() ?? 0;

try {
    $pdo = get_db();

    // Base query for active non-admin users
    $sql = "
        SELECT DISTINCT u.id, u.name, u.username, u.bio, u.location, u.profile_image, u.created_at
        FROM users u
        LEFT JOIN user_skills us ON u.id = us.user_id
        LEFT JOIN skills s ON us.skill_id = s.id
        WHERE u.status = 'active'
          AND u.role != 'admin'
          AND u.id != :current_uid
    ";

    $params = [':current_uid' => $currentUid];

    if (!empty($query)) {
        $sql .= " AND (u.name LIKE :q1 OR u.username LIKE :q2 OR u.bio LIKE :q3 OR s.name LIKE :q4 OR u.location LIKE :q5)";
        $wildcard = '%' . $query . '%';
        $params[':q1'] = $wildcard;
        $params[':q2'] = $wildcard;
        $params[':q3'] = $wildcard;
        $params[':q4'] = $wildcard;
        $params[':q5'] = $wildcard;
    }

    if (!empty($category)) {
        $sql .= " AND s.category = :category";
        $params[':category'] = $category;
    }

    if (!empty($skillId)) {
        $sql .= " AND us.skill_id = :skill_id";
        $params[':skill_id'] = $skillId;
    }

    if (in_array($skillType, ['OFFER', 'WANT'], true)) {
        $sql .= " AND us.skill_type = :skill_type";
        $params[':skill_type'] = $skillType;
    }

    if (!empty($proficiency)) {
        $sql .= " AND us.proficiency = :proficiency";
        $params[':proficiency'] = $proficiency;
    }

    if (!empty($location)) {
        $sql .= " AND u.location LIKE :loc";
        $params[':loc'] = '%' . $location . '%';
    }

    $sql .= " ORDER BY u.created_at DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    // Hydrate each user with their skills and average ratings
    foreach ($users as &$u) {
        $u['avatar_url'] = get_avatar_url($u['profile_image']);
        $u['member_since'] = date('M Y', strtotime($u['created_at']));

        // Skills offered
        $offStmt = $pdo->prepare("
            SELECT s.id, s.name, us.proficiency, us.description, s.category
            FROM user_skills us
            INNER JOIN skills s ON us.skill_id = s.id
            WHERE us.user_id = :uid AND us.skill_type = 'OFFER'
            ORDER BY s.name ASC
        ");
        $offStmt->execute([':uid' => $u['id']]);
        $u['skills_offered'] = $offStmt->fetchAll();

        // Skills wanted
        $wantStmt = $pdo->prepare("
            SELECT s.id, s.name, us.proficiency, us.description, s.category
            FROM user_skills us
            INNER JOIN skills s ON us.skill_id = s.id
            WHERE us.user_id = :uid AND us.skill_type = 'WANT'
            ORDER BY s.name ASC
        ");
        $wantStmt->execute([':uid' => $u['id']]);
        $u['skills_wanted'] = $wantStmt->fetchAll();

        // Rating
        $rStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(id) as total_reviews FROM reviews WHERE reviewed_user_id = :uid");
        $rStmt->execute([':uid' => $u['id']]);
        $rData = $rStmt->fetch();
        $u['avg_rating'] = $rData['avg_rating'] ? round((float)$rData['avg_rating'], 1) : 0;
        $u['total_reviews'] = (int)($rData['total_reviews'] ?? 0);
    }

    json_response(true, 'Discovery results loaded.', [
        'count' => count($users),
        'users' => $users
    ]);

} catch (Exception $e) {
    error_log('[Search Error] ' . $e->getMessage());
    json_response(false, 'Search failed.', null, 500);
}
