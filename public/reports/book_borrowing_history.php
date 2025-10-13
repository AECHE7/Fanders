<?php
/**
 * Borrowing History Report page for a specific book
 */

// Include configuration
require_once '../../app/config/config.php';

// Start output buffering
ob_start();

// Include all required files
function autoload($className) {
    $directories = [
        'app/core/',
        'app/models/',
        'app/services/',
        'app/utilities/'
    ];
    foreach ($directories as $directory) {
        $file = BASE_PATH . '/' . $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}
spl_autoload_register('autoload');

// Initialize session management
$session = new Session();

// Initialize authentication service
$auth = new AuthService();

// Initialize CSRF protection
$csrf = new CSRF();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    $session->setFlash('error', 'Please login to access this page.');
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

// Check for session timeout
if ($auth->checkSessionTimeout()) {
    $session->setFlash('error', 'Your session has expired. Please login again.');
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

// Check if user has permission to generate reports (Super Admin or Admin)
if (!$auth->hasRole(['super-admin', 'admin'])) {
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Get book ID from GET or POST
$bookId = null;
if (isset($_GET['book_id'])) {
    $bookId = (int)$_GET['book_id'];
} elseif (isset($_POST['book_id'])) {
    $bookId = (int)$_POST['book_id'];
}

if (!$bookId) {
    $session->setFlash('error', 'Book ID is required to generate report.');
    header('Location: ' . APP_URL . '/public/books/index.php');
    exit;
}

// Initialize ReportService
$reportService = new ReportService();

// Generate PDF report for borrowing history of the book
$reportService->generateBookBorrowingHistoryReport($bookId, true);

// End output buffering and flush output
ob_end_flush();
exit;
?>
