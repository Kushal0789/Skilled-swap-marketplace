<?php
/**
 * Database Configuration & Connection (Data Layer)
 * Skill Swap Marketplace
 */

// Prevent direct script execution if accessed outside PHP runtime
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}

// Database Credentials (Default for standard XAMPP setup)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'skill_swap');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a shared PDO database connection instance.
 *
 * @return PDO
 * @throws RuntimeException
 */
function get_db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log real error for system administrator
            error_log('[SkillSwap DB Connection Error] ' . $e->getMessage());

            // Check if this is an API request (JSON) or page request (HTML)
            $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
            if ($isApi) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Database connection failed. Please ensure MySQL is running in XAMPP and skill_swap database is imported.',
                    'errors'  => ['db_unavailable']
                ]);
                exit;
            }

            // Display friendly user message for HTML pages
            die('
            <div style="font-family: system-ui, sans-serif; max-width: 600px; margin: 80px auto; padding: 24px; border: 1px solid #fecaca; background: #fef2f2; border-radius: 12px; color: #991b1b;">
                <h2 style="margin-top: 0; color: #b91c1c;">Skill Swap: Database Connection Error</h2>
                <p>Could not connect to the MySQL database <strong>' . htmlspecialchars(DB_NAME) . '</strong>.</p>
                <p><strong>Troubleshooting Steps for XAMPP:</strong></p>
                <ol style="line-height: 1.6;">
                    <li>Open <strong>XAMPP Control Panel</strong> and make sure <strong>MySQL</strong> is started (green indicator).</li>
                    <li>Open <a href="http://localhost/phpmyadmin/" target="_blank" style="color: #4f46e5;">phpMyAdmin</a> and ensure the database <code>skill_swap</code> exists.</li>
                    <li>Import the schema file: <code>database/skill_swap.sql</code>.</li>
                    <li>Verify credentials in <code>config/db.php</code> (Default: user=root, password="").</li>
                </ol>
            </div>
            ');
        }
    }

    return $pdo;
}

/**
 * Execute a callback inside a database transaction.
 * Automatically commits on success and rolls back on exception.
 *
 * @param callable $callback function(PDO $pdo)
 * @return mixed
 * @throws Exception
 */
function db_transaction(callable $callback): mixed {
    $pdo = get_db();
    $pdo->beginTransaction();
    try {
        $result = $callback($pdo);
        $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
