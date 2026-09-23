<?php
/**
 * Logout API Endpoint
 * POST /api/auth/logout.php
 */

define('APP_INIT', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

logout_user();

json_response(true, 'Logged out successfully.', [
    'redirect_to' => 'index.php'
]);
