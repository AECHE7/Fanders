<?php
/**
 * Submit Collection Sheet - Fanders Microfinance System
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

// Check if user has permission to submit collection sheets
if (!$auth->hasRole(['administrator', 'manager', 'account_officer'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrf->validateToken($_POST['csrf_token'] ?? '')) {
        $session->setFlash('error', 'Invalid security token. Please try again.');
        header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
        exit;
    }

    $sheetId = (int)($_POST['sheet_id'] ?? 0);

    if (!$sheetId) {
        $session->setFlash('error', 'Invalid collection sheet ID.');
        header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
        exit;
    }

    // Initialize collection sheet service
    $collectionSheetService = new CollectionSheetService();

    // Get collection sheet details
    $sheet = $collectionSheetService->getCollectionSheetWithDetails($sheetId);
    if (!$sheet) {
        $session->setFlash('error', 'Collection sheet not found.');
        header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
        exit;
    }

    // Check if user can submit this sheet
    if ($userRole == 'account_officer' && $sheet['account_officer_id'] != $user['id']) {
        $session->setFlash('error', 'You do not have permission to submit this collection sheet.');
        header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
        exit;
    }

    if ($sheet['status'] != 'draft') {
        $session->setFlash('error', 'Only draft collection sheets can be submitted for approval.');
        header('Location: ' . APP_URL . '/public/collection-sheets/view.php?id=' . $sheetId);
        exit;
    }

    // Submit the collection sheet
    $success = $collectionSheetService->submitCollectionSheet($sheetId);

    if ($success) {
        $session->setFlash('success', 'Collection sheet submitted for approval successfully.');
    } else {
        $session->setFlash('error', 'Failed to submit collection sheet: ' . $collectionSheetService->getLastError());
    }

    header('Location: ' . APP_URL . '/public/collection-sheets/view.php?id=' . $sheetId);
    exit;
} else {
    // Invalid request method
    $session->setFlash('error', 'Invalid request method.');
    header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
    exit;
}
?>
