<?php
/**
 * Record Payment in Collection Sheet - Fanders Microfinance System
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

// Set content type to JSON for AJAX response
header('Content-Type: application/json');

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to access this page.']);
    exit;
}

// Check for session timeout
if ($auth->checkSessionTimeout()) {
    echo json_encode(['success' => false, 'message' => 'Your session has expired. Please login again.']);
    exit;
}

// Get current user data
$user = $auth->getCurrentUser();
$userRole = $user['role'];

// Check if user has permission to record payments
if (!$auth->hasRole(['administrator', 'manager', 'account_officer'])) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to access this page.']);
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrf->validateToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please try again.']);
        exit;
    }

    $sheetId = (int)($_POST['sheet_id'] ?? 0);
    $loanId = (int)($_POST['loan_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $notes = $_POST['notes'] ?? '';

    // Validate input
    if (!$sheetId || !$loanId || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
        exit;
    }

    // Initialize services
    $collectionSheetService = new CollectionSheetService();

    // Get collection sheet details
    $sheet = $collectionSheetService->getCollectionSheetWithDetails($sheetId);
    if (!$sheet) {
        echo json_encode(['success' => false, 'message' => 'Collection sheet not found.']);
        exit;
    }

    // Check if user can record payments for this sheet
    if ($userRole == 'account_officer' && $sheet['account_officer_id'] != $user['id']) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to record payments for this collection sheet.']);
        exit;
    }

    if ($sheet['status'] != 'draft') {
        echo json_encode(['success' => false, 'message' => 'Payments can only be recorded in draft collection sheets.']);
        exit;
    }

    // Record the payment
    $success = $collectionSheetService->updatePaymentInSheet($sheetId, $loanId, $amount, $notes);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Payment recorded successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to record payment: ' . $collectionSheetService->getLastError()]);
    }
    exit;
} else {
    // Invalid request method
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}
?>
