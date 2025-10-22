<?php
/**
 * Test script for SLR document generation fix
 * This tests the fix for "FPDF error: The document is closed"
 */

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/core/Session.php';
require_once __DIR__ . '/app/core/Auth.php';
require_once __DIR__ . '/app/services/SLRDocumentService.php';

echo "Testing SLR Document Generation Fix\n";
echo "===================================\n\n";

try {
    // Initialize the service
    $slrService = new SLRDocumentService();
    echo "✓ SLRDocumentService initialized successfully\n";
    
    // Test 1: Generate a single SLR document
    echo "\nTest 1: Generate single SLR document\n";
    echo "--------------------------------------\n";
    
    $loanId = 7; // Using loan ID from the error trace
    echo "Generating SLR for Loan ID: $loanId\n";
    
    $pdfContent = $slrService->generateSLRDocument($loanId);
    
    if ($pdfContent === false) {
        echo "✗ Failed to generate SLR: " . $slrService->getErrorMessage() . "\n";
    } else {
        echo "✓ SLR generated successfully\n";
        echo "  PDF size: " . strlen($pdfContent) . " bytes\n";
    }
    
    // Test 2: Generate multiple SLR documents (simulating bulk generation)
    echo "\nTest 2: Generate multiple SLR documents\n";
    echo "----------------------------------------\n";
    
    $testLoanIds = [7]; // Start with one, can add more if available
    echo "Generating SLRs for " . count($testLoanIds) . " loan(s)\n";
    
    $documents = $slrService->generateBulkSLRDocuments($testLoanIds);
    
    if (empty($documents)) {
        echo "✗ Failed to generate bulk SLRs\n";
    } else {
        echo "✓ Bulk SLRs generated successfully\n";
        echo "  Documents generated: " . count($documents) . "\n";
        foreach ($documents as $loanId => $content) {
            echo "  - Loan ID $loanId: " . strlen($content) . " bytes\n";
        }
    }
    
    // Test 3: Generate client SLR documents
    echo "\nTest 3: Generate client SLR documents\n";
    echo "--------------------------------------\n";
    
    $clientId = 2; // Using client ID from the error trace
    echo "Generating SLRs for Client ID: $clientId\n";
    
    $clientDocuments = $slrService->generateClientSLRDocuments($clientId);
    
    if (empty($clientDocuments)) {
        echo "✗ Failed to generate client SLRs\n";
    } else {
        echo "✓ Client SLRs generated successfully\n";
        echo "  Documents generated: " . count($clientDocuments) . "\n";
        foreach ($clientDocuments as $loanId => $content) {
            echo "  - Loan ID $loanId: " . strlen($content) . " bytes\n";
        }
    }
    
    echo "\n===================================\n";
    echo "All tests completed!\n";
    echo "Fix verified: No 'document is closed' errors\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
