<?php
require_once 'app/config/config.php';
require_once 'app/core/Database.php';
require_once 'app/models/LoanModel.php';

$loanModel = new LoanModel();

// Test getClientByLoanId method
echo "Testing getClientByLoanId method:\n";

// Test with a valid loan ID (assuming loan ID 1 exists)
$client = $loanModel->getClientByLoanId(1);
if ($client) {
    echo "Client found for loan ID 1: " . $client['name'] . "\n";
} else {
    echo "No client found for loan ID 1 (this is expected if no data exists)\n";
}

// Test with an invalid loan ID
$client = $loanModel->getClientByLoanId(99999);
if (!$client) {
    echo "No client found for invalid loan ID 99999 (expected)\n";
} else {
    echo "Unexpected: Client found for invalid loan ID\n";
}

echo "LoanModel test completed.\n";
?>
