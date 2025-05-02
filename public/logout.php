<?php
/**
 * Logout page for the Library Management System
 */

// Include configuration
require_once '../app/config/config.php';

// Include all required files
function autoload($className) {
    // Define the directories to look in
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

// Initialize session management and authentication
$session = new Session();
$auth = new AuthService();

// Logout user
$auth->logout();

// Set success message
$session->setFlash('success', 'You have been logged out successfully.');

// Redirect to login page
header('Location: ' . APP_URL . '/public/login.php');
exit;
