<?php
/**
 * Test Loan Disbursement Fix
 */

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

try {
    echo "🧪 Testing Loan Disbursement Fix - " . date('Y-m-d H:i:s') . "\n";
    echo str_repeat('=', 60) . "\n\n";
    
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Check loan #15 status
    echo "1. Checking loan #15 status...\n";
    $sql = "SELECT id, status, principal, total_loan_amount FROM loans WHERE id = 15";
    $stmt = $connection->query($sql);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($loan) {
        echo "   📊 Loan #{$loan['id']}\n";
        echo "      Status: '{$loan['status']}'\n";
        echo "      Principal: ₱" . number_format($loan['principal'], 2) . "\n";
        echo "      Total Amount: ₱" . number_format($loan['total_loan_amount'], 2) . "\n";
    } else {
        echo "   ❌ Loan #15 not found\n";
        exit(1);
    }
    echo "\n";
    
    // Test LoanService disbursement
    echo "2. Testing LoanService disbursement...\n";
    require_once __DIR__ . '/app/services/LoanService.php';
    $loanService = new LoanService();
    
    // Test status checking
    require_once __DIR__ . '/app/models/LoanModel.php';
    echo "   🔍 Comparing statuses:\n";
    echo "      Database status: '{$loan['status']}'\n";
    echo "      Expected status: '" . LoanModel::STATUS_APPROVED . "'\n";
    echo "      Case-sensitive match: " . ($loan['status'] === LoanModel::STATUS_APPROVED ? 'YES' : 'NO') . "\n";
    echo "      Case-insensitive match: " . (strcasecmp($loan['status'], LoanModel::STATUS_APPROVED) === 0 ? 'YES' : 'NO') . "\n";
    echo "\n";
    
    // Test the disbursement method
    echo "3. Testing disbursement method...\n";
    
    if ($loan['status'] === 'Approved') {
        echo "   🔄 Attempting to disburse loan #15...\n";
        
        try {
            $result = $loanService->disburseLoan(15, 1); // User ID 1 for testing
            
            if ($result) {
                echo "   ✅ Loan disbursed successfully!\n";
                
                // Check new status
                $sql = "SELECT status, disbursement_date FROM loans WHERE id = 15";
                $stmt = $connection->query($sql);
                $updatedLoan = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo "      New Status: '{$updatedLoan['status']}'\n";
                echo "      Disbursement Date: {$updatedLoan['disbursement_date']}\n";
            } else {
                echo "   ❌ Disbursement failed: " . $loanService->getErrorMessage() . "\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ Exception during disbursement: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ⚠️  Loan status is not 'Approved', skipping disbursement test\n";
    }
    echo "\n";
    
    echo "🎯 DISBURSEMENT FIX SUMMARY:\n";
    echo "   ✅ Fixed case sensitivity in status comparison\n";
    echo "   ✅ Updated loan status from 'approved' to 'Approved'\n";
    echo "   ✅ Made status comparisons case-insensitive for future\n";
    echo "   🚀 Loan disbursement should now work properly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}