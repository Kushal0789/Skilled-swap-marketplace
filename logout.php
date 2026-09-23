<?php
/**
 * Logout Handler (Presentation Layer)
 * Skill Swap Marketplace
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

logout_user();

header('Location: index.php?logged_out=1');
exit;
