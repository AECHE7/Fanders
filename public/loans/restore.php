<?php
/**
 * Restore cancelled loan application handler for Fanders Microfinance
 * Only cancelled loan applications can be restored to application status
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control (Managers and Admins can restore cancelled applications)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager']);

// Check if loan ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $session->setFlash('error', 'Loan ID is required.');
    header('Location: ' . APP_URL . '/public/loans/index.php');
    exit;
}

// Validate CSRF token
if (!$csrf->validateRequest()) {
    $session->setFlash('error', 'Invalid request.');
    header('Location: ' . APP_URL . '/public/loans/index.php');
    exit;
}

$loanId = (int)$_POST['id'];

// Initialize loan service
$loanService = new LoanService();

// Get loan data to check status
$loan = $loanService->getLoanWithClient($loanId);

if (!$loan) {
    $session->setFlash('error', 'Loan not found.');
    header('Location: ' . APP_URL . '/public/loans/index.php');
    exit;
}

// Check if loan can be restored (only cancelled applications can be restored)
if ($loan['status'] !== 'cancelled') {
    $session->setFlash('error', 'Only cancelled loan applications can be restored.');
    header('Location: ' . APP_URL . '/public/loans/view.php?id=' . $loanId);
    exit;
}

// Restore the cancelled loan application to application status
if ($loanService->restoreLoan($loanId)) {
    $session->setFlash('success', 'Loan application restored successfully.');
} else {
    $session->setFlash('error', $loanService->getErrorMessage());
}

// Redirect back to loans index
header('Location: ' . APP_URL . '/public/loans/index.php');
exit;
