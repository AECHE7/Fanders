<?php
/**
 * Debug SLR Generation Issue
 */

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

try {
    echo "🔍 Debugging SLR Generation Issue - " . date('Y-m-d H:i:s') . "\n";
    echo str_repeat('=', 60) . "\n\n";
    
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Check current generation rules
    echo "1. Checking generation rules...\n";
    $sql = "SELECT rule_name, trigger_event, auto_generate, is_active FROM slr_generation_rules";
    $stmt = $connection->query($sql);
    $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rules as $rule) {
        $status = $rule['is_active'] ? '🟢 Active' : '🔴 Inactive';
        $type = $rule['auto_generate'] ? 'AUTO' : 'MANUAL';
        echo "   • {$rule['rule_name']} - Trigger: '{$rule['trigger_event']}' - {$type} - {$status}\n";
    }
    echo "\n";
    
    // Test trigger lookup
    echo "2. Testing trigger lookup...\n";
    $testTriggers = ['manual', 'manual_request', 'loan_approval', 'loan_disbursement'];
    
    foreach ($testTriggers as $trigger) {
        $sql = "SELECT rule_name FROM slr_generation_rules WHERE trigger_event = ? AND is_active = true";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$trigger]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "   ✅ Trigger '{$trigger}' found: {$result['rule_name']}\n";
        } else {
            echo "   ❌ Trigger '{$trigger}' not found\n";
        }
    }
    echo "\n";
    
    // Check sample loans
    echo "3. Checking sample loans...\n";
    $sql = "SELECT id, status, principal FROM loans WHERE status IN ('approved', 'active', 'completed') LIMIT 3";
    $stmt = $connection->query($sql);
    $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($loans)) {
        echo "   ⚠️  No eligible loans found\n";
    } else {
        foreach ($loans as $loan) {
            echo "   • Loan #{$loan['id']} - Status: {$loan['status']} - Principal: ₱" . number_format($loan['principal'], 2) . "\n";
        }
    }
    echo "\n";
    
    // Check existing SLR documents
    echo "4. Checking existing SLR documents...\n";
    $sql = "SELECT COUNT(*) as count FROM slr_documents";
    $stmt = $connection->query($sql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   📊 Total SLR documents: {$result['count']}\n";
    
    if ($result['count'] > 0) {
        $sql = "SELECT s.document_number, s.status, l.id as loan_id, c.name as client_name
                FROM slr_documents s
                JOIN loans l ON s.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                LIMIT 3";
        $stmt = $connection->query($sql);
        $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   📄 Recent SLR documents:\n";
        foreach ($docs as $doc) {
            echo "      • {$doc['document_number']} - Loan #{$doc['loan_id']} - {$doc['client_name']} - {$doc['status']}\n";
        }
    }
    echo "\n";
    
    echo "🎯 ISSUES IDENTIFIED:\n";
    echo "1. ❌ SLR generation uses trigger 'manual' but database has 'manual_request'\n";
    echo "2. ❓ Need to check why SLR list might not show client documents\n";
    echo "\n";
    echo "🔧 FIXES NEEDED:\n";
    echo "1. Change 'manual' to 'manual_request' in generate.php\n";
    echo "2. Check SLR list query for client data\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}