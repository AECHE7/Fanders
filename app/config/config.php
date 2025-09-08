<?php
/**
 * Configuration file for the Library Management System
 */

// Database configuration
define('DB_HOST', ('localhost:3307'));
define('DB_NAME', ('library_sys'));
define('DB_USER', ('root'));
define('DB_PASS', (''));
define('DB_TYPE', 'mysql'); // Changed to 'mysql' for compatibility with Replit


// Application configuration
define('APP_NAME', 'Library Management System');
define('APP_URL', 'http://localhost/LibraryVault');
define('SESSION_LIFETIME', 1800); // 30 minutes in seconds
define('BASE_PATH', dirname(dirname(__DIR__)));

// Role definitions
define('ROLE_SUPER_ADMIN', 1);
define('ROLE_ADMIN', 2);
define('ROLE_BORROWER', 3);

// Penalty configuration
define('PENALTY_BASE_AMOUNT', 50); // Base amount in PHP Peso (₱)
define('PENALTY_DAILY_INCREMENT', 10); // Daily increment in PHP Peso (₱)

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
