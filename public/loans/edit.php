<?php
/**
 * Edit loan page for the Fanders Microfinance System
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

// Check if user has permission to edit loans (Super Admin, Admin, Manager, Account Officer)
if (!$auth->hasRole(['super-admin', 'admin', 'manager', 'account_officer'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Check if loan ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to loans page with error message
    $session->setFlash('error', 'Loan ID is required.');
    header('Location: ' . APP_URL . '/public/loans/index.php');
    exit;
}

$loanId = (int)$_GET['id'];

// Get current user data
$user = $auth->getCurrentUser();

// Initialize services
$loanService = new LoanService();
$clientService = new ClientService();

// Get loan data with client information
$loan = $loanService->getLoanWithClient($loanId);

if (!$loan) {
    // Loan not found
    $session->setFlash('error', 'Loan not found.');
    header('Location: ' . APP_URL . '/public/loans/index.php');
    exit;
}

// Check if loan can be edited (only applications and approved loans)
if (!in_array($loan['status'], ['application', 'approved'])) {
    $session->setFlash('error', 'Only loan applications and approved loans can be edited.');
    header('Location: ' . APP_URL . '/public/loans/view.php?id=' . $loanId);
    exit;
}

// Get all clients for the dropdown
$clients = $clientService->getAllForSelect();

// Process form submission
$loanData = [
    'client_id' => $loan['client_id'],
    'loan_amount' => $loan['loan_amount']
];

$loanCalculation = null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrf->validateRequest()) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Get form data
        $loanData = [
            'client_id' => isset($_POST['client_id']) ? (int)$_POST['client_id'] : $loan['client_id'],
            'loan_amount' => isset($_POST['loan_amount']) ? (float)$_POST['loan_amount'] : $loan['loan_amount']
        ];

        // Calculate loan details for preview
        if (!empty($loanData['loan_amount'])) {
            $loanCalculationService = new LoanCalculationService();
            $loanCalculation = $loanCalculationService->calculateLoan($loanData['loan_amount']);
        }

        // If submit button was clicked
        if (isset($_POST['submit_loan'])) {
            // Update the loan
            if ($loanService->updateLoan($loanId, $loanData)) {
                // Loan updated successfully
                $session->setFlash('success', 'Loan updated successfully.');
                header('Location: ' . APP_URL . '/public/loans/view.php?id=' . $loanId);
                exit;
            } else {
                // Failed to update loan
                $error = $loanService->getErrorMessage();
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
        <h1 class="h2">Edit Loan Application</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $loanId ?>" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="eye"></i> View Loan
                </a>
                <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="arrow-left"></i> Back to Loans
                </a>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- Loan Edit Form -->
    <div class="card">
        <div class="card-body">
            <?php include_once BASE_PATH . '/templates/loans/form.php'; ?>
        </div>
    </div>

    <!-- Loan Calculation Preview -->
    <?php if ($loanCalculation): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Updated Loan Calculation Preview</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Loan Summary</h6>
                    <table class="table table-sm">
                        <tr>
                            <td>Principal Amount:</td>
                            <td class="text-end">₱<?= number_format($loanCalculation['principal_amount'], 2) ?></td>
                        </tr>
                        <tr>
                            <td>Total Interest (5%):</td>
                            <td class="text-end">₱<?= number_format($loanCalculation['total_interest'], 2) ?></td>
                        </tr>
                        <tr>
                            <td>Insurance Fee:</td>
                            <td class="text-end">₱<?= number_format($loanCalculation['insurance_fee'], 2) ?></td>
                        </tr>
                        <tr class="table-primary">
                            <td><strong>Total Amount:</strong></td>
                            <td class="text-end"><strong>₱<?= number_format($loanCalculation['total_amount'], 2) ?></strong></td>
                        </tr>
                        <tr>
                            <td>Weekly Payment:</td>
                            <td class="text-end">₱<?= number_format($loanCalculation['weekly_payment'], 2) ?></td>
                        </tr>
                        <tr>
                            <td>Term:</td>
                            <td class="text-end">17 weeks (4 months)</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Payment Breakdown</h6>
                    <table class="table table-sm">
                        <tr>
                            <td>Principal per week:</td>
                            <td class="text-end">₱<?= number_format($loanCalculation['breakdown']['principal_per_week'], 2) ?></td>
                        </tr>
                        <tr>
                            <td>Interest per week:</td>
                            <td class="text-end">₱<?= number_format($loanCalculation['breakdown']['interest_per_week'], 2) ?></td>
                        </tr>
                        <tr>
                            <td>Insurance per week:</td>
                            <td class="text-end">₱<?= number_format($loanCalculation['breakdown']['insurance_per_week'], 2) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
