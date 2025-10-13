<?php
/**
 * Archive book handler
 */

// Include configuration
require_once '../../app/config/config.php';

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

// Initialize CSRF protection
$csrf = new CSRF();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    // Redirect to login page
    $session->setFlash('error', 'Please login to access this page.');
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

// Check for session timeout
if ($auth->checkSessionTimeout()) {
    // Session has timed out, redirect to login page with message
    $session->setFlash('error', 'Your session has expired. Please login again.');
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

// Get current user data
$user = $auth->getCurrentUser();
$userRole = $user['role'];

// Check if user has permission to archive books
if (!in_array($userRole, ['super-admin', 'admin'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to archive books.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Check if book ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    // Redirect to books page with error message
    $session->setFlash('error', 'Book ID is required.');
    header('Location: ' . APP_URL . '/public/books/index.php');
    exit;
}

// Validate CSRF token
if (!$csrf->validateRequest()) {
    // Redirect to books page with error message
    $session->setFlash('error', 'Invalid request.');
    header('Location: ' . APP_URL . '/public/books/index.php');
    exit;
}

$bookId = (int)$_POST['id'];
$archiveReason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

// Initialize book service
$bookService = new BookService();

// Archive book
if ($bookService->archiveBook($bookId)) {
    $session->setFlash('success', 'Book archived successfully.');
} else {
    $session->setFlash('error', $bookService->getErrorMessage());
}

// Redirect back to book view page
header('Location: ' . APP_URL . '/public/books/view.php?id=' . $bookId);
exit; 