<?php
require_once __DIR__ . '/public/init.php';

$loanModel = new LoanModel();

// Check distinct statuses
echo "=== CHECKING LOAN STATUSES ===\n\n";

$db = Database::getInstance();
$conn = $db->getConnection();

try {
    $stmt = $conn->query("SELECT DISTINCT status FROM loans ORDER BY status");
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Distinct loan statuses found:\n";
    foreach ($statuses as $status) {
        echo "  - '" . $status . "' (length: " . strlen($status) . ", trimmed: '" . trim($status) . "')\n";
    }
    echo "\n";
    
    // Check if any client has an active loan
    echo "=== CHECKING ACTIVE LOANS ===\n\n";
    $stmt = $conn->query("SELECT id, client_id, status FROM loans WHERE LOWER(status) = 'active' LIMIT 5");
    $activeLoans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($activeLoans)) {
        echo "No loans with status 'Active' (case-insensitive) found.\n";
    } else {
        echo "Found " . count($activeLoans) . " active loans:\n";
        foreach ($activeLoans as $loan) {
            echo "  - Loan ID: {$loan['id']}, Client ID: {$loan['client_id']}, Status: '{$loan['status']}'\n";
        }
    }
    echo "\n";
    
    // Test the getClientActiveLoan method
    echo "=== TESTING getClientActiveLoan METHOD ===\n\n";
    
    // Get all client IDs
    $stmt = $conn->query("SELECT DISTINCT client_id FROM loans LIMIT 10");
    $clientIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($clientIds as $clientId) {
        $activeLoan = $loanModel->getClientActiveLoan($clientId);
        if ($activeLoan) {
            echo "Client $clientId has ACTIVE loan: Loan ID {$activeLoan['id']}, Status: '{$activeLoan['status']}'\n";
        } else {
            // Check what loans this client actually has
            $stmt = $conn->prepare("SELECT id, status FROM loans WHERE client_id = ? ORDER BY created_at DESC LIMIT 3");
            $stmt->execute([$clientId]);
            $clientLoans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "Client $clientId has NO active loan. Recent loans: ";
            if (empty($clientLoans)) {
                echo "NONE\n";
            } else {
                $loanInfo = array_map(function($l) { return "ID {$l['id']} ({$l['status']})"; }, $clientLoans);
                echo implode(", ", $loanInfo) . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
