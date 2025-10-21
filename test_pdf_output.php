<?php
require_once 'app/config/config.php';
require_once 'app/core/Database.php';
require_once 'app/services/SLRDocumentService.php';

$slrService = new SLRDocumentService();

// Generate SLR document for loan ID 1
$pdfContent = $slrService->generateSLRDocument(1);

if ($pdfContent) {
    // Save PDF to file for inspection
    $filename = 'test_slr_loan_1.pdf';
    file_put_contents($filename, $pdfContent);
    echo "PDF generated and saved as '$filename'\n";
    echo "File size: " . filesize($filename) . " bytes\n";

    // Basic PDF validation (check for PDF header)
    if (strpos($pdfContent, '%PDF-') === 0) {
        echo "PDF header validation: PASSED\n";
    } else {
        echo "PDF header validation: FAILED\n";
    }

    // Check for expected content (note: PDF content is compressed, so we check metadata)
    if (strpos($pdfContent, 'Statement of Loan Repayment - Loan #1') !== false) {
        echo "Content validation: PASSED (Title found in metadata)\n";
    } else {
        echo "Content validation: FAILED (Title not found in metadata)\n";
    }

    // Since PDF content is compressed, we need to check if the PDF structure is valid
    if (strpos($pdfContent, '%PDF-') === 0 && strpos($pdfContent, '%%EOF') !== false) {
        echo "PDF structure validation: PASSED\n";
    } else {
        echo "PDF structure validation: FAILED\n";
    }

} else {
    echo "Failed to generate PDF\n";
}

echo "PDF output test completed.\n";
?>
