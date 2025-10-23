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
    echo "📋 Checking slr_generation_rules table...\n\n";
    
    $stmt = $pdo->query("SELECT rule_name, trigger_event, auto_generate, is_active FROM slr_generation_rules ORDER BY id");
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rules)) {
        echo "⚠️  No rules found in slr_generation_rules table!\n\n";
        echo "This is why SLR documents are not being generated automatically.\n";
    } else {
        echo "Found " . count($rules) . " rule(s):\n";
        foreach ($rules as $rule) {
            echo "\n- Rule: " . $rule['rule_name'] . "\n";
            echo "  Trigger: " . $rule['trigger_event'] . "\n";
            echo "  Auto-generate: " . ($rule['auto_generate'] === 't' || $rule['auto_generate'] === true ? 'YES' : 'NO') . "\n";
            echo "  Active: " . ($rule['is_active'] === 't' || $rule['is_active'] === true ? 'YES' : 'NO') . "\n";
        }
    }
    
    echo "\n📋 Checking if loan_disbursement rule exists...\n";
    $stmt = $pdo->prepare("SELECT * FROM slr_generation_rules WHERE trigger_event = 'loan_disbursement' AND is_active = true");
    $stmt->execute();
    $disbursementRule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$disbursementRule) {
        echo "❌ No active loan_disbursement rule found!\n";
        echo "\nThis is the problem. The code is ready but the rule is not configured.\n";
    } else {
        echo "✅ Found active loan_disbursement rule\n";
        echo "   Auto-generate: " . ($disbursementRule['auto_generate'] === 't' || $disbursementRule['auto_generate'] === true ? 'ENABLED' : 'DISABLED') . "\n";
    }
    
    echo "\n📋 Checking slr_documents table structure...\n";
    $stmt = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'slr_documents' AND column_name = 'loan_id'");
    $loanColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($loanColumn) {
        echo "✅ loan_id column exists in slr_documents table\n";
    } else {
        echo "❌ loan_id column missing in slr_documents table\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
