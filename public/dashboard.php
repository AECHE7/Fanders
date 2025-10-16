zz<?php
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
$loanService = new LoanService();
$clientService = new ClientService();
$paymentService = new PaymentService();

    // Get role-specific dashboard data
    if (in_array($userRole, [UserModel::$ROLE_CLIENT, UserModel::$ROLE_MANAGER, UserModel::$ROLE_ACCOUNT_OFFICER, UserModel::$ROLE_CASHIER, UserModel::$ROLE_STUDENT, UserModel::$ROLE_STAFF, UserModel::$ROLE_OTHER])) {
        // Client/Borrower Dashboard
        $clientId = $user['id']; // Assuming user ID maps to client ID, or need to get client by user ID
        $stats = [];

        // Get client's loans
        $clientLoans = $loanService->getLoansByClient($clientId);
        $activeLoans = array_filter($clientLoans, function($loan) {
            return $loan['status'] === 'active';
        });
        $loanHistory = $clientLoans;

        // Get overdue payments (simplified - loans with missed payments)
        $overduePayments = [];
        foreach ($activeLoans as $loan) {
            $nextWeek = $paymentService->getNextPaymentWeek($loan['id']);
            if ($nextWeek && $nextWeek < date('W')) {
                $overduePayments[] = $loan;
            }
        }

        // Get penalties (assuming penalty service exists for loans)
        $penalties = $penaltyService->getUserPenalties($clientId) ?? [];
        $totalPenaltiesDue = 0;
        if (is_array($penalties)) {
            foreach ($penalties as $penalty) {
                if (isset($penalty['penalty_amount'])) {
                    $totalPenaltiesDue += floatval($penalty['penalty_amount']);
                }
            }
        }
        $stats['total_penalties'] = $totalPenaltiesDue;
        $stats['active_loans'] = $activeLoans;
        $stats['loan_history'] = $loanHistory;
        $stats['overdue_count'] = count($overduePayments);

        // Get total borrowed (loan amounts)
        $stats['total_borrowed'] = array_sum(array_column($clientLoans, 'loan_amount'));
        $stats['current_borrowed'] = array_sum(array_column($activeLoans, 'loan_amount'));

        $dashboardTemplate = BASE_PATH . '/templates/dashboard/client.php';
        } elseif (in_array($userRole, [UserModel::$ROLE_ADMIN, UserModel::$ROLE_SUPER_ADMIN, UserModel::$ROLE_MANAGER, UserModel::$ROLE_ACCOUNT_OFFICER, UserModel::$ROLE_CASHIER])) {
        // Admin/Staff Dashboard
        $stats = [];

        // Get loan statistics
        $loanStats = $loanService->getLoanStats();
        $stats = array_merge($stats, $loanStats);

        // Get active loans
        $activeLoans = $loanService->getAllActiveLoansWithClients();

        // Get overdue payments
        $overduePayments = $paymentService->getOverduePayments();
        $stats['overdue_returns'] = count($overduePayments);

        // Get recent payments
        $recentPayments = $paymentService->getRecentPayments(5);
        $stats['recent_transactions'] = $recentPayments;

        // Get analytics
        $analytics = $reportService->getMonthlyActivitySummary();

        // Fetch total penalties amount from all penalty records
        $totalPenaltiesDue = $penaltyService->getTotalPenalties();
        $stats['total_penalties'] = $totalPenaltiesDue;

        if ($userRole === UserModel::$ROLE_SUPER_ADMIN) {
            // Additional super admin data
            $users = $userService->getAllUsersWithRoleNames();
            $clients = $clientService->getAllClients();
            $stats['total_clients'] = count($clients);
        } elseif (in_array($userRole, [UserModel::$ROLE_ADMIN, UserModel::$ROLE_MANAGER])) {
            // Additional admin/branch manager data
            $clients = $clientService->getAllClients();
            $stats['total_clients'] = count($clients);
        } else {
            // Account officer/cashier data
            $clients = $clientService->getAllClients();
            $stats['total_clients'] = count($clients);
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
