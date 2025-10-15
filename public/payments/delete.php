<?php
/**
 * Delete transaction page for the Library Management System
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

// Check if transaction ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to transactions page with error message
    $session->setFlash('error', 'Transaction ID is required.');
    header('Location: ' . APP_URL . '/public/transactions/index.php');
    exit;
}

$transactionId = (int)$_GET['id'];

// Check if user has permission to delete this transaction
if (!$auth->hasRole(['super-admin', 'admin'])) {
    $session->setFlash('error', 'You do not have permission to delete this transaction.');
    header('Location: ' . APP_URL . '/public/transactions/index.php');
    exit;
}

// Initialize transaction service
$transactionService = new TransactionService();

// Process deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrf->validateRequest()) {
        $session->setFlash('error', 'Invalid form submission. Please try again.');
        header('Location: ' . APP_URL . '/public/transactions/index.php');
        exit;
    }

    // Delete transaction using service method
    $result = $transactionService->deleteTransaction($transactionId);

    if ($result) {
        $session->setFlash('success', 'Transaction deleted successfully.');
    } else {
        $session->setFlash('error', 'Failed to delete transaction.');
    }

    header('Location: ' . APP_URL . '/public/transactions/index.php');
    exit;
}

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Delete Transaction</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-sm btn-outline-secondary">
                <i data-feather="arrow-left"></i> Back to Transactions
            </a>
        </div>
    </div>

    <div class="alert alert-warning">
        <p>Are you sure you want to delete this transaction? This action cannot be undone.</p>
        <form method="post" action="">
            <?= $csrf->getTokenField() ?>
            <button type="submit" class="btn btn-danger">Delete</button>
            <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
