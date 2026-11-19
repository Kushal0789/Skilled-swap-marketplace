<?php
/**
 * User Submitted Reports & Feedback API Endpoint
 * GET /api/reports/list.php
 * Skill Swap Marketplace
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
require_auth();

$reporterId = current_user_id();
$pdo = get_db();

try {
    // Auto-migrate schema if needed for backward compatibility
    try {
        $pdo->exec("ALTER TABLE reports MODIFY reported_user_id INT UNSIGNED NULL");
    } catch (Throwable $e) {}
    try {
        $pdo->exec("ALTER TABLE reports ADD COLUMN title VARCHAR(150) NULL AFTER report_type");
    } catch (Throwable $e) {}

    $stmt = $pdo->prepare("
        SELECT rep.id, rep.report_type, rep.title, rep.reason, rep.status, rep.created_at,
               u_to.name AS reported_name, u_to.username AS reported_username, u_to.profile_image AS reported_avatar
        FROM reports rep
        LEFT JOIN users u_to ON rep.reported_user_id = u_to.id
        WHERE rep.reporter_id = :uid
        ORDER BY rep.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([':uid' => $reporterId]);
    $reports = $stmt->fetchAll();

    foreach ($reports as &$r) {
        $r['time_ago'] = time_ago($r['created_at']);
        $r['reported_avatar'] = !empty($r['reported_avatar']) ? get_avatar_url($r['reported_avatar']) : null;
        $r['formatted_date'] = date('M j, Y \a\t g:i A', strtotime($r['created_at']));
    }

    json_response(true, 'Reports retrieved.', $reports);

} catch (Exception $e) {
    error_log('[Reports List Error] ' . $e->getMessage());
    json_response(false, 'Unable to load your reports.', null, 500);
}
