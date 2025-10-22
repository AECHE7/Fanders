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
    header('Location: ' . APP_URL . '/public/auth/login.php');
    exit;
}

// Check for session timeout
if ($auth->checkSessionTimeout()) {
    // Session has timed out, redirect to login page with message
    $session->setFlash('error', 'Your session has expired. Please login again.');
    header('Location: ' . APP_URL . '/public/auth/login.php');
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

<main class="main-content">
    <div class="content-wrapper">
        <!-- Page Header with Icon -->
        <div class="notion-page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #ffe6e6;">
                            <i data-feather="trash-2" style="width: 24px; height: 24px; color: rgb(220, 53, 69);"></i>
                        </div>
                    </div>
                    <h1 class="notion-page-title mb-0">Delete Transaction</h1>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="arrow-left" class="me-1" style="width: 14px; height: 14px;"></i> Back to Transactions
                    </a>
                </div>
            </div>
        </div>

    <div class="alert alert-warning">
        <p>Are you sure you want to delete this transaction? This action cannot be undone.</p>
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteTransactionModal">
            Delete Transaction
        </button>
        <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-secondary">Cancel</a>
    </div>
    </div>
</main>

<!-- Delete Transaction Modal -->
<div class="modal fade" id="deleteTransactionModal" tabindex="-1" aria-labelledby="deleteTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteTransactionModalLabel">
                    <i data-feather="trash-2"></i> Confirm Transaction Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i data-feather="alert-triangle"></i> <strong>PERMANENT DELETION:</strong> This action cannot be undone!
                </div>
                <p>You are about to delete transaction:</p>
                <ul class="list-unstyled ms-3">
                    <li><strong>Transaction ID:</strong> <?= htmlspecialchars($transactionId) ?></li>
                </ul>
                <p class="text-danger fw-bold">Are you absolutely sure you want to proceed?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" action="" style="display:inline;">
                    <?= $csrf->getTokenField() ?>
                    <button type="submit" class="btn btn-danger">
                        <i data-feather="trash-2"></i> Confirm Deletion
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
