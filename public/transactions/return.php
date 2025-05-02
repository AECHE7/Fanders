<?php
/**
 * Return book page for the Library Management System
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

// Check if user has permission to return books (Super Admin or Admin)
if (!$auth->hasRole([ROLE_SUPER_ADMIN, ROLE_ADMIN])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Check if transaction ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to transactions page with error message
    $session->setFlash('error', 'Transaction ID is required.');
    header('Location: ' . APP_URL . '/public/transactions/index.php');
    exit;
}

$transactionId = (int)$_GET['id'];

// Initialize services
$transactionService = new TransactionService();
$penaltyService = new PenaltyService();

// Get transaction details
$transaction = $transactionService->getTransactionDetails($transactionId);

if (!$transaction) {
    // Transaction not found
    $session->setFlash('error', 'Transaction not found.');
    header('Location: ' . APP_URL . '/public/transactions/index.php');
    exit;
}

// Check if book has already been returned
if ($transaction['return_date'] !== null) {
    $session->setFlash('error', 'This book has already been returned.');
    header('Location: ' . APP_URL . '/public/transactions/index.php');
    exit;
}

// Calculate overdue days and potential penalty
$dueDate = new DateTime($transaction['due_date']);
$today = new DateTime();
$isOverdue = $today > $dueDate;
$overdueInfo = null;

if ($isOverdue) {
    $interval = $today->diff($dueDate);
    $daysOverdue = $interval->days;
    $penaltyAmount = $penaltyService->calculatePenaltyAmount($daysOverdue);
    
    $overdueInfo = [
        'days' => $daysOverdue,
        'amount' => $penaltyAmount
    ];
}

// Process return form submission
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $csrf->validateRequest()) {
    // Return the book
    if ($transactionService->returnBook($transactionId)) {
        // Book returned successfully
        $session->setFlash('success', 'Book returned successfully.');
        
        // Redirect to different pages based on context
        if (isset($_GET['redirect']) && $_GET['redirect'] == 'user') {
            header('Location: ' . APP_URL . '/public/users/view.php?id=' . $transaction['user_id']);
        } else {
            header('Location: ' . APP_URL . '/public/transactions/index.php');
        }
        exit;
    } else {
        // Failed to return book
        $error = $transactionService->getErrorMessage();
    }
}

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Return Book</h1>
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
    
    <!-- Return Book Form -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Transaction Details</h5>
                </div>
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($transaction['book_title']) ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted">By <?= htmlspecialchars($transaction['author']) ?></h6>
                    <p class="card-text">
                        <strong>ISBN:</strong> <?= htmlspecialchars($transaction['isbn']) ?><br>
                        <strong>Borrower:</strong> <?= htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']) ?><br>
                        <strong>Borrow Date:</strong> <?= date('Y-m-d', strtotime($transaction['borrow_date'])) ?><br>
                        <strong>Due Date:</strong> <?= date('Y-m-d', strtotime($transaction['due_date'])) ?><br>
                        <strong>Status:</strong> 
                        <?php if ($transaction['status_label'] == 'Overdue'): ?>
                            <span class="badge bg-danger">Overdue</span>
                        <?php else: ?>
                            <span class="badge bg-primary">Borrowed</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Return Details</h5>
                </div>
                <div class="card-body">
                    <?php if ($isOverdue): ?>
                        <div class="alert alert-warning">
                            <h5 class="alert-heading">Overdue Notice</h5>
                            <p>This book is overdue by <?= $overdueInfo['days'] ?> days.</p>
                            <p class="mb-0">A penalty of ₱<?= number_format($overdueInfo['amount'], 2) ?> will be applied.</p>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $transactionId ?><?= isset($_GET['redirect']) ? '&redirect=' . $_GET['redirect'] : '' ?>" method="post">
                        <?= $csrf->getTokenField() ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Return Date</label>
                            <p class="form-control-static"><?= date('Y-m-d') ?></p>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="check"></i> Confirm Return
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
