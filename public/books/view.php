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
$transactions = $transactionService->getBookTransactionHistory($bookId);

// Handle delete request
if (isset($_POST['delete']) && $auth->hasRole([ROLE_SUPER_ADMIN, ROLE_ADMIN])) {
    if ($bookService->deleteBook($bookId)) {
        // Book deleted successfully
        $session->setFlash('success', 'Book deleted successfully.');
        header('Location: ' . APP_URL . '/public/books/index.php');
        exit;
    } else {
        // Failed to delete book
        $session->setFlash('error', $bookService->getErrorMessage());
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
                <?php if ($userRole == ROLE_SUPER_ADMIN || $userRole == ROLE_ADMIN): ?>
                    <a href="<?= APP_URL ?>/public/books/edit.php?id=<?= $bookId ?>" class="btn btn-sm btn-outline-primary">
                        <i data-feather="edit"></i> Edit Book
                    </a>
                <?php endif; ?>
                
                <?php if ($userRole == ROLE_BORROWER && $book['is_available'] && $book['available_copies'] > 0): ?>
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
            <?= $session->getFlash('success') ?>
        </div>
    <?php endif; ?>
    
    <?php if ($session->hasFlash('error')): ?>
        <div class="alert alert-danger">
            <?= $session->getFlash('error') ?>
        </div>
    <?php endif; ?>
    
    <!-- Book Details -->
    <?php include_once BASE_PATH . '/templates/books/view.php'; ?>
    
    <?php if ($userRole == ROLE_SUPER_ADMIN || $userRole == ROLE_ADMIN): ?>
        <!-- Delete Book Form -->
        <div class="card mt-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Danger Zone</h5>
            </div>
            <div class="card-body">
                <p class="card-text">Deleting this book is irreversible. Please be certain.</p>
                <form action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $bookId ?>" method="post" onsubmit="return confirm('Are you sure you want to delete this book? This action cannot be undone.');">
                    <button type="submit" name="delete" class="btn btn-danger">
                        <i data-feather="trash-2"></i> Delete Book
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
