<?php
/**
 * Dashboard page for the Library Management System
 * Notion-inspired design
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
    $session->setFlash('error', 'Please login to access the dashboard.');
    header('Location: ' . APP_URL . '/public/auth/login.php');
    exit;
}

// Check for session timeout
if ($auth->checkSessionTimeout()) {
    // Session has timed out, redirect to login page with message
    $session->setFlash('error', 'Your session has expired. Please login again.');
    header('Location: ' . APP_URL . '/public/auth/login.php');
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

    // Get role-specific dashboard data for admin and staff roles only
    if (in_array($userRole, [UserModel::$ROLE_ADMIN, UserModel::$ROLE_SUPER_ADMIN])) {
        // Super Admin & Admin Dashboard
        // Admin/Staff Dashboard with enhanced data fetching
        $stats = [];

        // Get loan statistics
        $loanStats = $loanService->getLoanStats();
        if (is_array($loanStats)) {
            $stats = array_merge($stats, $loanStats);
        }

        // Get active loans count using enhanced method
        $activeLoansFilter = ['status' => 'Active'];
        $stats['active_loans'] = $loanService->getTotalLoansCount($activeLoansFilter);

        // Get overdue payments using enhanced method
        $overduePayments = $paymentService->getOverduePayments();
        $stats['overdue_returns'] = is_array($overduePayments) ? count($overduePayments) : 0;

        // Get recent payments using enhanced method
        $recentPayments = $paymentService->getRecentPayments(5);
        $stats['recent_transactions'] = is_array($recentPayments) ? $recentPayments : [];

        // Get active loans list with details
        $activeLoansFullFilter = ['status' => 'Active', 'limit' => 5];
        $activeLoans = $loanService->getAllLoansWithClients($activeLoansFullFilter);
        $stats['active_loans_list'] = is_array($activeLoans) ? $activeLoans : [];

        // Get recent loans
        $recentLoans = $loanService->getRecentLoans(5);
        $stats['recent_loans'] = is_array($recentLoans) ? $recentLoans : [];

        // Get analytics placeholder
        $analytics = [
            'borrower_growth_text' => 'Active borrowers compared to last month',
            'monthly' => []
        ];

        // Map total_principal_disbursed to total_disbursed for template compatibility
        $stats['total_disbursed'] = $stats['total_principal_disbursed'] ?? 0;

        // Get total clients count
        $clientsFilter = [];
        $stats['total_clients'] = $clientService->getTotalClientsCount($clientsFilter);

        // Get recent clients for Recent Clients section
        $recentClientsData = $clientService->getAllClients();
        $stats['recent_clients'] = is_array($recentClientsData) ? array_slice($recentClientsData, 0, 5) : [];

        // Get total staff count for Client & Approvals Overview
        $userStats = $userService->getUserStats();
        if (is_array($userStats) && isset($userStats['role_counts'])) {
            $stats['total_staff'] = ($userStats['role_counts'][UserModel::$ROLE_ACCOUNT_OFFICER] ?? 0) +
                                   ($userStats['role_counts'][UserModel::$ROLE_CASHIER] ?? 0) +
                                   ($userStats['role_counts'][UserModel::$ROLE_MANAGER] ?? 0);
        } else {
            $stats['total_staff'] = 0;
        }

        if ($userRole === UserModel::$ROLE_SUPER_ADMIN) {
            // Additional super admin data - User role counts
            $userStats = $userService->getUserStats();
            if (is_array($userStats) && isset($userStats['role_counts'])) {
                $stats['total_students'] = ($userStats['role_counts'][UserModel::$ROLE_STUDENT] ?? 0);
                $stats['total_staff'] = ($userStats['role_counts'][UserModel::$ROLE_ACCOUNT_OFFICER] ?? 0) +
                                       ($userStats['role_counts'][UserModel::$ROLE_CASHIER] ?? 0) +
                                       ($userStats['role_counts'][UserModel::$ROLE_MANAGER] ?? 0);
                $stats['total_admins'] = ($userStats['role_counts'][UserModel::$ROLE_ADMIN] ?? 0) +
                                        ($userStats['role_counts'][UserModel::$ROLE_SUPER_ADMIN] ?? 0);
                $stats['total_others'] = ($userStats['role_counts'][UserModel::$ROLE_OTHER] ?? 0);
            }
        } else {
            // For non-super-admin, show total borrowers
            $stats['total_borrowers'] = $stats['total_clients'];
        }

        $dashboardTemplate = BASE_PATH . '/templates/dashboard/admin.php';
    } elseif (in_array($userRole, [UserModel::$ROLE_MANAGER, UserModel::$ROLE_ACCOUNT_OFFICER, UserModel::$ROLE_CASHIER])) {
        // Staff Dashboard (Manager, Account Officer, Cashier)
        $stats = [];

        // Get loan statistics
        $loanStats = $loanService->getLoanStats();
        if (is_array($loanStats)) {
            $stats = array_merge($stats, $loanStats);
        }

        // Get active loans count using enhanced method
        $activeLoansFilter = ['status' => 'Active'];
        $stats['active_loans'] = $loanService->getTotalLoansCount($activeLoansFilter);

        // Get overdue payments using enhanced method
        $overduePayments = $paymentService->getOverduePayments();
        $stats['overdue_payments'] = is_array($overduePayments) ? count($overduePayments) : 0;

        // Get recent payments using enhanced method
        $recentPayments = $paymentService->getRecentPayments(5);
        $stats['recent_transactions'] = is_array($recentPayments) ? $recentPayments : [];

        // Get active loans list with details
        $activeLoansFullFilter = ['status' => 'Active', 'limit' => 5];
        $activeLoans = $loanService->getAllLoansWithClients($activeLoansFullFilter);
        $stats['active_loans_list'] = is_array($activeLoans) ? $activeLoans : [];

        // Get recent loans
        $recentLoans = $loanService->getRecentLoans(5);
        $stats['recent_loans'] = is_array($recentLoans) ? $recentLoans : [];

        // Get analytics placeholder
        $analytics = [
            'borrower_growth_text' => 'Active borrowers compared to last month',
            'monthly' => []
        ];

        // Map total_principal_disbursed to total_disbursed for template compatibility
        $stats['total_portfolio'] = $stats['total_principal_disbursed'] ?? 0;

        // Get total clients count
        $clientsFilter = [];
        $stats['total_clients'] = $clientService->getTotalClientsCount($clientsFilter);

        // Get recent clients for Recent Clients section
        $recentClientsData = $clientService->getAllClients();
        $stats['recent_clients'] = is_array($recentClientsData) ? array_slice($recentClientsData, 0, 5) : [];

        // Get total staff count (staff can see this for context)
        $userStats = $userService->getUserStats();
        if (is_array($userStats) && isset($userStats['role_counts'])) {
            $stats['total_staff'] = ($userStats['role_counts'][UserModel::$ROLE_ACCOUNT_OFFICER] ?? 0) +
                                   ($userStats['role_counts'][UserModel::$ROLE_CASHIER] ?? 0) +
                                   ($userStats['role_counts'][UserModel::$ROLE_MANAGER] ?? 0);
        } else {
            $stats['total_staff'] = 0;
        }

        $dashboardTemplate = BASE_PATH . '/templates/dashboard/staff.php';
    } else {
        // Unknown or unauthorized role
        $session->setFlash('error', 'Invalid user role. Access denied.');
        header('Location: ' . APP_URL . '/public/auth/logout.php');
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
