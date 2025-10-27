<?php
/**
 * Enhanced Loan Calculation Test - Fanders Microfinance
 * Demonstrates the new flexible loan calculation system with fixed rates
 */

// Include the enhanced calculation service
require_once __DIR__ . '/../public/init.php';

echo "<h2>🏦 Fanders Enhanced Loan Calculation System Test</h2>\n";
echo "<hr>\n";

// Initialize the service
$loanCalc = new LoanCalculationService();

// Test scenarios
$testScenarios = [
    [
        'name' => 'Standard Loan (17 weeks)',
        'principal' => 25000,
        'term_weeks' => 17,
        'term_months' => 4
    ],
    [
        'name' => 'Short-term Loan (13 weeks)',
        'principal' => 15000,
        'term_weeks' => 13,
        'term_months' => 3
    ],
    [
        'name' => 'Extended Loan (26 weeks)',
        'principal' => 35000,
        'term_weeks' => 26,
        'term_months' => 6
    ],
    [
        'name' => 'Conversational Term Test (4 months)',
        'principal' => 20000,
        'conversational_term' => '4 months'
    ]
];

foreach ($testScenarios as $scenario) {
    echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px;'>\n";
    echo "<h3>📊 {$scenario['name']}</h3>\n";
    
    // Calculate loan based on scenario type
    if (isset($scenario['conversational_term'])) {
        $calculation = $loanCalc->calculateLoanFromConversationalTerm(
            $scenario['principal'], 
            $scenario['conversational_term']
        );
        echo "<p><strong>Input:</strong> ₱" . number_format($scenario['principal']) . " for " . $scenario['conversational_term'] . "</p>\n";
    } else {
        $calculation = $loanCalc->calculateLoan(
            $scenario['principal'], 
            $scenario['term_weeks'], 
            $scenario['term_months']
        );
        echo "<p><strong>Input:</strong> ₱" . number_format($scenario['principal']) . " for {$scenario['term_weeks']} weeks ({$scenario['term_months']} months)</p>\n";
    }
    
    if ($calculation) {
        // Display calculation summary
        $summary = $loanCalc->formatCalculationSummary($calculation);
        
        echo "<table style='width: 100%; border-collapse: collapse;'>\n";
        echo "<tr style='background: #f8f9fa;'><th style='padding: 8px; border: 1px solid #dee2e6;'>Component</th><th style='padding: 8px; border: 1px solid #dee2e6;'>Amount</th></tr>\n";
        
        foreach ($summary as $key => $item) {
            if ($key === 'term_info') continue; // Skip term info in table
            
            $class = $item['class'] ?? '';
            echo "<tr><td style='padding: 8px; border: 1px solid #dee2e6;'>{$item['label']}</td>";
            echo "<td style='padding: 8px; border: 1px solid #dee2e6; {$class}'>{$item['value']}</td></tr>\n";
        }
        
        echo "</table>\n";
        
        // Show payment schedule preview (first 3 and last payment)
        $schedule = $calculation['payment_schedule'];
        echo "<h4>📅 Payment Schedule Preview</h4>\n";
        echo "<table style='width: 100%; border-collapse: collapse; font-size: 0.9em;'>\n";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 6px; border: 1px solid #dee2e6;'>Week</th>";
        echo "<th style='padding: 6px; border: 1px solid #dee2e6;'>Total Payment</th>";
        echo "<th style='padding: 6px; border: 1px solid #dee2e6;'>Principal</th>";
        echo "<th style='padding: 6px; border: 1px solid #dee2e6;'>Interest</th>";
        echo "<th style='padding: 6px; border: 1px solid #dee2e6;'>Insurance</th>";
        echo "<th style='padding: 6px; border: 1px solid #dee2e6;'>Savings</th>";
        echo "</tr>\n";
        
        // Show first 3 payments
        for ($i = 0; $i < min(3, count($schedule)); $i++) {
            $payment = $schedule[$i];
            echo "<tr>";
            echo "<td style='padding: 6px; border: 1px solid #dee2e6;'>{$payment['week']}</td>";
            echo "<td style='padding: 6px; border: 1px solid #dee2e6;'>₱" . number_format($payment['expected_payment'], 2) . "</td>";
            echo "<td style='padding: 6px; border: 1px solid #dee2e6;'>₱" . number_format($payment['principal_payment'], 2) . "</td>";
            echo "<td style='padding: 6px; border: 1px solid #dee2e6;'>₱" . number_format($payment['interest_payment'], 2) . "</td>";
            echo "<td style='padding: 6px; border: 1px solid #dee2e6;'>₱" . number_format($payment['insurance_payment'], 2) . "</td>";
            echo "<td style='padding: 6px; border: 1px solid #dee2e6;'>₱" . number_format($payment['savings_payment'], 2) . "</td>";
            echo "</tr>\n";
        }
        
        // Show ellipsis if more than 4 payments
        if (count($schedule) > 4) {
            echo "<tr><td colspan='6' style='padding: 6px; border: 1px solid #dee2e6; text-align: center; font-style: italic;'>... (" . (count($schedule) - 4) . " more weeks) ...</td></tr>\n";
        }
        
        // Show last payment
        if (count($schedule) > 3) {
            $lastPayment = end($schedule);
            echo "<tr style='background: #fff3cd;'>";
            echo "<td style='padding: 6px; border: 1px solid #dee2e6;'>{$lastPayment['week']}</td>";
            echo "<td style='padding: 6px; border: 1px solid #dee2e6;'><strong>₱" . number_format($lastPayment['expected_payment'], 2) . "</strong></td>";
            echo "<td style='padding: 6px; border: 1px solid #dee2e6;'>₱" . number_format($lastPayment['principal_payment'], 2) . "</td>";
            echo "<td style='padding: 6px; border: 1px solid #dee2e6;'>₱" . number_format($lastPayment['interest_payment'], 2) . "</td>";
            echo "<td style='padding: 6px; border: 1px solid #dee2e6;'>₱" . number_format($lastPayment['insurance_payment'], 2) . "</td>";
            echo "<td style='padding: 6px; border: 1px solid #dee2e6;'>₱" . number_format($lastPayment['savings_payment'], 2) . "</td>";
            echo "</tr>\n";
        }
        
        echo "</table>\n";
        
        // Validation check
        $totalScheduledPayments = array_sum(array_column($schedule, 'expected_payment'));
        echo "<p><small><strong>Validation:</strong> Total scheduled payments: ₱" . number_format($totalScheduledPayments, 2);
        echo " | Expected total: ₱" . number_format($calculation['total_loan_amount'], 2);
        echo " | ✅ " . (abs($totalScheduledPayments - $calculation['total_loan_amount']) < 0.01 ? 'Matches' : 'Mismatch') . "</small></p>\n";
        
    } else {
        echo "<p style='color: red;'>❌ Calculation failed: " . $loanCalc->getErrorMessage() . "</p>\n";
    }
    
    echo "</div>\n";
}

