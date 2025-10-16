<?php
/**
 * New Loan Application Controller (add.php)
 * Role: Allows authorized staff to submit a new loan application for a client.
 * Integrates: LoanService, LoanCalculationService, ClientService
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control (Staff roles allowed to apply for loans)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account-officer']);

// Initialize services
$loanService = new LoanService();
$loanCalculationService = new LoanCalculationService();
$clientService = new ClientService();

// --- 1. Initial Data Setup ---
$loan = [
    'client_id' => isset($_GET['client_id']) ? (int)$_GET['client_id'] : '', // Pre-select if linked from client page
    'loan_amount' => ''
];
$loanCalculation = null;
$error = $session->getFlash('error'); // Retrieve any previous error
$clients = $clientService->getAllForSelect(); // Fetch active clients for dropdown

// If a client_id is passed, check if they are eligible for a loan
if (!empty($loan['client_id'])) {
    if (!$loanService->canClientApplyForLoan($loan['client_id'])) {
        $session->setFlash('error', $loanService->getErrorMessage() ?: "Client is ineligible for a new loan.");
        header('Location: ' . APP_URL . '/public/clients/view.php?id=' . $loan['client_id']);
        exit;
    }
}

// --- 2. Process Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrf->validateRequest()) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        // Gather and sanitize input
        $loan = [
            'client_id' => isset($_POST['client_id']) ? (int)$_POST['client_id'] : '',
            'loan_amount' => isset($_POST['loan_amount']) ? (float)$_POST['loan_amount'] : ''
        ];

        // --- Calculate Preview (Run regardless of submission type) ---
        if ($loan['loan_amount'] > 0) {
            $loanCalculation = $loanCalculationService->calculateLoan($loan['loan_amount']);
        }
        
        // Check if the "Apply" button was pressed (not just the 'Calculate' preview)
        if (isset($_POST['submit_loan'])) {
            
            // Apply for the loan
            $loanId = $loanService->applyForLoan($loan, $user['id']);

            if ($loanId) {
                // Success: Redirect to the loan list page
                $session->setFlash('success', 'Loan application submitted successfully. Pending Manager approval.');
                header('Location: ' . APP_URL . '/public/loans/index.php');
                exit;
            } else {
                // Failure: Store the specific error message from the service
                $error = $loanService->getErrorMessage() ?: "Failed to submit loan application.";
            }
        }
    }
    
    // Re-set error flash if an error occurred during POST
    if ($error) {
        $session->setFlash('error', $error);
    }
}

// --- 3. Display View ---
$pageTitle = "New Loan Application";
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">New Loan Application (17-Week Term)</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-sm btn-outline-secondary">
                <i data-feather="arrow-left"></i> Back to Loans List
            </a>
        </div>
    </div>
    
    <!-- Flash Messages -->
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

    <!-- Loan Application Form -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php 
            // The loan form template handles the input and the final submission.
            include_once BASE_PATH . '/templates/loans/form.php'; 
            ?>
        </div>
    </div>

    <!-- Loan Calculation Preview (Displayed only if successful calculation exists) -->
    <?php if ($loanCalculation && !isset($_POST['submit_loan']) && empty($error)): ?>
    <div class="card mt-4 shadow-sm border-primary">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Loan Calculation Preview - Ready to Submit</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Loan Summary</h6>
                    <table class="table table-sm table-borderless mb-3">
                        <tr>
                            <td class="fw-medium">Principal Amount:</td>
                            <td class="text-end">₱<?= number_format($loanCalculation['principal_amount'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="fw-medium">Total Interest (4 months @ 5%):</td>
                            <td class="text-end text-danger">₱<?= number_format($loanCalculation['total_interest'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="fw-medium">Fixed Insurance Fee:</td>
                            <td class="text-end text-danger">₱<?= number_format($loanCalculation['insurance_fee'], 2) ?></td>
                        </tr>
                        <tr class="table-info">
                            <td class="fw-bold">Total Repayment Amount:</td>
                            <td class="text-end fw-bold">₱<?= number_format($loanCalculation['total_amount'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-success">Mandatory Weekly Payment (17 weeks):</td>
                            <td class="text-end fw-bold text-success">₱<?= number_format($loanCalculation['weekly_payment'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Term:</td>
                            <td class="text-end text-muted">17 weeks (4 months)</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Weekly Breakdown (Approximate)</h6>
                    <table class="table table-sm table-borderless">
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
                        <tr>
                            <td class="fw-bold">Total Weekly Components:</td>
                            <td class="text-end fw-bold">₱<?= number_format(
                                $loanCalculation['breakdown']['principal_per_week'] + 
                                $loanCalculation['breakdown']['interest_per_week'] + 
                                $loanCalculation['breakdown']['insurance_per_week'], 2
                            ) ?></td>
                        </tr>
                    </table>
                     <p class="small text-muted mt-3">Note: The weekly amounts shown here are approximate. The final amount of the last payment will adjust for rounding.</p>
                </div>
            </div>
            
            <!-- Submit Button (appears after successful calculation) -->
             <div class="d-flex justify-content-end">
                <form action="<?= APP_URL ?>/public/loans/add.php" method="post" id="submitLoanForm">
                    <?= $csrf->getTokenField() ?>
                    <input type="hidden" name="client_id" value="<?= htmlspecialchars($loan['client_id']) ?>">
                    <input type="hidden" name="loan_amount" value="<?= htmlspecialchars($loan['loan_amount']) ?>">
                    <button type="submit" name="submit_loan" class="btn btn-success btn-lg">
                        <i data-feather="check-circle" class="me-1"></i> Submit Loan Application
                    </button>
                </form>
            </div>
            
        </div>
    </div>
    <?php endif; ?>

</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>