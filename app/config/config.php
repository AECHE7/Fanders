<?php

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'fanders');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') !== false ? getenv('DB_USER') : 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''); // Change this!
if (!defined('DB_TYPE')) define('DB_TYPE', getenv('DB_TYPE') !== false ? getenv('DB_TYPE') : 'mysql');
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') !== false ? getenv('DB_PORT') : '3306');
if (!defined('DB_POOL_MODE')) define('DB_POOL_MODE', getenv('DB_POOL_MODE') !== false ? getenv('DB_POOL_MODE') : false); // Pool Mode
// Database Settings - prefer environment variables where present
// Support full DATABASE_URL (e.g. postgres://user:pass@host:5432/dbname)
$databaseUrl = getenv('DATABASE_URL') !== false ? getenv('DATABASE_URL') : (getenv('SUPABASE_DB_URL') !== false ? getenv('SUPABASE_DB_URL') : false);

if ($databaseUrl) {
    $parsed = parse_url($databaseUrl);
    if ($parsed !== false) {
        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] : '';
        // Normalize postgres scheme to pgsql for PDO DSN
        $normalizedType = in_array($scheme, ['postgres', 'postgresql'], true) ? 'pgsql' : $scheme;

        // Resolve DB_TYPE with explicit checks to avoid unparenthesized ternary issues on PHP 8
        if (getenv('DB_TYPE') !== false) {
            $finalDbType = getenv('DB_TYPE');
        } else {
            $finalDbType = $normalizedType ? $normalizedType : 'pgsql';
        }
    if (!defined('DB_TYPE')) define('DB_TYPE', $finalDbType);
    if (!defined('DB_HOST')) define('DB_HOST', $parsed['host'] ?? 'localhost');
    if (!defined('DB_PORT')) define('DB_PORT', $parsed['port'] ?? (DB_TYPE === 'pgsql' ? '5432' : '3306'));
    if (!defined('DB_NAME')) define('DB_NAME', isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'fanders');
    if (!defined('DB_USER')) define('DB_USER', $parsed['user'] ?? '');
    if (!defined('DB_PASS')) define('DB_PASS', $parsed['pass'] ?? '');
    if (!defined('DB_POOL_MODE')) define('DB_POOL_MODE', getenv('DB_POOL_MODE') !== false ? getenv('DB_POOL_MODE') : false);
    } else {
        // Fallback to individual env vars if parse fails
    if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'localhost');
    if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'fanders');
    if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') !== false ? getenv('DB_USER') : 'root');
    if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''); // Change this!
    if (!defined('DB_TYPE')) define('DB_TYPE', getenv('DB_TYPE') !== false ? getenv('DB_TYPE') : 'mysql');
    if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') !== false ? getenv('DB_PORT') : '3306');
    if (!defined('DB_POOL_MODE')) define('DB_POOL_MODE', getenv('DB_POOL_MODE') !== false ? getenv('DB_POOL_MODE') : false); // Pool Mode
    }
} else {
    // No DATABASE_URL provided, use individual env vars (backwards compatible)
    if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'localhost');
    if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'fanders');
    if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') !== false ? getenv('DB_USER') : 'root');
    if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''); // Change this!
    if (!defined('DB_TYPE')) define('DB_TYPE', getenv('DB_TYPE') !== false ? getenv('DB_TYPE') : 'mysql');
    if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') !== false ? getenv('DB_PORT') : '3306');
    if (!defined('DB_POOL_MODE')) define('DB_POOL_MODE', getenv('DB_POOL_MODE') !== false ? getenv('DB_POOL_MODE') : false); // Pool Mode
}

// Security Settings
define('SESSION_NAME', 'lms_session');
define('PASSWORD_ALGORITHM', PASSWORD_BCRYPT);

// Application configuration
define('APP_NAME', 'Fanders Microfinance');
// Prefer APP_URL from environment when available (useful for hosted environments)
define('APP_URL', getenv('APP_URL') !== false ? getenv('APP_URL') : 'http://localhost/FandersMicrofinance');
define('SESSION_LIFETIME', 1800); // 30 minutes in seconds
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(dirname(__DIR__)));
}

// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session configuration
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); // Set secure cookie if HTTPS is used
ini_set('session.cookie_samesite', 'Lax');

// Timezone
date_default_timezone_set('Asia/Manila');