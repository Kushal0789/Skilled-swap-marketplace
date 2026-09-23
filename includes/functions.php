<?php
/**
 * Global Utility Functions (Application Layer)
 * Skill Swap Marketplace
 */

if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

/**
 * Send a standardized JSON API response and terminate execution.
 *
 * @param bool $success
 * @param string $message
 * @param mixed $data
 * @param int $statusCode
 * @param array $errors
 */
function json_response(bool $success, string $message = '', mixed $data = null, int $statusCode = 200, array $errors = []): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    
    // Prevent client caching of API responses
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
        'errors'  => $errors
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Retrieve and parse JSON payload or POST request body.
 *
 * @return array
 */
function get_json_input(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    return $_POST;
}

/**
 * Recursively sanitize input string or array.
 *
 * @param mixed $data
 * @return mixed
 */
function clean_input(mixed $data): mixed {
    if (is_array($data)) {
        return array_map('clean_input', $data);
    }
    if (is_string($data)) {
        return trim(strip_tags($data));
    }
    return $data;
}

/**
 * Escape HTML output to prevent Cross-Site Scripting (XSS).
 *
 * @param string|null $string
 * @return string
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Convert datetime string to human-friendly relative time (e.g. "5 mins ago").
 *
 * @param string|null $datetime
 * @return string
 */
function time_ago(?string $datetime): string {
    if (!$datetime) {
        return 'Recently';
    }

    $time = strtotime($datetime);
    if (!$time) {
        return 'Recently';
    }

    $diff = time() - $time;

    if ($diff < 5) {
        return 'Just now';
    }
    if ($diff < 60) {
        return $diff . ' seconds ago';
    }
    if ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' ' . ($mins == 1 ? 'min' : 'mins') . ' ago';
    }
    if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' ' . ($hours == 1 ? 'hour' : 'hours') . ' ago';
    }
    if ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' ' . ($days == 1 ? 'day' : 'days') . ' ago';
    }
    if ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . ' ' . ($weeks == 1 ? 'week' : 'weeks') . ' ago';
    }

    return date('M j, Y', $time);
}

/**
 * Resolves avatar image path with fallbacks.
 *
 * @param string|null $filename
 * @return string
 */
function get_avatar_url(?string $filename): string {
    if (empty($filename) || $filename === 'default-avatar.svg') {
        return 'assets/images/default-avatar.svg';
    }

    // Check if file physically exists in avatars upload directory
    $localPath = __DIR__ . '/../assets/uploads/avatars/' . basename($filename);
    if (file_exists($localPath)) {
        return 'assets/uploads/avatars/' . rawurlencode(basename($filename));
    }

    return 'assets/images/default-avatar.svg';
}

/**
 * Create a new notification for a specific user.
 * (Safely guarded in case notifications table is not created yet)
 *
 * @param PDO $pdo
 * @param int $userId
 * @param string $type
 * @param string $title
 * @param string $message
 * @param int|null $refId
 * @return int
 */
function create_notification(PDO $pdo, int $userId, string $type, string $title, string $message, ?int $refId = null): int {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, reference_id, is_read, created_at)
            VALUES (:user_id, :type, :title, :message, :ref_id, 0, NOW())
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':type'    => $type,
            ':title'   => $title,
            ':message' => $message,
            ':ref_id'  => $refId
        ]);

        return (int)$pdo->lastInsertId();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Count unread notifications for a user.
 *
 * @param PDO $pdo
 * @param int $userId
 * @return int
 */
function get_unread_notifications_count(PDO $pdo, int $userId): int {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0");
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Count unread incoming messages for a user across all their conversations.
 *
 * @param PDO $pdo
 * @param int $userId
 * @return int
 */
function get_unread_messages_count(PDO $pdo, int $userId): int {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(m.id)
            FROM messages m
            INNER JOIN conversations c ON m.conversation_id = c.id
            WHERE (c.user_one = :uid1 OR c.user_two = :uid2)
              AND m.sender_id != :uid3
              AND m.is_read = 0
        ");
        $stmt->execute([
            ':uid1' => $userId,
            ':uid2' => $userId,
            ':uid3' => $userId
        ]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get dynamic real-time platform statistics for the landing page & admin.
 *
 * @param PDO $pdo
 * @return array
 */
function get_platform_stats(PDO $pdo): array {
    try {
        // Active users count
        $usersCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

        // Total unique skills available in catalog
        $skillsCount = (int)$pdo->query("SELECT COUNT(*) FROM skills")->fetchColumn();

        // Successful/accepted swaps
        $swapsCount = (int)$pdo->query("SELECT COUNT(*) FROM swap_requests WHERE status = 'accepted' OR status = 'completed'")->fetchColumn();

        // Total skill exchange offerings listed by users ('teach')
        $offeringsCount = (int)$pdo->query("SELECT COUNT(*) FROM user_skills WHERE type = 'teach'")->fetchColumn();

        return [
            'users'     => max($usersCount, 1),
            'skills'    => max($skillsCount, 1),
            'swaps'     => $swapsCount,
            'offerings' => max($offeringsCount, 1)
        ];
    } catch (Exception $e) {
        return [
            'users'     => 1,
            'skills'    => 1,
            'swaps'     => 0,
            'offerings' => 1
        ];
    }
}