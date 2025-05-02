<?php
/**
 * Borrow book page for the Library Management System
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
$userRole = $user['role_id'];

// Initialize services
$bookService = new BookService();
$userService = new UserService();
$transactionService = new TransactionService();
$borrowerModel = new BorrowerModel();

// Process book borrowing
$error = '';
$bookId = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$durationDays = isset($_GET['duration']) ? (int)$_GET['duration'] : 14;

// For admins - if user_id is not provided, display a form to select a borrower
$borrowers = [];
if (($userRole == ROLE_SUPER_ADMIN || $userRole == ROLE_ADMIN) && empty($userId)) {
    $borrowers = $userService->getAllBorrowers();
}

// If the user is a borrower, use their ID
if ($userRole == ROLE_BORROWER) {
    $userId = $user['id'];
}

// Validate the book ID
if (empty($bookId)) {
    // Redirect to books page with error message
    $session->setFlash('error', 'Book ID is required.');
    header('Location: ' . APP_URL . '/public/books/index.php');
    exit;
}

// Get book data
$book = $bookService->getBookWithCategory($bookId);

if (!$book) {
    // Book not found
    $session->setFlash('error', 'Book not found.');
    header('Location: ' . APP_URL . '/public/books/index.php');
    exit;
}

// Check if book is available
if (!$book['is_available'] || $book['available_copies'] <= 0) {
    $session->setFlash('error', 'This book is not available for borrowing.');
    header('Location: ' . APP_URL . '/public/books/view.php?id=' . $bookId);
    exit;
}

// Process borrow form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $csrf->validateRequest()) {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : $user['id'];
    $durationDays = isset($_POST['duration']) ? (int)$_POST['duration'] : 14;
    
    // Validate duration
    if ($durationDays < 1 || $durationDays > 30) {
        $error = 'Loan duration must be between 1 and 30 days.';
    } else {
        // Borrow the book
        $transactionId = $transactionService->borrowBook($userId, $bookId, $durationDays);
        
        if ($transactionId) {
            // Book borrowed successfully
            $session->setFlash('success', 'Book borrowed successfully.');
            
            if ($userRole == ROLE_BORROWER) {
                header('Location: ' . APP_URL . '/public/transactions/index.php');
            } else {
                header('Location: ' . APP_URL . '/public/books/view.php?id=' . $bookId);
            }
            exit;
        } else {
            // Failed to borrow book
            $error = $transactionService->getErrorMessage();
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
        <h1 class="h2">Borrow Book</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/books/view.php?id=<?= $bookId ?>" class="btn btn-sm btn-outline-secondary">
                <i data-feather="arrow-left"></i> Back to Book Details
            </a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <!-- Borrow Book Form -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Book Information</h5>
                </div>
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted">By <?= htmlspecialchars($book['author']) ?></h6>
                    <p class="card-text">
                        <strong>ISBN:</strong> <?= htmlspecialchars($book['isbn']) ?><br>
                        <strong>Category:</strong> <?= htmlspecialchars($book['category_name']) ?><br>
                        <strong>Available Copies:</strong> <?= $book['available_copies'] ?> of <?= $book['total_copies'] ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Borrow Details</h5>
                </div>
                <div class="card-body">
                    <form action="<?= $_SERVER['PHP_SELF'] ?>?book_id=<?= $bookId ?>" method="post">
                        <?= $csrf->getTokenField() ?>
                        
                        <?php if ($userRole == ROLE_SUPER_ADMIN || $userRole == ROLE_ADMIN): ?>
                            <div class="mb-3">
                                <label for="user_id" class="form-label">Borrower</label>
                                <select name="user_id" id="user_id" class="form-select" required>
                                    <option value="">Select Borrower</option>
                                    <?php foreach ($borrowers as $borrower): ?>
                                        <?php if ($borrower['is_active']): ?>
                                            <option value="<?= $borrower['id'] ?>" <?= $userId == $borrower['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($borrower['first_name'] . ' ' . $borrower['last_name'] . ' (' . $borrower['username'] . ')') ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label">Borrower</label>
                                <p class="form-control-static">
                                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="duration" class="form-label">Loan Duration (Days)</label>
                            <select name="duration" id="duration" class="form-select" required>
                                <option value="7" <?= $durationDays == 7 ? 'selected' : '' ?>>7 days</option>
                                <option value="14" <?= $durationDays == 14 ? 'selected' : '' ?>>14 days</option>
                                <option value="21" <?= $durationDays == 21 ? 'selected' : '' ?>>21 days</option>
                                <option value="30" <?= $durationDays == 30 ? 'selected' : '' ?>>30 days</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Borrow Date</label>
                            <p class="form-control-static"><?= date('Y-m-d') ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Due Date</label>
                            <p class="form-control-static">
                                <?= date('Y-m-d', strtotime('+' . $durationDays . ' days')) ?>
                            </p>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="check"></i> Confirm Borrowing
                            </button>
                        </div>
                    </form>
                </div>
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
