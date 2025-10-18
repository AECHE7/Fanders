<?php

// Database Settings - prefer environment variables where present
define('DB_HOST', getenv('DB_HOST') !== false ? getenv('DB_HOST') : 'localhost');
define('DB_NAME', getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'fanders');
define('DB_USER', getenv('DB_USER') !== false ? getenv('DB_USER') : 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''); // Change this!
define('DB_TYPE', getenv('DB_TYPE') !== false ? getenv('DB_TYPE') : 'mysql');
define('DB_PORT', getenv('DB_PORT') !== false ? getenv('DB_PORT') : '3307');
define('DB_POOL_MODE', getenv('DB_POOL_MODE') !== false ? getenv('DB_POOL_MODE') : false); // Pool Mode

// Security Settings
define('SESSION_NAME', 'lms_session');
define('PASSWORD_ALGORITHM', PASSWORD_BCRYPT);

// Application configuration
define('APP_NAME', 'Fanders Microfinance');
define('APP_URL', 'http://localhost/FandersMicrofinance');
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