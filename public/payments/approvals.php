<?php
/**
 * Record Payment Controller (record.php)
 * Role: Allows Cashiers/AOs to record a payment against an Active Loan, triggering transactional updates.
 * Integrates: LoanService, PaymentService, LoanCalculationService
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control (Only Staff roles responsible for handling cash/payments)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier', 'account-officer']);

// Initialize services
$loanService = new LoanService();
$paymentService = new PaymentService();
$loanCalculationService = new LoanCalculationService();

// --- 1. Get Loan ID and Initial Data ---
$loanId = isset($_GET['loan_id']) ? (int)$_GET['loan_id'] : 0;
$loanData = null;
$error = $session->getFlash('error');

if ($loanId <= 0) {
    $session->setFlash('error', 'Loan ID is required to record a payment.');
    header('Location: ' . APP_URL . '/public/loans/index.php');
    exit;
}

// Fetch loan data and check status
$loanData = $loanService->getLoanWithClient($loanId);

if (!$loanData || $loanData['status'] !== LoanModel::$STATUS_ACTIVE) {
    $session->setFlash('error', 'Loan not found or is not currently active for payments.');
    header('Location: ' . APP_URL . '/public/loans/index.php');
    exit;
}

// Get payment summary data for display (total paid, outstanding)
$paymentSummary = $paymentService->getLoanPaymentSummary($loanId);
$nextExpectedPayment = $loanData['weekly_payment'];
$remainingBalance = $loanData['total_loan_amount'] - ($paymentSummary['total_paid'] ?? 0);

// --- 2. Process Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrf->validateRequest()) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        // Gather and sanitize input
        $paymentAmount = isset($_POST['payment_amount']) ? (float)$_POST['payment_amount'] : 0;
        $notes = trim($_POST['notes'] ?? '');

        // Basic server-side validation
        if ($paymentAmount <= 0) {
            $error = "Payment amount must be greater than zero.";
        } else if ($paymentAmount > $remainingBalance + 0.01) { // Allow for tiny rounding error
             $error = "Payment amount exceeds the remaining balance of ₱" . number_format($remainingBalance, 2) . ".";
        }

        if (empty($error)) {
            // Attempt to record payment via service
            $paymentId = $paymentService->recordPayment(
                $loanId, 
                $paymentAmount, 
                $user['id'], // recorded_by (the staff member)
                $notes
            );

            if ($paymentId) {
                // Success: Redirect to the loan view page
                $session->setFlash('success', "Payment of ₱" . number_format($paymentAmount, 2) . " successfully recorded.");
                header('Location: ' . APP_URL . '/public/loans/view.php?id=' . $loanId);
                exit;
            } else {
                // Failure: Store the specific error message from the service (e.g., transaction failed)
                $error = $paymentService->getErrorMessage() ?: "Failed to record payment due to a transactional error.";
            }
        }
    }
    
    // Re-set error flash if an error occurred during POST
    if ($error) {
        $session->setFlash('error', $error);
    }
}

// --- 3. Display View ---
$pageTitle = "Record Payment for Loan ID " . $loanId;
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Record Payment</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $loanId ?>" class="btn btn-sm btn-outline-secondary">
                <i data-feather="arrow-left"></i> Back to Loan
            </a>
        </div>
    </div>
    
    <!-- Flash Messages -->
    <?php if ($session->hasFlash('error')): ?>
        <div class="alert alert-danger">
            <?= $session->getFlash('error') ?>
        </div>
    <?php endif; ?>

    <!-- Payment Information Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Loan Details (Client: <?= htmlspecialchars($loanData['client_name']) ?>)</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Loan ID:</dt>
                        <dd class="col-sm-6 fw-bold text-primary">#<?= $loanId ?></dd>
                        
                        <dt class="col-sm-6">Weekly Payment Due:</dt>
                        <dd class="col-sm-6">₱<?= number_format($nextExpectedPayment, 2) ?></dd>
                        
                        <dt class="col-sm-6">Total Loan Amount:</dt>
                        <dd class="col-sm-6">₱<?= number_format($loanData['total_loan_amount'], 2) ?></dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Total Paid To Date:</dt>
                        <dd class="col-sm-6 text-success fw-bold">₱<?= number_format($paymentSummary['total_paid'] ?? 0, 2) ?></dd>
                        
                        <dt class="col-sm-6">Remaining Balance:</dt>
                        <dd class="col-sm-6 text-danger fw-bold">₱<?= number_format($remainingBalance, 2) ?></dd>
                        
                        <dt class="col-sm-6">Disbursement Date:</dt>
                        <dd class="col-sm-6"><?= date('M d, Y', strtotime($loanData['disbursement_date'])) ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Recording Form -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Record Payment Transaction</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= APP_URL ?>/public/payments/record.php?loan_id=<?= $loanId ?>" class="needs-validation" novalidate>
                <?= $csrf->getTokenField() ?>
                <input type="hidden" name="loan_id" value="<?= $loanId ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="payment_amount" class="form-label fw-bold">Payment Amount (₱)</label>
                        <input type="number" step="0.01" min="0.01" max="<?= $remainingBalance + 0.01 ?>" 
                               class="form-control form-control-lg" id="payment_amount" name="payment_amount" 
                               value="<?= htmlspecialchars($nextExpectedPayment) ?>" required>
                        <div class="invalid-feedback">
                            Please enter a valid payment amount (up to ₱<?= number_format($remainingBalance, 2) ?>).
                        </div>
                        <small class="form-text text-muted">Next expected payment is ₱<?= number_format($nextExpectedPayment, 2) ?>.</small>
                    </div>

                    <div class="col-md-6">
                        <label for="notes" class="form-label">Notes / Remarks</label>
                        <input type="text" class="form-control" id="notes" name="notes" placeholder="e.g., Payment received in full">
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i data-feather="save"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>