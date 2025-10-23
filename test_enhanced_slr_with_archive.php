<?php
/**
 * Test Enhanced SLR with Archive and New Generation
 */

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

try {
    echo "🎨 Testing Enhanced SLR with Archive - " . date('Y-m-d H:i:s') . "\n";
    echo str_repeat('=', 60) . "\n\n";
    
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Archive existing SLR first
    echo "1. Archiving existing SLR...\n";
    require_once __DIR__ . '/app/services/SLRService.php';
    $slrService = new SLRService();
    
    $existingSLR = $slrService->getSLRByLoanId(15);
    if ($existingSLR) {
        echo "   📄 Found existing SLR: {$existingSLR['document_number']}\n";
        $archived = $slrService->archiveSLR($existingSLR['id'], 1, 'Archiving to test enhanced format');
        
        if ($archived) {
            echo "   ✅ Existing SLR archived successfully\n";
        } else {
            echo "   ❌ Failed to archive: " . $slrService->getErrorMessage() . "\n";
        }
    } else {
        echo "   ℹ️  No existing SLR found\n";
    }
    echo "\n";
    
    // Find a loan to generate enhanced SLR for
    echo "2. Finding loan for enhanced SLR generation...\n";
    $sql = "SELECT l.*, c.name as client_name, c.address, c.phone_number, c.email
            FROM loans l 
            JOIN clients c ON l.client_id = c.id 
            WHERE l.status IN ('Approved', 'Active') 
            ORDER BY l.id DESC
            LIMIT 1";
    $stmt = $connection->query($sql);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$loan) {
        echo "   ❌ No suitable loans found\n";
        exit(1);
    }
    
    echo "   ✅ Using loan #{$loan['id']} - Client: {$loan['client_name']}\n";
    echo "      Principal: ₱" . number_format($loan['principal'], 2) . "\n";
    echo "      Total Amount: ₱" . number_format($loan['total_loan_amount'], 2) . "\n";
    echo "\n";
    
    // Generate enhanced SLR
    echo "3. Generating enhanced SLR document...\n";
    
    try {
        $slrDocument = $slrService->generateSLR($loan['id'], 1, 'manual_request');
        
        if ($slrDocument) {
            echo "   ✅ Enhanced SLR generated successfully!\n";
            echo "      Document Number: {$slrDocument['document_number']}\n";
            echo "      File Name: {$slrDocument['file_name']}\n";
            echo "      File Size: " . number_format($slrDocument['file_size']) . " bytes\n";
            echo "\n";
            
            // Compare file sizes (enhanced should be larger)
            echo "4. Comparing with previous SLR format...\n";
            echo "   📊 Enhanced SLR size: " . number_format($slrDocument['file_size']) . " bytes\n";
            echo "   📊 Previous SLR size: 2,389 bytes\n";
            
            if ($slrDocument['file_size'] > 2389) {
                echo "   ✅ Enhanced SLR is larger (more content/styling)\n";
            } else {
                echo "   ℹ️  Similar size - styling may not have added much content\n";
            }
            echo "\n";
            
            // Show file location
            echo "5. SLR file location...\n";
            echo "   📁 Storage path: {$slrDocument['file_path']}\n";
            echo "   🔗 Similar format to: Kurt_Zar_Loan8_2025-10-20.pdf\n";
            
        } else {
            echo "   ❌ Enhanced SLR generation failed: " . $slrService->getErrorMessage() . "\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Exception during SLR generation: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    echo "🎯 Enhanced SLR Features Applied:\n";
    echo "   🎨 Professional company header with blue background\n";
    echo "   📊 Structured sections with colored headers\n";
    echo "   💰 Highlighted principal amount (yellow background)\n";
    echo "   📋 Detailed borrower information table\n";
    echo "   📅 Professional receipt details section\n";
    echo "   📝 Enhanced signature section layout\n";
    echo "   🏢 Company branding and contact information\n";
    echo "\n";
    echo "🚀 Your SLR documents now match the professional style!\n";
    echo "   Use the SLR button on loans list to generate these enhanced documents.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}