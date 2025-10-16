<?php
/**
 * Overdue Books Endpoint
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

// // Check if user is logged in and is admin
// if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
//     $session->setFlash('error', 'You must be logged in as an admin to access this page.');
//     header('Location: ' . APP_URL . '/login.php');
//     exit;
// }

// Initialize services
$transactionService = new TransactionService();

// Initialize CSRF protection
$csrf = new CSRF();

// Get overdue loans
$overdueLoans = $transactionService->getOverdueLoans();

// Process form submission
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrf->validateRequest()) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Get and validate input
        $transactionId = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;

        if (!$transactionId) {
            $error = 'Invalid transaction ID.';
        } else {
            // Process return
            if ($transactionService->returnBook($transactionId)) {
                $session->setFlash('success', 'Book returned successfully.');
                header('Location: ' . APP_URL . '/transactions/view.php?id=' . $transactionId);
                exit;
            } else {
                $error = $transactionService->getErrorMessage();
            }
        }
    }
}

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $transactionService->exportTransactionsToPDF('overdue');
    exit;
}

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';

// Include overdue books template
include_once BASE_PATH . '/templates/transactions/overdue.php';

// Include footer
include_once BASE_PATH . '/templates/layout/footer.php'; 