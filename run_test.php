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

    // Check for expected content
    if (strpos($pdfContent, 'FANDERS MICROFINANCE') !== false) {
        echo "Content validation: PASSED (Header found)\n";
    } else {
        echo "Content validation: FAILED (Header not found)\n";
    }

    if (strpos($pdfContent, 'STATEMENT OF LOAN REPAYMENT') !== false) {
        echo "Content validation: PASSED (Title found)\n";
    } else {
        echo "Content validation: FAILED (Title not found)\n";
    }

} else {
    echo "Failed to generate PDF\n";
}

echo "PDF output test completed.\n";
?>
