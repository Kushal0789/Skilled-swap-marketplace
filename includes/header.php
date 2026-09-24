<?php
/**
 * Global Header Component (Presentation Layer)
 * Skill Swap Marketplace
 */

if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$currentUser = current_user();
$isLoggedIn = is_logged_in();
$csrfToken = generate_csrf_token();

$unreadNotifs = 0;
$unreadMsgs = 0;

if ($isLoggedIn && $currentUser) {
    $pdo = get_db();
    $unreadNotifs = get_unread_notifications_count($pdo, $currentUser['id']);
    $unreadMsgs = get_unread_messages_count($pdo, $currentUser['id']);
}

$pageTitle = $pageTitle ?? 'Skill Swap Marketplace - Learn. Share. Connect.';
$pageDesc = $pageDesc ?? 'Connect with peers to exchange skills, knowledge, and hobbies. Offer what you know, learn what you want.';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDesc) ?>">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    
    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- Design System Stylesheets -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body>
    <!-- Top Navigation Bar -->
    <header class="site-header">
        <div class="nav-container">
            <a href="index.php" class="brand-logo" id="nav-brand">
                <span class="brand-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 3h5v5"></path>
                        <path d="M4 20L21 3"></path>
                        <path d="M21 16v5h-5"></path>
                        <path d="M15 15l6 6"></path>
                        <path d="M4 4l5 5"></path>
                    </svg>
                </span>
                <span class="brand-text">Skill<strong>Swap</strong></span>
            </a>

            <!-- Desktop Navigation Links -->
            <nav class="nav-links" id="desktop-nav">
                <a href="index.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'active' : '' ?>">Home</a>
                <a href="discover.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'discover.php') ? 'active' : '' ?>">Discover Skills</a>
                
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'active' : '' ?>">Dashboard</a>
                    <a href="matches.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'matches.php') ? 'active' : '' ?>">
                        Matches
                        
                    </a>
                    <a href="requests.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'requests.php') ? 'active' : '' ?>">
                        Requests
                    </a>
                    <a href="messages.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'messages.php') ? 'active' : '' ?>">
                        Messages
                        <?php if ($unreadMsgs > 0): ?>
                            <span class="badge-pill bg-primary" id="unread-msg-badge"><?= $unreadMsgs ?></span>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <a href="index.php#how-it-works" class="nav-link">How It Works</a>
                    <a href="index.php#featured-skills" class="nav-link">Categories</a>
                <?php endif; ?>
            </nav>

            <!-- Header Action Controls -->
            <div class="nav-actions">
                <!-- Theme Switcher -->
                <button type="button" class="btn-icon theme-toggle" id="theme-toggle-btn" aria-label="Toggle Dark/Light Mode" title="Toggle theme">
                    <svg class="sun-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                    <svg class="moon-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                </button>

                <?php if ($isLoggedIn && $currentUser): ?>
                    <!-- Notification Bell with Dropdown -->
                    <div class="notif-dropdown-wrapper">
                        <button type="button" class="btn-icon notif-bell-btn" id="notif-bell-btn" aria-label="Notifications" title="Notifications">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                            <span class="notif-count-badge <?= ($unreadNotifs > 0) ? '' : 'hidden' ?>" id="global-notif-count"><?= $unreadNotifs ?></span>
                        </button>
                        <div class="notif-panel hidden" id="notif-panel">
                            <div class="notif-panel-header">
                                <h3>Notifications</h3>
                                <button type="button" class="btn-link" id="mark-all-read-btn">Mark all read</button>
                            </div>
                            <div class="notif-list" id="notif-list-container">
                                <div class="notif-empty-state">Loading notifications...</div>
                            </div>
                        </div>
                    </div>

                    <!-- User Profile Dropdown Menu -->
                    <div class="user-menu-wrapper">
                        <button type="button" class="user-avatar-btn" id="user-avatar-menu-btn" aria-haspopup="true">
                            <img src="<?= e(get_avatar_url($currentUser['profile_image'])) ?>" alt="<?= e($currentUser['name']) ?>" class="nav-avatar-img">
                            <span class="user-display-name"><?= e(explode(' ', $currentUser['name'])[0]) ?></span>
                            <svg class="chevron-down" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="user-dropdown-menu hidden" id="user-dropdown-panel">
                            <div class="dropdown-user-info">
                                <p class="info-name"><?= e($currentUser['name']) ?></p>
                                <p class="info-username">@<?= e($currentUser['username']) ?></p>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="dashboard.php" class="dropdown-item">Dashboard</a>
                            <a href="profile.php" class="dropdown-item">My Profile & Skills</a>
                            <a href="requests.php" class="dropdown-item">Swap Requests</a>
                            <a href="messages.php" class="dropdown-item">Chat Messages</a>
                            <a href="settings.php" class="dropdown-item">Settings</a>
                            <?php if (is_admin()): ?>
                                <div class="dropdown-divider"></div>
                                <a href="admin.php" class="dropdown-item text-primary font-bold">Admin Console</a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item text-danger">Sign Out</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-ghost" id="nav-login-btn">Sign In</a>
                    <a href="register.php" class="btn btn-primary" id="nav-register-btn">Get Started</a>
                <?php endif; ?>

                <!-- Mobile Hamburger Button -->
                <button type="button" class="mobile-menu-toggle" id="mobile-menu-toggle-btn" aria-label="Toggle Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>

        <!-- Mobile Drawer Navigation -->
        <div class="mobile-drawer hidden" id="mobile-drawer">
            <div class="mobile-nav-links">
                <a href="index.php" class="mobile-nav-link">Home</a>
                <a href="discover.php" class="mobile-nav-link">Discover Skills</a>
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php" class="mobile-nav-link">Dashboard</a>
                    <a href="matches.php" class="mobile-nav-link">Matches</a>
                    <a href="requests.php" class="mobile-nav-link">Requests</a>
                    <a href="messages.php" class="mobile-nav-link">Messages (<?= $unreadMsgs ?>)</a>
                    <a href="profile.php" class="mobile-nav-link">Profile & Skills</a>
                    <a href="settings.php" class="mobile-nav-link">Settings</a>
                    <?php if (is_admin()): ?>
                        <a href="admin.php" class="mobile-nav-link text-primary">Admin Console</a>
                    <?php endif; ?>
                    <a href="logout.php" class="mobile-nav-link text-danger">Sign Out</a>
                <?php else: ?>
                    <a href="login.php" class="mobile-nav-link">Sign In</a>
                    <a href="register.php" class="mobile-nav-link text-primary font-bold">Create Account</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <main class="site-main">
