<?php
/**
 * API Endpoint: Get Client Loans
 * Returns active loans for a specific client
 */

header('Content-Type: application/json');

require_once '../../public/init.php';

// Check if user is authenticated
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get parameters
$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : 'active';

// Convert status to proper case for LoanModel constants
if (strtolower($status) === 'active') {
    $status = 'Active';
} elseif (strtolower($status) === 'completed') {
    $status = 'Completed';
} elseif (strtolower($status) === 'approved') {
    $status = 'Approved';
} elseif (strtolower($status) === 'application') {
    $status = 'Application';
} elseif (strtolower($status) === 'defaulted') {
    $status = 'Defaulted';
}

if (!$clientId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Client ID is required']);
    exit;
}

try {
    $loanService = new LoanService();
    
    // Get loans for the client with the specified status
    $loans = $loanService->getLoansByClient($clientId, ['status' => $status]);
    
    if ($loans === false) {
        throw new Exception($loanService->getErrorMessage() ?: 'Failed to fetch loans');
    }
    
    // Return the loans
    echo json_encode([
        'success' => true,
        'loans' => $loans,
        'count' => count($loans)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
