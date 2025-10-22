<?php
/**
 * Payments list page for the Fanders Microfinance System
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
$userRole = $user['role'];

// Initialize payment service
$paymentService = new PaymentService();

// Initialize client service
$clientService = new ClientService();

// Determine clientId based on role
if ($userRole == 'borrower' || $userRole == 'client') {
    $clientId = $user['id'];
} elseif ($userRole == 'administrator' || $userRole == 'manager' || $userRole == 'collector') {
    // For staff, clientId is optional, set to null if not provided
    $clientId = isset($_GET['client_id']) && !empty($_GET['client_id']) ? (int)$_GET['client_id'] : null;
} else {
    if (!isset($_GET['client_id']) || empty($_GET['client_id'])) {
        // Redirect to clients page with error message
        $session->setFlash('error', 'Client ID is required.');
        header('Location: ' . APP_URL . '/public/clients/index.php');
        exit;
    }
    $clientId = (int)$_GET['client_id'];
}

if ($clientId !== null) {
    // Get client data
    $viewClient = $clientService->getClientWithDetails($clientId);

    if (!$viewClient) {
        // Client not found
        $session->setFlash('error', 'Client not found.');
        header('Location: ' . APP_URL . '/public/clients/index.php');
        exit;
    }

    // Check if user has permission to view this client
    // Administrator can view any client, Manager can view clients in their area
    if ($userRole == 'manager' && (!is_array($viewClient) || $viewClient['assigned_manager'] != $user['id'])) {
        // Redirect to dashboard with error message
        $session->setFlash('error', 'You do not have permission to view this client.');
        header('Location: ' . APP_URL . '/public/dashboard/index.php');
        exit;
    }

    // Get payment history for client
    $payments = $paymentService->getPaymentsByClient($clientId);

    // Get client stats
    $clientStats = $clientService->getClientPaymentStats($clientId);
} else {
    $viewClient = null;
    $payments = [];
}

$startDateFilter = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDateFilter = isset($_GET['end_date']) ? $_GET['end_date'] : null;
$loanIdFilter = isset($_GET['loan_id']) && !empty($_GET['loan_id']) ? (int)$_GET['loan_id'] : null;

// Get payments based on user role and filters
if ($userRole == 'administrator' || $userRole == 'manager') {
    // Staff can see all payments or filter by client/loan
    $payments = $paymentService->getPaymentsForReports($startDateFilter, $endDateFilter, $clientId, $loanIdFilter);
    if ($payments === false) {
        echo "Error fetching payments: " . $paymentService->getLastError();
    }
} elseif ($userRole == 'collector') {
    // Collectors can see payments they collected
    $payments = $paymentService->getPaymentsByCollector($user['id'], $startDateFilter, $endDateFilter);
} else {
    // Clients can only see their own payments
    $payments = $paymentService->getPaymentsByClient($user['id']);
}
// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Payments</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <?php if ($userRole == 'administrator' || $userRole == 'manager'): ?>
                    <a href="<?= APP_URL ?>/public/payments/add.php" class="btn btn-sm btn-outline-success">
                        <i data-feather="plus"></i> Record Payment
                    </a>
                    <a href="<?= APP_URL ?>/public/reports/payments.php" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="file-text"></i> Generate Report
                    </a>
                <?php endif; ?>
                <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-sm btn-outline-primary">
                    <i data-feather="credit-card"></i> View Loans
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

    <!-- Filter Options -->
    <?php if ($userRole == 'administrator' || $userRole == 'manager'): ?>
        <div class="row mb-3">
            <div class="col-md-10">
                <form action="<?= $_SERVER['PHP_SELF'] ?>" method="get" class="d-flex flex-wrap gap-2">
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDateFilter) ?>" placeholder="Start Date">
                    <span class="align-self-center">-</span>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDateFilter) ?>" placeholder="End Date">
                    <input type="number" name="loan_id" class="form-control" value="<?= htmlspecialchars($loanIdFilter) ?>" placeholder="Loan ID">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Reset</a>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Payments List -->
    <?php include_once BASE_PATH . '/templates/payments/list.php'; ?>
    </div>
</main>

<?php
// Handle payment form submissions
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$csrf->validateRequest()) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Handle payment recording
        if (isset($_POST['record_payment'])) {
            $loanId = isset($_POST['loan_id']) ? (int)$_POST['loan_id'] : 0;
            $paymentAmount = isset($_POST['payment_amount']) ? (float)$_POST['payment_amount'] : 0;
            $weekNumber = isset($_POST['week_number']) ? (int)$_POST['week_number'] : 0;
            $collectedBy = isset($_POST['collected_by']) ? (int)$_POST['collected_by'] : null;
            $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';
            $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

            if (!$loanId || !$paymentAmount || !$weekNumber) {
                $error = 'Loan ID, payment amount, and week number are required.';
            } else {
                $paymentId = $paymentService->recordPayment($loanId, $paymentAmount, $weekNumber, $user['id'], $collectedBy, $paymentMethod, $notes);
                if ($paymentId) {
                    $session->setFlash('success', 'Payment recorded successfully.');
                    header('Location: ' . APP_URL . '/public/payments/index.php');
                    exit;
                } else {
                    $error = $paymentService->getErrorMessage();
                }
            }
        }
    }
}

// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
