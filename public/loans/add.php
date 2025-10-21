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

// Debug file for logging
$debugFile = BASE_PATH . '/LOAN_DEBUG_LOG.txt';

// --- 1. Initial Data Setup ---
$loan = [
    'client_id' => isset($_GET['client_id']) ? (int)$_GET['client_id'] : '', // Pre-select if linked from client page
    'loan_amount' => '',
    'loan_term' => 17 // Default to 17 weeks
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
    if (!$csrf->validateRequest(false)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        // Gather and sanitize input
        $loan = [
            'client_id' => isset($_POST['client_id']) ? (int)$_POST['client_id'] : '',
            'loan_amount' => isset($_POST['loan_amount']) ? (float)$_POST['loan_amount'] : '',
            'loan_term' => isset($_POST['loan_term']) ? (int)$_POST['loan_term'] : 17
        ];

        // --- Calculate Preview (Run regardless of submission type) ---
        if ($loan['loan_amount'] > 0) {
            $loanCalculation = $loanCalculationService->calculateLoan($loan['loan_amount'], $loan['loan_term']);
            if (!$loanCalculation) {
                $error = $loanCalculationService->getErrorMessage() ?: "Failed to calculate loan details.";
                // Log calculation error for debugging
                error_log("Loan calculation error on add.php: " . $error . " (amount: " . $loan['loan_amount'] . ", term: " . $loan['loan_term'] . ")");
            } else {
                // Do NOT regenerate CSRF token here on calculate - keep token consistent until final submission
                // The submit action will rely on the same token generated for the initial form render
                // $csrf->generateToken(); // intentionally disabled
            }
        }
        
        // DEBUG: Check what POST variables we're getting
        $debugLine = "\n=== POST DEBUG AT " . date('Y-m-d H:i:s') . " ===\n";
        $debugLine .= "POST keys: " . implode(", ", array_keys($_POST)) . "\n";
        $debugLine .= "submit_loan isset? " . (isset($_POST['submit_loan']) ? 'YES' : 'NO') . "\n";
        $debugLine .= "submit_loan value: " . ($_POST['submit_loan'] ?? 'UNDEFINED') . "\n";
        $debugLine .= "Error at this point: " . ($error ?? 'NONE') . "\n";
        $debugLine .= "Calculation success: " . ($loanCalculation ? 'YES' : 'NO') . "\n";
        file_put_contents(BASE_PATH . '/LOAN_DEBUG_LOG.txt', $debugLine, FILE_APPEND);
        
        // Check if the "Apply" button was pressed (not just the 'Calculate' preview)
        if (isset($_POST['submit_loan']) && $loanCalculation && !$error) {
            $debugLog = "\n=== SUBMISSION AT " . date('Y-m-d H:i:s') . " ===\n";
            $debugLog .= "Client ID: " . $loan['client_id'] . "\n";
            $debugLog .= "Amount: " . $loan['loan_amount'] . "\n";
            $debugLog .= "Term: " . $loan['loan_term'] . "\n";
            
            error_log("=== SUBMISSION HANDLER TRIGGERED ===");

            // Map form data to service expected format
            $loanData = [
                'client_id' => $loan['client_id'],
                'principal' => $loan['loan_amount'],
                'term_weeks' => $loan['loan_term']
            ];

            $debugLog .= "LoanData: " . json_encode($loanData) . "\n";
            $debugLog .= "User ID: " . ($user['id'] ?? 'NULL') . "\n";

            // Apply for the loan
            $loanId = $loanService->applyForLoan($loanData, $user['id']);
            
            $debugLog .= "applyForLoan returned: " . ($loanId ? "ID=$loanId" : "FALSE/NULL") . "\n";

            if ($loanId) {
                $debugLog .= "RESULT: SUCCESS - Loan created with ID: " . $loanId . "\n";
                error_log($debugLog);
                file_put_contents($debugFile, $debugLog, FILE_APPEND);
                
                $session->setFlash('success', 'Loan application submitted successfully. Pending Manager approval.');
                header('Location: ' . APP_URL . '/public/loans/index.php');
                exit;
            } else {
                // Failure: Get the error message from the service
                $submissionError = $loanService->getErrorMessage();
                
                // Better error message handling - check for truly empty, not just falsy
                if (!$submissionError || trim($submissionError) === '') {
                    $error = "Failed to submit loan application. Please check the form and try again.";
                    error_log("CRITICAL: Loan submission failed for client_id=" . $loan['client_id'] . " but no error message provided");
                } else {
                    $error = $submissionError;
                }
                
                $debugLog .= "Service Error: " . $error . "\n";
                $debugLog .= "RESULT: FAILURE\n";
                
                error_log($debugLog);
                file_put_contents($debugFile, $debugLog, FILE_APPEND);
                
                $session->setFlash('error', $error);
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

<main class="main-content">
    <div class="content-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">New Loan Application (4-52 Week Terms)</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-sm btn-outline-secondary">
                <i data-feather="arrow-left"></i> Back to Loans List
            </a>
        </div>
    </div>
    
    <!-- Flash Messages -->
    <?php if ($session->hasFlash('success')): ?>
        <div class="alert alert-success">
            ✓ <?= $session->getFlash('success') ?>
        </div>
    <?php endif; ?>
    <?php if ($session->hasFlash('error')): ?>
        <div class="alert alert-danger">
            ✗ <?= $session->getFlash('error') ?>
        </div>
    <?php endif; ?>
    
    <!-- Check if error variable is set directly -->
    <?php if (!empty($error) && !$session->hasFlash('error')): ?>
        <div class="alert alert-warning">
            <strong>Warning:</strong> Error detected but not in flash: <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <!-- DEBUG: Show calculation status -->
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_id']) && isset($_POST['loan_amount'])): ?>
        <div class="alert alert-info">
            <strong>Debug Info:</strong> 
            Form submitted with Amount=₱<?= htmlspecialchars($_POST['loan_amount']) ?>, Term=<?= htmlspecialchars($_POST['loan_term']) ?> weeks
            <?php if ($loanCalculation): ?>
                - ✓ Calculation successful
            <?php else: ?>
                - ✗ Calculation failed: <?= htmlspecialchars($error ?: 'Unknown error') ?>
            <?php endif; ?>
            
            <?php if (isset($_POST['submit_loan'])): ?>
                <hr>
                <strong>Submission Status:</strong>
                <?php if ($error): ?>
                    ✗ SUBMISSION FAILED: <?= htmlspecialchars($error) ?>
                <?php else: ?>
                    ✓ No error recorded
                <?php endif; ?>
            <?php endif; ?>
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
    <?php 
        // DEBUG: Show what's happening with calculation
        error_log("DEBUG add.php: loanCalculation=" . ($loanCalculation ? 'SET' : 'NULL') . ", error=" . ($error ?: 'EMPTY'));
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_id']) && isset($_POST['loan_amount'])) {
            error_log("DEBUG add.php: POST data - client_id={$_POST['client_id']}, amount={$_POST['loan_amount']}, term={$_POST['loan_term']}");
        }
    ?>
    <?php if ($loanCalculation && empty($error)): ?>
        <?php
        // Calculate approximate weekly breakdown
        $principal_per_week = round($loanCalculation['principal'] / $loanCalculation['term_weeks'], 2);
        $interest_per_week = round($loanCalculation['total_interest'] / $loanCalculation['term_weeks'], 2);
        $insurance_per_week = round($loanCalculation['insurance_fee'] / $loanCalculation['term_weeks'], 2);
        $term_weeks = $loanCalculation['term_weeks'];
        $term_months = round($term_weeks / 4.333, 1); // Approximate months
        ?>
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
                            <td class="text-end">₱<?= number_format($loanCalculation['principal'], 2) ?></td>
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
                            <td class="text-end fw-bold">₱<?= number_format($loanCalculation['total_loan_amount'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-success">Mandatory Weekly Payment (<?= $term_weeks ?> weeks):</td>
                            <td class="text-end fw-bold text-success">₱<?= number_format($loanCalculation['weekly_payment_base'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Term:</td>
                            <td class="text-end text-muted"><?= $term_weeks ?> weeks (<?= $term_months ?> months)</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Weekly Breakdown (Approximate)</h6>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td>Principal per week:</td>
                            <td class="text-end">₱<?= number_format($principal_per_week, 2) ?></td>
                        </tr>
                        <tr>
                            <td>Interest per week:</td>
                            <td class="text-end">₱<?= number_format($interest_per_week, 2) ?></td>
                        </tr>
                        <tr>
                            <td>Insurance per week:</td>
                            <td class="text-end">₱<?= number_format($insurance_per_week, 2) ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Total Weekly Components:</td>
                            <td class="text-end fw-bold">₱<?= number_format(
                                $principal_per_week +
                                $interest_per_week +
                                $insurance_per_week, 2
                            ) ?></td>
                        </tr>
                    </table>
                     <p class="small text-muted mt-3">Note: The weekly amounts shown here are approximate. The final amount of the last payment will adjust for rounding.</p>
                </div>
            </div>

            <!-- Submit Button (appears after successful calculation) -->
            <div class="d-flex justify-content-end">
                <!-- The submit button will be part of the main form (templates/loans/form.php) and displayed when calculation is successful -->
                <button type="submit" form="loanForm" name="submit_loan" value="1" class="btn btn-success btn-lg">
                    <i data-feather="check-circle" class="me-1"></i> Submit Loan Application
                </button>
            </div>

        </div>
    </div>
    <?php endif; ?>
    </div>
</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>