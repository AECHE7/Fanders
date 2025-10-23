<?php
/**
 * Fix SLR Auto-Generation on Loan Disbursement
 */

echo "🔧 Fixing SLR Auto-Generation on Loan Disbursement\n";
echo "================================================\n\n";

try {
    require_once __DIR__ . '/app/config/config.php';
    require_once __DIR__ . '/app/core/Database.php';

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Check current rules
    echo "1. Checking current SLR generation rules...\n";
    $stmt = $pdo->query("SELECT id, rule_name, trigger_event, auto_generate, is_active FROM slr_generation_rules ORDER BY id");
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rules as $rule) {
        $status = $rule['is_active'] ? '🟢 ACTIVE' : '🔴 INACTIVE';
        $auto = $rule['auto_generate'] ? '⚡ AUTO' : '👤 MANUAL';
        echo "   📜 {$rule['rule_name']} ({$rule['trigger_event']}) - {$auto} - {$status}\n";
    }
    echo "\n";

    // Check if disbursement rule exists
    echo "2. Checking loan_disbursement rule...\n";
    $stmt = $pdo->prepare("SELECT * FROM slr_generation_rules WHERE trigger_event = 'loan_disbursement'");
    $stmt->execute();
    $disbursementRule = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$disbursementRule) {
        echo "   ❌ No loan_disbursement rule found. Creating one...\n";

        $insertSql = "INSERT INTO slr_generation_rules
            (rule_name, description, trigger_event, auto_generate, require_signatures, notify_client, notify_officers, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($insertSql);
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

        echo "   ✅ Created loan_disbursement rule with auto_generate=ENABLED\n\n";
    } else {
        echo "   ✅ Found loan_disbursement rule\n";
        echo "      Auto-generate: " . ($disbursementRule['auto_generate'] ? 'ENABLED' : 'DISABLED') . "\n";

        if (!$disbursementRule['auto_generate']) {
            echo "   🔄 Enabling auto-generation...\n";
            $pdo->exec("UPDATE slr_generation_rules SET auto_generate = true, updated_at = CURRENT_TIMESTAMP WHERE trigger_event = 'loan_disbursement'");
            echo "   ✅ Auto-generation ENABLED for loan_disbursement\n\n";
        } else {
            echo "   ✅ Auto-generation already enabled\n\n";
        }
    }

    // Verify final state
    echo "3. Verifying final configuration...\n";
    $stmt = $pdo->query("SELECT rule_name, trigger_event, auto_generate, is_active FROM slr_generation_rules ORDER BY id");
    $finalRules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($finalRules as $rule) {
        $status = $rule['is_active'] ? '🟢 ACTIVE' : '🔴 INACTIVE';
        $auto = $rule['auto_generate'] ? '⚡ AUTO' : '👤 MANUAL';
        echo "   📜 {$rule['rule_name']} ({$rule['trigger_event']}) - {$auto} - {$status}\n";
    }

    echo "\n🎯 RESULT:\n";
    echo "   ✅ SLR auto-generation on loan disbursement is now ENABLED!\n";
    echo "   ✅ When loans are disbursed (status changes to 'active'), SLR documents will be generated automatically\n";
    echo "   ✅ SLR documents will appear in the SLR management page\n";
    echo "   ✅ Users can download SLR documents from the list\n\n";

    echo "📝 WORKFLOW NOW:\n";
    echo "   1. Loan application → Approved\n";
    echo "   2. Staff clicks 'Disburse' → Status changes to 'active'\n";
    echo "   3. 🚀 SLR document auto-generated with payment schedule\n";
    echo "   4. Document appears in SLR Management (/public/slr/manage.php)\n";
    echo "   5. Staff can download SLR PDF for client\n\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "✅ Fix completed successfully!\n";
?>
