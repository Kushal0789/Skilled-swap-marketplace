<?php
/**
 * Create Report API Endpoint
 * POST /api/reports/create.php
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

$reporterId = current_user_id();
$input = get_json_input();

$reportedUserId = (int)($input['reported_user_id'] ?? 0);
$reportType = trim($input['report_type'] ?? 'inappropriate_content');
$reason = trim($input['reason'] ?? '');

if ($reportedUserId <= 0) {
    json_response(false, 'Target user is required.', null, 422);
}

if (empty($reason)) {
    json_response(false, 'Please describe the reason for your report.', null, 422);
}

try {
    $pdo = get_db();

    $stmt = $pdo->prepare("
        INSERT INTO reports (reporter_id, reported_user_id, report_type, reason, status, created_at)
        VALUES (:rep_id, :target_id, :type, :reason, 'pending', NOW())
    ");
    $stmt->execute([
        ':rep_id'    => $reporterId,
        ':target_id' => $reportedUserId,
        ':type'      => $reportType,
        ':reason'    => $reason
    ]);

    json_response(true, 'Your report has been submitted to moderators. Thank you for keeping the platform safe.', [
        'report_id' => (int)$pdo->lastInsertId()
    ], 201);

} catch (Exception $e) {
    error_log('[Report Create Error] ' . $e->getMessage());
    json_response(false, 'Unable to submit report.', null, 500);
}
