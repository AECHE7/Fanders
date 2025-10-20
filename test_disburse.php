<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'test_disburse.log');

// Start output buffering immediately to prevent headers sent issues
if (ob_get_level() === 0) {
    ob_start();
}

// Open output file
$outputFile = fopen('test_disburse_output.txt', 'w');

function logOutput($message) {
    global $outputFile;
    echo $message . "\n";
    fwrite($outputFile, $message . "\n");
}

logOutput("Starting disbursement test...");

// Skip auth check for this test script
$skip_auth_check = true;

try {
    require_once 'public/init.php';
    logOutput("Init file loaded successfully");
} catch (Exception $e) {
    logOutput("Error loading init.php: " . $e->getMessage());
    fclose($outputFile);
    exit;
}

try {
    $loanService = new LoanService();
    logOutput("LoanService created successfully");
} catch (Exception $e) {
    logOutput("Error creating LoanService: " . $e->getMessage());
    fclose($outputFile);
    exit;
}

// Get an approved loan to test disbursement
$approvedLoans = $loanService->getLoansByStatus('Approved');
if (empty($approvedLoans)) {
    echo "No approved loans found to test disbursement.\n";
    echo "Creating a test approved loan...\n";

    // Create a test client first
    $clientService = new ClientService();
    $clientData = [
        'name' => 'Test Client for Disbursement',
        'email' => 'test_disburse@example.com',
        'phone_number' => '09123456789',
        'address' => 'Test Address',
        'identification_type' => 'National ID',
        'identification_number' => 'TEST123456',
        'date_of_birth' => '1990-01-01'
    ];
    $clientId = $clientService->create($clientData);

    if (!$clientId) {
        echo "Failed to create test client: " . $clientService->getErrorMessage() . "\n";
        exit;
    }

    echo "Created test client ID: $clientId\n";

    // Create a test loan application
    $loanData = [
        'client_id' => $clientId,
        'principal' => 5000.00
    ];
    $loanId = $loanService->applyForLoan($loanData, 1); // User ID 1

    if (!$loanId) {
        echo "Failed to create test loan: " . $loanService->getErrorMessage() . "\n";
        exit;
    }

    echo "Created test loan ID: $loanId\n";

    // Approve the loan
    $approved = $loanService->approveLoan($loanId, 1);
    if (!$approved) {
        echo "Failed to approve test loan: " . $loanService->getErrorMessage() . "\n";
        exit;
    }

    echo "Approved test loan ID: $loanId\n";

    // Get the approved loan
    $approvedLoans = $loanService->getLoansByStatus('Approved');
    if (empty($approvedLoans)) {
        echo "Still no approved loans after creating test data.\n";
        exit;
    }
}

$testLoan = $approvedLoans[0];
echo "Testing disbursement for Loan ID: {$testLoan['id']}, Status: {$testLoan['status']}\n";

// Attempt disbursement
$disbursedBy = 1; // Assuming user ID 1 exists
$success = $loanService->disburseLoan($testLoan['id'], $disbursedBy);

if ($success) {
    echo "Disbursement successful!\n";

    // Check updated status
    $updatedLoan = $loanService->getLoanWithClient($testLoan['id']);
    echo "Updated Status: {$updatedLoan['status']}\n";
    echo "Disbursement Date: {$updatedLoan['disbursement_date']}\n";
} else {
    echo "Disbursement failed: " . $loanService->getErrorMessage() . "\n";
}
