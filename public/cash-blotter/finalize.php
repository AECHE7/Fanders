<?php
/**
 * Finalize Cash Blotter Entry - Fanders Microfinance System
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

// Check if user has permission to finalize cash blotter (Administrator only)
if ($userRole !== 'administrator') {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to finalize cash blotter entries.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $session->setFlash('error', 'Invalid request method.');
    header('Location: ' . APP_URL . '/public/cash-blotter/index.php');
    exit;
}

// Validate CSRF token
if (!$csrf->validateToken($_POST['csrf_token'] ?? '')) {
    $session->setFlash('error', 'Invalid security token. Please try again.');
    header('Location: ' . APP_URL . '/public/cash-blotter/index.php');
    exit;
}

// Get blotter ID
$blotterId = isset($_POST['blotter_id']) ? (int)$_POST['blotter_id'] : null;
if (!$blotterId) {
    $session->setFlash('error', 'Invalid blotter ID.');
    header('Location: ' . APP_URL . '/public/cash-blotter/index.php');
    exit;
}

// Initialize cash blotter service
$cashBlotterService = new CashBlotterService();

// Get blotter details
$blotter = $cashBlotterService->getBlotterById($blotterId);
if (!$blotter) {
    $session->setFlash('error', 'Cash blotter entry not found.');
    header('Location: ' . APP_URL . '/public/cash-blotter/index.php');
    exit;
}

// Check if already finalized
if ($blotter['status'] === 'finalized') {
    $session->setFlash('error', 'This cash blotter entry is already finalized.');
    header('Location: ' . APP_URL . '/public/cash-blotter/view.php?id=' . $blotterId);
    exit;
}

// Validate balance before finalizing
$calculatedBalance = $blotter['opening_balance'] + $blotter['total_collections'] - $blotter['total_loan_releases'] - $blotter['total_expenses'];
if (abs($calculatedBalance - $blotter['closing_balance']) > 0.01) {
    $session->setFlash('error', 'Cannot finalize: Balance calculation does not match. Please correct the entry first.');
    header('Location: ' . APP_URL . '/public/cash-blotter/view.php?id=' . $blotterId);
    exit;
}

// Finalize the blotter
$result = $cashBlotterService->finalizeBlotter($blotterId);
if ($result) {
    $session->setFlash('success', 'Cash blotter entry has been finalized successfully.');
} else {
    $session->setFlash('error', 'Failed to finalize cash blotter entry.');
}

// Redirect back to view page
header('Location: ' . APP_URL . '/public/cash-blotter/view.php?id=' . $blotterId);
exit;
?>
