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

        // Total skill exchange offerings listed by users ('OFFER')
        $offeringsCount = (int)$pdo->query("SELECT COUNT(*) FROM user_skills WHERE skill_type = 'OFFER'")->fetchColumn();

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

/**
 * Supported social media & chat platforms definition.
 *
 * @return array
 */
function get_social_platforms_meta(): array {
    return [
        'whatsapp' => [
            'name'        => 'WhatsApp',
            'placeholder' => '+1234567890 or wa.me/1234567890',
            'hint'        => 'Phone number with country code. Opens direct WhatsApp chat.',
            'badge'       => 'Direct Chat',
            'color'       => '#25D366',
            'icon'        => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.54 1.764.819 2.791.819h.002c3.18 0 5.767-2.586 5.768-5.766 0-3.18-2.587-5.765-5.77-5.765zm3.376 8.204c-.149.418-.753.79-1.045.84-.282.049-.646.079-2.072-.511-1.824-.755-3.003-2.607-3.094-2.729-.091-.122-.74-1.002-.74-1.928s.475-1.378.653-1.564c.178-.186.388-.232.518-.232.13 0 .259.002.373.008.119.006.279-.045.437.334.162.388.552 1.345.6 1.442.049.097.081.21.016.339-.065.129-.098.21-.194.323-.098.113-.205.253-.293.34-.097.097-.199.202-.086.396.113.194.502.828 1.077 1.341.741.66 1.365.865 1.559.962.194.097.307.081.421-.049.113-.129.486-.566.615-.76.13-.194.259-.162.437-.097.178.065 1.134.535 1.328.632.194.097.324.146.372.227.049.081.049.469-.1 1.015zM12 2C6.477 2 2 6.477 2 12c0 1.891.524 3.662 1.435 5.177L2 22l4.974-1.399C8.423 21.498 10.154 22 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>',
            'format_url'  => function($v) {
                if (str_starts_with($v, 'http')) return $v;
                $clean = preg_replace('/[^0-9]/', '', $v);
                return !empty($clean) ? 'https://wa.me/' . $clean : '';
            }
        ],
        'discord' => [
            'name'        => 'Discord',
            'placeholder' => 'username#1234 or discord.gg/invite',
            'hint'        => 'Discord handle or invite URL. Click to copy handle or join server.',
            'badge'       => 'Community / Chat',
            'color'       => '#5865F2',
            'icon'        => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994.021-.041.001-.09-.041-.106a13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.929 1.793 8.18 1.793 12.061 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.894.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.028zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>',
            'format_url'  => function($v) {
                if (str_starts_with($v, 'http')) return $v;
                return '#discord:' . trim($v);
            }
        ],
        'linkedin' => [
            'name'        => 'LinkedIn',
            'placeholder' => 'linkedin.com/in/username or username',
            'hint'        => 'LinkedIn profile link or username.',
            'badge'       => 'Network',
            'color'       => '#0A66C2',
            'icon'        => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>',
            'format_url'  => function($v) {
                if (str_starts_with($v, 'http')) return $v;
                if (str_contains($v, 'linkedin.com')) return 'https://' . ltrim($v, '/');
                return 'https://linkedin.com/in/' . ltrim(trim($v), '@/');
            }
        ],
        'github' => [
            'name'        => 'GitHub',
            'placeholder' => 'github.com/username or username',
            'hint'        => 'GitHub profile URL or username.',
            'badge'       => 'Code / Repos',
            'color'       => '#f0f6fc',
            'icon'        => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/></svg>',
            'format_url'  => function($v) {
                if (str_starts_with($v, 'http')) return $v;
                if (str_contains($v, 'github.com')) return 'https://' . ltrim($v, '/');
                return 'https://github.com/' . ltrim(trim($v), '@/');
            }
        ],
        'twitter' => [
            'name'        => 'X (Twitter)',
            'placeholder' => '@username or x.com/username',
            'hint'        => 'X / Twitter handle or URL.',
            'badge'       => 'Social',
            'color'       => '#e2e8f0',
            'icon'        => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
            'format_url'  => function($v) {
                if (str_starts_with($v, 'http')) return $v;
                if (str_contains($v, 'x.com') || str_contains($v, 'twitter.com')) return 'https://' . ltrim($v, '/');
                return 'https://x.com/' . ltrim(trim($v), '@/');
            }
        ],
        'instagram' => [
            'name'        => 'Instagram',
            'placeholder' => '@username or instagram.com/username',
            'hint'        => 'Instagram handle or profile link.',
            'badge'       => 'Photos / DMs',
            'color'       => '#E4405F',
            'icon'        => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>',
            'format_url'  => function($v) {
                if (str_starts_with($v, 'http')) return $v;
                if (str_contains($v, 'instagram.com')) return 'https://' . ltrim($v, '/');
                return 'https://instagram.com/' . ltrim(trim($v), '@/');
            }
        ],
        'facebook' => [
            'name'        => 'Facebook',
            'placeholder' => 'https://facebook.com/yourprofile',
            'hint'        => 'Facebook profile URL or username.',
            'badge'       => 'Profile',
            'color'       => '#1877F2',
            'icon'        => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
            'format_url'  => function($v) {
                if (str_starts_with($v, 'http')) return $v;
                if (str_contains($v, 'facebook.com')) return 'https://' . ltrim($v, '/');
                return 'https://facebook.com/' . ltrim(trim($v), '@/');
            }
        ],
        'website' => [
            'name'        => 'Portfolio / Website',
            'placeholder' => 'https://yourwebsite.com',
            'hint'        => 'Personal portfolio, project website, or blog.',
            'badge'       => 'Portfolio',
            'color'       => 'var(--primary)',
            'icon'        => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>',
            'format_url'  => function($v) {
                if (str_starts_with($v, 'http')) return $v;
                return 'https://' . ltrim(trim($v), '/');
            }
        ],
        'contact_email' => [
            'name'        => 'Public Email',
            'placeholder' => 'your.email@example.com',
            'hint'        => 'Direct email for member inquiries and swap proposals.',
            'badge'       => 'Direct Mail',
            'color'       => '#EA4335',
            'icon'        => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>',
            'format_url'  => function($v) {
                return 'mailto:' . trim($v);
            }
        ],
    ];
}

/**
 * Filter and format active social links for a user.
 * Empty or null values are completely omitted.
 *
 * @param array $user
 * @return array
 */
function get_user_social_links(array $user): array {
    $platforms = get_social_platforms_meta();
    $active = [];

    foreach ($platforms as $key => $meta) {
        $val = trim($user[$key] ?? '');
        if ($val === '') {
            continue; // HIDE EMPTY LINKS!
        }

        $url = ($meta['format_url'])($val);
        $isCopy = str_starts_with($url, '#discord:');

        $active[$key] = [
            'key'         => $key,
            'name'        => $meta['name'],
            'value'       => $val,
            'url'         => $url,
            'is_copy'     => $isCopy,
            'copy_text'   => $isCopy ? substr($url, 9) : '',
            'badge'       => $meta['badge'],
            'color'       => $meta['color'],
            'icon'        => $meta['icon'],
            'direct_chat' => in_array($key, ['whatsapp', 'discord', 'contact_email', 'facebook'], true)
        ];
    }

    return $active;
}