<?php
/**
 * Admin Skills Taxonomy API Endpoint
 * GET /api/admin/skills.php
 * POST /api/admin/skills.php (add / delete)
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
    $action = $input['action'] ?? '';

    try {
        if ($action === 'add') {
            $name = trim($input['name'] ?? '');
            $category = trim($input['category'] ?? 'General');
            $desc = trim($input['description'] ?? '');

            if (empty($name)) {
                json_response(false, 'Skill name cannot be empty.', null, 422);
            }

            $stmt = $pdo->prepare("INSERT INTO skills (name, category, description, created_at) VALUES (:n, :c, :d, NOW())");
            $stmt->execute([':n' => $name, ':c' => $category, ':d' => $desc]);

            json_response(true, 'Skill added to master catalog.', ['id' => (int)$pdo->lastInsertId()], 201);
        } elseif ($action === 'delete') {
            $skillId = (int)($input['skill_id'] ?? 0);
            if ($skillId <= 0) {
                json_response(false, 'Invalid skill ID.', null, 422);
            }

            $delStmt = $pdo->prepare("DELETE FROM skills WHERE id = :id");
            $delStmt->execute([':id' => $skillId]);

            json_response(true, 'Skill removed from catalog.');
        } else {
            json_response(false, 'Invalid action.', null, 422);
        }
    } catch (Exception $e) {
        error_log('[Admin Skills Error] ' . $e->getMessage());
        json_response(false, 'Operation failed.', null, 500);
    }
}

// GET: Skills with usage counts
try {
    $stmt = $pdo->query("
        SELECT s.id, s.name, s.category, s.description, s.created_at,
               (SELECT COUNT(*) FROM user_skills WHERE skill_id = s.id AND skill_type = 'OFFER') as offered_by,
               (SELECT COUNT(*) FROM user_skills WHERE skill_id = s.id AND skill_type = 'WANT') as wanted_by
        FROM skills s
        ORDER BY s.category ASC, s.name ASC
    ");
    $skills = $stmt->fetchAll();

    json_response(true, 'Skills retrieved.', $skills);
} catch (Exception $e) {
    error_log('[Admin Skills List Error] ' . $e->getMessage());
    json_response(false, 'Failed to load skills.', null, 500);
}
