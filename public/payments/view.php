<?php
/**
 * View transaction details page for the Library Management System
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

// Debugging: log transaction ID
error_log("Transaction ID received: " . $transactionId);

$transactionService = new TransactionService();

// Get transaction data
$transaction = $transactionService->getTransactionById($transactionId);

// Debugging: log transaction data
if ($transaction === false || $transaction === null) {
    error_log("Transaction not found for ID: " . $transactionId);
}

if (!$transaction) {
    $session->setFlash('error', 'Transaction not found.');
    header('Location: ' . APP_URL . '/public/transactions/index.php');
    exit;
}

$userService = new UserService();
$viewUser = $userService->getUserWithRoleName($transaction['user_id']);

if (!$viewUser) {
    $session->setFlash('error', 'User associated with this transaction not found.');
    header('Location: ' . APP_URL . '/public/transactions/index.php');
    exit;
}

// Permission check for borrower role
if ($userRole == 'borrower' && $transaction['user_id'] != $user['id']) {
    $session->setFlash('error', 'You do not have permission to view this transaction.');
    header('Location: ' . APP_URL . '/public/transactions/index.php');
    exit;
}

// Permission check for admin role: can only view borrowers (student, staff, other)
if ($userRole == 'admin' && !in_array($viewUser['role'], ['student', 'staff', 'other'])) {
    $session->setFlash('error', 'You do not have permission to view this transaction.');
    header('Location: ' . APP_URL . '/public/transactions/index.php');
    exit;
}

// Fetch borrowed books if user is student or staff
$borrowedBooks = [];
if (in_array($viewUser['role'], ['student', 'staff'])) {
    $bookService = new BookService();
    $borrowedBooks = $bookService->getUserBorrowedBooks($viewUser['id']);
} else {
    $bookService = new BookService();
}

$book = $bookService->getBookWithCategory($transaction['book_id']);

// Get transaction history for this book
$bookTransactions = $transactionService->getBookTransactionHistory($transaction['book_id']);

// Get user transaction history if needed (for super-admin viewing borrower)
$userTransactions = [];
$borrowerStats = [];
if ($userRole == 'super-admin' && in_array($viewUser['role'], ['student', 'staff', 'other'])) {
    $userTransactions = $transactionService->getUserTransactionHistory($viewUser['id']);
    $borrowerStats = $userService->getBorrowerStats($viewUser['id']);
}

// Handle delete request
if (isset($_POST['delete']) && $auth->hasRole(['super-admin', 'admin']) && $csrf->validateRequest()) {
    if ($transactionService->deleteTransaction($transactionId)) {
        // Transaction deleted successfully
        $session->setFlash('success', 'Transaction deleted successfully.');
        header('Location: ' . APP_URL . '/public/transactions/index.php');
        exit;
    } else {
        // Failed to delete transaction
        $session->setFlash('error', $transactionService->getErrorMessage());
    }
}

include_once BASE_PATH . '/templates/layout/header.php';

include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Transaction Details</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <?php if ($auth->hasRole(['super-admin', 'admin'])): ?>
                    <a href="<?= APP_URL ?>/public/transactions/borrow.php?id=<?= $transactionId ?>" class="btn btn-sm btn-outline-primary">
                        <i data-feather="edit"></i> Edit Transaction
                    </a>
                <?php endif; ?>
                <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="arrow-left"></i> Back to Transactions
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

    <!-- Transaction Details -->
    <?php include BASE_PATH . '/templates/transactions/view.php'; ?>

    <!-- Book Details -->
    <?php
    // Pass $bookTransactions as $transactions for book template
    $transactions = $bookTransactions;
    include_once BASE_PATH . '/templates/books/view.php';
    ?>

    <?php if ($auth->hasRole(['super-admin', 'admin'])): ?>
        <!-- Delete Transaction Form -->
        <div class="card mt-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Danger Zone</h5>
            </div>
            <div class="card-body">
                <p class="card-text">Deleting this transaction is irreversible. Please be certain.</p>
                <form action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $transactionId ?>" method="post" onsubmit="return confirm('Are you sure you want to delete this transaction? This action cannot be undone.');">
                    <?= $csrf->getTokenField() ?>
                    <button type="submit" name="delete" class="btn btn-danger">
                        <i data-feather="trash-2"></i> Delete Transaction
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
