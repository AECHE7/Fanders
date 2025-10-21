<?php
/**
 * Debug test for loan submission flow
 * Tests the two-step form: Calculate -> Submit
 */

session_start();

// Simulate the first submission (Calculate button)
echo "=== STEP 1: Calculate Button Submission ===\n";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['client_id'] = '1';
$_POST['loan_amount'] = '5000';
$_POST['loan_term'] = '17';
$_POST['calculate'] = 'Calculate'; // This is what the Calculate button sends

// Check what happens
echo "POST data after Calculate:\n";
echo "  client_id: " . $_POST['client_id'] . "\n";
echo "  loan_amount: " . $_POST['loan_amount'] . "\n";
echo "  loan_term: " . $_POST['loan_term'] . "\n";
echo "  calculate: " . ($_POST['calculate'] ?? 'NOT SET') . "\n";
echo "  submit_loan: " . ($_POST['submit_loan'] ?? 'NOT SET') . "\n";

echo "\nChecking conditions:\n";
echo "  isset(\$_POST['submit_loan']): " . (isset($_POST['submit_loan']) ? 'TRUE' : 'FALSE') . "\n";
echo "  isset(\$_POST['calculate']): " . (isset($_POST['calculate']) ? 'TRUE' : 'FALSE') . "\n";

echo "\n=== STEP 2: Submit Button Submission (Preview Form) ===\n";
// Reset and simulate the second submission (Submit button from preview)
$_POST = [];
$_POST['client_id'] = '1';
$_POST['loan_amount'] = '5000';
$_POST['loan_term'] = '17';
$_POST['submit_loan'] = 'Submit Loan Application'; // This is from the hidden form's submit button

echo "POST data after Submit:\n";
echo "  client_id: " . $_POST['client_id'] . "\n";
echo "  loan_amount: " . $_POST['loan_amount'] . "\n";
echo "  loan_term: " . $_POST['loan_term'] . "\n";
echo "  calculate: " . ($_POST['calculate'] ?? 'NOT SET') . "\n";
echo "  submit_loan: " . ($_POST['submit_loan'] ?? 'NOT SET') . "\n";

echo "\nChecking conditions:\n";
echo "  isset(\$_POST['submit_loan']): " . (isset($_POST['submit_loan']) ? 'TRUE' : 'FALSE') . "\n";
echo "  isset(\$_POST['calculate']): " . (isset($_POST['calculate']) ? 'TRUE' : 'FALSE') . "\n";

echo "\n=== ANALYSIS ===\n";
echo "The flow should be:\n";
echo "1. User fills form → Clicks Calculate\n";
echo "2. POST sent with 'calculate' button → Server validates and calculates\n";
echo "3. Preview shown with 'submit_loan' hidden form\n";
echo "4. User clicks Submit → POST sent with 'submit_loan'\n";
echo "5. Server detects 'submit_loan' and creates the loan\n";
echo "\nPOSSIBLE ISSUES:\n";
echo "- The Calculate button may not be triggering a POST correctly\n";
echo "- The hidden form may not be submitting all required fields\n";
echo "- The CSRF token validation might be failing silently\n";
echo "- The JavaScript may be preventing form submission\n";
?>
