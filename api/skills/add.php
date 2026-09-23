<?php
/**
 * Add Skill to User Profile API Endpoint
 * POST /api/skills/add.php
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

$userId = current_user_id();
$input = get_json_input();

$skillId = !empty($input['skill_id']) ? (int)$input['skill_id'] : null;
$newSkillName = trim($input['new_skill_name'] ?? '');
$newSkillCategory = trim($input['category'] ?? 'Other');
$skillType = strtoupper(trim($input['skill_type'] ?? 'OFFER'));
$proficiency = trim($input['proficiency'] ?? 'Intermediate');
$description = trim($input['description'] ?? '');

$validTypes = ['OFFER', 'WANT'];
if (!in_array($skillType, $validTypes, true)) {
    json_response(false, 'Invalid skill type. Must be OFFER or WANT.', null, 422);
}

$validProficiencies = ['Beginner', 'Intermediate', 'Advanced', 'Expert'];
if (!in_array($proficiency, $validProficiencies, true)) {
    $proficiency = 'Intermediate';
}

try {
    $pdo = get_db();

    // If new skill name is specified, create or retrieve master skill
    if ($skillId === null && !empty($newSkillName)) {
        $findStmt = $pdo->prepare("SELECT id FROM skills WHERE LOWER(name) = LOWER(:name) LIMIT 1");
        $findStmt->execute([':name' => $newSkillName]);
        $existing = $findStmt->fetch();

        if ($existing) {
            $skillId = (int)$existing['id'];
        } else {
            $insertSkill = $pdo->prepare("INSERT INTO skills (name, category, description, created_at) VALUES (:name, :category, :description, NOW())");
            $insertSkill->execute([
                ':name'        => $newSkillName,
                ':category'    => !empty($newSkillCategory) ? $newSkillCategory : 'General',
                ':description' => 'User added skill'
            ]);
            $skillId = (int)$pdo->lastInsertId();
        }
    }

    if (!$skillId) {
        json_response(false, 'Please select a skill or enter a custom skill name.', null, 422);
    }

    // Verify skill exists
    $checkSkill = $pdo->prepare("SELECT id, name FROM skills WHERE id = :id");
    $checkSkill->execute([':id' => $skillId]);
    $skillData = $checkSkill->fetch();
    if (!$skillData) {
        json_response(false, 'Selected skill does not exist in catalog.', null, 404);
    }

    // Check if user already has this skill under this type
    $dupStmt = $pdo->prepare("SELECT id FROM user_skills WHERE user_id = :uid AND skill_id = :sid AND skill_type = :type LIMIT 1");
    $dupStmt->execute([
        ':uid'  => $userId,
        ':sid'  => $skillId,
        ':type' => $skillType
    ]);

    if ($dupStmt->fetch()) {
        json_response(false, "You already have '{$skillData['name']}' in your {$skillType} list.", null, 409);
    }

    // Insert user skill
    $insertStmt = $pdo->prepare("
        INSERT INTO user_skills (user_id, skill_id, skill_type, proficiency, description, created_at)
        VALUES (:uid, :sid, :type, :prof, :desc, NOW())
    ");
    $insertStmt->execute([
        ':uid'  => $userId,
        ':sid'  => $skillId,
        ':type' => $skillType,
        ':prof' => $proficiency,
        ':desc' => $description
    ]);

    $userSkillId = (int)$pdo->lastInsertId();

    json_response(true, "Successfully added {$skillData['name']} to your {$skillType} list!", [
        'id'          => $userSkillId,
        'skill_id'    => $skillId,
        'skill_name'  => $skillData['name'],
        'skill_type'  => $skillType,
        'proficiency' => $proficiency,
        'description' => $description
    ], 201);

} catch (Exception $e) {
    error_log('[Add User Skill Error] ' . $e->getMessage());
    json_response(false, 'An error occurred while adding the skill.', null, 500);
}
