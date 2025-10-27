<?php
/**
 * Collection Automation API - Handles automated collection sheet operations
 */
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

// Ensure only authorized users can access
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

if (!$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account_officer'], false)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
    exit;
}

// CSRF protection for non-GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !$csrf->validateRequest(false)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid security token']);
    exit;
}

$service = new CollectionSheetService();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add_loan_automated':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $sheetId = (int)($_POST['sheet_id'] ?? 0);
            $loanId = (int)($_POST['loan_id'] ?? 0);
            $options = [
                'auto_calculate' => filter_var($_POST['auto_calculate'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'lock_form' => filter_var($_POST['lock_form'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'auto_notes' => filter_var($_POST['auto_notes'] ?? true, FILTER_VALIDATE_BOOLEAN)
            ];
            
            if (!$sheetId || !$loanId) {
                throw new Exception('Sheet ID and Loan ID are required');
            }
            
            $result = $service->addLoanAutomated($sheetId, $loanId, $options);
            
            if ($result) {
                echo json_encode([
                    'success' => true, 
                    'data' => $result,
                    'message' => 'Loan added to collection sheet automatically'
                ]);
            } else {
                throw new Exception($service->getErrorMessage() ?: 'Failed to add loan to collection sheet');
            }
            break;
            
        case 'auto_collect_clients':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $sheetId = (int)($_POST['sheet_id'] ?? 0);
            $clientIds = $_POST['client_ids'] ?? [];
            $options = [
                'only_due_payments' => filter_var($_POST['only_due_payments'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'max_per_client' => (int)($_POST['max_per_client'] ?? 1),
                'auto_submit' => filter_var($_POST['auto_submit'] ?? false, FILTER_VALIDATE_BOOLEAN)
            ];
            
            if (!$sheetId || empty($clientIds)) {
                throw new Exception('Sheet ID and client IDs are required');
            }
            
            if (!is_array($clientIds)) {
                $clientIds = explode(',', $clientIds);
            }
            $clientIds = array_map('intval', $clientIds);
            
            $results = $service->autoCollectForClients($sheetId, $clientIds, $options);
            
            $totalAdded = 0;
            foreach ($results as $clientResults) {
                $totalAdded += count($clientResults);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $results,
                'summary' => [
                    'clients_processed' => count($clientIds),
                    'total_loans_added' => $totalAdded
                ],
                'message' => "Auto-collected payments for {$totalAdded} loans from " . count($clientIds) . " clients"
            ]);
            break;
            
        case 'enable_automation':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $sheetId = (int)($_POST['sheet_id'] ?? 0);
            $settings = [
                'auto_calculate' => filter_var($_POST['auto_calculate'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'lock_after_add' => filter_var($_POST['lock_after_add'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'auto_submit_when_complete' => filter_var($_POST['auto_submit_when_complete'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'prevent_manual_entry' => filter_var($_POST['prevent_manual_entry'] ?? true, FILTER_VALIDATE_BOOLEAN)
            ];
            
            if (!$sheetId) {
                throw new Exception('Sheet ID is required');
            }
            
            $success = $service->enableAutomatedMode($sheetId, $settings);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'data' => ['sheet_id' => $sheetId, 'settings' => $settings],
                    'message' => 'Automated mode enabled for collection sheet'
                ]);
            } else {
                throw new Exception('Failed to enable automated mode');
            }
            break;
            
        case 'get_due_loans':
            $clientIds = $_GET['client_ids'] ?? [];
            if (!empty($clientIds)) {
                if (!is_array($clientIds)) {
                    $clientIds = explode(',', $clientIds);
                }
                $clientIds = array_map('intval', $clientIds);
            }
            
            $loanModel = new LoanModel();
            $dueLoans = $loanModel->getLoansRequiringPayment($clientIds);
            
            echo json_encode([
                'success' => true,
                'data' => $dueLoans,
                'count' => count($dueLoans),
                'message' => count($dueLoans) . ' loans requiring payment found'
            ]);
            break;
            
        case 'get_automation_status':
            $sheetId = (int)($_GET['sheet_id'] ?? 0);
            
            if (!$sheetId) {
                throw new Exception('Sheet ID is required');
            }
            
            $sheetModel = new CollectionSheetModel();
            $metadata = $sheetModel->getAutomationMetadata($sheetId);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'sheet_id' => $sheetId,
                    'automated_mode' => $metadata !== null,
                    'settings' => $metadata['automation_settings'] ?? null,
                    'enabled_at' => $metadata['enabled_at'] ?? null
                ]
            ]);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}