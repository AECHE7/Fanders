<?php
/**
 * API Endpoint: Get Loan Calculation Configuration
 * Returns current loan limits and calculation constants
 */

// Include initialization
require_once '../../public/init.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . APP_URL);
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Authenticate user (basic auth check)
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

try {
    // Initialize loan calculation service
    $loanCalcService = new LoanCalculationService();
    
    // Get loan limits and configuration
    $limits = $loanCalcService->getLoanAmountLimits();
    $terms = $loanCalcService->getCommonLoanTerms();
    
    // Build comprehensive configuration response
    $config = [
        'loan_limits' => $limits,
        'business_rules' => [
            'interest_rate' => LoanCalculationService::INTEREST_RATE,
            'insurance_fee' => LoanCalculationService::INSURANCE_FEE,
            'savings_rate' => LoanCalculationService::SAVINGS_RATE,
            'default_weeks' => LoanCalculationService::DEFAULT_WEEKS_IN_LOAN,
            'default_months' => LoanCalculationService::DEFAULT_LOAN_TERM_MONTHS
        ],
        'common_terms' => $terms,
        'formatted_rules' => [
            'interest_display' => (LoanCalculationService::INTEREST_RATE * 100) . '% per month',
            'insurance_display' => '₱' . number_format(LoanCalculationService::INSURANCE_FEE, 2) . ' (fixed)',
            'savings_display' => (LoanCalculationService::SAVINGS_RATE * 100) . '% deduction'
        ],
        'validation' => [
            'min_weeks' => 4,
            'max_weeks' => 52,
            'step_amount' => 100
        ]
    ];
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $config,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    // Log error for debugging
    error_log("Loan config API error: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to retrieve loan configuration',
        'message' => $e->getMessage()
    ]);
}
?>