<?php
/**
 * Logout handler for the Library Management System
 */

// Include configuration
require_once '../app/config/config.php';

// Start output buffering
ob_start();

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

// Initialize session management
$session = new Session();

// Initialize authentication service
$auth = new AuthService();

// Check if user is logged in
if ($auth->isLoggedIn()) {
    // Perform logout
    $auth->logout();
    
    // Set success message
    $session->setFlash('success', 'You have been successfully logged out.');
} else {
    // User is not logged in
    $session->setFlash('error', 'You are not logged in.');
}

// Redirect to login page
header('Location: ' . APP_URL . '/public/login.php');
exit;

// End output buffering
ob_end_flush();
?>
