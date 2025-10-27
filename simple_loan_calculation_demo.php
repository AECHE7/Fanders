<?php
/**
 * Simple Loan Calculation Demonstration
 * Shows the calculation formulas and sample calculations
 */

// Display the enhanced loan calculation constants and formulas
echo "🏦 FANDERS ENHANCED LOAN CALCULATION SYSTEM\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Business Rules
echo "📋 BUSINESS RULES (FIXED RATES):\n";
echo "- Interest Rate: 5% per month\n";
echo "- Insurance Fee: ₱425.00 (one-time)\n";
echo "- Savings Deduction: 1% of principal\n";
echo "- Payment Frequency: Weekly\n\n";

// Calculation Formula
echo "🧮 CALCULATION FORMULA:\n";
echo "Total Loan Amount = Principal + Interest + Insurance + Savings\n";
echo "Where:\n";
echo "- Interest = Principal × 5% × Number of Months\n";
echo "- Insurance = ₱425.00 (fixed)\n";
echo "- Savings = Principal × 1%\n";
echo "- Weekly Payment = Total Loan Amount ÷ Number of Weeks\n\n";

// Sample Calculations
$samples = [
    ['principal' => 25000, 'weeks' => 17, 'months' => 4, 'name' => 'Standard 4-Month Loan'],
    ['principal' => 15000, 'weeks' => 13, 'months' => 3, 'name' => 'Short-Term 3-Month Loan'],
    ['principal' => 35000, 'weeks' => 26, 'months' => 6, 'name' => 'Extended 6-Month Loan']
];

echo "💡 SAMPLE CALCULATIONS:\n";
echo str_repeat("-", 70) . "\n";

foreach ($samples as $sample) {
    $principal = $sample['principal'];
    $weeks = $sample['weeks'];
    $months = $sample['months'];
    
    // Calculate components
    $interest = $principal * 0.05 * $months;
    $insurance = 425.00;
    $savings = $principal * 0.01;
    $total = $principal + $interest + $insurance + $savings;
    $weeklyPayment = $total / $weeks;
    
    echo "\n📊 {$sample['name']}\n";
    echo "Principal Amount: ₱" . number_format($principal, 2) . "\n";
    echo "Term: {$weeks} weeks ({$months} months)\n";
    echo "\nBreakdown:\n";
    echo "- Principal: ₱" . number_format($principal, 2) . "\n";
    echo "- Interest (5% × {$months} months): ₱" . number_format($interest, 2) . "\n";
    echo "- Insurance Fee: ₱" . number_format($insurance, 2) . "\n";
    echo "- Savings (1%): ₱" . number_format($savings, 2) . "\n";
    echo "- TOTAL LOAN AMOUNT: ₱" . number_format($total, 2) . "\n";
    echo "- Weekly Payment: ₱" . number_format($weeklyPayment, 2) . "\n";
    echo str_repeat("-", 50) . "\n";
}

echo "\n🎯 KEY FEATURES:\n";
echo "✅ Fixed business rates (5% interest, ₱425 insurance, 1% savings)\n";
echo "✅ Flexible loan terms (weeks and months are user-configurable)\n";
echo "✅ Conversational term parsing ('3 months', '17 weeks', etc.)\n";
echo "✅ Detailed payment schedule generation\n";
echo "✅ Component-wise calculation breakdown\n";
echo "✅ Validation and error handling\n\n";

echo "📈 COMMON LOAN TERMS:\n";
$commonTerms = [
    ['display' => '3 months', 'weeks' => 13, 'months' => 3],
    ['display' => '4 months', 'weeks' => 17, 'months' => 4],
    ['display' => '5 months', 'weeks' => 22, 'months' => 5],
    ['display' => '6 months', 'weeks' => 26, 'months' => 6]
];

foreach ($commonTerms as $term) {
    echo "- {$term['display']}: {$term['weeks']} weeks\n";
}

echo "\n✨ IMPLEMENTATION COMPLETE!\n";
echo "Ready to integrate with loan creation forms and database.\n\n";

echo "Generated on " . date('F j, Y \a\t g:i A') . "\n";
?>