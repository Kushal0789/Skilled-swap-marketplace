<?php
/**
 * Create Report & Feedback API Endpoint
 * POST /api/reports/create.php
 * Skill Swap Marketplace
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

$rawCategory = trim($input['category'] ?? $input['report_type'] ?? '');
$rawTitle    = trim($input['title'] ?? $input['subject'] ?? '');
$rawReason   = trim($input['reason'] ?? $input['description'] ?? '');
$rawTarget   = (int)($input['reported_user_id'] ?? 0);

// Normalize category names
$categoryMap = [
    'bug_report'       => 'Bug Report',
    'bug report'       => 'Bug Report',
    'user_report'      => 'User Report',
    'user report'      => 'User Report',
    'technical_issue'  => 'Technical Issue',
    'technical issue'  => 'Technical Issue',
    'suggestion'       => 'Suggestion',
    'general_feedback' => 'General Feedback',
    'general feedback' => 'General Feedback',
    'feedback'         => 'General Feedback',
    'other'            => 'Other'
];

$normalizedKey = strtolower($rawCategory);
$reportType = $categoryMap[$normalizedKey] ?? 'General Feedback';

// Validation
$errors = [];

if (empty($rawTitle)) {
    $errors['title'] = 'Please provide a subject or title.';
} elseif (mb_strlen($rawTitle) > 150) {
    $errors['title'] = 'Subject must be 150 characters or fewer.';
}

if (empty($rawReason)) {
    $errors['reason'] = 'Please enter a detailed description.';
} elseif (mb_strlen($rawReason) < 10) {
    $errors['reason'] = 'Please provide at least 10 characters explaining your feedback or report.';
}

$reportedUserId = null;
if ($reportType === 'User Report') {
    if ($rawTarget <= 0) {
        $errors['reported_user_id'] = 'Please select or specify the member you are reporting.';
    } elseif ($rawTarget === $reporterId) {
        $errors['reported_user_id'] = 'You cannot report your own account.';
    } else {
        $reportedUserId = $rawTarget;
    }
} elseif ($rawTarget > 0 && $rawTarget !== $reporterId) {
    // If user provided a target for another category, we can still record it
    $reportedUserId = $rawTarget;
}

if (!empty($errors)) {
    json_response(false, 'Please correct the validation errors.', null, 422, $errors);
}

try {
    $pdo = get_db();

    // Auto-migrate schema if needed for backward compatibility
    static $schemaChecked = false;
    if (!$schemaChecked) {
        try {
            $pdo->exec("ALTER TABLE reports MODIFY reported_user_id INT UNSIGNED NULL");
        } catch (Throwable $e) {}
        try {
            $pdo->exec("ALTER TABLE reports ADD COLUMN title VARCHAR(150) NULL AFTER report_type");
        } catch (Throwable $e) {}
        $schemaChecked = true;
    }

    // Verify reported user exists if specified
    if ($reportedUserId !== null) {
        $uStmt = $pdo->prepare("SELECT id FROM users WHERE id = :id LIMIT 1");
        $uStmt->execute([':id' => $reportedUserId]);
        if (!$uStmt->fetch()) {
            json_response(false, 'The selected user does not exist.', null, 422, ['reported_user_id' => 'User not found.']);
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO reports (reporter_id, reported_user_id, report_type, title, reason, status, created_at)
        VALUES (:rep_id, :target_id, :type, :title, :reason, 'pending', NOW())
    ");
    $stmt->execute([
        ':rep_id'    => $reporterId,
        ':target_id' => $reportedUserId,
        ':type'      => $reportType,
        ':title'     => $rawTitle,
        ':reason'    => $rawReason
    ]);

    $newReportId = (int)$pdo->lastInsertId();

    json_response(true, 'Your report/feedback has been submitted to moderators. Thank you for helping improve SkillSwap!', [
        'report_id' => $newReportId
    ], 201);

} catch (Exception $e) {
    error_log('[Report Create Error] ' . $e->getMessage());
    json_response(false, 'Unable to submit report. Please try again.', null, 500);
}
