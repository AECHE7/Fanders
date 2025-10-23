<?php
/**
 * Fix Loan Status Case Issue
 */

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

try {
    echo "🔧 Fixing Loan Status Case Issue - " . date('Y-m-d H:i:s') . "\n";
    echo str_repeat('=', 60) . "\n\n";
    
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Check current loan statuses
    echo "1. Checking current loan statuses...\n";
    $sql = "SELECT DISTINCT status, COUNT(*) as count FROM loans GROUP BY status ORDER BY status";
    $stmt = $connection->query($sql);
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Current statuses in database:\n";
    foreach ($statuses as $status) {
        echo "      • '{$status['status']}' - {$status['count']} loans\n";
    }
    echo "\n";
    
    // Check LoanModel constants
    echo "2. Checking LoanModel status constants...\n";
    require_once __DIR__ . '/app/models/LoanModel.php';
    
    $reflection = new ReflectionClass('LoanModel');
    $constants = $reflection->getConstants();
    
    echo "   Expected statuses from LoanModel:\n";
    foreach ($constants as $name => $value) {
        if (strpos($name, 'STATUS_') === 0) {
            echo "      • {$name} = '{$value}'\n";
        }
    }
    echo "\n";
    
    // Fix the status case issue
    echo "3. Fixing status case issues...\n";
    
    $fixes = [
        'approved' => 'Approved',
        'active' => 'Active', 
        'completed' => 'Completed',
        'pending' => 'Pending',
        'rejected' => 'Rejected'
    ];
    
    foreach ($fixes as $incorrect => $correct) {
        $sql = "UPDATE loans SET status = ? WHERE status = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$correct, $incorrect]);
        $affected = $stmt->rowCount();
        
        if ($affected > 0) {
            echo "   ✅ Updated {$affected} loans from '{$incorrect}' to '{$correct}'\n";
        }
    }
    echo "\n";
    
    // Verify the fix
    echo "4. Verifying loan #15 status...\n";
    $sql = "SELECT id, status FROM loans WHERE id = 15";
    $stmt = $connection->query($sql);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($loan) {
        echo "   📊 Loan #{$loan['id']} status: '{$loan['status']}'\n";
        
        // Test if it matches the constant
        if ($loan['status'] === LoanModel::STATUS_APPROVED) {
            echo "   ✅ Status matches LoanModel::STATUS_APPROVED\n";
            echo "   🚀 Disbursement should now work!\n";
        } else {
            echo "   ❌ Status still doesn't match LoanModel::STATUS_APPROVED ('{LoanModel::STATUS_APPROVED}')\n";
        }
    }
    echo "\n";
    
    // Show final status summary
    echo "5. Final status summary...\n";
    $sql = "SELECT DISTINCT status, COUNT(*) as count FROM loans GROUP BY status ORDER BY status";
    $stmt = $connection->query($sql);
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Updated statuses in database:\n";
    foreach ($statuses as $status) {
        echo "      • '{$status['status']}' - {$status['count']} loans\n";
    }
    
    echo "\n🎯 LOAN DISBURSEMENT SHOULD NOW WORK!\n";
    echo "   The status case mismatch has been fixed.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}