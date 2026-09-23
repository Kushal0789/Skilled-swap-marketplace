<?php
/**
 * Master Skills List API Endpoint
 * GET /api/skills/list.php
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = get_db();
    
    $category = trim($_GET['category'] ?? '');
    $search = trim($_GET['q'] ?? '');

    $sql = "SELECT id, name, category, description FROM skills WHERE 1=1";
    $params = [];

    if (!empty($category)) {
        $sql .= " AND category = :category";
        $params[':category'] = $category;
    }

    if (!empty($search)) {
        $sql .= " AND (name LIKE :search OR description LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY category ASC, name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $skills = $stmt->fetchAll();

    // Fetch list of distinct categories for filters
    $catStmt = $pdo->query("SELECT DISTINCT category FROM skills ORDER BY category ASC");
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

    json_response(true, 'Skills retrieved successfully.', [
        'skills'     => $skills,
        'categories' => $categories
    ]);

} catch (Exception $e) {
    error_log('[Skills List Error] ' . $e->getMessage());
    json_response(false, 'Unable to load skills catalog.', null, 500);
}
