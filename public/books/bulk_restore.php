<?php
/**
 * Bulk restore archived books handler
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

// // Check if user has permission to restore books
// if ($userRole !== 'super-admin' || $userRole !== 'admin') {
//     // Redirect to dashboard with error message
//     $session->setFlash('error', 'You do not have permission to restore books.');
//     header('Location: ' . APP_URL . '/public/dashboard.php');
//     exit;
// }

// // Check if book IDs are provided
// if (!isset($_POST['book_ids']) || empty($_POST['book_ids'])) {
//     // Redirect to archived books page with error message
//     $session->setFlash('error', 'No books selected for restoration.');
//     header('Location: ' . APP_URL . '/public/books/archived.php');
//     exit;
// }

// Validate CSRF token
if (!$csrf->validateRequest()) {
    // Redirect to archived books page with error message
    $session->setFlash('error', 'Invalid request.');
    header('Location: ' . APP_URL . '/public/books/archived.php');
    exit;
}

$bookIds = $_POST['book_ids'];

// Initialize book service
$bookService = new BookService();

// Restore books
$successCount = 0;
$failedCount = 0;

foreach ($bookIds as $bookId) {
    if ($bookService->restoreBook($bookId)) {
        $successCount++;
    } else {
        $failedCount++;
    }
}

// Set appropriate flash message
if ($successCount > 0) {
    $message = "Successfully restored {$successCount} book" . ($successCount > 1 ? 's' : '');
    if ($failedCount > 0) {
        $message .= ", but failed to restore {$failedCount} book" . ($failedCount > 1 ? 's' : '');
    }
    $session->setFlash('success', $message);
} else {
    $session->setFlash('error', 'Failed to restore any books.');
}

// Redirect back to archived books page
header('Location: ' . APP_URL . '/public/books/index.php');
exit; 