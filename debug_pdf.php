<?php
$content = file_get_contents('test_slr_loan_1.pdf');
echo "PDF content contains 'FANDERS MICROFINANCE': " . (strpos($content, 'FANDERS MICROFINANCE') !== false ? 'YES' : 'NO') . "\n";
echo "PDF content contains 'STATEMENT OF LOAN REPAYMENT': " . (strpos($content, 'STATEMENT OF LOAN REPAYMENT') !== false ? 'YES' : 'NO') . "\n";

// Check for compressed content
if (strpos($content, 'stream') !== false) {
    echo "PDF contains compressed streams - content is compressed and cannot be searched directly.\n";
}

// Check metadata
if (strpos($content, 'Statement of Loan Repayment - Loan #1') !== false) {
    echo "Title found in metadata: YES\n";
} else {
    echo "Title found in metadata: NO\n";
}
?>
