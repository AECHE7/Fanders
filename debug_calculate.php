<?php
/**
 * Debug script to test the loan calculation process
 * Tests the exact flow when user clicks Calculate
 */

// Initialize
require_once './public/init.php';

$loanCalculationService = new LoanCalculationService();

// Test data (typical user input)
$testCases = [
    ['amount' => 5000, 'term' => 17, 'label' => 'Standard case: 5000, 17 weeks'],
    ['amount' => 1000, 'term' => 4, 'label' => 'Minimum case: 1000, 4 weeks'],
    ['amount' => 50000, 'term' => 52, 'label' => 'Maximum case: 50000, 52 weeks'],
];

echo "=== LOAN CALCULATION DEBUG TEST ===\n\n";

foreach ($testCases as $test) {
    echo "Test: {$test['label']}\n";
    echo "Input: Amount={$test['amount']}, Term={$test['term']}\n";
    echo "---\n";
    
    // Call the calculate function
    $result = $loanCalculationService->calculateLoan($test['amount'], $test['term']);
    
    if ($result) {
        echo "✓ CALCULATION SUCCESSFUL\n";
        echo "  Principal: ₱" . number_format($result['principal'], 2) . "\n";
        echo "  Total Interest: ₱" . number_format($result['total_interest'], 2) . "\n";
        echo "  Insurance Fee: ₱" . number_format($result['insurance_fee'], 2) . "\n";
        echo "  Total Amount: ₱" . number_format($result['total_loan_amount'], 2) . "\n";
        echo "  Weekly Payment: ₱" . number_format($result['weekly_payment_base'], 2) . "\n";
    } else {
        echo "✗ CALCULATION FAILED\n";
        echo "  Error: " . $loanCalculationService->getErrorMessage() . "\n";
    }
    
    echo "\n";
}

echo "\n=== TESTING VALIDATION SEPARATELY ===\n\n";

$validationCases = [
    ['amount' => 5000, 'label' => 'Valid amount: 5000'],
    ['amount' => 999, 'label' => 'Too low: 999'],
    ['amount' => 50001, 'label' => 'Too high: 50001'],
    ['amount' => 0, 'label' => 'Zero: 0'],
    ['amount' => -1000, 'label' => 'Negative: -1000'],
];

foreach ($validationCases as $test) {
    echo "Test: {$test['label']}\n";
    $isValid = $loanCalculationService->validateLoanAmount($test['amount']);
    if ($isValid) {
        echo "✓ VALID\n";
    } else {
        echo "✗ INVALID: " . $loanCalculationService->getErrorMessage() . "\n";
    }
    echo "\n";
}

echo "=== DEBUG COMPLETE ===\n";
?>