// Show available common terms
echo "<div style='border: 1px solid #17a2b8; padding: 15px; margin: 10px 0; border-radius: 5px; background: #e7f3ff;'>\n";
echo "<h3>📋 Available Loan Terms</h3>\n";
$commonTerms = $loanCalc->getCommonLoanTerms();

echo "<table style='width: 100%; border-collapse: collapse;'>\n";
echo "<tr style='background: #cce7ff;'>";
echo "<th style='padding: 8px; border: 1px solid #17a2b8;'>Term Option</th>";
echo "<th style='padding: 8px; border: 1px solid #17a2b8;'>Weeks</th>";
echo "<th style='padding: 8px; border: 1px solid #17a2b8;'>Months (for interest)</th>";
echo "<th style='padding: 8px; border: 1px solid #17a2b8;'>Description</th>";
echo "</tr>\n";

foreach ($commonTerms as $term) {
    echo "<tr>";
    echo "<td style='padding: 8px; border: 1px solid #17a2b8;'><strong>{$term['display']}</strong></td>";
    echo "<td style='padding: 8px; border: 1px solid #17a2b8;'>{$term['weeks']}</td>";
    echo "<td style='padding: 8px; border: 1px solid #17a2b8;'>{$term['months']}</td>";
    echo "<td style='padding: 8px; border: 1px solid #17a2b8;'>{$term['description']}</td>";
    echo "</tr>\n";
}

echo "</table>\n";
echo "</div>\n";

// Show calculation constants
echo "<div style='border: 1px solid #28a745; padding: 15px; margin: 10px 0; border-radius: 5px; background: #e8f5e8;'>\n";
echo "<h3>⚙️ Business Rules & Constants</h3>\n";
echo "<ul>\n";
echo "<li><strong>Interest Rate:</strong> " . (LoanCalculationService::INTEREST_RATE * 100) . "% (fixed)</li>\n";
echo "<li><strong>Insurance Fee:</strong> ₱" . number_format(LoanCalculationService::INSURANCE_FEE, 2) . " (fixed per loan)</li>\n";
echo "<li><strong>Savings Deduction:</strong> " . (LoanCalculationService::SAVINGS_RATE * 100) . "% of principal</li>\n";
echo "<li><strong>Default Terms:</strong> " . LoanCalculationService::DEFAULT_WEEKS_IN_LOAN . " weeks (" . LoanCalculationService::DEFAULT_LOAN_TERM_MONTHS . " months)</li>\n";
echo "<li><strong>Flexible:</strong> Weeks and months can be customized per loan</li>\n";
echo "<li><strong>Calculation Formula:</strong> Total = Principal + (Principal × 5% × Months) + ₱425 + (Principal × 1%)</li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "<p><em>Generated on " . date('F j, Y \a\t g:i A') . "</em></p>\n";
?>