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

// Determine userId based on role
if ($userRole == 'staff' || $userRole == 'student' || $userRole == 'other') {
    $userId = $user['id'];
} elseif ($userRole == 'admin' || $userRole == 'super-admin') {
    // For admin and super-admin, userId is optional, set to null if not provided
    $userId = isset($_GET['id']) && !empty($_GET['id']) ? (int)$_GET['id'] : null;
} else {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        // Redirect to users page with error message
        $session->setFlash('error', 'User ID is required.');
        header('Location: ' . APP_URL . '/public/users/index.php');
        exit;
    }
    $userId = (int)$_GET['id'];
}

$transactionService = new TransactionService();

// Initialize user service
$userService = new UserService();

if ($userId !== null) {
    // Get user data
    $viewUser = $userService->getUserWithRoleName($userId);

    if (!$viewUser) {
        // User not found
        $session->setFlash('error', 'User not found.');
        header('Location: ' . APP_URL . '/public/users/index.php');
        exit;
    }

    // Get borrowed books for the user
    $borrowedBooks = [];
    if (is_array($viewUser) && in_array($viewUser['role'], ['student', 'staff'])) {
        $bookService = new BookService();
        $borrowedBooks = $bookService->getUserBorrowedBooks($userId);
    }

    // Check if user has permission to view this user
    // Super Admin can view any user, Admin can only view borrowers
    if ($userRole == 'admin' && (!is_array($viewUser) || !in_array($viewUser['role'], ['student', 'staff', 'other']))) {
        // Redirect to dashboard with error message
        $session->setFlash('error', 'You do not have permission to view this user.');
        header('Location: ' . APP_URL . '/public/dashboard.php');
        exit;
    }

    /// Get transaction history for borrowers
    $transactions = [];
    if (is_array($viewUser) && $viewUser['role'] == 'super-admin') {
        $transactions = $transactionService->getUserTransactionHistory($userId);
        
        // Get borrower stats
        $borrowerStats = $userService->getBorrowerStats($userId);
    }
} else {
    $viewUser = null;
    $borrowedBooks = [];
    $transactions = [];
}

$startDateFilter = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDateFilter = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : null;

// Normalize status filter to lowercase for consistency
if ($statusFilter !== null) {
    $statusFilter = strtolower($statusFilter);
}

// Get transactions based on user role and filters
if ($userRole == 'super-admin' || $userRole == 'admin') {
    // Use the new filtered transactions method with date and status filters
    $transactions = $transactionService->getTransactionsForReports($startDateFilter, $endDateFilter, $statusFilter);
    if ($transactions === false) {
        echo "Error fetching transactions: " . $transactionService->getLastError();
    }
} else {
    // Borrowers can only see their own transactions including pending approvals
    $statuses = ['borrowed', 'returned', 'overdue', 'pending_approval', 'pending_return_approval'];
    $allTransactions = $transactionService->getUserTransactionHistory($user['id']);
    if ($allTransactions === false) {
        echo "Error fetching transactions: " . $transactionService->getLastError();
    } else {
        // Filter transactions to include only those with allowed statuses
        $transactions = array_filter($allTransactions, function($t) use ($statuses) {
            return in_array($t['status'], $statuses);
        });
    }
}
// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Transactions</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <?php if ($userRole == 'super-admin' || $userRole == 'admin'): ?>
                    
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

    <!-- Filter Options -->
    <?php if ($userRole == 'super-admin' || $userRole == 'admin'): ?>
        <div class="row mb-3">
            <div class="col-md-8">
                <form action="<?= $_SERVER['PHP_SELF'] ?>" method="get" class="d-flex">
                    <input type="date" name="start_date" class="form-control " value="<?=htmlspecialchars($startDateFilter) ?> " placeholder="Start Date">
                    <h1>-</h1>
                    <input type="date" name="end_date" class="form-control me-2" value="<?=htmlspecialchars($endDateFilter) ?>" placeholder="End Date">
                    <select name="status" class="form-select me-2">
                        <option value="" <?= $statusFilter == '' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="borrowed" <?= $statusFilter == 'borrowed' ? 'selected' : '' ?>>Borrowed</option>
                        <option value="returned" <?= $statusFilter == 'returned' ? 'selected' : '' ?>>Returned</option>
                        <option value="overdue" <?= $statusFilter == 'overdue' ? 'selected' : '' ?>>Overdue</option>
                    </select>
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <button class="btn btn-secondary">
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn">Reset</a>
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Transactions List -->
    <?php include_once BASE_PATH . '/templates/transactions/list.php'; ?>
</main>

<?php
// Handle return book and approval form submissions
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$csrf->validateRequest()) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        if (isset($_POST['return_book'])) {
            // Handle return book
            $transactionId = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;
            if (!$transactionId) {
                $error = 'Invalid transaction ID.';
            } else {
                // Update transaction status to 'returning' (pending return approval)
                $updateData = [
                    'status' => 'returning',
                    'updated_at' => date('Y-m-d H:i:s'),
                    'return_date' => null
                ];
                if ($transactionService->updateTransaction($transactionId, $updateData)) {
                    $session->setFlash('success', 'Return record submitted and pending admin action.');
                    header('Location: ' . APP_URL . '/public/transactions/index.php');
                    exit;
                } else {
                    $error = $transactionService->getErrorMessage();
                }
            }
        }
        if (isset($_POST['approve'])) {
            // Handle approve request
            $transactionId = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;
            if (!$transactionId) {
                $error = 'Invalid transaction ID.';
            } else {
                $transaction = $transactionService->getTransactionById($transactionId);
                if (!$transaction) {
                    $error = 'Transaction not found.';
                } else {
                    if ($transaction['status'] === 'pending_approval') {
                        $result = $transactionService->approveBorrowRequest($transactionId);
                    } elseif ($transaction['status'] === 'pending_return_approval') {
                        $result = $transactionService->approveReturnRequest($transactionId);
                    } else {
                        $error = 'Invalid transaction status for approval.';
                    }
                    if (isset($result) && $result) {
                        $session->setFlash('success', 'Transaction approved successfully.');
                        header('Location: ' . APP_URL . '/public/transactions/index.php');
                        exit;
                    } else {
                        $error = $transactionService->getErrorMessage() ?: 'Failed to approve transaction.';
                    }
                }
            }
        }
        if (isset($_POST['reject'])) {
            // Handle reject request
            $transactionId = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;
            if (!$transactionId) {
                $error = 'Invalid transaction ID.';
            } else {
                $updateData = [
                    'status' => 'rejected',
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                if ($transactionService->updateTransaction($transactionId, $updateData)) {
                    $session->setFlash('success', 'Transaction request rejected.');
                    header('Location: ' . APP_URL . '/public/transactions/index.php');
                    exit;
                } else {
                    $error = $transactionService->getErrorMessage() ?: 'Failed to reject transaction.';
                }
            }
        }
    }
}

// Get active loans for return form
$activeLoans = $transactionService->getActiveLoans();

// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
