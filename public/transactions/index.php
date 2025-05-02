<?php
/**
 * Transactions list page for the Library Management System
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
$userRole = $user['role_id'];

// Initialize transaction service
$transactionService = new TransactionService();

// Process filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$transactions = [];

// Get transactions based on user role and filters
if ($userRole == ROLE_SUPER_ADMIN || $userRole == ROLE_ADMIN) {
    // Admins can see all transactions
    if ($statusFilter == 'overdue') {
        $transactions = $transactionService->getOverdueLoans();
    } elseif ($statusFilter == 'active') {
        $transactions = $transactionService->getActiveLoans();
    } else {
        $transactions = $transactionService->getAllTransactionsWithDetails();
    }
} else {
    // Borrowers can only see their own transactions
    $transactions = $transactionService->getUserTransactionHistory($user['id']);
}

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <?php if ($userRole == ROLE_BORROWER): ?>
                My Loans
            <?php else: ?>
                Transactions
            <?php endif; ?>
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <?php if ($userRole == ROLE_SUPER_ADMIN || $userRole == ROLE_ADMIN): ?>
                    <a href="<?= APP_URL ?>/public/reports/transactions.php" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="file-text"></i> Generate Report
                    </a>
                <?php endif; ?>
                <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-sm btn-outline-primary">
                    <i data-feather="book"></i> Browse Books
                </a>
            </div>
        </div>
    </div>
    
    <?php if ($session->hasFlash('success')): ?>
        <div class="alert alert-success">
            <?= $session->getFlash('success') ?>
        </div>
    <?php endif; ?>
    
    <?php if ($session->hasFlash('error')): ?>
        <div class="alert alert-danger">
            <?= $session->getFlash('error') ?>
        </div>
    <?php endif; ?>
    
    <?php if ($userRole == ROLE_SUPER_ADMIN || $userRole == ROLE_ADMIN): ?>
        <!-- Filter Options for Admins -->
        <div class="row mb-3">
            <div class="col-md-6">
                <form action="<?= $_SERVER['PHP_SELF'] ?>" method="get" class="d-flex">
                    <select name="status" class="form-select me-2">
                        <option value="" <?= $statusFilter == '' ? 'selected' : '' ?>>All Transactions</option>
                        <option value="active" <?= $statusFilter == 'active' ? 'selected' : '' ?>>Active Loans</option>
                        <option value="overdue" <?= $statusFilter == 'overdue' ? 'selected' : '' ?>>Overdue Loans</option>
                    </select>
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Reset</a>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Transactions List -->
    <?php include_once BASE_PATH . '/templates/transactions/list.php'; ?>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
