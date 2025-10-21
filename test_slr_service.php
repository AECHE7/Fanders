<?php
require_once 'app/config/config.php';
require_once 'app/core/Database.php';
require_once 'app/services/SLRDocumentService.php';

$slrService = new SLRDocumentService();

// Test SLR document generation
echo "Testing SLRDocumentService:\n";

// Test with a valid loan ID (assuming loan ID 1 exists)
try {
    $pdfContent = $slrService->generateSLRDocument(1);
    if ($pdfContent) {
        echo "SLR document generated successfully for loan ID 1\n";
        echo "PDF content length: " . strlen($pdfContent) . " bytes\n";
    } else {
        echo "Failed to generate SLR document for loan ID 1\n";
    }
} catch (Exception $e) {
    echo "Error generating SLR document: " . $e->getMessage() . "\n";
}

// Test with an invalid loan ID
try {
    $pdfContent = $slrService->generateSLRDocument(99999);
    if (!$pdfContent) {
        echo "No SLR document generated for invalid loan ID 99999 (expected)\n";
    } else {
        echo "Unexpected: SLR document generated for invalid loan ID\n";
    }
} catch (Exception $e) {
    echo "Error (expected) for invalid loan ID: " . $e->getMessage() . "\n";
}

echo "SLRDocumentService test completed.\n";
?>
