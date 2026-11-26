<?php
/**
 * Availability Management Page (Presentation Layer)
 * Skill Swap Marketplace
 */

define('APP_INIT', true);
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/availability.php';

require_auth();

$currentUid = current_user_id();
$pdo = get_db();

$availability = get_user_availability($pdo, $currentUid);
$statusesMeta = availability_statuses();
$selectedStatus = $availability['status'] ?? 'available';

$pageTitle = 'Availability & Weekly Schedule - Skill Swap';
$pageDesc = 'Manage your availability status and weekly schedule for skill exchanges.';
$pageCss = 'availability.css';
$pageScript = 'availability.js';
include __DIR__ . '/includes/header.php';
?>

<div class="availability-page-container">
    <!-- Top Header -->
    <div class="avail-page-header">
        <div>
            <a href="profile.php" class="avail-back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Back to Profile
            </a>
            <h1 class="avail-page-title">Availability Schedule</h1>
            <p class="avail-page-subtitle">Set your swap availability status and weekly hours so matched partners know when you're free.</p>
        </div>

        <div class="avail-header-right">
            <span id="current-status-pill" class="avail-status-badge status-<?= e($selectedStatus) ?>">
                <span class="avail-status-dot"></span>
                <span id="current-status-pill-text"><?= e($statusesMeta[$selectedStatus]['label']) ?></span>
            </span>
            <button type="button" class="btn btn-primary" id="btn-save-avail-top">Save Availability</button>
        </div>
    </div>

    <!-- Inline Feedback Banner -->
    <div id="avail-feedback-banner" class="avail-feedback-banner hidden" role="status" aria-live="polite"></div>

    <form id="availability-form" novalidate>
        <!-- A. Overall Availability Status -->
        <section class="avail-section-card" aria-labelledby="status-heading">
            <div class="avail-section-header">
                <div>
                    <h2 class="avail-section-title" id="status-heading">Overall Availability Status</h2>
                    <p class="avail-section-desc">Let potential partners know whether you are currently open to scheduling sessions.</p>
                </div>
            </div>

            <div class="status-options-grid" role="radiogroup" aria-label="Availability status">
                <?php foreach ($statusesMeta as $key => $meta):
                    $isChecked = ($selectedStatus === $key);
                ?>
                    <label class="status-option-card <?= $isChecked ? 'is-selected' : '' ?>" data-status="<?= e($key) ?>">
                        <input type="radio" name="availability_status" value="<?= e($key) ?>" <?= $isChecked ? 'checked' : '' ?>>
                        <span class="status-option-indicator dot-<?= e($key) ?>" aria-hidden="true"></span>
                        <span>
                            <span class="status-option-title"><?= e($meta['label']) ?></span>
                            <span class="status-option-desc"><?= e($meta['description']) ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div id="status-unavailable-note" class="status-unavailable-note <?= $selectedStatus === 'unavailable' ? '' : 'hidden' ?>">
                While set to <strong>Not Available</strong>, matches will show no common availability, while your configured weekly slots below remain saved for when you return.
            </div>
        </section>

        <!-- B. Weekly Availability Schedule -->
        <section class="avail-section-card" aria-labelledby="weekly-heading">
            <div class="avail-section-header">
                <div>
                    <h2 class="avail-section-title" id="weekly-heading">Weekly Availability</h2>
                    <p class="avail-section-desc">Enable days when you can meet and add one or more non-overlapping time slots.</p>
                </div>
                <div class="avail-quick-tools">
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-copy-mon-weekdays" title="Copy Monday's slots to Tuesday–Friday">
                        Copy Mon to Weekdays
                    </button>
                    <button type="button" class="btn btn-ghost btn-sm" id="btn-clear-week">
                        Clear Week
                    </button>
                </div>
            </div>

            <div class="weekly-schedule-list" id="weekly-schedule-list">
                <!-- Hydrated cleanly by availability.js from server payload -->
            </div>
        </section>

        <!-- C. Save Footer -->
        <div class="avail-save-footer">
            <span class="avail-save-summary" id="avail-save-summary">Loading schedule summary...</span>
            <div style="display: flex; gap: 0.75rem;">
                <a href="profile.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" id="btn-save-avail-bottom">Save Availability</button>
            </div>
        </div>
    </form>
</div>

<script id="initial-availability-data" type="application/json"><?= json_encode($availability, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
