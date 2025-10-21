<?php
/**
 * Test Client CSRF Fix
 * This test verifies that the CSRF token issue in client creation is resolved.
 */

// Set up test environment
require_once 'app/config/config.php';
require_once 'vendor/autoload.php';

function autoload($className) {
    $directories = [
        'app/core/',
        'app/models/',
        'app/services/',
        'app/utilities/'
    ];

    foreach ($directories as $directory) {
        $file = BASE_PATH . '/' . $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}
spl_autoload_register('autoload');

// Initialize services
$session = new Session();
$csrf = new CSRF();

echo "=== Testing CSRF Fix for Client Creation ===\n\n";

// Test 1: CSRF Token Generation
echo "1. Testing CSRF token generation...\n";
$token1 = $csrf->getToken();
echo "   Token generated: " . substr($token1, 0, 16) . "...\n";

// Test 2: Token persistence
echo "2. Testing token persistence...\n";
$token2 = $csrf->getToken();
$same = ($token1 === $token2);
echo "   Same token returned: " . ($same ? "YES" : "NO") . "\n";

// Test 3: Token validation without regeneration
echo "3. Testing token validation without regeneration...\n";
$_POST['csrf_token'] = $token1;
$valid = $csrf->validateRequest(false); // Don't regenerate
echo "   Token valid: " . ($valid ? "YES" : "NO") . "\n";

// Test 4: Token should still be the same after validation
echo "4. Testing token remains same after validation...\n";
$token3 = $csrf->getToken();
$stillSame = ($token1 === $token3);
echo "   Token unchanged: " . ($stillSame ? "YES" : "NO") . "\n";

// Test 5: Multiple validations should work
echo "5. Testing multiple validations...\n";
$_POST['csrf_token'] = $token1;
$valid1 = $csrf->validateRequest(false);
$_POST['csrf_token'] = $token1;
$valid2 = $csrf->validateRequest(false);
echo "   First validation: " . ($valid1 ? "PASS" : "FAIL") . "\n";
echo "   Second validation: " . ($valid2 ? "PASS" : "FAIL") . "\n";

// Clean up
unset($_POST['csrf_token']);

echo "\n=== Test Complete ===\n";
echo "CSRF fix should now prevent 'Invalid security token' errors\n";
echo "when multiple AJAX requests (like session timeout checks) are happening.\n\n";

// Summary
if ($same && $valid && $stillSame && $valid1 && $valid2) {
    echo "✅ ALL TESTS PASSED - CSRF fix is working correctly!\n";
} else {
    echo "❌ SOME TESTS FAILED - Please review the implementation.\n";
}
?>