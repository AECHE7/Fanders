<?php
/**
 * SLR Disburse Action (disburse.php)
 * Role: Handles POST request to finalize disbursement, activating the loan transactionally.
 */
// Set flag to skip default auth check in init.php since we handle it below
$skip_auth_check = true; 
require_once '../../public/init.php';

// Ensure the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Only Admin/Manager can disburse
if (!$auth->isLoggedIn() || !$auth->hasRole(['super-admin', 'admin', 'manager'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permission denied.']);
    exit;
}

// Set JSON response header
header('Content-Type: application/json');

// Validate CSRF token
if (!$csrf->validateRequest()) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
    exit;
}

$slrId = isset($_POST['slr_id']) ? (int)$_POST['slr_id'] : 0;
$slrDocumentService = new SlrDocumentService();

if ($slrId > 0) {
    // Perform the critical transactional disbursement
    $success = $slrDocumentService->completeDisbursementTransaction($slrId);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Loan successfully disbursed and activated.']);
    } else {
        echo json_encode(['success' => false, 'message' => $slrDocumentService->getErrorMessage() ?: 'Failed to complete disbursement transaction.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid SLR ID.']);
}
?>