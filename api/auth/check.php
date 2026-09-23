<?php
/**
 * Auth Session Check API Endpoint
 * GET /api/auth/check.php
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$user = current_user();

if ($user) {
    json_response(true, 'User is authenticated.', [
        'is_logged_in' => true,
        'user'         => $user
    ]);
} else {
    json_response(true, 'Guest user.', [
        'is_logged_in' => false,
        'user'         => null
    ]);
}
