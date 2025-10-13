<?php
/**
 * View book details page for the Library Management System
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

// Check if book ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to books page with error message
    $session->setFlash('error', 'Book ID is required.');
    header('Location: ' . APP_URL . '/public/books/index.php');
    exit;
}

$bookId = (int)$_GET['id'];

// Initialize book service
$bookService = new BookService();

// Get book data
$book = $bookService->getBookWithCategory($bookId);

if (!$book) {
    // Book not found
    $session->setFlash('error', 'Book not found.');
    header('Location: ' . APP_URL . '/public/books/index.php');
    exit;
}

// Get transaction history for this book
$transactionService = new TransactionService();

// Remove any other filtering or restrictions here
// Fetch all transactions for super-admin and admin, else fetch user-specific transactions
if ($userRole === 'super-admin' || $userRole === 'admin') {
    $transactions = $transactionService->getBookTransactionHistory($bookId);
} else {
    $transactions = $transactionService->getBookTransactionHistoryByUser($bookId, $user['id']);
}

// Handle delete request
if (isset($_POST['delete']) && $auth->hasRole(['super-admin']) && $csrf->validateRequest()) {
    if ($bookService->deleteBook($bookId, $userRole)) {
        // Book deleted successfully
        $session->setFlash('success', 'Book deleted successfully.');
        header('Location: ' . APP_URL . '/public/books/index.php');

    } else {
        // Failed to delete book
        $session->setFlash('error', $bookService->getErrorMessage());
    }
}

// Handle archive request
if (isset($_POST['archive']) && $auth->hasRole(['super-admin', 'admin']) && $csrf->validateRequest()) {
    if ($bookService->archiveBook($bookId)) {
        // Book archived successfully
        $session->setFlash('success', 'Book archived successfully.');
        header('Location: ' . APP_URL . '/public/books/index.php');
        exit;
    } else {
        // Failed to archive book
        $session->setFlash('error', $bookService->getErrorMessage());
    }
}
// Handle return book request

if (isset($_POST['return_book']) && $csrf->validateRequest()) {
    $transactionId = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;
    if ($transactionId > 0) {
        // Instead of directly returning the book, set status to 'returning' for admin approval
        $updateData = [
            'status' => 'returning',
            'updated_at' => date('Y-m-d H:i:s'),
            'return_date' => null
        ];
        if ($transactionService->updateTransaction($transactionId, $updateData)) {
            $session->setFlash('success', 'Return record submitted and pending admin action.');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $session->setFlash('error', $transactionService->getLastError());
        }
    } else {
        $session->setFlash('error', 'Invalid transaction ID.');
    }
}


// Handle borrow book request

if (isset($_POST['borrow_book']) && $csrf->validateRequest()) {
    if ($auth->hasRole(['borrower'])) {
        if ($transactionService->borrowBook($user['id'], $bookId)) {
            $session->setFlash('success', 'Book borrowed successfully.');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } else {
            $session->setFlash('error', $transactionService->getLastError());
        }
    } else {
        $session->setFlash('error', 'You do not have permission to borrow books.');
    }
}


// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Book Details</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <?php if ($userRole == 'super-admin' || $userRole == 'admin'): ?>
                    <a href="<?= APP_URL ?>/public/books/edit.php?id=<?= $bookId ?>" class="btn btn-sm btn-outline-primary">
                        <i data-feather="edit"></i> Edit Book
                    </a>
                <?php endif; ?>
                
                <?php if ($userRole == 'borrower' && $book['status'] && $book['available_copies'] > 0): ?>
                    <a href="<?= APP_URL ?>/public/transactions/borrow.php?book_id=<?= $bookId ?>" class="btn btn-sm btn-outline-success">
                        <i data-feather="book"></i> Borrow Book
                    </a>
                <?php endif; ?>
                
                <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="arrow-left"></i> Back to Books
                </a>
            </div>
        </div>
    </div>
    
    <?php if ($session->hasFlash('success')): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($session->getFlash('success') ?? '') ?>
        </div>
    <?php endif; ?>
    
    <?php if ($session->hasFlash('error')): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($session->getFlash('error') ?? '') ?>
        </div>
    <?php endif; ?>
    
    <!-- Book Details -->
    <?php include_once BASE_PATH . '/templates/books/view.php'; ?>

            <!-- Transaction History -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Transaction History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($transactions)): ?>
                        <div class="alert alert-info">
                            No transactions found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Book Title</th>
                                        <th>Borrower</th>
                                        <th>Loan Date</th>
                                        <th>Due Date</th>
                                        <th>Return Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($transaction['id'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($book['title'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($transaction['name'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($transaction['borrow_date'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($transaction['due_date'] ?? '') ?></td>
                                            <td>
                                                <?= !empty($transaction['return_date']) ? htmlspecialchars($transaction['return_date']) : 'Not returned' ?>
                                            </td>
                                            <td>
                                                <?php if (($transaction['status_label'] ?? '') == 'overdue'): ?>
                                                    <span class="badge bg-danger">Overdue</span>
                                                <?php elseif (($transaction['status_label'] ?? '') == 'borrowed'): ?>
                                                    <span class="badge bg-success">Borrowed</span>
                                                <?php elseif (($transaction['status_label'] ?? '') == 'returned'): ?>
                                                    <span class="badge bg-secondary">Returned</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($transaction['status_label'] ?? '') ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= APP_URL ?>/public/transactions/view.php?id=<?= htmlspecialchars($transaction['id'] ?? '') ?>" class="btn btn-outline-primary">
                                                        <i data-feather="eye"></i>
                                                    </a>
                                                    <?php if (($transaction['status_label'] ?? '') == 'borrowed' || ($transaction['status_label'] ?? '') == 'overdue'): ?>
                                                    <form action="<?= $_SERVER['PHP_SELF'] . '?id=' . $bookId ?>" method="post" class="d-inline ms-1">
                                                        <input type="hidden" name="transaction_id" value="<?= htmlspecialchars($transaction['id'] ?? '') ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->getToken() ?? '') ?>">
                                                        <button type="submit" name="return_book" class="btn btn-outline-success" 
                                                                onclick="return confirm('Are you sure you want to return this book?')">
                                                            <i data-feather="rotate-ccw"></i>
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

    <!-- Delete functiony -->
    <?php if ($userRole == 'super-admin'): ?>
        <!-- Danger Zone -->
        <div class="card mt-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Danger Zone</h5>
            </div>
            <div class="card-body">
                <p class="card-text">Deleting or archiving this book is irreversible. Please be certain.</p>
                <form action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $bookId ?>" method="post" onsubmit="return confirm('Are you sure you want to delete this book? This action cannot be undone.');" class="d-inline me-2">
                    <?= $csrf->getTokenField() ?>
                    <button type="submit" name="delete" class="btn btn-danger">
                        <i data-feather="trash-2"></i> Delete Book
                    </button>
                </form>
                <form action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $bookId ?>" method="post" onsubmit="return confirm('Are you sure you want to archive this book?');" class="d-inline">
                    <?= $csrf->getTokenField() ?>
                    <button type="submit" name="archive" class="btn btn-warning text-white">
                        <i data-feather="archive"></i> Archive Book
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
