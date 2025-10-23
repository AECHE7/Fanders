<?php
/**
 * Simple Payment Schedule Test
 */
echo "=== TESTING PAYMENT SCHEDULE CALCULATION ===\n\n";

// Include required files
require_once __DIR__ . '/app/services/LoanCalculationService.php';

try {
    $loanCalc = new LoanCalculationService();
    
    // Test calculation with sample data
    $principal = 10000;
    $termWeeks = 17;
    
    echo "Testing loan calculation:\n";
    echo "Principal: ₱" . number_format($principal, 2) . "\n";
    echo "Term: {$termWeeks} weeks\n\n";
    
    $result = $loanCalc->calculateLoan($principal, $termWeeks);
    
    if ($result) {
        echo "✓ Calculation successful:\n";
        echo "  Total Interest: ₱" . number_format($result['total_interest'], 2) . "\n";
        echo "  Insurance Fee: ₱" . number_format($result['insurance_fee'], 2) . "\n";
        echo "  Total Amount: ₱" . number_format($result['total_loan_amount'], 2) . "\n";
        echo "  Weekly Payment: ₱" . number_format($result['weekly_payment_base'], 2) . "\n\n";
        
        if (isset($result['payment_schedule']) && is_array($result['payment_schedule'])) {
            echo "✓ Payment schedule generated (" . count($result['payment_schedule']) . " payments):\n";
            echo "Week | Payment    | Principal  | Interest   | Insurance  \n";
            echo "------|------------|------------|------------|------------\n";
            
            $disbursementDate = date('Y-m-d');
            foreach (array_slice($result['payment_schedule'], 0, 5) as $payment) {
                $dueDate = date('M d', strtotime($disbursementDate . ' +' . ($payment['week'] - 1) . ' weeks'));
                printf("%2d   | ₱%8.2f | ₱%8.2f | ₱%8.2f | ₱%8.2f (%s)\n", 
                    $payment['week'],
                    $payment['expected_payment'],
                    $payment['principal_payment'],
                    $payment['interest_payment'],
                    $payment['insurance_payment'],
                    $dueDate
                );
            }
            echo "...and " . (count($result['payment_schedule']) - 5) . " more payments\n\n";
            
            echo "✓ Enhanced SLR will now include this detailed schedule!\n";
        } else {
            echo "✗ Payment schedule not available\n";
        }
        
    } else {
        echo "✗ Calculation failed: " . $loanCalc->getErrorMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>