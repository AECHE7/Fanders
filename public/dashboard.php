<?php
/**
 * Dashboard page for the Library Management System
 * Notion-inspired design
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
$userRole = isset($user['role']) ? $user['role'] : null;

// Initialize services
$userService = new UserService();
$bookService = new BookService();
$transactionService = new TransactionService();
$penaltyService = new PenaltyService();
$reportService = new ReportService();

// Initialize models
$userModel = new UserModel();
$transactionModel = new TransactionModel();

    // Get role-specific dashboard data
    if (in_array($userRole, [UserModel::$ROLE_STUDENT, UserModel::$ROLE_STAFF, UserModel::$ROLE_OTHER])) {
        // Borrower Dashboard
        error_log("User ID for borrower stats: " . $user['id']);
        $stats = $userService->getBorrowerStats($user['id']);
        error_log("Active loans from getBorrowerStats: " . print_r($stats['active_loans'], true));
        $activeLoans = $stats['active_loans'] ?? [];
        $loanHistory = $stats['loan_history'] ?? [];
        $overdueBooks = $transactionModel->getUserOverdueLoans($user['id']);
$penalties = $penaltyService->getUserPenalties($user['id']);

// Calculate total penalties due from penalties array
$totalPenaltiesDue = 0;
if (is_array($penalties)) {
    foreach ($penalties as $penalty) {
        if (isset($penalty['penalty_amount'])) {
            $totalPenaltiesDue += floatval($penalty['penalty_amount']);
        }
    }
}
$stats['total_penalties'] = $totalPenaltiesDue;

$availableBooks = $stats['available_books'] ?? [];
$dashboardTemplate = BASE_PATH . '/templates/dashboard/borrower.php';
        } elseif (in_array($userRole, [UserModel::$ROLE_ADMIN, UserModel::$ROLE_SUPER_ADMIN])) {
        // Admin Dashboard
        $stats = $userService->getAdminStats();
        $activeLoans = $transactionModel->getActiveLoans();
        $overdueBooks = $transactionModel->getOverdueLoans();
        $recentlyAddedBooks = $bookService->getRecentlyAddedBooks(5);
        $recentTransactions = $stats['recent_transactions'] ?? [];
        $analytics = $reportService->getMonthlyActivitySummary();

        // Fetch total penalties amount from all penalty records
        $totalPenaltiesDue = $penaltyService->getTotalPenalties();
        $stats['total_penalties'] = $totalPenaltiesDue;
        
        if ($userRole === UserModel::$ROLE_SUPER_ADMIN) {
            // Additional super admin data
            $users = $userService->getAllUsersWithRoleNames();
            $mostBorrowedBooks = $bookService->getMostBorrowedBooks(5);
        } else {
            // Additional admin data
            $borrowers = $userService->getAllBorrowers();
        }
        
        $dashboardTemplate = BASE_PATH . '/templates/dashboard/admin.php';
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
// //Debug print for $stats to investigate missing borrowed books data
// echo '<pre>Stats Debug: ';
// print_r($stats);
// echo '</pre>';// 

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
