<?php
/**
 * Dashboard page for the Library Management System
 */

// Include configuration
require_once '../app/config/config.php';

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
    $session->setFlash('error', 'Please login to access the dashboard.');
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

// Initialize services based on user role
$userService = new UserService();
$bookService = new BookService();

// Get role-specific dashboard data
if ($userRole == ROLE_SUPER_ADMIN) {
    // Super Admin Dashboard
    $stats = $userService->getSystemStats();
    $users = $userService->getAllUsersWithRoleNames();
    $recentlyAddedBooks = $bookService->getRecentlyAddedBooks(5);
    $mostBorrowedBooks = $bookService->getMostBorrowedBooks(5);
    
    // Include Super Admin dashboard template
    $dashboardTemplate = BASE_PATH . '/templates/dashboard/admin.php';
} elseif ($userRole == ROLE_ADMIN) {
    // Admin Dashboard
    $stats = $userService->getAdminStats();
    $borrowers = $userService->getAllBorrowers();
    $recentlyAddedBooks = $bookService->getRecentlyAddedBooks(5);
    
    // Initialize transaction service
    $transactionService = new TransactionService();
    $activeLoans = $transactionService->getActiveLoans();
    $overdueLoans = $transactionService->getOverdueLoans();
    
    // Include Admin dashboard template
    $dashboardTemplate = BASE_PATH . '/templates/dashboard/admin.php';
} elseif ($userRole == ROLE_BORROWER) {
    // Borrower Dashboard
    $borrowerModel = new BorrowerModel();
    $stats = $borrowerModel->getBorrowerStats($user['id']);
    
    // Initialize transaction service
    $transactionService = new TransactionService();
    $activeLoans = $borrowerModel->getActiveLoans($user['id']);
    $loanHistory = $borrowerModel->getLoanHistory($user['id']);
    $overdueBooks = $borrowerModel->getOverdueBooks($user['id']);
    
    // Initialize penalty service
    $penaltyService = new PenaltyService();
    $penalties = $penaltyService->getUserPenalties($user['id']);
    
    // Include Borrower dashboard template
    $dashboardTemplate = BASE_PATH . '/templates/dashboard/borrower.php';
} else {
    // Unknown role
    $session->setFlash('error', 'Invalid user role.');
    header('Location: ' . APP_URL . '/public/logout.php');
    exit;
}

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <?php if ($userRole == ROLE_SUPER_ADMIN || $userRole == ROLE_ADMIN): ?>
                    <a href="<?= APP_URL ?>/public/reports/books.php" class="btn btn-sm btn-outline-secondary">Books Report</a>
                    <a href="<?= APP_URL ?>/public/reports/users.php" class="btn btn-sm btn-outline-secondary">Users Report</a>
                    <a href="<?= APP_URL ?>/public/reports/transactions.php" class="btn btn-sm btn-outline-secondary">Transactions Report</a>
                <?php endif; ?>
                
                <?php if ($userRole == ROLE_BORROWER): ?>
                    <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-sm btn-outline-secondary">Available Books</a>
                    <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-sm btn-outline-secondary">My Loans</a>
                <?php endif; ?>
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
    
    <?php
    // Include role-specific dashboard content
    if (file_exists($dashboardTemplate)) {
        include $dashboardTemplate;
    } else {
        echo '<div class="alert alert-danger">Dashboard template not found.</div>';
    }
    ?>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
