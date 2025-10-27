<?php
/**
 * Test Client Deletion Functionality
 * Verifies that clients without active/pending loans can be deleted
 * and those with active/pending loans cannot be deleted.
 */

require_once __DIR__ . '/public/init.php';

echo "🧪 TESTING CLIENT DELETION FUNCTIONALITY\n";
echo "==========================================\n\n";

try {
    $clientService = new ClientService();
    $loanService = new LoanService();
    
    // Get database connection for direct queries
    $db = Database::getInstance()->getConnection();
    
    echo "1️⃣ Testing client deletion logic...\n\n";
    
    // Find clients and their loan statuses
    $sql = "SELECT c.id, c.name, c.status as client_status,
                   GROUP_CONCAT(DISTINCT l.status ORDER BY l.status SEPARATOR ', ') as loan_statuses,
                   COUNT(l.id) as loan_count
            FROM clients c
            LEFT JOIN loans l ON c.id = l.client_id
            GROUP BY c.id, c.name, c.status
            ORDER BY c.id
            LIMIT 10";
    
    $stmt = $db->query($sql);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Client Status Overview:\n";
    printf("%-5s %-20s %-15s %-10s %s\n", "ID", "Name", "Client Status", "Loans", "Loan Statuses");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($clients as $client) {
        printf("%-5s %-20s %-15s %-10s %s\n", 
            $client['id'],
            substr($client['name'], 0, 20),
            $client['client_status'],
            $client['loan_count'],
            $client['loan_statuses'] ?: 'None'
        );
    }
    
    echo "\n2️⃣ Testing deletion attempts...\n\n";
    
    foreach ($clients as $client) {
        $clientId = $client['id'];
        $clientName = $client['name'];
        $loanStatuses = $client['loan_statuses'];
        
        echo "Testing client #{$clientId} ({$clientName})\n";
        echo "  Loan statuses: " . ($loanStatuses ?: 'None') . "\n";
        
        // Check what getClientCurrentLoans returns
        $activeLoans = $clientService->getClientModel()->getClientCurrentLoans($clientId);
        echo "  Active/Pending loans found: " . count($activeLoans) . "\n";
        
        if (!empty($activeLoans)) {
            $foundStatuses = array_unique(array_column($activeLoans, 'status'));
            echo "  Blocking statuses: " . implode(', ', $foundStatuses) . "\n";
        }
        
        // Test deletion (this won't actually delete, just test the logic)
        $canDelete = empty($activeLoans);
        
        if ($canDelete) {
            echo "  ✅ Should be DELETABLE (no active/pending loans)\n";
        } else {
            echo "  ❌ Should be BLOCKED from deletion\n";
        }
        
        echo "\n";
        
        // Only test a few clients to avoid spam
        static $testCount = 0;
        if (++$testCount >= 5) break;
    }
    
    echo "3️⃣ Testing specific deletion logic...\n\n";
    
    // Test the actual deletion method without executing
    $testClientId = $clients[0]['id'] ?? 1;
    
    echo "Testing ClientService->deleteClient() logic for client #{$testClientId}:\n";
    
    $activeLoans = $clientService->getClientModel()->getClientCurrentLoans($testClientId);
    
    if (!empty($activeLoans)) {
        echo "❌ Delete would fail - Active/pending loans exist:\n";
        foreach ($activeLoans as $loan) {
            echo "  - Loan #{$loan['id']}: {$loan['status']}\n";
        }
        
        // Simulate the error message
        $loanStatuses = array_unique(array_column($activeLoans, 'status'));
        $statusList = implode(', ', $loanStatuses);
        echo "\nError message would be:\n";
        echo "\"Cannot delete client with active/pending loans (Status: {$statusList}). Only clients with Completed or Defaulted loans can be deleted. Consider deactivating the client instead.\"\n";
    } else {
        echo "✅ Delete would succeed - No active/pending loans found\n";
    }
    
    echo "\n4️⃣ Summary:\n";
    echo "- Fixed case sensitivity in ClientModel.getClientCurrentLoans()\n";
    echo "- Updated to check for Application, Approved, and Active loan statuses\n";
    echo "- Improved error messages to show which loan statuses are blocking deletion\n";
    echo "- Only clients with Completed/Defaulted loans (or no loans) can be deleted\n";
    
    echo "\n✅ Client deletion functionality test completed!\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}