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
$loanService = new LoanService();
$clientService = new ClientService();
$paymentService = new PaymentService();
// $penaltyService = new PenaltyService();
$userService = new UserService();
// $reportService = new ReportService();

    // Get role-specific dashboard data with enhanced filtering
    if (in_array($userRole, [UserModel::$ROLE_CLIENT, UserModel::$ROLE_MANAGER, UserModel::$ROLE_ACCOUNT_OFFICER, UserModel::$ROLE_CASHIER, UserModel::$ROLE_STUDENT, UserModel::$ROLE_STAFF, UserModel::$ROLE_OTHER])) {
        // Client/Borrower Dashboard
        $clientId = $user['id']; // Assuming user ID maps to client ID, or need to get client by user ID
        $stats = [];

        // Get client's loans with enhanced filtering
        $clientLoans = $loanService->getLoansByClient($clientId);
        $clientLoans = is_array($clientLoans) ? $clientLoans : [];
        
        $activeLoans = array_filter($clientLoans, function($loan) {
            return $loan['status'] === 'active';
        });
        $loanHistory = $clientLoans;

        // Get overdue payments using enhanced method
        $overduePayments = $paymentService->getOverduePayments(['client_id' => $clientId]);

        $stats['active_loans'] = $activeLoans;
        $stats['loan_history'] = $loanHistory;
        $stats['overdue_count'] = is_array($overduePayments) ? count($overduePayments) : 0;

        // Get total borrowed (loan amounts)
        $stats['total_borrowed'] = is_array($clientLoans) && !empty($clientLoans) ? 
            array_sum(array_column($clientLoans, 'loan_amount')) : 0;
        $stats['current_borrowed'] = is_array($activeLoans) && !empty($activeLoans) ? 
            array_sum(array_column($activeLoans, 'loan_amount')) : 0;

        $dashboardTemplate = BASE_PATH . '/templates/dashboard/client.php';
        } elseif (in_array($userRole, [UserModel::$ROLE_ADMIN, UserModel::$ROLE_SUPER_ADMIN, UserModel::$ROLE_MANAGER, UserModel::$ROLE_ACCOUNT_OFFICER, UserModel::$ROLE_CASHIER])) {
        // Admin/Staff Dashboard with enhanced data fetching
        $stats = [];

        // Get loan statistics
        $loanStats = $loanService->getLoanStats();
        if (is_array($loanStats)) {
            $stats = array_merge($stats, $loanStats);
        }

        // Get active loans count using enhanced method
        $activeLoansFilter = ['status' => 'active', 'limit' => 1]; // Just get count efficiently
        $stats['active_loans'] = $loanService->getTotalLoansCount($activeLoansFilter);

        // Get overdue payments using enhanced method
        $overduePayments = $paymentService->getOverduePayments();
        $stats['overdue_returns'] = is_array($overduePayments) ? count($overduePayments) : 0;

        // Get recent payments using enhanced method
        $recentPayments = $paymentService->getRecentPayments(5);
        $stats['recent_transactions'] = is_array($recentPayments) ? $recentPayments : [];

        // Get analytics
        // $analytics = $reportService->getMonthlyActivitySummary();

        // Fetch total penalties amount from all penalty records
        // $totalPenaltiesDue = $penaltyService->getTotalPenalties();
        // $stats['total_penalties'] = $totalPenaltiesDue;

        // Map total_principal_disbursed to total_disbursed for template compatibility
        $stats['total_disbursed'] = $stats['total_principal_disbursed'] ?? 0;

        if ($userRole === UserModel::$ROLE_SUPER_ADMIN) {
            // Additional super admin data
            $users = $userService->getAllUsersWithRoleNames();
            $users = is_array($users) ? $users : [];
            $clientsFilter = ['status' => 'active', 'limit' => 1]; // Just get count efficiently
            $stats['total_clients'] = $clientService->getTotalClientsCount($clientsFilter);
        } elseif (in_array($userRole, [UserModel::$ROLE_ADMIN, UserModel::$ROLE_MANAGER])) {
            // Additional admin/branch manager data
            $clientsFilter = ['status' => 'active', 'limit' => 1];
            $stats['total_clients'] = $clientService->getTotalClientsCount($clientsFilter);
        } else {
            // Account officer/cashier data
            $clientsFilter = ['status' => 'active', 'limit' => 1];
            $stats['total_clients'] = $clientService->getTotalClientsCount($clientsFilter);
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

<main class="main-content">
    <div class="content-wrapper">
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
    </div>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
