<?php
/**
 * Simple client deletion test - minimal dependencies
 */

echo "Testing client deletion issue...\n";

// Check if we can directly access the database
try {
    // Use the existing database configuration
    require_once dirname(__FILE__) . '/app/config/config.php';
    
    $host = DB_HOST;
    $dbname = DB_NAME;
    $username = DB_USER;  
    $password = DB_PASS;
    
    echo "Connecting to database...\n";
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connected\n";
    
    // Find a client to test with
    $stmt = $pdo->query("SELECT c.id, c.name, COUNT(l.id) as loan_count 
                         FROM clients c 
                         LEFT JOIN loans l ON c.id = l.client_id 
                         GROUP BY c.id, c.name 
                         ORDER BY loan_count ASC 
                         LIMIT 1");
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        echo "❌ No clients found\n";
        exit(1);
    }
    
    $clientId = $client['id'];
    $clientName = $client['name'];
    $loanCount = $client['loan_count'];
    
    echo "Testing with Client ID: $clientId ($clientName) - Has $loanCount loans\n";
    
    // Check for active loans first
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM loans 
                           WHERE client_id = ? 
                           AND LOWER(status) IN ('application', 'approved', 'active')");
    $stmt->execute([$clientId]);
    $activeLoans = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "Active/pending loans: $activeLoans\n";
    
    if ($activeLoans > 0) {
        echo "❌ Client has active loans - deletion should be blocked\n";
    } else {
        echo "✅ No active loans - testing deletion constraints...\n";
        
        // Check what foreign key constraints might prevent deletion
        $tables_to_check = [
            'loans' => 'client_id',
            'collection_records' => 'client_id', 
            'client_documents' => 'client_id'
        ];
        
        foreach ($tables_to_check as $table => $column) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table WHERE $column = ?");
                $stmt->execute([$clientId]);
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "  - $table: $count records\n";
                
                if ($count > 0) {
                    echo "    ⚠️ This might prevent deletion due to foreign key constraints\n";
                }
            } catch (Exception $e) {
                echo "  - $table: Could not check (" . $e->getMessage() . ")\n";
            }
        }
        
        // Try to see what the actual error would be
        echo "\nTesting actual deletion attempt...\n";
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
            $result = $stmt->execute([$clientId]);
            
            if ($result && $stmt->rowCount() > 0) {
                echo "✅ Deletion would succeed (rolling back...)\n";
                $pdo->rollBack();
            } else {
                echo "❌ Deletion returned false or affected 0 rows\n";
                $pdo->rollBack();
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "❌ Deletion failed with error: " . $e->getMessage() . "\n";
            
            // Check if it's a foreign key constraint error
            if (strpos($e->getMessage(), 'foreign key constraint') !== false || 
                strpos($e->getMessage(), 'Cannot delete') !== false) {
                echo "🔍 This is a foreign key constraint violation!\n";
                echo "💡 Solution: Delete related records first or use ON DELETE CASCADE\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
}

echo "\nTest complete.\n";