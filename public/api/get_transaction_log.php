<?php
/**
 * API: Get Transaction Log Details
 * Returns JSON details for a given transaction log id
 */

header('Content-Type: application/json');
// Skip the authentication redirect for API endpoints
$GLOBALS['skip_auth_check'] = true;
require_once '../../public/init.php';

// Authentication
if (!isset($auth) || !$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$transactionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$transactionId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing transaction id']);
    exit;
}

require_once '../../app/services/TransactionService.php';
$transactionService = new TransactionService();
try {
    $log = $transactionService->getTransactionById($transactionId);
    if (!$log) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }

    // Decode details JSON if present
    $log['details'] = null;
    if (!empty($log['details'])) {
        $decoded = json_decode($log['details'], true);
        $log['details'] = $decoded === null ? $log['details'] : $decoded;
    }

    echo json_encode(['success' => true, 'transaction' => $log]);
} catch (Exception $e) {
    http_response_code(500);
    error_log('API get_transaction_log error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error']);
}