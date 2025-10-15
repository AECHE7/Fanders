<?php
/**
 * View user details page for the Library Management System
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

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to users page with error message
    $session->setFlash('error', 'User ID is required.');
    header('Location: ' . APP_URL . '/public/users/index.php');
    exit;
}

$userId = (int)$_GET['id'];

// Initialize user service
$userService = new UserService();

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
$activeLoans = [];
$loanHistory = [];
if (in_array($viewUser['role'], ['super-admin','student', 'staff', 'other', 'borrower'])) {
    $bookService = new BookService();
    $borrowedBooks = $bookService->getUserBorrowedBooks($userId);

    if ($viewUser['role'] == 'borrower') {
        $stats = $userService->getBorrowerStats($userId);
        $activeLoans = $stats['active_loans'] ?? [];
        $loanHistory = $stats['loan_history'] ?? [];
    }
}

// Check if user has permission to view this user
// Super Admin can view any user, Admin can only view borrowers or themselves
if ($userRole == 'admin' && !in_array($viewUser['role'], ['student', 'staff', 'other']) && $userId !== $user['id']) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to view this user.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Get transaction history for borrowers
$transactions = [];
if (in_array($viewUser['role'], ['super-admin', 'admin', 'staff', 'staff', 'others'])) {
    $transactionService = new TransactionService();
    $transactions = $transactionService->getUserTransactionHistory($userId);
    
    // Get borrower stats
    $borrowerStats = $userService->getBorrowerStats($userId);
}

// Handle activate/deactivate/delete actions
if (isset($_POST['action']) && $csrf->validateRequest()) {
    if ($_POST['action'] == 'activate' && $viewUser['status'] === UserModel::$STATUS_INACTIVE) {
        if ($userService->activateUser($userId)) {
            $session->setFlash('success', 'User activated successfully.');
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $userId);
            exit;
        } else {
            $session->setFlash('error', 'Failed to activate user.');
        }
    } elseif ($_POST['action'] == 'deactivate' && $viewUser['status'] === UserModel::$STATUS_ACTIVE) {
        if ($userService->deactivateUser($userId)) {
            $session->setFlash('success', 'User deactivated successfully.');
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $userId);
            exit;
        } else {
            $session->setFlash('error', $userService->getErrorMessage());
        }
    } elseif ($_POST['action'] == 'delete') {
        if ($userService->deleteUser($userId)) {
            $session->setFlash('success', 'User deleted successfully.');
            header('Location: ' . APP_URL . '/public/users/index.php');
            exit;
        } else {
            $session->setFlash('error', $userService->getErrorMessage());
        }
    }
}

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">User Details</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                    <a href="<?= APP_URL ?>/public/users/edit.php?id=<?= $userId ?>" class="btn btn-sm btn-outline-primary">
                        <i data-feather="edit"></i> Edit User Profile
                    </a>
                    <?php if ($userRole === 'super-admin' || $userRole === 'admin'): ?>
                        <a href="<?= APP_URL ?>/public/users/index.php" class="btn btn-sm btn-outline-secondary">
                            <i data-feather="arrow-left"></i> Back to Users
                        </a>
                    <?php endif; ?>
                    <a href="<?= APP_URL ?>/public/dashboard.php" class="btn btn-sm btn-outline-secondary">
                            <i data-feather="arrow-left"></i> Back to Dashboard
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
    
    <!-- User Details -->
    <?php include_once BASE_PATH . '/templates/users/view.php'; ?>
    
    <!-- Borrower Transactions -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Transaction History</h5>
        </div>
        <div class="card-body">
            <?php if (empty($transactions)): ?>
                <p class="text-muted">No transaction history found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Book Title</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Return Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?= $transaction['id'] ?></td>
                                    <td><?= htmlspecialchars($transaction['book_title']) ?></td>
                                    <td><?= date('Y-m-d', strtotime($transaction['borrow_date'])) ?></td>
                                    <td><?= date('Y-m-d', strtotime($transaction['due_date'])) ?></td>
                                    <td>
                                        <?= $transaction['return_date'] ? date('Y-m-d', strtotime($transaction['return_date'])) : 'Not returned' ?>
                                    </td>
                                    <td>
                                        <?php if ($transaction['status_label'] == 'Overdue'): ?>
                                            <span class="badge bg-danger">Overdue</span>
                                        <?php elseif ($transaction['status_label'] == 'Borrowed'): ?>
                                            <span class="badge bg-primary">Borrowed</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Returned</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    
    <?php if (($userRole == 'super-admin') || 
             ($userRole == 'admin')): ?>
        <!-- User Management Actions -->
        <div class="card mt-4">
            <div class="card-header bg-<?= $viewUser['status'] ? 'warning' : 'success' ?> text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">User Status Management</h5>
            </div>
            <div class="card-body">
            <?php if ($viewUser['status'] === UserModel::$STATUS_ACTIVE): ?>
                <p class="card-text">This user is currently active. You can deactivate this account.</p>
                <form action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $userId ?>" method="post" onsubmit="return confirm('Are you sure you want to deactivate this user?');">
                    <?= $csrf->getTokenField() ?>
                    <input type="hidden" name="action" value="deactivate">
                    <button type="submit" class="btn btn-warning">
                        <i data-feather="user-x"></i> Deactivate User
                    </button>
                </form>
            <?php else: ?>
                <p class="card-text">This user is currently inactive. You can activate this account.</p>
                <form action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $userId ?>" method="post">
                    <?= $csrf->getTokenField() ?>
                    <input type="hidden" name="action" value="activate">
                    <button type="submit" class="btn btn-success">
                        <i data-feather="user-check"></i> Activate User
                    </button>
                </form>
            <?php endif; ?>
            </div>
        </div>

        <!-- <div class="card mt-4">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Delete Zone</h5>
            </div>
            <div class="card-body">
            <?php if ($userRole === 'super-admin' || $userRole === 'admin'): ?>
                <p class="card-text">Deleting this user is irreversible. Please be certain..</p>
                    <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $userId ?>" onsubmit="return confirm('Are you sure you want to delete this user account? This action cannot be undone.');" style="margin:0;">
                        <?= $csrf->getTokenField() ?>
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i data-feather="trash-2"></i> Delete User
                        </button>
                    </form>
                <?php endif; ?>
            </div> -->
        </div>
    <?php endif; ?>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
