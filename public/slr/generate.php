<?php
/**
 * SLR Generation and Download Handler
 * Generates and downloads SLR PDF documents
 */

// Centralized initialization
require_once '../../public/init.php';

// Enforce role-based access control
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier']);

// Initialize service
$loanReleaseService = new LoanReleaseService();

// Get loan ID
$loanId = isset($_GET['loan_id']) ? (int)$_GET['loan_id'] : null;

if (!$loanId) {
    $_SESSION['error'] = 'Invalid loan ID.';
    header('Location: index.php');
    exit;
}

// Generate and save SLR document
$filePath = $loanReleaseService->generateAndSaveSLR($loanId, null, $_SESSION['user_id']);

if ($filePath) {
    // Set headers for PDF download
    $filename = 'SLR_' . str_pad($loanId, 6, '0', STR_PAD_LEFT) . '_' . date('Ymd') . '.pdf';
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');

    // Output file and clean up
    readfile($filePath);
} else {
    $_SESSION['error'] = 'Failed to generate SLR: ' . $loanReleaseService->getErrorMessage();
    header('Location: index.php');
    exit;
}
