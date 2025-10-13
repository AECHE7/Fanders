<?php

// Database configuration
define('DB_HOST', 'aws-1-ap-southeast-1.pooler.supabase.com');
define('DB_PORT', 6543); // Add the port
define('DB_NAME', 'postgres');
define('DB_USER', 'postgres.smzpalngwpwylljdvppb');
define('DB_PASS', '105489100018Gadiano');
define('DB_TYPE', 'pgsql'); 
define('DB_POOL_MODE', 'transaction'); // Add the pool mode

// Application configuration
define('APP_NAME', 'Fanders Microfinance');
define('APP_URL', 'http://localhost/Fanders');
define('SESSION_LIFETIME', 1800); // 30 minutes in seconds
define('BASE_PATH', dirname(dirname(__DIR__)));

// Role definitions
define('ROLE_SUPER_ADMIN', 1);
define('ROLE_ADMIN', 2);
define('ROLE_BORROWER', 3);

// Penalty configuration
define('PENALTY_BASE_AMOUNT', 50); // Base amount in PHP Peso (₱)
define('PENALTY_DAILY_INCREMENT', 10); // Daily increment in PHP Peso (₱)

// Borrower settings
define('BORROWER_MAX_BOOKS', 5); // Maximum number of books a borrower can have at once
define('BORROWER_MAX_DAYS', 14); // Maximum number of days a borrower can keep a book
define('BORROWER_PENALTY_DAYS', 7); // Number of days before a penalty is applied
define('BORROWER_PENALTY_AMOUNT', 1.00); // Penalty amount per day

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