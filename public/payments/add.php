<?php
/**
 * Add transaction page for the Library Management System
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

// Check if user has permission to add transactions (Super Admin or Admin)
if (!$auth->hasRole(['super-admin', 'admin'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Initialize transaction service
$transactionService = new TransactionService();

// Default structure for the form
$newTransaction = [
    'user_id' => '',
    'book_id' => '',
    'borrow_date' => '',
    'due_date' => '',
    'return_date' => null,
    'status' => 'borrowed',
];

$error = '';
$formSubmitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formSubmitted = true;
    
    // Validate CSRF token
    if (!$csrf->validateRequest()) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Gather form input
        $newTransaction = [
            'user_id' => isset($_POST['user_id']) ? (int)$_POST['user_id'] : '',
            'book_id' => isset($_POST['book_id']) ? (int)$_POST['book_id'] : '',
            'borrow_date' => isset($_POST['borrow_date']) ? trim($_POST['borrow_date']) : '',
            'due_date' => isset($_POST['due_date']) ? trim($_POST['due_date']) : '',
            'return_date' => null,
            'status' => 'borrowed',
        ];
        
        // Validate required fields
        if (empty($newTransaction['user_id']) || empty($newTransaction['book_id']) || empty($newTransaction['borrow_date']) || empty($newTransaction['due_date'])) {
            $error = 'Please fill in all required fields.';
        }
        
        if (empty($error)) {
        // Add transaction using TransactionService borrowBook method
        $transactionId = $transactionService->borrowBook($newTransaction['user_id'], $newTransaction['book_id'], (strtotime($newTransaction['due_date']) - strtotime($newTransaction['borrow_date'])) / (60 * 60 * 24));
        
        if ($transactionId) {
            // Transaction added successfully
            $session->setFlash('success', 'Transaction added successfully.');
            header('Location: ' . APP_URL . '/public/transactions/index.php');
            exit;
        } else {
            // Failed to add transaction
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
        <h1 class="h2">Add Transaction</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-sm btn-outline-secondary">
                <i data-feather="arrow-left"></i> Back to Transactions
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
    
    <!-- Transaction Form -->
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
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
