<?php
/**
 * Debug API authentication
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug: Starting API test\n";

// Set skip flag
$GLOBALS['skip_auth_check'] = true;
echo "Debug: Set skip_auth_check to true\n";

// Test if the variable is available
if (isset($GLOBALS['skip_auth_check'])) {
    echo "Debug: GLOBALS skip_auth_check is set to: " . ($GLOBALS['skip_auth_check'] ? 'true' : 'false') . "\n";
} else {
    echo "Debug: GLOBALS skip_auth_check is NOT set\n";
}

header('Content-Type: application/json');

require_once '../../public/init.php';

echo json_encode(['success' => true, 'message' => 'API authentication test passed']);
?>