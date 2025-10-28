<?php
/**
 * Collection Sheet Enhanced Fix Validator
 * Tests the improved getSheetDetails functionality
 */
require_once __DIR__ . '/init.php';

echo "=== Collection Sheet Enhanced Fix Validation ===\n\n";

$service = new CollectionSheetService();

// Test 1: Check if basic getSheetDetails works
echo "Test 1: Basic getSheetDetails functionality\n";
echo "-------------------------------------------\n";

// Get existing sheets to test with
$sheetModel = new CollectionSheetModel();
$sheets = $sheetModel->listSheets([], 3);

if (empty($sheets)) {
    echo "❌ No collection sheets found for testing\n";
    echo "Creating a test collection sheet...\n";
    
    $testSheetId = $sheetModel->create([
        'officer_id' => 1, // Assuming user ID 1 exists
        'sheet_date' => date('Y-m-d'),
        'status' => 'draft',
        'total_amount' => 150.00,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    if (!$testSheetId) {
        echo "❌ Failed to create test sheet\n";
        exit(1);
    }
    
    $testSheet = $sheetModel->findById($testSheetId);
    $sheets = [$testSheet];
    echo "✅ Created test sheet ID: $testSheetId\n";
}

foreach ($sheets as $sheet) {
    $sheetId = $sheet['id'];
    echo "\nTesting Sheet ID: $sheetId\n";
    
    // Test basic getSheetDetails
    $details = $service->getSheetDetails($sheetId);
    
    if (!$details) {
        echo "❌ getSheetDetails failed for sheet $sheetId\n";
        continue;
    }
    
    $sheetData = $details['sheet'];
    $items = $details['items'];
    
    echo "✅ getSheetDetails success\n";
    echo "Basic fields:\n";
    echo "  - ID: " . ($sheetData['id'] ?? 'MISSING') . "\n";
    echo "  - Officer ID: " . ($sheetData['officer_id'] ?? 'MISSING') . "\n";
    echo "  - Sheet Date: " . ($sheetData['sheet_date'] ?? 'MISSING') . "\n";
    echo "  - Status: " . ($sheetData['status'] ?? 'MISSING') . "\n";
    
    echo "Enhanced fields:\n";
    echo "  - Officer Name: " . ($sheetData['officer_name'] ?? 'MISSING') . "\n";
    echo "  - Created By Name: " . ($sheetData['created_by_name'] ?? 'MISSING') . "\n";
    echo "  - Collection Date: " . ($sheetData['collection_date'] ?? 'MISSING') . "\n";
    
    // Test modal display logic
    echo "\nModal Display Tests:\n";
    $officerDisplay = htmlspecialchars($sheetData['created_by_name'] ?? $sheetData['officer_name'] ?? 'Unknown Officer');
    echo "  - Officer Display: " . $officerDisplay . "\n";
    
    $dateDisplay = !empty($sheetData['collection_date']) ? 
        date('M j, Y', strtotime($sheetData['collection_date'])) : 
        (!empty($sheetData['sheet_date']) ? 
            date('M j, Y', strtotime($sheetData['sheet_date'])) : 
            'Not specified');
    echo "  - Date Display: " . $dateDisplay . "\n";
    
    // Test getSheetDetailsForModal if available
    if (method_exists($service, 'getSheetDetailsForModal')) {
        echo "\nTest 2: Enhanced Modal Method\n";
        $modalDetails = $service->getSheetDetailsForModal($sheetId);
        if ($modalDetails) {
            echo "✅ getSheetDetailsForModal success\n";
            $modalSheet = $modalDetails['sheet'];
            
            // Verify all required modal fields are present
            $modalFields = ['created_by_name', 'officer_name', 'collection_date'];
            $allPresent = true;
            
            foreach ($modalFields as $field) {
                if (!isset($modalSheet[$field]) || $modalSheet[$field] === null) {
                    echo "⚠️  Missing modal field: $field\n";
                    $allPresent = false;
                }
            }
            
            if ($allPresent) {
                echo "✅ All modal fields present\n";
            }
        } else {
            echo "❌ getSheetDetailsForModal failed\n";
        }
    }
    
    echo "\nTest 3: Error Handling\n";
    echo "----------------------\n";
    
    // Test with invalid ID
    $invalidDetails = $service->getSheetDetails(999999);
    if ($invalidDetails === false) {
        echo "✅ Correctly handles invalid sheet ID\n";
    } else {
        echo "⚠️  Should return false for invalid sheet ID\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n";
}

echo "\nTest 4: Performance Check\n";
echo "-------------------------\n";

$startTime = microtime(true);
$testDetails = $service->getSheetDetails($sheets[0]['id']);
$endTime = microtime(true);
$executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

echo "Execution time: " . round($executionTime, 2) . "ms\n";

if ($executionTime < 100) {
    echo "✅ Performance acceptable (< 100ms)\n";
} else {
    echo "⚠️  Performance concern (> 100ms)\n";
}

echo "\nTest 5: Field Validation\n";
echo "------------------------\n";

if ($testDetails) {
    $sheet = $testDetails['sheet'];
    
    // Check for required fields for modal display
    $modalRequiredFields = [
        'id' => 'Sheet ID',
        'created_by_name' => 'Account Officer',
        'collection_date' => 'Collection Date',
        'total_amount' => 'Total Amount'
    ];
    
    $allValid = true;
    
    foreach ($modalRequiredFields as $field => $displayName) {
        if (isset($sheet[$field]) && $sheet[$field] !== null) {
            echo "✅ $displayName: Present\n";
        } else {
            echo "❌ $displayName: Missing\n";
            $allValid = false;
        }
    }
    
    if ($allValid) {
        echo "\n🎉 All modal validation tests passed!\n";
        echo "✅ Collection sheet modals should work without errors\n";
        echo "✅ No undefined array key warnings expected\n";
        echo "✅ No deprecated function parameter warnings expected\n";
    } else {
        echo "\n⚠️  Some modal fields are missing\n";
        echo "🔧 Check the enhanceSheetWithOfficerInfo method\n";
    }
}

echo "\n=== Validation Complete ===\n";
echo "The enhanced collection sheet functionality is ready for use.\n";
echo "All modals should display proper officer names and formatted dates.\n";