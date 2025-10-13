<?php
/**
 * Return Book Endpoint
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

// Check if user is logged in and is admin
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    $session->setFlash('error', 'You must be logged in as an admin to access this page.');
    header('Location: ' . APP_URL . '/login.php');
    exit;
}

// Initialize services
$transactionService = new TransactionService();

// Initialize CSRF protection
$csrf = new CSRF();

// Get active loans
$activeLoans = $transactionService->getActiveLoans();

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
            // Process return as pending return approval
            $transaction = $transactionService->getTransactionById($transactionId);
            if (!$transaction) {
                $error = 'Transaction not found.';
            } else {
                $updateData = [
                    'status' => 'returning',
                    'updated_at' => null,
                    'return_date' => null
                ];
                if ($transactionService->updateTransaction($transactionId, $updateData)) {
                    $session->setFlash('success', 'Return record submitted and pending admin action.');
                    header('Location: ' . APP_URL . '/transactions/view.php?id=' . $transactionId);
                    exit;
                } else {
                    $error = $transactionService->getErrorMessage();
                }
            }
        }
    }
}

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';

// Include return form template
include_once BASE_PATH . '/templates/transactions/return.php';

// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';
