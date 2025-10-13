<?php
/**
 * Handle approval and rejection of borrow and return requests
 */

require_once '../../app/config/config.php';

ob_start();

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

$session = new Session();
$auth = new AuthService();

if (!$auth->isLoggedIn() || !$auth->hasRole(['super-admin', 'admin'])) {
    $session->setFlash('error', 'You must be logged in as an admin to access this page.');
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

$transactionService = new TransactionService();
$csrf = new CSRF();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$csrf->validateRequest()) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $transactionId = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;
        $action = isset($_POST['action']) ? $_POST['action'] : '';

        if (!$transactionId || !in_array($action, ['approve', 'reject'])) {
            $error = 'Invalid request.';
        } else {
            $transaction = $transactionService->getTransactionById($transactionId);
            if (!$transaction) {
                $error = 'Transaction not found.';
            } else {
                if ($action === 'approve') {
                    if ($transaction['status'] === 'borrowing') {
                        // Approve borrow request
                        $result = $transactionService->approveBorrowRequest($transactionId);
                    } elseif ($transaction['status'] === 'returning') {
                        // Approve return request
                        $result = $transactionService->approveReturnRequest($transactionId);
                    } else {
                        $error = 'Invalid transaction status for approval.';
                    }
                    if (isset($result) && $result) {
                        $success = 'Transaction approved successfully.';
                    } else {
                        $error = $transactionService->getErrorMessage() ?: 'Failed to approve transaction.';
                    }
                } elseif ($action === 'reject') {
                    // Reject request by updating status to 'rejected'
                    $updateData = [
                        'status' => 'rejected',
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    if ($transactionService->updateTransaction($transactionId, $updateData)) {
                        $success = 'Transaction request rejected.';
                    } else {
                        $error = $transactionService->getErrorMessage() ?: 'Failed to reject transaction.';
                    }
                }
            }
        }
    }
}

// Get all pending approval transactions with new statuses
$pendingTransactions = $transactionService->getTransactionsByStatuses(['borrowing', 'returning']);

include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';

include_once BASE_PATH . '/templates/transactions/approvals.php';

include_once BASE_PATH . '/templates/layout/footer.php';
?>
