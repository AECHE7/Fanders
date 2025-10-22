<?php
/**
 * Export Integrity Test Script
 * Tests all export functions to ensure error messages don't leak into generated files
 */

// Set error reporting to catch all issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize session for flash messages
session_start();

// Initialize the application
require_once __DIR__ . '/public/init.php';
require_once __DIR__ . '/app/services/ReportService.php';
require_once __DIR__ . '/app/utilities/ExcelExportUtility.php';

echo "<h1>Export Integrity Test Results</h1>\n";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .pass { color: green; font-weight: bold; }
    .fail { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>\n";

/**
 * Test function to validate export output integrity
 */
function testExportIntegrity($testName, $callable) {
    echo "<div class='test-section'>\n";
    echo "<h3>Testing: $testName</h3>\n";
    
    // Capture output
    ob_start();
    $error = null;
    
    try {
        $callable();
        $output = ob_get_contents();
        
        // Check if output contains error messages or PHP warnings
        $errorPatterns = [
            '/error/i',
            '/warning/i',
            '/notice/i',
            '/fatal/i',
            '/exception/i',
            '/undefined/i',
            '/failed/i',
            '/<\?php/i',
            '/call to undefined/i',
            '/parse error/i'
        ];
        
        $hasErrors = false;
        $foundPatterns = [];
        
        foreach ($errorPatterns as $pattern) {
            if (preg_match($pattern, $output)) {
                $hasErrors = true;
                $foundPatterns[] = $pattern;
            }
        }
        
        // Check if it's valid XML for Excel exports
        $isValidXML = false;
        if (strpos($output, '<?xml') === 0) {
            $dom = new DOMDocument();
            $isValidXML = @$dom->loadXML($output);
        }
        
        if ($hasErrors) {
            echo "<span class='fail'>FAIL</span> - Error messages detected in export output\n";
            echo "<p>Found error patterns: " . implode(', ', $foundPatterns) . "</p>\n";
            echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "...</pre>\n";
        } elseif (strpos($output, '<?xml') === 0 && !$isValidXML) {
            echo "<span class='fail'>FAIL</span> - Invalid XML structure\n";
            echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "...</pre>\n";
        } elseif (empty($output)) {
            echo "<span class='warning'>WARNING</span> - No output generated\n";
        } else {
            echo "<span class='pass'>PASS</span> - Export output appears clean\n";
            if ($isValidXML) {
                echo "<p>✓ Valid XML structure</p>\n";
            }
            echo "<p>Output size: " . strlen($output) . " bytes</p>\n";
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        echo "<span class='pass'>PASS</span> - Exception properly caught: " . htmlspecialchars($error) . "\n";
    } catch (Error $e) {
        $error = $e->getMessage();
        echo "<span class='fail'>FAIL</span> - Fatal error: " . htmlspecialchars($error) . "\n";
    }
    
    ob_end_clean();
    echo "</div>\n";
}

// Initialize report service
$reportService = new ReportService();

// Test 1: Excel export with empty data
testExportIntegrity("Excel Export with Empty Data", function() use ($reportService) {
    $reportService->exportLoanReportExcel([], []);
});

// Test 2: Excel export with invalid data
testExportIntegrity("Excel Export with Invalid Data", function() use ($reportService) {
    $reportService->exportLoanReportExcel("invalid", []);
});

// Test 3: Excel export with malformed data
testExportIntegrity("Excel Export with Malformed Data", function() use ($reportService) {
    $badData = [
        ['incomplete' => 'record'],
        ['loan_number' => null, 'client_name' => null]
    ];
    $reportService->exportLoanReportExcel($badData, []);
});

// Test 4: Excel export with valid data
testExportIntegrity("Excel Export with Valid Data", function() use ($reportService) {
    $validData = [
        [
            'loan_number' => 'L001',
            'client_name' => 'John Doe',
            'principal_amount' => 10000,
            'total_amount' => 12000,
            'total_paid' => 5000,
            'remaining_balance' => 7000,
            'status' => 'active'
        ]
    ];
    $reportService->exportLoanReportExcel($validData, []);
});

// Test 5: Financial summary with missing data
testExportIntegrity("Financial Summary Export with Missing Data", function() use ($reportService) {
    $incompleteData = ['period' => ['from' => '2024-01-01']]; // Missing required keys
    $reportService->exportFinancialSummaryExcel($incompleteData);
});

// Test 6: Direct ExcelExportUtility tests
testExportIntegrity("Direct ExcelExportUtility - Empty Headers and Rows", function() {
    ExcelExportUtility::outputSingleSheet('Test', [], [], 'test.xls');
});

testExportIntegrity("Direct ExcelExportUtility - Valid Data", function() {
    $headers = ['Name', 'Value'];
    $rows = [['Test', 123], ['Sample', 456]];
    ExcelExportUtility::outputSingleSheet('Test', $headers, $rows, 'test.xls');
});

// Test 7: KeyValue export with empty data
testExportIntegrity("KeyValue Export with Empty Data", function() {
    ExcelExportUtility::outputKeyValueSheet('Test', [], 'test.xls');
});

echo "<h2>Test Summary</h2>\n";
echo "<p>All export functions have been tested for integrity. Review the results above to ensure:</p>\n";
echo "<ul>\n";
echo "<li>✓ No error messages leak into export files</li>\n";
echo "<li>✓ Invalid data is properly handled with exceptions</li>\n";
echo "<li>✓ Valid XML structure is generated for Excel exports</li>\n";
echo "<li>✓ Output buffers are properly cleared</li>\n";
echo "</ul>\n";

echo "<h2>Recommendations</h2>\n";
echo "<ul>\n";
echo "<li>Always validate data before calling export functions</li>\n";
echo "<li>Use try-catch blocks around export calls</li>\n";
echo "<li>Clear output buffers before generating exports</li>\n";
echo "<li>Log errors instead of displaying them during exports</li>\n";
echo "</ul>\n";
?>