<?php
/**
 * Debug Client Deletion Issue
 * This script will test the deletion process step by step to identify the exact failure point
 */

// Start output buffering to prevent header issues
ob_start();

echo "🔍 DEBUG CLIENT DELETION ISSUE\n";
echo "===============================\n\n";

// Define constants manually
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__FILE__));
}

try {
    require_once BASE_PATH . '/public/init.php';
    echo "✅ Initialization successful\n";
} catch (Exception $e) {
    echo "❌ Init failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n1️⃣ Testing database connection...\n";
try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    echo "✅ Database connection successful\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n2️⃣ Finding a test client to examine...\n";
try {
    $sql = "SELECT c.id, c.name, c.status, 
                   COUNT(l.id) as total_loans,
                   GROUP_CONCAT(DISTINCT l.status) as loan_statuses
            FROM clients c 
            LEFT JOIN loans l ON c.id = l.client_id 
            GROUP BY c.id, c.name, c.status 
            ORDER BY total_loans ASC, c.id ASC 
            LIMIT 5";
    
    $stmt = $connection->query($sql);
    $testClients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($testClients)) {
        echo "❌ No clients found in database\n";
        exit(1);
    }
    
    echo "📋 Available test clients:\n";
    foreach ($testClients as $client) {
        printf("ID: %d, Name: %s, Status: %s, Loans: %d (%s)\n", 
            $client['id'], 
            $client['name'], 
            $client['status'],
            $client['total_loans'],
            $client['loan_statuses'] ?: 'None'
        );
    }
    
    // Pick the client with the fewest loans for testing
    $testClient = $testClients[0];
    $testClientId = $testClient['id'];
    
    echo "\n🎯 Testing with Client ID: {$testClientId} ({$testClient['name']})\n";
    
} catch (Exception $e) {
    echo "❌ Failed to query clients: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n3️⃣ Testing ClientService deletion logic...\n";
try {
    $clientService = new ClientService();
    
    // Step 1: Check if client has active loans
    echo "   Checking for active/pending loans...\n";
    $clientModel = $clientService->getClientModel();
    $activeLoans = $clientModel->getClientCurrentLoans($testClientId);
    
    echo "   Found " . count($activeLoans) . " active/pending loans\n";
    if (!empty($activeLoans)) {
        foreach ($activeLoans as $loan) {
            echo "   - Loan #{$loan['id']}: {$loan['status']}\n";
        }
        echo "   ⚠️ Client has active loans - deletion should be blocked\n";
    } else {
        echo "   ✅ No active loans found - deletion should be allowed\n";
    }
    
    // Step 2: Test the service deletion logic (without actually deleting)
    echo "\n   Testing ClientService->deleteClient() logic...\n";
    if (!empty($activeLoans)) {
        echo "   ❌ Deletion would be blocked by business logic\n";
        
        // Show what the error message would be
        $loanStatuses = array_unique(array_column($activeLoans, 'status'));
        $statusList = implode(', ', $loanStatuses);
        echo "   Error message: \"Cannot delete client with active/pending loans (Status: {$statusList}). Only clients with Completed or Defaulted loans can be deleted. Consider deactivating the client instead.\"\n";
    } else {
        echo "   ✅ Business logic allows deletion\n";
        
        // Test the actual BaseModel delete method
        echo "\n   Testing BaseModel->delete() method...\n";
        
        // First, let's check what foreign key constraints might exist
        echo "   Checking for foreign key constraints...\n";
        
        try {
            // Check if there are any related records that might prevent deletion
            $fkChecks = [
                'loans' => "SELECT COUNT(*) as count FROM loans WHERE client_id = ?",
                'payments' => "SELECT COUNT(*) as count FROM payments p JOIN loans l ON p.loan_id = l.id WHERE l.client_id = ?",
                'collection_records' => "SELECT COUNT(*) as count FROM collection_records WHERE client_id = ?",
                'client_documents' => "SELECT COUNT(*) as count FROM client_documents WHERE client_id = ?"
            ];
            
            foreach ($fkChecks as $table => $query) {
                try {
                    $stmt = $connection->prepare($query);
                    $stmt->execute([$testClientId]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $count = $result['count'] ?? 0;
                    
                    if ($count > 0) {
                        echo "   ⚠️ Found {$count} related records in {$table}\n";
                    } else {
                        echo "   ✅ No related records in {$table}\n";
                    }
                } catch (Exception $e) {
                    echo "   ⚠️ Could not check {$table}: " . $e->getMessage() . "\n";
                }
            }
            
        } catch (Exception $e) {
            echo "   ❌ Foreign key check failed: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ClientService test failed: " . $e->getMessage() . "\n";
}

echo "\n4️⃣ Simulating the actual deletion attempt...\n";

try {
    // Let's try to understand what happens in the BaseModel delete method
    echo "   Simulating BaseModel->delete() call...\n";
    
    $clientModel = new ClientModel();
    
    // Check the table name
    $reflection = new ReflectionClass($clientModel);
    $tableProperty = $reflection->getProperty('table');
    $tableProperty->setAccessible(true);
    $tableName = $tableProperty->getValue($clientModel);
    
    echo "   Table name: {$tableName}\n";
    
    // Try a dry-run query to see what would happen
    $sql = "DELETE FROM {$tableName} WHERE id = ?";
    echo "   SQL would be: {$sql}\n";
    echo "   Parameters: [{$testClientId}]\n";
    
    // Check if we can run a SELECT to make sure the client exists
    $checkSql = "SELECT id, name FROM {$tableName} WHERE id = ?";
    $stmt = $connection->prepare($checkSql);
    $stmt->execute([$testClientId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($client) {
        echo "   ✅ Client exists: ID {$client['id']}, Name: {$client['name']}\n";
    } else {
        echo "   ❌ Client not found with ID {$testClientId}\n";
    }
    
    // Now let's see if we can understand why the delete might fail
    echo "\n   Testing actual delete operation (if safe)...\n";
    
    if (!empty($activeLoans)) {
        echo "   ⚠️ Skipping actual delete test due to active loans\n";
    } else {
        echo "   ⚠️ Would test actual deletion here, but skipping to avoid data loss\n";
        echo "   💡 The issue might be:\n";
        echo "      - Foreign key constraints\n";
        echo "      - Database connection issues\n";
        echo "      - Transaction rollbacks\n";
        echo "      - Silent MySQL errors\n";
    }
    
} catch (Exception $e) {
    echo "❌ Simulation failed: " . $e->getMessage() . "\n";
}

echo "\n5️⃣ Recommendations:\n";
echo "   1. Check MySQL error logs for foreign key constraint violations\n";
echo "   2. Verify that all related data is properly handled before deletion\n";
echo "   3. Consider implementing soft deletes instead of hard deletes\n";
echo "   4. Add better error logging to the BaseModel delete method\n";

echo "\n✅ Debug analysis complete!\n";

// Clean output buffer
ob_end_clean();