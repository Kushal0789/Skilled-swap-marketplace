<?php
/**
 * Admin Reports Moderation API Endpoint
 * GET /api/admin/reports.php
 * POST /api/admin/reports.php (resolve / dismiss)
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
require_admin();

$pdo = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = get_json_input();
    $reportId = (int)($input['report_id'] ?? 0);
    $status = trim($input['status'] ?? 'resolved');

    if ($reportId <= 0 || !in_array($status, ['resolved', 'dismissed'], true)) {
        json_response(false, 'Invalid parameters.', null, 422);
    }

    try {
        $stmt = $pdo->prepare("UPDATE reports SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $reportId]);

        json_response(true, "Report marked as {$status}.");
    } catch (Exception $e) {
        error_log('[Admin Report Update Error] ' . $e->getMessage());
        json_response(false, 'Failed to update report status.', null, 500);
    }
}

// GET: List reports
try {
    // Auto-migrate schema if needed
    try {
        $pdo->exec("ALTER TABLE reports MODIFY reported_user_id INT UNSIGNED NULL");
    } catch (Throwable $e) {}
    try {
        $pdo->exec("ALTER TABLE reports ADD COLUMN title VARCHAR(150) NULL AFTER report_type");
    } catch (Throwable $e) {}

    $stmt = $pdo->query("
        SELECT rep.id, rep.report_type, rep.title, rep.reason, rep.status, rep.created_at,
               u_from.name AS reporter_name, u_from.username AS reporter_username, u_from.profile_image AS reporter_profile_image,
               u_to.name AS reported_name, u_to.username AS reported_username, u_to.id AS reported_user_id,
               u_to.profile_image AS reported_profile_image, u_to.status AS reported_status
        FROM reports rep
        INNER JOIN users u_from ON rep.reporter_id = u_from.id
        LEFT JOIN users u_to ON rep.reported_user_id = u_to.id
        ORDER BY rep.created_at DESC
    ");
    $reports = $stmt->fetchAll();

    foreach ($reports as &$r) {
        $r['time_ago'] = time_ago($r['created_at']);
        $r['reporter_avatar'] = get_avatar_url($r['reporter_profile_image'] ?? 'default-avatar.svg');
        $r['reported_avatar'] = get_avatar_url($r['reported_profile_image'] ?? 'default-avatar.svg');
    }

    json_response(true, 'Reports loaded.', $reports);
} catch (Exception $e) {
    error_log('[Admin Reports List Error] ' . $e->getMessage());
    json_response(false, 'Failed to load reports.', null, 500);
}
