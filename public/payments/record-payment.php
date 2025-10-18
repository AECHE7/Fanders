<?php
/**
 * Record Payment Processor
 * Handles the POST request to record a payment against an active loan.
 * Integrates: LoanService, PaymentService
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../init.php';

// Enforce role-based access control (Only Staff roles responsible for handling cash/payments)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier', 'account-officer']);

// Initialize services
$loanService = new LoanService();
$paymentService = new PaymentService();

// Get Loan ID from GET parameter
$loanId = isset($_GET['loan_id']) ? (int)$_GET['loan_id'] : 0;

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

// Get payment summary for validation
$paymentSummary = $paymentService->getPaymentSummaryByLoan($loanId);
$remainingBalance = $loanData['total_loan_amount'] - ($paymentSummary['total_paid'] ?? 0);

// Process Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrf->validateRequest()) {
        $session->setFlash('error', 'Invalid security token. Please refresh and try again.');
        header('Location: ' . APP_URL . '/public/payments/approvals.php?loan_id=' . $loanId);
        exit;
    }

    // Gather and sanitize input
    $paymentAmount = isset($_POST['payment_amount']) ? (float)$_POST['payment_amount'] : 0;
    $notes = trim($_POST['notes'] ?? '');

    // Basic server-side validation
    if ($paymentAmount <= 0) {
        $error = "Payment amount must be greater than zero.";
    } elseif ($paymentAmount > $remainingBalance + 0.01) { // Allow for tiny rounding error
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
            // Failure: Store the specific error message from the service
            $session->setFlash('error', $paymentService->getErrorMessage() ?: "Failed to record payment due to a transactional error.");
            header('Location: ' . APP_URL . '/public/payments/approvals.php?loan_id=' . $loanId);
            exit;
        }
    } else {
        $session->setFlash('error', $error);
        header('Location: ' . APP_URL . '/public/payments/approvals.php?loan_id=' . $loanId);
        exit;
    }
} else {
    // If not POST, redirect back to the form
    header('Location: ' . APP_URL . '/public/payments/approvals.php?loan_id=' . $loanId);
    exit;
}
?>
