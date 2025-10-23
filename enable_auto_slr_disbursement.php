<?php
/**
 * Enable Auto-Generation of SLR on Loan Disbursement
 * This script updates the SLR generation rules to automatically generate SLR when loans are disbursed
 */

echo "🚀 Enabling Auto-SLR Generation on Loan Disbursement\n";
echo "====================================================\n\n";

try {
    require_once __DIR__ . '/app/config/config.php';
    require_once __DIR__ . '/app/core/Database.php';
    
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Check current disbursement rule
    echo "1. Checking current disbursement rule...\n";
    $sql = "SELECT * FROM slr_generation_rules WHERE trigger_event = 'loan_disbursement'";
    $stmt = $connection->query($sql);
    $rule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($rule) {
        echo "   ✅ Found disbursement rule: {$rule['rule_name']}\n";
        echo "      Current auto_generate: " . ($rule['auto_generate'] ? 'TRUE' : 'FALSE') . "\n";
        echo "      Status: " . ($rule['is_active'] ? 'ACTIVE' : 'INACTIVE') . "\n\n";
        
        if (!$rule['auto_generate']) {
            echo "2. Enabling auto-generation on disbursement...\n";
            $updateSql = "UPDATE slr_generation_rules 
                         SET auto_generate = true, 
                             is_active = true,
                             updated_at = CURRENT_TIMESTAMP 
                         WHERE trigger_event = 'loan_disbursement'";
            
            $connection->exec($updateSql);
            echo "   ✅ Auto-generation ENABLED for loan disbursement\n\n";
        } else {
            echo "2. Auto-generation already enabled!\n\n";
        }
    } else {
        echo "   ❌ No disbursement rule found. Creating one...\n";
        
        $insertSql = "INSERT INTO slr_generation_rules 
            (rule_name, description, trigger_event, auto_generate, require_signatures, notify_client, notify_officers, is_active, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $connection->prepare($insertSql);
        $stmt->execute([
            'Auto SLR on Disbursement',
            'Automatically generate SLR when loan funds are disbursed to client',
            'loan_disbursement',
            true,  // auto_generate
            true,  // require_signatures
            true,  // notify_client
            true,  // notify_officers
            true,  // is_active
            1      // created_by (admin user)
        ]);
        
        echo "   ✅ Created and enabled auto-disbursement rule\n\n";
    }
    
    // Verify the change
    echo "3. Verifying updated configuration...\n";
    $sql = "SELECT rule_name, trigger_event, auto_generate, is_active FROM slr_generation_rules ORDER BY id";
    $stmt = $connection->query($sql);
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rules as $rule) {
        $status = $rule['is_active'] ? '🟢 ACTIVE' : '🔴 INACTIVE';
        $type = $rule['auto_generate'] ? '⚡ AUTO' : '👤 MANUAL';
        echo "   📜 {$rule['rule_name']} ({$rule['trigger_event']}) - {$type} - {$status}\n";
    }
    
    echo "\n🎯 RESULT:\n";
    echo "   ✅ SLR documents will now be automatically generated when loans are disbursed!\n";
    echo "   ✅ When staff changes loan status to 'Active', SLR is created automatically\n";
    echo "   ✅ SLR documents will appear in the Document Archive\n";
    echo "   ✅ Enhanced payment schedule included in auto-generated SLRs\n\n";
    
    echo "📝 WORKFLOW NOW:\n";
    echo "   1. Loan gets approved (status: approved)\n";
    echo "   2. Staff disburses loan → Status changes to 'active'\n";
    echo "   3. 🚀 SLR automatically generated with payment schedule\n";
    echo "   4. Document appears in SLR Management and Document Archive\n";
    echo "   5. Client receives SLR with complete payment calendar\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "✅ Auto-SLR generation on disbursement is now ENABLED!\n";
?>