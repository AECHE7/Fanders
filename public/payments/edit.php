<?php
/**
 * Edit transaction page for the Library Management System
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

// Initialize transaction service
$transactionService = new TransactionService();

// Get transaction data
$editTransaction = $transactionService->getTransactionById($transactionId);

if (!$editTransaction) {
    // Transaction not found
    $session->setFlash('error', 'Transaction not found.');
    header('Location: ' . APP_URL . '/public/transactions/index.php');
    exit;
}

// Check if user has permission to edit this transaction
// Super Admin can edit any transaction, Admin can edit transactions
if (!$auth->hasRole(['super-admin', 'admin'])) {
    $session->setFlash('error', 'You do not have permission to edit this transaction.');
    header('Location: ' . APP_URL . '/public/transactions/index.php');
    exit;
}

// Process form submission
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrf->validateRequest()) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Get form data with fallbacks to existing transaction data
        $updatedTransaction = [
            'user_id' => isset($_POST['user_id']) ? (int)$_POST['user_id'] : $editTransaction['user_id'],
            'book_id' => isset($_POST['book_id']) ? (int)$_POST['book_id'] : $editTransaction['book_id'],
            'borrow_date' => isset($_POST['borrow_date']) ? trim($_POST['borrow_date']) : $editTransaction['borrow_date'],
            'due_date' => isset($_POST['due_date']) ? trim($_POST['due_date']) : $editTransaction['due_date'],
            'return_date' => isset($_POST['return_date']) ? trim($_POST['return_date']) : $editTransaction['return_date'],
            'status' => isset($_POST['status']) ? trim($_POST['status']) : $editTransaction['status'],
        ];

        // Validate required fields
        if (empty($updatedTransaction['user_id']) || empty($updatedTransaction['book_id']) || empty($updatedTransaction['borrow_date']) || empty($updatedTransaction['due_date'])) {
            $error = 'Please fill in all required fields.';
        }

        if (empty($error)) {
            // Update the transaction using TransactionService updateTransaction method
            $result = $transactionService->updateTransaction($transactionId, $updatedTransaction);

            if ($result) {
                // Transaction updated successfully
                $session->setFlash('success', 'Transaction updated successfully.');
                header('Location: ' . APP_URL . '/public/transactions/view.php?id=' . $transactionId);
                exit;
            } else {
                // Failed to update transaction
                $error = $transactionService->getErrorMessage() ?: 'Failed to update transaction.';
            }
        }
    }
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
                        <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #fff4e6;">
                            <i data-feather="edit-3" style="width: 24px; height: 24px; color: rgb(255, 165, 0);"></i>
                        </div>
                    </div>
                    <h1 class="notion-page-title mb-0">Edit Transaction</h1>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="arrow-left" class="me-1" style="width: 14px; height: 14px;"></i> Back to Transactions
                    </a>
                </div>
            </div>
        </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <!-- Transaction Edit Form -->
    <div class="card">
        <div class="card-body">
            <?php
            $formPath = BASE_PATH . '/templates/transactions/form.php';
            if (file_exists($formPath)) {
                include_once $formPath;
            } else {
                echo '<div class="alert alert-danger">Transaction form template is missing: ' . htmlspecialchars($formPath) . '</div>';
            }
            ?>
        </div>
    </div>
    </div>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
