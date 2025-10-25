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

// Get pre-selected client details for display
$preSelectedClientData = null;
if (!empty($loan['client_id'])) {
    $preSelectedClientData = $clientService->getById($loan['client_id']);
    
    // Check if client exists
    if (!$preSelectedClientData) {
        $session->setFlash('error', 'Selected client not found.');
        header('Location: ' . APP_URL . '/public/loans/add.php');
        exit;
    }
    
    // Check if they are eligible for a loan
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
        // Gather raw input (keep raw values for better validation messaging)
        $loanAmountRaw = $_POST['loan_amount'] ?? '';
        $loanTermRaw = $_POST['loan_term'] ?? '';

        // Sanitize into typed values where possible, but keep raw values for validation
        $loan = [
            'client_id' => isset($_POST['client_id']) ? (int)$_POST['client_id'] : '',
            'loan_amount' => is_numeric($loanAmountRaw) ? (float)$loanAmountRaw : $loanAmountRaw,
            'loan_term' => is_numeric($loanTermRaw) ? (int)$loanTermRaw : $loanTermRaw
        ];

        // --- Calculate Preview (Run only when inputs are valid) ---
        // Prevent calling the heavy calculation process if principal amount or term inputs are invalid.
        $canCalculate = true;

        // Validate principal amount using the service helper (gives consistent error messages)
        if (!$loanCalculationService->validateLoanAmount($loanAmountRaw)) {
            $error = $loanCalculationService->getErrorMessage();
            $canCalculate = false;
        }

        // Validate term (must be numeric and within business bounds)
        if (!is_numeric($loanTermRaw) || (int)$loanTermRaw < 4 || (int)$loanTermRaw > 52) {
            $termMsg = 'Loan term must be a number between 4 and 52 weeks.';
            $error = !empty($error) ? trim($error . ' ' . $termMsg) : $termMsg;
            $canCalculate = false;
        }

        if ($canCalculate) {
            $loanCalculation = $loanCalculationService->calculateLoan((float)$loanAmountRaw, (int)$loanTermRaw);
            if (!$loanCalculation) {
                $error = $loanCalculationService->getErrorMessage() ?: "Failed to calculate loan details.";
                // Log calculation error for debugging
                error_log("Loan calculation error on add.php: " . $error . " (amount: " . $loanAmountRaw . ", term: " . $loanTermRaw . ")");
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
$pageTitle = $preSelectedClientData 
    ? "New Loan Application for " . htmlspecialchars($preSelectedClientData['name'])
    : "New Loan Application";
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2">
                <?php if ($preSelectedClientData): ?>
                    New Loan for <?= htmlspecialchars($preSelectedClientData['name']) ?>
                <?php else: ?>
                    New Loan Application (4-52 Week Terms)
                <?php endif; ?>
            </h1>
            <?php if ($preSelectedClientData): ?>
                <p class="text-muted mb-0">
                    <small>
                        <i data-feather="phone" style="width: 14px; height: 14px;"></i> 
                        <?= htmlspecialchars($preSelectedClientData['phone_number'] ?? 'N/A') ?>
                        <span class="mx-2">•</span>
                        <i data-feather="mail" style="width: 14px; height: 14px;"></i> 
                        <?= htmlspecialchars($preSelectedClientData['email'] ?? 'N/A') ?>
                        <span class="mx-2">•</span>
                        <a href="<?= APP_URL ?>/public/clients/view.php?id=<?= $loan['client_id'] ?>" class="text-decoration-none">
                            <i data-feather="arrow-left" style="width: 14px; height: 14px;"></i> Back to Client Profile
                        </a>
                    </small>
                </p>
            <?php endif; ?>
        </div>
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

            <!-- Submit Button: placed in preview for clearer UX; triggers confirmation modal -->
            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#confirmSubmitModal">
                    <i data-feather="check-circle" class="me-1"></i> Submit Loan Application
                </button>
            </div>

        </div>
    </div>
    <?php endif; ?>
    </div>
</main>

<!-- Confirmation Modal for Loan Submission -->
<?php if ($loanCalculation && empty($error)): ?>
<div class="modal fade" id="confirmSubmitModal" tabindex="-1" aria-labelledby="confirmSubmitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="confirmSubmitModalLabel">
                    <i data-feather="alert-circle" class="me-2" style="width:20px;height:20px;"></i>
                    Confirm Loan Application Submission
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">You are about to submit a loan application with the following details:</p>
                <div class="card bg-light">
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">Client:</dt>
                            <dd class="col-sm-7">
                                <?php 
                                $selectedClient = array_filter($clients, function($c) use ($loan) { 
                                    return $c['id'] == $loan['client_id']; 
                                });
                                echo !empty($selectedClient) ? htmlspecialchars(reset($selectedClient)['name']) : 'Client ID: ' . $loan['client_id'];
                                ?>
                            </dd>
                            <dt class="col-sm-5">Principal Amount:</dt>
                            <dd class="col-sm-7 fw-bold">₱<?= number_format($loan['loan_amount'], 2) ?></dd>
                            <dt class="col-sm-5">Loan Term:</dt>
                            <dd class="col-sm-7"><?= $loan['loan_term'] ?> weeks</dd>
                            <dt class="col-sm-5">Total Repayment:</dt>
                            <dd class="col-sm-7 text-danger fw-bold">₱<?= number_format($loanCalculation['total_loan_amount'], 2) ?></dd>
                            <dt class="col-sm-5">Weekly Payment:</dt>
                            <dd class="col-sm-7 text-success fw-bold">₱<?= number_format($loanCalculation['weekly_payment_base'], 2) ?></dd>
                        </dl>
                    </div>
                </div>
                <p class="mt-3 mb-0 text-muted small">
                    <i data-feather="info" class="me-1" style="width:14px;height:14px;"></i>
                    Once submitted, this loan will be marked as "Application" and will require manager approval before disbursement.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i data-feather="x" class="me-1" style="width:16px;height:16px;"></i>
                    Cancel
                </button>
                <button type="submit" form="loanForm" name="submit_loan" value="1" class="btn btn-success">
                    <i data-feather="check" class="me-1" style="width:16px;height:16px;"></i>
                    Confirm & Submit
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>