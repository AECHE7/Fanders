<?php
/**
 * Debug SLR Generation Error for Completed Loans
 */

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/services/SLRService.php';

try {
    echo "🔍 Debugging SLR Error: 'Failed to create SLR record' - " . date('Y-m-d H:i:s') . "\n";
    echo str_repeat('=', 70) . "\n\n";
    
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Step 1: Check generation rules
    echo "1. Checking SLR generation rules...\n";
    $sql = "SELECT * FROM slr_generation_rules WHERE trigger_event = 'manual_request' AND is_active = true";
    $stmt = $connection->query($sql);
    $rule = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($rule) {
        echo "   ✅ Found manual_request rule: {$rule['rule_name']}\n";
        echo "      Auto Generate: " . ($rule['auto_generate'] ? 'Yes' : 'No') . "\n";
        echo "      Active: " . ($rule['is_active'] ? 'Yes' : 'No') . "\n";
    } else {
        echo "   ❌ No active manual_request rule found!\n";
        
        // Check all rules
        echo "\n   🔍 All existing rules:\n";
        $allRules = $connection->query("SELECT rule_name, trigger_event, is_active FROM slr_generation_rules")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($allRules as $r) {
            $status = $r['is_active'] ? '✅' : '❌';
            echo "      {$status} {$r['rule_name']} ({$r['trigger_event']})\n";
        }
    }
    echo "\n";
    
    // Step 2: Find a completed loan
    echo "2. Finding a completed loan to test...\n";
    $sql = "SELECT id, principal, client_id, status FROM loans WHERE status = 'completed' LIMIT 1";
    $stmt = $connection->query($sql);
    $completedLoan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($completedLoan) {
        echo "   ✅ Found completed loan: #{$completedLoan['id']} (Status: {$completedLoan['status']})\n";
        echo "      Principal: ₱" . number_format($completedLoan['principal'], 2) . "\n";
        
        // Step 3: Check if SLR already exists
        echo "\n3. Checking if SLR already exists for this loan...\n";
        $sql = "SELECT document_number, status FROM slr_documents WHERE loan_id = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$completedLoan['id']]);
        $existingSLR = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingSLR) {
            echo "   ℹ️  SLR already exists: {$existingSLR['document_number']} (Status: {$existingSLR['status']})\n";
        } else {
            echo "   ✅ No existing SLR - ready to test generation\n";
        }
        
        // Step 4: Test SLR generation with detailed debugging
        echo "\n4. Testing SLR generation...\n";
        
        // Initialize SLR service
        $slrService = new SLRService();
        
        echo "   🔄 Attempting to generate SLR for loan #{$completedLoan['id']}...\n";
        
        try {
            $slrDocument = $slrService->generateSLR($completedLoan['id'], 1, 'manual_request');
            
            if ($slrDocument) {
                echo "   ✅ SLR generated successfully!\n";
                echo "      Document Number: {$slrDocument['document_number']}\n";
                echo "      File: {$slrDocument['file_name']}\n";
                echo "      Status: {$slrDocument['status']}\n";
            } else {
                echo "   ❌ SLR generation failed!\n";
                echo "      Error: " . $slrService->getErrorMessage() . "\n";
                
                // Additional debugging - check the exact database error
                echo "\n   🔍 Additional debugging:\n";
                
                // Test database connectivity
                echo "      Database connection: ";
                $testQuery = $connection->query("SELECT NOW() as current_time");
                if ($testQuery) {
                    echo "✅ Working\n";
                } else {
                    echo "❌ Failed\n";
                }
                
                // Check slr_documents table structure
                echo "      Table structure check: ";
                $tableCheck = $connection->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'slr_documents' AND table_schema = 'public' ORDER BY ordinal_position");
                if ($tableCheck) {
                    echo "✅ Table exists\n";
                    $columns = $tableCheck->fetchAll(PDO::FETCH_COLUMN);
                    echo "         Columns: " . implode(', ', $columns) . "\n";
                } else {
                    echo "❌ Table check failed\n";
                }
            }
            
        } catch (Exception $e) {
            echo "   ❌ Exception during generation:\n";
            echo "      Message: " . $e->getMessage() . "\n";
            echo "      File: " . $e->getFile() . ":" . $e->getLine() . "\n";
            echo "      Stack trace:\n" . $e->getTraceAsString() . "\n";
        }
        
    } else {
        echo "   ❌ No completed loans found for testing\n";
        
        // Check what loan statuses exist
        echo "\n   🔍 Available loan statuses:\n";
        $statusQuery = $connection->query("SELECT status, COUNT(*) as count FROM loans GROUP BY status ORDER BY count DESC");
        $statuses = $statusQuery->fetchAll(PDO::FETCH_ASSOC);
        foreach ($statuses as $status) {
            echo "      • {$status['status']}: {$status['count']} loans\n";
        }
    }
    
    echo "\n" . str_repeat('=', 70) . "\n";
    echo "🎯 DEBUGGING COMPLETE\n";
    
} catch (Exception $e) {
    echo "❌ Error during debugging: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>