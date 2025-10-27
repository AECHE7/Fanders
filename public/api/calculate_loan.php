<?php
/**
 * API Endpoint: Calculate Loan Preview
 * Returns loan calculation preview with validation
 */

// Include initialization
require_once '../../public/init.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . APP_URL);
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

// Authenticate user
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Check for staff role access
if (!$auth->hasRole(['super-admin', 'admin', 'manager', 'account-officer'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Staff roles required.']);
    exit;
}

// Only allow POST requests for calculations
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    $principal = isset($input['principal']) ? (float)$input['principal'] : 0;
    $weeks = isset($input['weeks']) ? (int)$input['weeks'] : LoanCalculationService::DEFAULT_WEEKS_IN_LOAN;
    $months = isset($input['months']) ? (int)$input['months'] : LoanCalculationService::DEFAULT_LOAN_TERM_MONTHS;
    
    // Initialize loan calculation service
    $loanCalcService = new LoanCalculationService();
    
    // Validate principal amount using service
    if (!$loanCalcService->validateLoanAmount($principal)) {
        echo json_encode([
            'success' => false,
            'error' => $loanCalcService->getErrorMessage(),
            'validation_failed' => true
        ]);
        exit;
    }
    
    // Validate term weeks
    if ($weeks < 4 || $weeks > 52) {
        echo json_encode([
            'success' => false,
            'error' => 'Loan term must be between 4 and 52 weeks.',
            'validation_failed' => true
        ]);
        exit;
    }
    
    // Calculate loan
    $calculation = $loanCalcService->calculateLoan($principal, $weeks, $months);
    
    if (!$calculation) {
        echo json_encode([
            'success' => false,
            'error' => $loanCalcService->getErrorMessage() ?: 'Calculation failed'
        ]);
        exit;
    }
    
    // Format calculation for UI display
    $formattedSummary = $loanCalcService->formatCalculationSummary($calculation);
    
    // Return successful calculation
    echo json_encode([
        'success' => true,
        'data' => [
            'calculation' => $calculation,
            'formatted_summary' => $formattedSummary,
            'input' => [
                'principal' => $principal,
                'weeks' => $weeks,
                'months' => $months
            ]
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // Log error for debugging
    error_log("Loan calculation API error: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Calculation service error',
        'message' => $e->getMessage()
    ]);
}
?>