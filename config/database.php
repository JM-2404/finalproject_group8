<?php
/**
 * AccessAble — Database connection (PDO)
 * Owner: Member 4
 *
 * Credentials are NOT stored in this file. They are read, in order, from:
 *   1. config/env.php            (local file, ignored by Git — copy env.example.php)
 *   2. Server environment variables DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT
 *   3. XAMPP defaults (localhost / root / no password) for local development
 *
 * Usage in any page:
 *   require_once __DIR__ . '/config/database.php';
 *   $pdo = getDB();          // or use the global $pdo created below
 */

if (defined('ACCESSABLE_DB_LOADED')) {
    return;
}
define('ACCESSABLE_DB_LOADED', true);

$envFile = __DIR__ . '/env.php';
$env = is_file($envFile) ? (require $envFile) : [];
if (!is_array($env)) {
    $env = [];
}

/** Read a setting from env.php, then the server environment, then a default. */
function accessableConfig(array $env, string $key, string $default): string
{
    if (isset($env[$key]) && $env[$key] !== '') {
        return (string) $env[$key];
    }
    $value = getenv($key);
    return ($value !== false && $value !== '') ? $value : $default;
}

define('DB_HOST', accessableConfig($env, 'DB_HOST', 'localhost'));
define('DB_PORT', accessableConfig($env, 'DB_PORT', '3306'));
define('DB_NAME', accessableConfig($env, 'DB_NAME', 'accessable'));
define('DB_USER', accessableConfig($env, 'DB_USER', 'root'));
define('DB_PASS', accessableConfig($env, 'DB_PASS', ''));
define('APP_ENV', accessableConfig($env, 'APP_ENV', 'development')); // 'production' on the live host

// Never show PHP errors to visitors on the live site.
if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

/**
 * Return the shared PDO connection (created once per request).
 * Uses real prepared statements, exceptions on error and associative fetches.
 */
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log('AccessAble DB connection failed: ' . $e->getMessage());
        http_response_code(503);
        if (APP_ENV === 'production') {
            exit('Sorry, AccessAble is temporarily unavailable. Please try again shortly.');
        }
        exit('Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            . '<br>Check config/env.php and that MySQL is running and database/accessable.sql has been imported.');
    }

    return $pdo;
}

// Convenience global for teammates who prefer $pdo directly.
$pdo = getDB();
