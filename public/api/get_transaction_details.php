<?php
/**
 * API Endpoint: Get Transaction Details
 * Returns comprehensive information about a specific transaction
 */

header('Content-Type: application/json');

require_once '../../public/init.php';

// Check if user is authenticated
if (!$auth->isLoggedIn()) {
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

try {
    $transactionService = new TransactionService();
    $transactionLogModel = new TransactionLogModel();
    
    // Get the transaction details
    $transaction = $transactionLogModel->findById($transactionId);
    
    if (!$transaction) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        exit;
    }
    
    // Get user information if available
    $userData = null;
    if ($transaction['user_id']) {
        $userService = new UserService();
        $userData = $userService->getUserById($transaction['user_id']);
    }
    
    // Parse details JSON
    $details = json_decode($transaction['details'], true) ?? [];
    
    // Get related entity information based on entity type
    $entityData = null;
    $entityName = 'Unknown';
    
    switch ($transaction['entity_type']) {
        case 'client':
            if ($transaction['entity_id']) {
                $clientService = new ClientService();
                $entityData = $clientService->getClientById($transaction['entity_id']);
                $entityName = $entityData ? $entityData['name'] : 'Deleted Client';
            }
            break;
            
        case 'loan':
            if ($transaction['entity_id']) {
                $loanService = new LoanService();
                $entityData = $loanService->getLoanById($transaction['entity_id']);
                if ($entityData) {
                    $clientService = new ClientService();
                    $clientData = $clientService->getClientById($entityData['client_id']);
                    $entityName = $clientData ? $clientData['name'] . ' (Loan #' . $entityData['id'] . ')' : 'Loan #' . $entityData['id'];
                } else {
                    $entityName = 'Deleted Loan';
                }
            }
            break;
            
        case 'payment':
            if ($transaction['entity_id']) {
                // Assuming there's a payment service
                $entityName = 'Payment #' . $transaction['entity_id'];
            }
            break;
            
        case 'user':
            if ($transaction['entity_id']) {
                $userService = new UserService();
                $targetUser = $userService->getUserById($transaction['entity_id']);
                $entityName = $targetUser ? $targetUser['name'] : 'Deleted User';
            }
            break;
            
        default:
            $entityName = ucfirst($transaction['entity_type']);
    }
    
    // Format the response
    $response = [
        'success' => true,
        'transaction' => [
            'id' => $transaction['id'],
            'entity_type' => $transaction['entity_type'],
            'entity_id' => $transaction['entity_id'],
            'entity_name' => $entityName,
            'action' => $transaction['action'],
            'action_label' => ucfirst(str_replace('_', ' ', $transaction['action'])),
            'timestamp' => $transaction['timestamp'],
            'formatted_date' => date('M j, Y \a\t g:i A', strtotime($transaction['timestamp'])),
            'ip_address' => $transaction['ip_address'],
            'user' => [
                'id' => $transaction['user_id'],
                'name' => $userData ? $userData['name'] : 'System',
                'role' => $userData ? $userData['role'] : 'system'
            ],
            'details' => $details,
            'formatted_details' => formatTransactionDetails($transaction['action'], $details)
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Transaction details API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

/**
 * Format transaction details based on action type
 */
function formatTransactionDetails($action, $details) {
    $formatted = [];
    
    switch ($action) {
        case 'login':
        case 'logout':
            if (isset($details['user_agent'])) {
                $formatted['Browser'] = substr($details['user_agent'], 0, 100);
            }
            if (isset($details['session_duration'])) {
                $formatted['Session Duration'] = $details['session_duration'] . ' minutes';
            }
            break;
            
        case 'client_created':
        case 'client_updated':
            if (isset($details['client_name'])) {
                $formatted['Client Name'] = $details['client_name'];
            }
            if (isset($details['phone_number'])) {
                $formatted['Phone Number'] = $details['phone_number'];
            }
            if (isset($details['address'])) {
                $formatted['Address'] = $details['address'];
            }
            break;
            
        case 'loan_created':
        case 'loan_approved':
        case 'loan_disbursed':
            if (isset($details['principal'])) {
                $formatted['Principal Amount'] = '₱' . number_format($details['principal'], 2);
            }
            if (isset($details['principal_amount'])) {
                $formatted['Principal Amount'] = '₱' . number_format($details['principal_amount'], 2);
            }
            if (isset($details['interest_rate'])) {
                $formatted['Interest Rate'] = $details['interest_rate'] . '%';
            }
            if (isset($details['term_months'])) {
                $formatted['Term'] = $details['term_months'] . ' months';
            }
            if (isset($details['weekly_payment'])) {
                $formatted['Weekly Payment'] = '₱' . number_format($details['weekly_payment'], 2);
            }
            if (isset($details['loan_officer'])) {
                $formatted['Loan Officer'] = $details['loan_officer'];
            }
            break;
            
        case 'payment_recorded':
        case 'payment_created':
            if (isset($details['amount'])) {
                $formatted['Payment Amount'] = '₱' . number_format($details['amount'], 2);
            }
            if (isset($details['payment_method'])) {
                $formatted['Payment Method'] = ucfirst($details['payment_method']);
            }
            if (isset($details['reference_number'])) {
                $formatted['Reference Number'] = $details['reference_number'];
            }
            break;
            
        case 'user_created':
        case 'user_updated':
            if (isset($details['user_name'])) {
                $formatted['User Name'] = $details['user_name'];
            }
            if (isset($details['role'])) {
                $formatted['Role'] = ucfirst($details['role']);
            }
            if (isset($details['email'])) {
                $formatted['Email'] = $details['email'];
            }
            break;
    }
    
    // Add any additional details that weren't specifically formatted
    foreach ($details as $key => $value) {
        if (!array_key_exists(ucfirst(str_replace('_', ' ', $key)), $formatted) && 
            !in_array($key, ['user_agent']) && // Skip very long fields
            is_scalar($value)) {
            $formatted[ucfirst(str_replace('_', ' ', $key))] = $value;
        }
    }
    
    return $formatted;
}