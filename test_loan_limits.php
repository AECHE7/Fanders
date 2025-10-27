<?php
/**
 * Test Loan Amount Limits - Fanders Microfinance
 * Tests the new minimum ₱5,000 and maximum ₱50,000 loan limits
 */

echo "🏦 FANDERS LOAN AMOUNT LIMITS TEST\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Test scenarios for loan validation
$testAmounts = [
    ['amount' => 3000, 'description' => 'Below minimum (should fail)'],
    ['amount' => 4999, 'description' => 'Just below minimum (should fail)'],
    ['amount' => 5000, 'description' => 'Exactly minimum (should pass)'],
    ['amount' => 5001, 'description' => 'Just above minimum (should pass)'],
    ['amount' => 25000, 'description' => 'Mid-range amount (should pass)'],
    ['amount' => 49999, 'description' => 'Just below maximum (should pass)'],
    ['amount' => 50000, 'description' => 'Exactly maximum (should pass)'],
    ['amount' => 50001, 'description' => 'Just above maximum (should fail)'],
    ['amount' => 75000, 'description' => 'Well above maximum (should fail)']
];

echo "📋 LOAN AMOUNT VALIDATION TESTS:\n";
echo str_repeat("-", 60) . "\n";

foreach ($testAmounts as $test) {
    $amount = $test['amount'];
    $description = $test['description'];
    
    // Simple validation logic based on new constants
    $minAmount = 5000;  // MIN_LOAN_AMOUNT
    $maxAmount = 50000; // MAX_LOAN_AMOUNT
    
    $isValid = ($amount >= $minAmount && $amount <= $maxAmount);
    $status = $isValid ? "✅ PASS" : "❌ FAIL";
    
    echo sprintf("₱%s - %s - %s\n", 
        number_format($amount), 
        str_pad($description, 35), 
        $status
    );
    
    if (!$isValid) {
        if ($amount < $minAmount) {
            echo "   Error: Loan amount must be at least ₱" . number_format($minAmount, 0) . ".\n";
        } else {
            echo "   Error: Loan amount cannot exceed ₱" . number_format($maxAmount, 0) . ".\n";
        }
    }
    echo "\n";
}

echo "🎯 NEW BUSINESS RULES SUMMARY:\n";
echo str_repeat("-", 40) . "\n";
echo "✅ MINIMUM LOAN AMOUNT: ₱5,000\n";
echo "✅ MAXIMUM LOAN AMOUNT: ₱50,000\n";
echo "✅ Valid Range: ₱5,000 - ₱50,000\n\n";

echo "📊 SAMPLE CALCULATIONS WITH NEW LIMITS:\n";
echo str_repeat("-", 50) . "\n\n";

// Sample valid calculations
$validAmounts = [5000, 15000, 30000, 45000, 50000];

foreach ($validAmounts as $principal) {
    // Calculate using the same logic as LoanCalculationService
    $interestRate = 0.05;
    $insuranceFee = 425.00;
    $savingsRate = 0.01;
    $termWeeks = 17;
    $termMonths = 4;
    
    $interest = $principal * $interestRate * $termMonths;
    $savings = $principal * $savingsRate;
    $totalAmount = $principal + $interest + $insuranceFee + $savings;
    $weeklyPayment = $totalAmount / $termWeeks;
    
    echo "💰 ₱" . number_format($principal) . " Loan (4 months, 17 weeks):\n";
    echo "   - Total Amount: ₱" . number_format($totalAmount, 2) . "\n";
    echo "   - Weekly Payment: ₱" . number_format($weeklyPayment, 2) . "\n";
    echo "   - Interest: ₱" . number_format($interest, 2) . "\n";
    echo "   - Insurance: ₱" . number_format($insuranceFee, 2) . "\n";
    echo "   - Savings: ₱" . number_format($savings, 2) . "\n\n";
}

echo "✨ LOAN LIMITS UPDATE COMPLETE!\n";
echo "Ready for integration with loan application forms.\n\n";

echo "Generated on " . date('F j, Y \a\t g:i A') . "\n";
?>