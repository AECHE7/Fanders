<?php
/**
 * Test Client Creation Script
 * This script tests the client creation process to identify issues
 */

// Initialize the application
require_once __DIR__ . '/public/init.php';

echo "=== Client Creation Test ===\n\n";

// Test 1: Check database connection
echo "1. Testing database connection...\n";
try {
    $db = Database::getInstance();
    $result = $db->single("SELECT 1 as test");
    echo "   ✓ Database connection OK\n\n";
} catch (Exception $e) {
    echo "   ✗ Database connection FAILED: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Check clients table structure
echo "2. Checking clients table structure...\n";
try {
    $columns = $db->resultSet("DESCRIBE clients");
    echo "   Columns found:\n";
    foreach ($columns as $col) {
        echo "   - {$col['Field']} ({$col['Type']}) " . 
             ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . 
             ($col['Key'] ? " [{$col['Key']}]" : "") . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Failed to check table structure: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 3: Test ClientModel directly
echo "3. Testing ClientModel...\n";
try {
    $clientModel = new ClientModel();
    
    // Test data
    $testData = [
        'name' => 'Test Client ' . time(),
        'email' => 'test' . time() . '@example.com',
        'phone_number' => '12345' . substr(time(), -5),
        'address' => '123 Test Street',
        'date_of_birth' => '1990-01-01',
        'identification_type' => 'national_id',
        'identification_number' => 'TEST' . time(),
        'status' => 'active'
    ];
    
    echo "   Attempting to create client with data:\n";
    echo "   - Name: {$testData['name']}\n";
    echo "   - Email: {$testData['email']}\n";
    echo "   - Phone: {$testData['phone_number']}\n";
    
    $clientId = $clientModel->create($testData);
    
    if ($clientId) {
        echo "   ✓ Client created successfully! ID: {$clientId}\n";
        
        // Verify the client was created
        $createdClient = $clientModel->findById($clientId);
        if ($createdClient) {
            echo "   ✓ Client verified in database\n";
            echo "   Client details: " . json_encode($createdClient, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "   ✗ Client created but not found in database!\n";
        }
    } else {
        echo "   ✗ Failed to create client\n";
        $error = $clientModel->getLastError();
        echo "   Error: " . ($error ?: "Unknown error") . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Exception: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n\n";
}

// Test 4: Test ClientService
echo "4. Testing ClientService...\n";
try {
    $clientService = new ClientService();
    
    // Test data
    $testData = [
        'name' => 'Service Test Client ' . time(),
        'email' => 'service' . time() . '@example.com',
        'phone_number' => '98765' . substr(time(), -5),
        'address' => '456 Service Avenue',
        'date_of_birth' => '1985-05-15',
        'identification_type' => 'passport',
        'identification_number' => 'SVCTEST' . time(),
        'status' => 'active'
    ];
    
    echo "   Attempting to create client via service...\n";
    $clientId = $clientService->createClient($testData);
    
    if ($clientId) {
        echo "   ✓ Client created via service! ID: {$clientId}\n";
    } else {
        echo "   ✗ Failed to create client via service\n";
        $error = $clientService->getErrorMessage();
        echo "   Error: " . ($error ?: "Unknown error") . "\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Exception: " . $e->getMessage() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n\n";
}

// Test 5: Check for common validation issues
echo "5. Testing validation rules...\n";

// Test invalid phone number
echo "   a) Testing invalid phone number format...\n";
try {
    $clientService = new ClientService();
    $testData = [
        'name' => 'Invalid Phone Test',
        'email' => 'invalid' . time() . '@example.com',
        'phone_number' => '123', // Too short
        'address' => '789 Invalid Street',
        'date_of_birth' => '1990-01-01',
        'identification_type' => 'national_id',
        'identification_number' => 'INVALID' . time(),
        'status' => 'active'
    ];
    
    $result = $clientService->createClient($testData);
    if (!$result) {
        echo "      ✓ Validation caught invalid phone: " . $clientService->getErrorMessage() . "\n";
    } else {
        echo "      ✗ Invalid phone was accepted!\n";
    }
} catch (Exception $e) {
    echo "      ✗ Exception: " . $e->getMessage() . "\n";
}

// Test invalid email
echo "   b) Testing invalid email format...\n";
try {
    $clientService = new ClientService();
    $testData = [
        'name' => 'Invalid Email Test',
        'email' => 'not-an-email', // Invalid format
        'phone_number' => '12345678901',
        'address' => '789 Invalid Street',
        'date_of_birth' => '1990-01-01',
        'identification_type' => 'national_id',
        'identification_number' => 'INVALIDEMAIL' . time(),
        'status' => 'active'
    ];
    
    $result = $clientService->createClient($testData);
    if (!$result) {
        echo "      ✓ Validation caught invalid email: " . $clientService->getErrorMessage() . "\n";
    } else {
        echo "      ✗ Invalid email was accepted!\n";
    }
} catch (Exception $e) {
    echo "      ✗ Exception: " . $e->getMessage() . "\n";
}

// Test underage client
echo "   c) Testing underage client (< 18 years)...\n";
try {
    $clientService = new ClientService();
    $testData = [
        'name' => 'Underage Test',
        'email' => 'underage' . time() . '@example.com',
        'phone_number' => '12345678902',
        'address' => '789 Invalid Street',
        'date_of_birth' => date('Y-m-d', strtotime('-10 years')), // 10 years old
        'identification_type' => 'national_id',
        'identification_number' => 'UNDERAGE' . time(),
        'status' => 'active'
    ];
    
    $result = $clientService->createClient($testData);
    if (!$result) {
        echo "      ✓ Validation caught underage client: " . $clientService->getErrorMessage() . "\n";
    } else {
        echo "      ✗ Underage client was accepted!\n";
    }
} catch (Exception $e) {
    echo "      ✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
