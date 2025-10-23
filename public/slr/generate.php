<?php
/**
 * Enhanced SLR Generation and Download Handler
 * Uses the new SLRService for comprehensive document management
 */

// Centralized initialization
require_once '../init.php';

// Enforce role-based access control
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier']);

// Initialize enhanced SLR service (adapter for refactor)
require_once __DIR__ . '/../../app/services/SLRServiceAdapter.php';
$slrService = new SLRServiceAdapter();

// Get loan ID
$loanId = isset($_GET['loan_id']) ? (int)$_GET['loan_id'] : null;
$action = isset($_GET['action']) ? $_GET['action'] : 'generate';

if (!$loanId) {
    $session->setFlash('error', 'Invalid loan ID.');
    header('Location: ' . APP_URL . '/public/loans/index.php');
    exit;
}

switch ($action) {
    case 'generate':
        // Generate new SLR document
        $slrDocument = $slrService->generateSLR($loanId, $user['id'], 'manual_request');
        
        if ($slrDocument) {
            $session->setFlash('success', 'SLR document generated successfully! It is now listed in SLR Documents.');
            // Redirect to SLR management list filtered by this loan
            header('Location: ' . APP_URL . '/public/slr/manage.php?loan_id=' . $loanId);
            exit;
        } else {
            $session->setFlash('error', 'Failed to generate SLR: ' . $slrService->getErrorMessage());
            header('Location: ' . APP_URL . '/public/loans/index.php');
            exit;
        }
        break;
        
    case 'download':
        // Download existing SLR
        $slrDocument = $slrService->getSLRByLoanId($loanId);
        
        if (!$slrDocument) {
            $session->setFlash('error', 'No SLR document found for this loan. Generate one first.');
            header('Location: ' . APP_URL . '/public/slr/generate.php?action=generate&loan_id=' . $loanId);
            exit;
        }
        
        $fileInfo = $slrService->downloadSLR($slrDocument['id'], $user['id'], 'Manual download from loan list');
        
        if ($fileInfo) {
            // Set headers for PDF download
            $filename = 'SLR_' . str_pad($loanId, 6, '0', STR_PAD_LEFT) . '_' . date('Ymd') . '.pdf';
            
            header('Content-Type: ' . $fileInfo['content_type']);
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . $fileInfo['file_size']);
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');

            // Output file
            readfile($fileInfo['file_path']);
            exit;
        } else {
            $session->setFlash('error', 'Failed to download SLR: ' . $slrService->getErrorMessage());
            header('Location: ' . APP_URL . '/public/loans/index.php');
            exit;
        }
        break;
        
    case 'view':
        // View SLR details without downloading
        $slrDocument = $slrService->getSLRByLoanId($loanId);
        
        if (!$slrDocument) {
            $session->setFlash('error', 'No SLR document found for this loan.');
            header('Location: ' . APP_URL . '/public/loans/index.php');
            exit;
        }
        
        // Log the view access
        $slrService->logSLRAccess($slrDocument['id'], 'view', $user['id'], 'View from loan list');
        
        // Redirect to SLR management page (to be created)
        header('Location: ' . APP_URL . '/public/slr/manage.php?id=' . $slrDocument['id']);
        exit;
        break;
        
    default:
        $session->setFlash('error', 'Invalid action specified.');
        header('Location: ' . APP_URL . '/public/loans/index.php');
        exit;
}
