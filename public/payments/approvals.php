<?php
/**
 * Record Payment Controller (record.php)
 * Role: Allows Cashiers/AOs to record a payment against an Active Loan, triggering transactional updates.
 * Integrates: LoanService, PaymentService, LoanCalculationService
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';
require_once BASE_PATH . '/app/utilities/Permissions.php';

// Enforce role-based access control (Only Staff roles responsible for handling cash/payments)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier', 'account-officer']);

$currentUser = $auth->getCurrentUser() ?: [];
$currentRole = $currentUser['role'] ?? '';

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

if (!$loanData || $loanData['status'] !== LoanModel::STATUS_ACTIVE) {
    $session->setFlash('error', 'Loan not found or is not currently active for payments.');
    header('Location: ' . APP_URL . '/public/loans/index.php');
    exit;
}

// Get payment summary data for display (total paid, outstanding)
$paymentSummary = $paymentService->getPaymentSummaryByLoan($loanId);
$nextExpectedPayment = round($loanData['total_loan_amount'] / 17, 2);
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

<main class="main-content">
    <div class="content-wrapper">
        <!-- Page Header with Icon -->
        <div class="notion-page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #e6f4ea;">
                            <i data-feather="check-circle" style="width: 24px; height: 24px; color: rgb(34, 139, 34);"></i>
                        </div>
                    </div>
                    <h1 class="notion-page-title mb-0">Record Payment</h1>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <?php if (Permissions::canAccessPayments($currentRole)): ?>
                        <a href="<?= APP_URL ?>/public/payments/index.php" class="btn btn-sm btn-outline-secondary">
                            <i data-feather="arrow-left" class="me-1" style="width: 14px; height: 14px;"></i> Back to Payments
                        </a>
                    <?php endif; ?>
                </div>
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
    <div class="enhanced-form-wrapper">
        <form method="POST" action="<?= APP_URL ?>/public/payments/record-payment.php?loan_id=<?= $loanId ?>" class="enhanced-form needs-validation" novalidate>
            <?= $csrf->getTokenField() ?>
            <input type="hidden" name="loan_id" value="<?= $loanId ?>">

            <!-- Enhanced Form Header -->
            <div class="enhanced-form-header">
                <div class="enhanced-form-header-icon">
                    <i data-feather="credit-card"></i>
                </div>
                <h1 class="enhanced-form-header-title">Record Payment Transaction</h1>
                <p class="enhanced-form-header-subtitle">
                    Process payment for Loan #<?= $loanId ?> - <?= htmlspecialchars($loanData['client_name']) ?>
                </p>
            </div>

            <!-- Enhanced Form Body -->
            <div class="enhanced-form-body">
                <!-- Payment Details Section -->
                <section class="enhanced-form-section">
                    <div class="enhanced-form-section-header">
                        <div class="enhanced-form-section-icon">
                            <i data-feather="dollar-sign"></i>
                        </div>
                        <h2 class="enhanced-form-section-title">Payment Details</h2>
                        <div class="enhanced-form-section-divider"></div>
                    </div>

                    <div class="enhanced-form-grid">
                        <!-- Payment Amount Field -->
                        <div class="enhanced-form-group">
                            <label for="payment_amount" class="enhanced-form-label required">Payment Amount (₱)</label>
                            <div class="enhanced-form-input-wrapper">
                                <input type="number" 
                                    step="0.01" 
                                    min="0.01" 
                                    max="<?= $remainingBalance + 0.01 ?>" 
                                    class="enhanced-form-control" 
                                    id="payment_amount" 
                                    name="payment_amount" 
                                    value="<?= htmlspecialchars($nextExpectedPayment) ?>" 
                                    required
                                    placeholder="0.00">
                                <div class="enhanced-form-icon">
                                    <i data-feather="dollar-sign"></i>
                                </div>
                            </div>
                            <div class="enhanced-form-error">
                                Please enter a valid payment amount (up to ₱<?= number_format($remainingBalance, 2) ?>).
                            </div>
                            <div class="enhanced-form-help">
                                Next expected payment is ₱<?= number_format($nextExpectedPayment, 2) ?>. Maximum allowed: ₱<?= number_format($remainingBalance, 2) ?>.
                            </div>
                        </div>

                        <!-- Notes Field -->
                        <div class="enhanced-form-group">
                            <label for="notes" class="enhanced-form-label">Notes / Remarks</label>
                            <div class="enhanced-form-input-wrapper">
                                <input type="text" 
                                    class="enhanced-form-control" 
                                    id="notes" 
                                    name="notes" 
                                    placeholder="e.g., Payment received in full"
                                    maxlength="255">
                                <div class="enhanced-form-icon">
                                    <i data-feather="message-circle"></i>
                                </div>
                            </div>
                            <div class="enhanced-form-help">Optional notes about this payment transaction.</div>
                        </div>

                        <!-- Payment Summary Info -->
                        <div class="enhanced-form-group">
                            <div class="enhanced-alert enhanced-alert-info">
                                <div class="enhanced-alert-icon">
                                    <i data-feather="info"></i>
                                </div>
                                <div class="enhanced-alert-content">
                                    <strong>Payment Summary:</strong><br>
                                    • Remaining Balance: ₱<?= number_format($remainingBalance, 2) ?><br>
                                    • Total Paid: ₱<?= number_format($paymentSummary['total_paid'] ?? 0, 2) ?><br>
                                    • Loan Amount: ₱<?= number_format($loanData['total_loan_amount'], 2) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Form Actions -->
            <div class="enhanced-form-actions">
                <a href="<?= APP_URL ?>/public/payments/index.php" class="btn btn-outline-secondary me-2">Cancel</a>
                <button type="submit" class="btn btn-success px-4">
                    <i data-feather="check-circle" class="me-1" style="width: 16px; height: 16px;"></i>
                    Record Payment
                </button>
            </div>

        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('.enhanced-form');
        const paymentAmount = document.getElementById('payment_amount');
        const maxAmount = <?= $remainingBalance + 0.01 ?>;

        // Replace feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }

        // Form validation
        if (form) {
            form.addEventListener('submit', function(event) {
                let valid = true;

                // Validate payment amount
                if (paymentAmount) {
                    const amount = parseFloat(paymentAmount.value);
                    if (isNaN(amount) || amount <= 0 || amount > maxAmount) {
                        paymentAmount.setCustomValidity("Payment amount must be between 0.01 and " + maxAmount.toFixed(2));
                        valid = false;
                    } else {
                        paymentAmount.setCustomValidity("");
                    }
                }

                if (!form.checkValidity() || !valid) {
                    event.preventDefault();
                    event.stopPropagation();
                    const invalidField = form.querySelector(':invalid');
                    if (invalidField) {
                        invalidField.focus();
                        const fieldGroup = invalidField.closest('.enhanced-form-group');
                        if (fieldGroup) {
                            fieldGroup.classList.add('shake-animation');
                            setTimeout(() => {
                                fieldGroup.classList.remove('shake-animation');
                            }, 820);
                        }
                    }
                }
                form.classList.add('was-validated');
            });

            // Real-time validation for payment amount
            if (paymentAmount) {
                paymentAmount.addEventListener('input', function() {
                    const amount = parseFloat(this.value);
                    if (isNaN(amount) || amount <= 0 || amount > maxAmount) {
                        this.setCustomValidity("Payment amount must be between 0.01 and " + maxAmount.toFixed(2));
                    } else {
                        this.setCustomValidity("");
                    }
                });
            }
        }
    });
    </script>

    <style>
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        .shake-animation { animation: shake 0.8s ease; }
    </style>
    </div>
</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>