<?php
/**
 * API Endpoint: Get Transaction Details - Debug Version
 * Returns comprehensive information about a specific transaction
 */

// Enable error reporting but capture errors
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors directly
ob_start(); // Start output buffering to catch any unexpected output

header('Content-Type: application/json');

try {
    // Try to include init.php
    if (!file_exists('../../public/init.php')) {
        throw new Exception('init.php not found');
    }
    
    require_once '../../public/init.php';
    
    // Check if required services exist
    $requiredFiles = [
        '../../app/services/TransactionService.php',
        '../../app/models/TransactionLogModel.php',
        '../../app/services/UserService.php',
        '../../app/services/ClientService.php',
        '../../app/services/LoanService.php'
    ];
    
    foreach ($requiredFiles as $file) {
        if (!file_exists($file)) {
            throw new Exception("Required file not found: $file");
        }
        require_once $file;
    }
    
    // Check if user is authenticated
    if (!isset($auth) || !$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    // Get transaction ID
    $transactionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$transactionId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Transaction ID is required']);
        exit;
    }
    
    // Create services
    $transactionService = new TransactionService();
    $transactionLogModel = new TransactionLogModel();
    
    // Get the transaction details
    $transaction = $transactionLogModel->findById($transactionId);
    
    if (!$transaction) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }
    
    // Simple response for now
    echo json_encode([
        'success' => true,
        'transaction' => [
            'id' => $transaction['id'],
            'action' => $transaction['action'],
            'timestamp' => $transaction['timestamp'],
            'entity_type' => $transaction['entity_type'],
            'entity_id' => $transaction['entity_id']
        ]
    ]);
    
} catch (Exception $e) {
    // Clear any previous output
    ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Internal server error',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    // Clear any previous output
    ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'PHP Fatal Error',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

// Clean up output buffering
ob_end_flush();
?>