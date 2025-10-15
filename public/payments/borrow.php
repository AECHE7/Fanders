<?php
/**
 * Borrow Book Endpoint
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
    $session->setFlash('error', 'Please login to access this page.');
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

// Check for session timeout
if ($auth->checkSessionTimeout()) {
    $session->setFlash('error', 'Your session has expired. Please login again.');
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

// Check if user has admin or super-admin role
if (!$auth->hasRole(['super-admin', 'admin'])) {
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

//Get book id


// Initialize services
$transactionService = new TransactionService();
$bookService = new BookService();
$userService = new UserService();

// Initialize CSRF protection
$csrf = new CSRF();

// Get available books and active borrowers
$borrowers = $userService->getActiveBorrowers();

// Process search if submitted
$searchTerm = '';
$categoryId = '';
$books = [];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
    $books = $bookService->searchBooks($searchTerm);
} elseif (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
    $categoryId = (int)$_GET['category_id'];
    $books = $bookService->getBooksByCategory($categoryId);
} else {
    // Get all books
    $books = $bookService->getAllBooksWithCategories();
}

// Process form submission
$error = '';
$formSubmitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formSubmitted = true;
    // Validate CSRF token
    if (!$csrf->validateRequest()) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Get and validate input
        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $bookId = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
        $durationDays = isset($_POST['duration_days']) ? (int)$_POST['duration_days'] : 14;

        if (!$userId || !$bookId) {
            $error = 'Invalid input. Please try again.';
        } else {
            // Process borrowing as pending approval
            $borrowDate = date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime("+{$durationDays} days"));
            $data = [
                'user_id' => $userId,
                'book_id' => $bookId,
                'borrow_date' => $borrowDate,
                'due_date' => $dueDate,
                'return_date' => null,
                'status' => 'pending_approval',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $transactionId = $transactionService->createTransactionRequest($data);

            if ($transactionId) {
                $session->setFlash('success', 'Borrow request submitted and pending admin approval.');
                header('Location: ' . APP_URL . '/public/transactions/view.php?id=' . $transactionId);
                exit;
            } else {
                $error = $transactionService->getErrorMessage();
            }
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
        <h1 class="h2">Borrow Books</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/users/index.php" class="btn btn-sm btn-outline-secondary">
                <i data-feather="arrow-left"></i> Back to Users
            </a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <?php if ($formSubmitted && !$error): ?>
        <div class="alert alert-success">
           Transaction added successfully.
        </div>
    <?php endif; ?>
    
    <!-- User Form -->
    <div class="card">
        <div class="card-body">
            <?php
            $formPath = BASE_PATH . '/templates/transactions/borrow.php';
            if (file_exists($formPath)) {
                include_once $formPath;
            } else {
                echo '<div class="alert alert-danger">User form template is missing: ' . htmlspecialchars($formPath) . '</div>';
            }
            ?>
        </div>
    </div>
</main>

<?php

// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

