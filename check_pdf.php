<?php
$content = file_get_contents('test_slr_loan_1.pdf');
echo "First 200 characters of PDF:\n";
echo substr($content, 0, 200) . "\n\n";

echo "PDF starts with %PDF?: " . (strpos($content, '%PDF-') === 0 ? 'YES' : 'NO') . "\n";
echo "Contains 'FANDERS MICROFINANCE'?: " . (strpos($content, 'FANDERS MICROFINANCE') !== false ? 'YES' : 'NO') . "\n";
echo "Contains 'STATEMENT OF LOAN REPAYMENT'?: " . (strpos($content, 'STATEMENT OF LOAN REPAYMENT') !== false ? 'YES' : 'NO') . "\n";
?>
