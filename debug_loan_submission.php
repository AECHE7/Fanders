<?php
/**
 * Comprehensive Loan Submission Debugging Tool
 * Helps identify where the loan submission is failing
 */

require_once 'public/init.php';

$loanService = new LoanService();
$loanCalculationService = new LoanCalculationService();
$clientService = new ClientService();

echo "=== LOAN SUBMISSION DEBUGGING ===\n\n";

// Test with sample data
$testClient = $clientService->getAll()[0] ?? null;
if (!$testClient) {
    echo "ERROR: No clients found in database. Create a client first.\n";
    exit;
}

$clientId = $testClient['id'];
$principal = 5000;
$termWeeks = 17;

echo "Test Data:\n";
echo "  Client ID: $clientId\n";
echo "  Principal: $principal\n";
echo "  Term Weeks: $termWeeks\n\n";

// Step 1: Check if client is eligible
echo "Step 1: Check if client can apply for loan\n";
if ($loanService->canClientApplyForLoan($clientId)) {
    echo "  ✓ Client is eligible\n\n";
} else {
    echo "  ✗ Client is NOT eligible\n";
    echo "  Error: " . $loanService->getErrorMessage() . "\n\n";
    exit;
}

// Step 2: Validate loan data
echo "Step 2: Validate loan data internally\n";
$valid = true;
$errors = [];

if ($clientId <= 0) {
    $valid = false;
    $errors[] = "client_id is invalid (must be > 0)";
}

if (!$clientService->getById($clientId)) {
    $valid = false;
    $errors[] = "Client does not exist in database";
}

if ($principal < 1000 || $principal > 50000) {
    $valid = false;
    $errors[] = "Principal amount out of range (must be 1000-50000)";
}

if ($termWeeks < 4 || $termWeeks > 52) {
    $valid = false;
    $errors[] = "Term weeks out of range (must be 4-52)";
}

if ($valid) {
    echo "  ✓ All validation checks passed\n\n";
} else {
    echo "  ✗ Validation failed:\n";
    foreach ($errors as $error) {
        echo "    - $error\n";
    }
    echo "\n";
}

// Step 3: Test calculation
echo "Step 3: Test loan calculation\n";
$calculation = $loanCalculationService->calculateLoan($principal, $termWeeks);
if ($calculation) {
    echo "  ✓ Calculation successful\n";
    echo "    Total amount: ₱" . number_format($calculation['total_loan_amount'], 2) . "\n";
    echo "    Weekly payment: ₱" . number_format($calculation['weekly_payment_base'], 2) . "\n\n";
} else {
    echo "  ✗ Calculation failed\n";
    echo "    Error: " . $loanCalculationService->getErrorMessage() . "\n\n";
    exit;
}

// Step 4: Test service apply
echo "Step 4: Test LoanService::applyForLoan()\n";
$userId = $_SESSION['user_id'] ?? 1;
$loanData = [
    'client_id' => $clientId,
    'principal' => $principal,
    'term_weeks' => $termWeeks
];

echo "  Input data:\n";
echo "    client_id: {$loanData['client_id']} (type: " . gettype($loanData['client_id']) . ")\n";
echo "    principal: {$loanData['principal']} (type: " . gettype($loanData['principal']) . ")\n";
echo "    term_weeks: {$loanData['term_weeks']} (type: " . gettype($loanData['term_weeks']) . ")\n";
echo "\n";

// Don't actually create - just test the method logic
$result = $loanService->applyForLoan($loanData, $userId);
if ($result) {
    echo "  ✓ Loan application successful\n";
    echo "    New Loan ID: $result\n";
} else {
    echo "  ✗ Loan application failed\n";
    echo "    Error: " . $loanService->getErrorMessage() . "\n";
}

echo "\n=== END DEBUG ===\n";
?>
