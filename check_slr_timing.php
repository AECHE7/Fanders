<?php
/**
 * Check Current SLR Generation Rules
 */

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

try {
    echo "📋 Current SLR Generation Rules - " . date('Y-m-d H:i:s') . "\n";
    echo str_repeat('=', 60) . "\n\n";
    
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    $sql = "SELECT rule_name, trigger_event, auto_generate, is_active, description,
                   applies_to_loan_types, min_principal_amount, max_principal_amount
            FROM slr_generation_rules 
            ORDER BY id";
    
    $stmt = $connection->query($sql);
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rules)) {
        echo "❌ No SLR generation rules found!\n";
        exit(1);
    }
    
    echo "🎯 SLR Generation Timing Rules:\n\n";
    
    foreach ($rules as $rule) {
        $status = $rule['is_active'] ? '🟢 ACTIVE' : '🔴 INACTIVE';
        $type = $rule['auto_generate'] ? '⚡ AUTO' : '👤 MANUAL';
        
        echo "📜 {$rule['rule_name']}\n";
        echo "   🔄 Trigger: {$rule['trigger_event']}\n";
        echo "   ⚙️  Type: {$type}\n";
        echo "   📊 Status: {$status}\n";
        echo "   📝 Description: {$rule['description']}\n";
        
        if ($rule['applies_to_loan_types']) {
            echo "   🏷️  Loan Types: {$rule['applies_to_loan_types']}\n";
        }
        
        if ($rule['min_principal_amount']) {
            echo "   💰 Min Amount: ₱" . number_format($rule['min_principal_amount'], 2) . "\n";
        }
        
        if ($rule['max_principal_amount']) {
            echo "   💰 Max Amount: ₱" . number_format($rule['max_principal_amount'], 2) . "\n";
        }
        
        echo "\n";
    }
    
    echo str_repeat('-', 60) . "\n";
    echo "🕐 WHEN SLRs ARE GENERATED:\n\n";
    
    foreach ($rules as $rule) {
        if (!$rule['is_active']) continue;
        
        switch ($rule['trigger_event']) {
            case 'loan_approval':
                if ($rule['auto_generate']) {
                    echo "✅ AUTOMATICALLY when loan is APPROVED\n";
                    echo "   → System generates SLR immediately upon approval\n";
                    echo "   → No manual action required\n\n";
                } else {
                    echo "👤 MANUALLY after loan is APPROVED\n";
                    echo "   → Staff must click 'SLR' button\n";
                    echo "   → Generated on-demand\n\n";
                }
                break;
                
            case 'loan_disbursement':
                echo "💰 MANUALLY during loan DISBURSEMENT\n";
                echo "   → Staff generates when actually giving money to client\n";
                echo "   → Best practice: generate before handing over funds\n\n";
                break;
                
            case 'manual_request':
                echo "🖱️  MANUALLY on staff REQUEST\n";
                echo "   → Any time staff clicks 'SLR' button\n";
                echo "   → For client requests, audits, or documentation needs\n\n";
                break;
        }
    }
    
    echo "🎯 RECOMMENDED WORKFLOW:\n";
    echo "1. Loan gets approved → Auto-generate SLR (if auto rule is active)\n";
    echo "2. Before disbursement → Manual SLR generation (if not auto)\n";
    echo "3. Client requests copy → Manual generation anytime\n";
    echo "4. Audit requirements → Manual generation as needed\n\n";
    
    echo "📝 CURRENT SYSTEM BEHAVIOR:\n";
    $autoApprovalActive = false;
    $manualActive = false;
    
    foreach ($rules as $rule) {
        if ($rule['is_active']) {
            if ($rule['trigger_event'] === 'loan_approval' && $rule['auto_generate']) {
                $autoApprovalActive = true;
                echo "   🤖 SLRs AUTO-GENERATED on loan approval\n";
            }
            if ($rule['trigger_event'] === 'manual_request') {
                $manualActive = true;
                echo "   👤 Manual SLR generation available anytime\n";
            }
            if ($rule['trigger_event'] === 'loan_disbursement') {
                echo "   💰 Manual SLR generation available during disbursement\n";
            }
        }
    }
    
    if ($autoApprovalActive) {
        echo "\n⚡ AUTOMATIC MODE: SLRs are created immediately when loans are approved!\n";
    } else {
        echo "\n👤 MANUAL MODE: Staff must generate SLRs by clicking the SLR button.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}