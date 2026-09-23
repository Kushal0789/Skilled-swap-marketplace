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
    $stmt = $pdo->query("
        SELECT rep.id, rep.report_type, rep.reason, rep.status, rep.created_at,
               u_from.name AS reporter_name, u_from.username AS reporter_username,
               u_to.name AS reported_name, u_to.username AS reported_username, u_to.id AS reported_user_id
        FROM reports rep
        INNER JOIN users u_from ON rep.reporter_id = u_from.id
        INNER JOIN users u_to ON rep.reported_user_id = u_to.id
        ORDER BY rep.created_at DESC
    ");
    $reports = $stmt->fetchAll();

    foreach ($reports as &$r) {
        $r['time_ago'] = time_ago($r['created_at']);
    }

    json_response(true, 'Reports loaded.', $reports);
} catch (Exception $e) {
    error_log('[Admin Reports List Error] ' . $e->getMessage());
    json_response(false, 'Failed to load reports.', null, 500);
}
