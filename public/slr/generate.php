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

// Generate SLR document
$pdfContent = $loanReleaseService->generateSLRDocument($loanId);

if ($pdfContent) {
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="SLR_' . str_pad($loanId, 6, '0', STR_PAD_LEFT) . '_' . date('Ymd') . '.pdf"');
    header('Content-Length: ' . strlen($pdfContent));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    echo $pdfContent;
    exit;
} else {
    $_SESSION['error'] = 'Failed to generate SLR: ' . $loanReleaseService->getErrorMessage();
    header('Location: index.php');
    exit;
}
