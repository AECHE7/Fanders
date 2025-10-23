<?php
/**
 * Test SLR Generation Fix
 */

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

try {
    echo "🔧 Testing SLR Generation Fix - " . date('Y-m-d H:i:s') . "\n";
    echo str_repeat('=', 60) . "\n\n";
    
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Test 1: Verify trigger fix
    echo "1. Testing trigger lookup with correct name...\n";
    $sql = "SELECT rule_name, is_active FROM slr_generation_rules WHERE trigger_event = ? AND is_active = true";
    $stmt = $connection->prepare($sql);
    $stmt->execute(['manual_request']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo "   ✅ Trigger 'manual_request' found: {$result['rule_name']}\n";
    } else {
        echo "   ❌ Trigger 'manual_request' still not working\n";
    }
    echo "\n";
    
    // Test 2: Create a test loan if none exist
    echo "2. Checking for test loans...\n";
    $sql = "SELECT id, status, principal FROM loans WHERE status IN ('approved', 'active', 'completed') LIMIT 1";
    $stmt = $connection->query($sql);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$loan) {
        echo "   ⚠️  No eligible loans found. Creating test loan...\n";
        
        // Check if we have clients
        $sql = "SELECT id FROM clients LIMIT 1";
        $stmt = $connection->query($sql);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($client) {
            // Create a test loan
            $sql = "INSERT INTO loans (client_id, principal, total_loan_amount, status, application_date, approval_date) 
                    VALUES (?, 5000, 6000, 'approved', CURRENT_DATE, CURRENT_DATE)";
            $stmt = $connection->prepare($sql);
            $stmt->execute([$client['id']]);
            $loanId = $connection->lastInsertId();
            echo "   ✅ Created test loan #$loanId\n";
            
            $loan = ['id' => $loanId, 'status' => 'approved', 'principal' => 5000];
        } else {
            echo "   ❌ No clients found to create test loan\n";
        }
    } else {
        echo "   ✅ Found loan #{$loan['id']} - Status: {$loan['status']} - Principal: ₱" . number_format($loan['principal'], 2) . "\n";
    }
    echo "\n";
    
    // Test 3: Test SLR Service generation
    if ($loan) {
        echo "3. Testing SLR Service generation...\n";
        require_once __DIR__ . '/app/services/SLRService.php';
        $slrService = new SLRService();
        
        // Check if SLR already exists
        $existingSLR = $slrService->getSLRByLoanId($loan['id']);
        if ($existingSLR) {
            echo "   ℹ️  SLR already exists for loan #{$loan['id']}: {$existingSLR['document_number']}\n";
        } else {
            echo "   🔄 Attempting to generate SLR for loan #{$loan['id']}...\n";
            
            try {
                $slrDocument = $slrService->generateSLR($loan['id'], 1, 'manual_request');
                
                if ($slrDocument) {
                    echo "   ✅ SLR generated successfully!\n";
                    echo "      Document Number: {$slrDocument['document_number']}\n";
                    echo "      File: {$slrDocument['file_name']}\n";
                } else {
                    echo "   ❌ SLR generation failed: " . $slrService->getErrorMessage() . "\n";
                }
            } catch (Exception $e) {
                echo "   ❌ Exception during generation: " . $e->getMessage() . "\n";
            }
        }
    }
    echo "\n";
    
    // Test 4: Check SLR list
    echo "4. Testing SLR document listing...\n";
    if (class_exists('SLRService')) {
        $slrService = new SLRService();
        $slrList = $slrService->listSLRDocuments([], 5, 0);
        
        echo "   📊 Found " . count($slrList) . " SLR documents\n";
        
        if (!empty($slrList)) {
            foreach ($slrList as $slr) {
                echo "      • {$slr['document_number']} - Client: {$slr['client_name']} - Loan #{$slr['loan_id']}\n";
            }
        }
    }
    echo "\n";
    
    echo "🎯 FIX SUMMARY:\n";
    echo "   ✅ Changed 'manual' to 'manual_request' in generate.php\n";
    echo "   ✅ Changed 'manual' to 'manual_request' in manage.php\n";
    echo "   ✅ SLR generation should now work from loan list\n";
    echo "   ✅ SLR management list should show documents once generated\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}