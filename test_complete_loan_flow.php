<?php
/**
 * Complete Loan Flow Test
 * Simulates the entire two-step loan creation process
 */

require_once 'public/init.php';

echo "=== COMPLETE LOAN SUBMISSION FLOW TEST ===\n\n";

// Initialize services
$loanService = new LoanService();
$loanCalculationService = new LoanCalculationService();
$clientService = new ClientService();
$userModel = new UserModel();

// Get first active client
$allClients = $clientService->getAll();
if (empty($allClients)) {
    echo "ERROR: No clients in database\n";
    exit;
}

$testClient = $allClients[0];
$clientId = $testClient['id'];
$clientName = $testClient['name'] ?? 'Unknown';

// Get first active user (preferably staff)
$allUsers = $userModel->getAll();
if (empty($allUsers)) {
    echo "ERROR: No users in database\n";
    exit;
}

$testUser = $allUsers[0];
$userId = $testUser['id'];
$userName = $testUser['name'] ?? 'Unknown';

echo "Test Configuration:\n";
echo "  Client: ID=$clientId, Name=$clientName, Status={$testClient['status']}\n";
echo "  User:   ID=$userId, Name=$userName, Role={$testUser['role']}\n\n";

// Test data
$principal = 5000;
$termWeeks = 17;

// ===== STEP 1: CHECK CLIENT ELIGIBILITY =====
echo "STEP 1: Check Client Eligibility\n";
echo "  Checking if client can apply for loan...\n";

if (!$loanService->canClientApplyForLoan($clientId)) {
    echo "  ✗ FAILED: " . $loanService->getErrorMessage() . "\n";
    exit;
}
echo "  ✓ Client is eligible\n\n";

// ===== STEP 2: FIRST CALCULATION (As if Calculate button was clicked) =====
echo "STEP 2: First Calculation (Calculate Button)\n";
echo "  Input: Principal=₱$principal, Term=$termWeeks weeks\n";

$calc1 = $loanCalculationService->calculateLoan($principal, $termWeeks);
if (!$calc1) {
    echo "  ✗ FAILED: " . $loanCalculationService->getErrorMessage() . "\n";
    exit;
}

echo "  ✓ Calculation successful\n";
echo "    - Total Amount: ₱" . number_format($calc1['total_loan_amount'], 2) . "\n";
echo "    - Weekly Payment: ₱" . number_format($calc1['weekly_payment_base'], 2) . "\n";
echo "    - Term: " . $calc1['term_weeks'] . " weeks\n\n";

// ===== STEP 3: VALIDATE DATA FOR SUBMISSION =====
echo "STEP 3: Validate Data for Submission\n";

$loanData = [
    'client_id' => $clientId,
    'principal' => $principal,
    'term_weeks' => $termWeeks
];

echo "  Data to submit:\n";
echo "    - client_id: {$loanData['client_id']} (type: " . gettype($loanData['client_id']) . ")\n";
echo "    - principal: {$loanData['principal']} (type: " . gettype($loanData['principal']) . ")\n";
echo "    - term_weeks: {$loanData['term_weeks']} (type: " . gettype($loanData['term_weeks']) . ")\n";
echo "    - user_id: $userId (type: " . gettype($userId) . ")\n\n";

// ===== STEP 4: APPLY FOR LOAN (Submit Button simulation) =====
echo "STEP 4: Apply for Loan (Submit Button)\n";
echo "  Calling LoanService::applyForLoan()...\n\n";

$newLoanId = $loanService->applyForLoan($loanData, $userId);

if (!$newLoanId) {
    echo "  ✗ LOAN CREATION FAILED\n";
    echo "  Error Message: " . $loanService->getErrorMessage() . "\n\n";
    exit;
}

echo "  ✓ LOAN CREATED SUCCESSFULLY\n";
echo "  New Loan ID: $newLoanId\n\n";

// ===== STEP 5: VERIFY LOAN IN DATABASE =====
echo "STEP 5: Verify Loan in Database\n";

$createdLoan = $loanService->getLoanWithClient($newLoanId);
if (!$createdLoan) {
    echo "  ✗ ERROR: Loan not found in database after creation\n";
    exit;
}

echo "  ✓ Loan verified in database\n";
echo "    - Loan ID: {$createdLoan['id']}\n";
echo "    - Client: {$createdLoan['client_name']}\n";
echo "    - Principal: ₱" . number_format($createdLoan['principal'], 2) . "\n";
echo "    - Total Amount: ₱" . number_format($createdLoan['total_loan_amount'], 2) . "\n";
echo "    - Status: {$createdLoan['status']}\n";
echo "    - Created: {$createdLoan['created_at']}\n\n";

echo "=== ALL TESTS PASSED ===\n";
echo "The loan creation flow is working correctly!\n";
echo "If submissions still fail in the browser, the issue may be:\n";
echo "  - CSRF token validation\n";
echo "  - Form field data not being passed correctly\n";
echo "  - Browser-side validation blocking submission\n";
echo "  - JavaScript errors in the form\n";
?>
