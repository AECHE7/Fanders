<?php
/**
 * Test CSRF Token Flow for Client View
 * This script simulates the CSRF token generation and validation
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting test...\n";

try {
    require_once __DIR__ . '/public/init.php';
    echo "Init loaded successfully\n";
} catch (Exception $e) {
    echo "Error loading init: " . $e->getMessage() . "\n";
    exit(1);
}

echo "=== CSRF Token Flow Test ===\n\n";

// Simulate the view.php token generation
echo "1. Generating token (as done in view.php):\n";
$csrfToken = $csrf->generateToken();
echo "   Generated Token: " . $csrfToken . "\n";
echo "   Token stored in session with name: 'csrf_token'\n\n";

// Simulate form submission
echo "2. Simulating form submission:\n";
$_POST['csrf_token'] = $csrfToken;
$_POST['action'] = 'activate';
echo "   POST['csrf_token'] = " . $_POST['csrf_token'] . "\n";
echo "   POST['action'] = " . $_POST['action'] . "\n\n";

// Validate (without regeneration for testing)
echo "3. Validating token:\n";
$isValid = $csrf->validateRequest(false);
echo "   Validation Result: " . ($isValid ? "✅ VALID" : "❌ INVALID") . "\n\n";

if ($isValid) {
    echo "✅ SUCCESS: CSRF token validation works correctly!\n";
    echo "The client status management should work now.\n";
} else {
    echo "❌ FAILURE: CSRF token validation still failing.\n";
    echo "Further investigation needed.\n";
}

// Test with custom token name (the old problematic way)
echo "\n=== Testing OLD Method (with setTokenName) ===\n\n";
$csrf2 = new CSRF();
$csrf2->setTokenName('csrf_token_view');
echo "1. Set custom token name to 'csrf_token_view'\n";
$customToken = $csrf2->generateToken();
echo "   Generated Token: " . $customToken . "\n";
echo "   Token stored in session with name: 'csrf_token_view'\n\n";

// Simulate form with default name
$_POST['csrf_token'] = $customToken;
echo "2. Form submits with name='csrf_token'\n";
echo "   POST['csrf_token'] = " . $_POST['csrf_token'] . "\n\n";

echo "3. Validating (looking for 'csrf_token_view' in POST):\n";
$isValid2 = $csrf2->validateRequest(false);
echo "   Validation Result: " . ($isValid2 ? "✅ VALID" : "❌ INVALID") . "\n\n";

if (!$isValid2) {
    echo "❌ This confirms the bug: Token name mismatch causes validation to fail.\n";
    echo "   Session has: 'csrf_token_view'\n";
    echo "   POST has: 'csrf_token'\n";
    echo "   They don't match, so validation fails.\n";
}

echo "\n=== Test Complete ===\n";
