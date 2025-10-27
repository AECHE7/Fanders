<?php
/**
 * TransactionService - Handles transaction/event logging for the microfinance system
 *
 * This service provides methods to log all system events including:
 * - User authentication events (login, logout, session extension)
 * - CRUD operations for clients, loans, payments, and users
 * - System events and configuration changes
 * 
 * Uses transaction_logs table exclusively for comprehensive audit trail
 */

require_once BASE_PATH . '/app/core/BaseService.php';
require_once BASE_PATH . '/app/models/TransactionLogModel.php';

class TransactionService extends BaseService {
    private $transactionLogModel;

    public function __construct() {
        parent::__construct();
        $this->transactionLogModel = new TransactionLogModel();
    }

    /**
     * Logs a user login event.
     * @param int $userId User ID
     * @param array $additionalData Additional login data (IP, user agent, etc.)
     * @return bool Success status
     */
    public function logUserLogin($userId, $additionalData = []) {
        $details = array_merge([
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ], $additionalData);

        return $this->transactionLogModel->create([
            'entity_type' => 'user',
            'entity_id' => $userId,
            'action' => 'login',
            'user_id' => $userId,
            'details' => json_encode($details),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }

    /**
     * Logs a user logout event.
     * @param int $userId User ID
     * @param array $additionalData Additional logout data
     * @return bool Success status
     */
    public function logUserLogout($userId, $additionalData = []) {
        return $this->transactionLogModel->create([
            'entity_type' => 'user',
            'entity_id' => $userId,
            'action' => 'logout',
            'user_id' => $userId,
            'details' => json_encode($additionalData),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }

    /**
     * Logs a session extension event.
     * @param int $userId User ID
     * @param array $additionalData Additional session data
     * @return bool Success status
     */
    public function logSessionExtended($userId, $additionalData = []) {
        return $this->transactionLogModel->create([
            'entity_type' => 'user',
            'entity_id' => $userId,
            'action' => 'session_extended',
            'user_id' => $userId,
            'details' => json_encode($additionalData),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }

    /**
     * Logs user CRUD operations.
     * @param string $action Action type (created, updated, deleted, viewed)
     * @param int $userId User performing the action
     * @param int|null $targetUserId Target user ID (for updates/deletes)
     * @param array $additionalData Additional data
     * @return bool Success status
     */
    public function logUserTransaction($action, $userId, $targetUserId = null, $additionalData = []) {
        $details = array_merge([
            'target_user_id' => $targetUserId
        ], $additionalData);

        return $this->transactionLogModel->create([
            'entity_type' => 'user',
            'entity_id' => $targetUserId,
            'action' => $action,
            'user_id' => $userId,
            'details' => json_encode($details),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }

    /**
     * Logs client CRUD operations.
     * @param string $action Action type (created, updated, deleted, viewed)
     * @param int $userId User performing the action
     * @param int|null $clientId Client ID
     * @param array $additionalData Additional data
     * @return bool Success status
     */
    public function logClientTransaction($action, $userId, $clientId = null, $additionalData = []) {
        return $this->transactionLogModel->create([
            'entity_type' => 'client',
            'entity_id' => $clientId,
            'action' => $action,
            'user_id' => $userId,
            'details' => json_encode($additionalData),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }

    /**
     * Logs loan CRUD operations.
     * @param string $action Action type (created, updated, approved, disbursed, completed, cancelled, deleted, viewed)
     * @param int $userId User performing the action
     * @param int|null $loanId Loan ID
     * @param array $additionalData Additional data
     * @return bool Success status
     */
    public function logLoanTransaction($action, $userId, $loanId = null, $additionalData = []) {
        return $this->transactionLogModel->create([
            'entity_type' => 'loan',
            'entity_id' => $loanId,
            'action' => $action,
            'user_id' => $userId,
            'details' => json_encode($additionalData),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }

    /**
     * Logs payment CRUD operations.
     * @param string $action Action type (created, recorded, approved, cancelled, overdue, viewed)
     * @param int $userId User performing the action
     * @param int|null $paymentId Payment ID
     * @param array $additionalData Additional data
     * @return bool Success status
     */
    public function logPaymentTransaction($action, $userId, $paymentId = null, $additionalData = []) {
        return $this->transactionLogModel->create([
            'entity_type' => 'payment',
            'entity_id' => $paymentId,
            'action' => $action,
            'user_id' => $userId,
            'details' => json_encode($additionalData),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }

    /**
     * Logs system events.
     * @param string $action Action type (backup, backup_restored, config_changed, maintenance)
     * @param int $userId User performing the action
     * @param array $additionalData Additional data
     * @return bool Success status
     */
    public function logSystemTransaction($action, $userId, $additionalData = []) {
        return $this->transactionLogModel->create([
            'entity_type' => 'system',
            'entity_id' => null,
            'action' => $action,
            'user_id' => $userId,
            'details' => json_encode($additionalData),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }

    /**
     * Gets transaction history with filtering.
     * @param array $filters Filter options
     * @param int $limit Number of records
     * @param int $offset Offset for pagination
     * @return array
     */
    public function getTransactionHistory($filters = [], $limit = 50, $offset = 0) {
        // Map old filter keys to new structure if needed
        $mappedFilters = [];
        
        if (!empty($filters['date_from'])) {
            $mappedFilters['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $mappedFilters['date_to'] = $filters['date_to'];
        }
        if (!empty($filters['user_id'])) {
            $mappedFilters['user_id'] = $filters['user_id'];
        }
        if (!empty($filters['entity_type'])) {
            $mappedFilters['entity_type'] = $filters['entity_type'];
        }
        if (!empty($filters['search'])) {
            $mappedFilters['search'] = $filters['search'];
        }
        
        return $this->transactionLogModel->getFilteredLogs($mappedFilters, $limit, $offset);
    }

    /**
     * Gets transaction statistics.
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array
     */
    public function getTransactionStats($startDate, $endDate) {
        return $this->transactionLogModel->getTransactionStats($startDate, $endDate);
    }

    /**
     * Gets the count of filtered transactions for pagination.
     * @param array $filters Filter options
     * @return int
     */
    public function getTransactionCount($filters = []) {
        return $this->transactionLogModel->getFilteredCount($filters);
    }

    /**
     * Gets a transaction by ID with user information.
     * @param int $transactionId Transaction ID
     * @return array|null Transaction data or null if not found
     */
    public function getTransactionById($transactionId) {
        $sql = "SELECT tl.*, u.name as user_name, u.email as user_email
                FROM transaction_logs tl
                LEFT JOIN users u ON tl.user_id = u.id
                WHERE tl.id = ?";
        
        $result = $this->transactionLogModel->query($sql, [$transactionId]);
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Gets all available transaction types.
     * @return array
     */
    public function getTransactionTypes() {
        return [
            // Authentication
            'login' => 'User Login',
            'logout' => 'User Logout',
            'session_extended' => 'Session Extended',
            
            // User management
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'viewed' => 'Viewed',
            
            // Loan lifecycle
            'approved' => 'Approved',
            'disbursed' => 'Disbursed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            
            // Payment
            'recorded' => 'Payment Recorded',
            'overdue' => 'Marked Overdue',
            
            // System
            'backup' => 'System Backup',
            'backup_restored' => 'Backup Restored',
            'config_changed' => 'Configuration Changed',
            'maintenance' => 'Database Maintenance',
            
            // Collection sheets
            'collection_sheet_posted' => 'Collection Sheet Posted',
            'collection_sheet_rejected' => 'Collection Sheet Rejected',
            
            // SLR
            'slr_generated' => 'SLR Generated',
            'slr_archived' => 'SLR Archived',
            'slr_accessed' => 'SLR Accessed'
        ];
    }

    /**
     * Logs a generic system event with flexible action and reference.
     * @param string $action Action type/name
     * @param int $userId User performing the action
     * @param int|null $referenceId Reference ID (collection sheet, loan, etc.)
     * @param array $additionalData Additional data
     * @return bool Success status
     */
    public function logGeneric($action, $userId, $referenceId = null, $additionalData = []) {
        $details = array_merge([
            'reference_id' => $referenceId
        ], $additionalData);

        return $this->transactionLogModel->create([
            'entity_type' => 'system',
            'entity_id' => $referenceId,
            'action' => $action,
            'user_id' => $userId,
            'details' => json_encode($details),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }

    /**
     * Exports transactions to PDF.
     * @param array $transactions Transaction data
     * @param array $filters Applied filters
     * @return string PDF output
     */
    public function exportTransactionsPDF($transactions, $filters = []) {
        require_once BASE_PATH . '/app/utilities/PDFGenerator.php';
        $pdf = new PDFGenerator();
        $pdf->setTitle('Transaction History Report');

        $title = 'Transaction History Report';
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $title .= ' (' . $filters['date_from'] . ' to ' . $filters['date_to'] . ')';
        }

        $pdf->addHeader($title);
        $pdf->addLine('Generated on: ' . date('Y-m-d H:i:s'));
        $pdf->addSpace();

        // Define columns
        $columns = [
            ['header' => 'Date & Time', 'width' => 30],
            ['header' => 'User', 'width' => 35],
            ['header' => 'Entity', 'width' => 25],
            ['header' => 'Action', 'width' => 30],
            ['header' => 'Entity ID', 'width' => 20],
            ['header' => 'Details', 'width' => 60]
        ];

        // Prepare data
        $tableData = [];

        foreach ($transactions as $transaction) {
            $details = json_decode($transaction['details'], true);
            $detailText = '';

            if ($details) {
                if (isset($details['amount'])) {
                    $detailText = '₱' . number_format($details['amount'], 2);
                } elseif (isset($details['principal'])) {
                    $detailText = '₱' . number_format($details['principal'], 2);
                } elseif (isset($details['message'])) {
                    $detailText = substr($details['message'], 0, 40);
                } else {
                    $detailText = substr(json_encode($details), 0, 40);
                }
            }

            $tableData[] = [
                date('M j, Y H:i', strtotime($transaction['timestamp'] ?? $transaction['created_at'])),
                $transaction['user_name'] ?? 'System',
                ucfirst($transaction['entity_type']),
                ucfirst(str_replace('_', ' ', $transaction['action'])),
                $transaction['entity_id'] ?? '-',
                $detailText ?: '-'
            ];
        }

        $pdf->addTable($columns, $tableData);

        // Add summary
        $pdf->addSpace();
        $pdf->addSubHeader('Summary');
        $totalTransactions = count($transactions);
        $pdf->addLine("Total Transactions: $totalTransactions");

        if (!empty($filters['entity_type'])) {
            $pdf->addLine("Filtered by Entity: " . ucfirst($filters['entity_type']));
        }

        if (!empty($filters['action'])) {
            $pdf->addLine("Filtered by Action: " . ucfirst(str_replace('_', ' ', $filters['action'])));
        }

        return $pdf->output('D', 'transaction_history_' . date('Y-m-d') . '.pdf');
    }
}
