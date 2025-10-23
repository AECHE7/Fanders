<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Load configuration
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "🔧 Enabling auto-generation of SLR documents on loan disbursement...\n\n";
    
    // Update the loan_disbursement rule to enable auto_generate
    $sql = "UPDATE slr_generation_rules 
            SET auto_generate = true,
                updated_at = CURRENT_TIMESTAMP
            WHERE trigger_event = 'loan_disbursement'";
    
    $pdo->exec($sql);
    
    echo "✅ Successfully enabled auto-generation!\n\n";
    
    // Verify the change
    echo "📋 Current configuration:\n";
    $stmt = $pdo->query("SELECT rule_name, trigger_event, auto_generate, is_active FROM slr_generation_rules ORDER BY id");
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rules as $rule) {
        echo "\n- Rule: " . $rule['rule_name'] . "\n";
        echo "  Trigger: " . $rule['trigger_event'] . "\n";
        echo "  Auto-generate: " . ($rule['auto_generate'] === 't' || $rule['auto_generate'] === true ? '✅ ENABLED' : '❌ DISABLED') . "\n";
        echo "  Active: " . ($rule['is_active'] === 't' || $rule['is_active'] === true ? '✅ YES' : '❌ NO') . "\n";
    }
    
    echo "\n\n🎉 SLR documents will now be automatically generated when loans are disbursed!\n";
    echo "The relationship loan_id in slr_documents table is already in place.\n";
    echo "SLR documents will appear in the SLR list immediately after loan disbursement.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
