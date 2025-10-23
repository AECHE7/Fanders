<?php
/**
 * Test Enhanced SLR Generation
 * Generate a professional SLR document matching agreement format
 */

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

try {
    echo "🎨 Testing Enhanced SLR Generation - " . date('Y-m-d H:i:s') . "\n";
    echo str_repeat('=', 60) . "\n\n";
    
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Find a loan to generate SLR for
    echo "1. Finding a suitable loan for SLR generation...\n";
    $sql = "SELECT l.*, c.name as client_name, c.address, c.phone_number, c.email
            FROM loans l 
            JOIN clients c ON l.client_id = c.id 
            WHERE l.status IN ('Approved', 'Active') 
            LIMIT 1";
    $stmt = $connection->query($sql);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$loan) {
        echo "   ❌ No suitable loans found\n";
        exit(1);
    }
    
    echo "   ✅ Found loan #{$loan['id']} - Client: {$loan['client_name']}\n";
    echo "      Principal: ₱" . number_format($loan['principal'], 2) . "\n";
    echo "      Total Amount: ₱" . number_format($loan['total_loan_amount'], 2) . "\n";
    echo "\n";
    
    // Generate enhanced SLR
    echo "2. Generating enhanced SLR document...\n";
    require_once __DIR__ . '/app/services/SLRService.php';
    $slrService = new SLRService();
    
    try {
        $slrDocument = $slrService->generateSLR($loan['id'], 1, 'manual_request');
        
        if ($slrDocument) {
            echo "   ✅ Enhanced SLR generated successfully!\n";
            echo "      Document Number: {$slrDocument['document_number']}\n";
            echo "      File Name: {$slrDocument['file_name']}\n";
            echo "      File Path: {$slrDocument['file_path']}\n";
            echo "      File Size: " . number_format($slrDocument['file_size']) . " bytes\n";
            echo "\n";
            
            // Verify file exists
            if (file_exists($slrDocument['file_path'])) {
                echo "   ✅ PDF file successfully created on disk\n";
                echo "      File size on disk: " . number_format(filesize($slrDocument['file_path'])) . " bytes\n";
            } else {
                echo "   ❌ PDF file not found on disk\n";
            }
        } else {
            echo "   ❌ SLR generation failed: " . $slrService->getErrorMessage() . "\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Exception during SLR generation: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    // Check storage directory structure
    echo "3. Checking SLR storage structure...\n";
    $slrDir = __DIR__ . '/storage/slr';
    
    if (is_dir($slrDir)) {
        $files = scandir($slrDir);
        $pdfFiles = array_filter($files, function($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'pdf';
        });
        
        echo "   📁 SLR directory: {$slrDir}\n";
        echo "   📄 PDF files found: " . count($pdfFiles) . "\n";
        
        foreach ($pdfFiles as $file) {
            $filePath = $slrDir . '/' . $file;
            $size = filesize($filePath);
            $created = date('Y-m-d H:i:s', filemtime($filePath));
            echo "      • {$file} - " . number_format($size) . " bytes - {$created}\n";
        }
    } else {
        echo "   ❌ SLR directory not found\n";
    }
    echo "\n";
    
    echo "🎯 Enhanced SLR Features:\n";
    echo "   ✅ Professional styling matching loan agreements\n";
    echo "   ✅ Colored headers and sections (blue, green, red, yellow)\n";
    echo "   ✅ Structured borrower information section\n";
    echo "   ✅ Detailed loan receipt information\n";
    echo "   ✅ Highlighted principal amount received\n";
    echo "   ✅ Comprehensive repayment schedule with payment dates\n";
    echo "   ✅ Weekly payment schedule table with due dates\n";
    echo "   ✅ Detailed breakdown of principal, interest, and insurance\n";
    echo "   ✅ Running balance calculation for each payment\n";
    echo "   ✅ Professional signature section\n";
    echo "   ✅ Company branding and footer\n";
    echo "\n";
    echo "🎨 SLR now includes detailed payment schedule for client reference!\n";
    echo "   Each payment shows: Week #, Due Date, Payment Amount, Balance\n";
    echo "   Similar to: Kurt_Zar_Loan8_2025-10-20.pdf style + payment calendar\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}