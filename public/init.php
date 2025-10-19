<?php
/**
 * Application Initialization File (public/init.php)
 * This file handles all necessary bootstrapping for public-facing controllers:
 * 1. Defines Constants (BASE_PATH, APP_URL).
 * 2. Sets up the Autoloader for all Core, Model, and Service classes.
 * 3. Initializes Session, CSRF, and AuthService.
 * 4. Enforces the session timeout check on every request.
 * * NOTE: Controllers (e.g., public/loans/add.php) should include this file first.
 */

// Define application root path
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Start output buffering immediately to prevent headers sent issues
if (ob_get_level() === 0) {
    ob_start();
}

// Include configuration file
require_once BASE_PATH . '/app/config/config.php';

// --- 1. Autoloader Setup ---
function autoload($className) {
    // Define the directories to look in (relative to BASE_PATH)
    $directories = [
        'app/core/',
        'app/models/',
        'app/services/',
        'app/utilities/'
    ];

    // Try to find the class file
    foreach ($directories as $directory) {
        $file = BASE_PATH . '/' . $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}

// Register autoloader
spl_autoload_register('autoload');

// --- 2. Initialize Core Services ---

// Initialize session management
$session = new Session();

// Initialize authentication service
$auth = new AuthService();

// Initialize CSRF protection
$csrf = new CSRF();
$csrfToken = $csrf->getToken(); // Get current token for form use

// --- 3. Global Security Checks ---

// Check if user is logged in (excluding login/init pages themselves)
if (!isset($skip_auth_check) || $skip_auth_check !== true) {
    if (!$auth->isLoggedIn()) {
        // Redirect to login page
        $session->setFlash('error', 'Please login to access this page.');
        header('Location: ' . APP_URL . '/public/login.php');
        exit;
    }

    // Check for session timeout - will be handled by JavaScript modal
    // Store timeout status for JavaScript to detect
    $sessionTimeout = $auth->checkSessionTimeout();
    if ($sessionTimeout) {
        $session->set('session_timed_out', true);
    } else {
        $session->remove('session_timed_out');
    }
    
    // Get current user data after successful login/check
    $user = $auth->getCurrentUser();
    $userRole = $user['role'];
}