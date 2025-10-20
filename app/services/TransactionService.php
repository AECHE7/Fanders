<?php
/**
 * TransactionService - Handles transaction/event logging for the microfinance system
 *
 * This service provides methods to log all system events including:
 * - User authentication events (login, logout, session extension)
 * - CRUD operations for clients, loans, payments, and users
 * - System events and configuration changes
 */

require_once BASE_PATH . '/app/core/BaseService.php';
require_once BASE_PATH . '/app/models/TransactionModel.php';
require_once BASE_PATH . '/app/models/TransactionLogModel.php';

class TransactionService extends BaseService {
    private $transactionModel;
    private $transactionLogModel;

    public function __construct() {
        parent::__construct();
        $this->transactionModel = new TransactionModel();
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
            'action' => 'login',
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ], $additionalData);

        return $this->transactionModel->create([
            'user_id' => $userId,
            'transaction_type' => TransactionModel::TYPE_LOGIN,
            'details' => $details
        ]);
    }

    /**
     * Logs a user logout event.
     * @param int $userId User ID
     * @param array $additionalData Additional logout data
     * @return bool Success status
     */
    public function logUserLogout($userId, $additionalData = []) {
        $details = array_merge([
            'action' => 'logout',
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ], $additionalData);

        return $this->transactionModel->create([
            'user_id' => $userId,
            'transaction_type' => TransactionModel::TYPE_LOGOUT,
            'details' => $details
        ]);
    }

    /**
     * Logs a session extension event.
     * @param int $userId User ID
     * @param array $additionalData Additional session data
     * @return bool Success status
     */
    public function logSessionExtended($userId, $additionalData = []) {
        $details = array_merge([
            'action' => 'session_extended',
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ], $additionalData);

        return $this->transactionModel->create([
            'user_id' => $userId,
            'transaction_type' => TransactionModel::TYPE_SESSION_EXTENDED,
            'details' => $details
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
        $transactionTypes = [
            'created' => TransactionModel::TYPE_USER_CREATED,
            'updated' => TransactionModel::TYPE_USER_UPDATED,
            'deleted' => TransactionModel::TYPE_USER_DELETED,
            'viewed' => TransactionModel::TYPE_USER_VIEWED
        ];

        $transactionType = $transactionTypes[$action] ?? TransactionModel::TYPE_USER_VIEWED;

        $details = array_merge([
            'action' => $action,
            'timestamp' => date('Y-m-d H:i:s'),
            'target_user_id' => $targetUserId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ], $additionalData);

        return $this->transactionModel->create([
            'user_id' => $userId,
            'transaction_type' => $transactionType,
            'reference_id' => $targetUserId,
            'details' => $details
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
        $transactionTypes = [
            'created' => TransactionModel::TYPE_CLIENT_CREATED,
            'updated' => TransactionModel::TYPE_CLIENT_UPDATED,
            'deleted' => TransactionModel::TYPE_CLIENT_DELETED,
            'viewed' => TransactionModel::TYPE_CLIENT_VIEWED
        ];

        $transactionType = $transactionTypes[$action] ?? TransactionModel::TYPE_CLIENT_VIEWED;

        $details = array_merge([
            'action' => $action,
            'timestamp' => date('Y-m-d H:i:s'),
            'client_id' => $clientId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ], $additionalData);

        return $this->transactionModel->create([
            'user_id' => $userId,
            'transaction_type' => $transactionType,
            'reference_id' => $clientId,
            'details' => $details
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
        $transactionTypes = [
            'created' => TransactionModel::TYPE_LOAN_CREATED,
            'updated' => TransactionModel::TYPE_LOAN_UPDATED,
            'approved' => TransactionModel::TYPE_LOAN_APPROVED,
            'disbursed' => TransactionModel::TYPE_LOAN_DISBURSED,
            'completed' => TransactionModel::TYPE_LOAN_COMPLETED,
            'cancelled' => TransactionModel::TYPE_LOAN_CANCELLED,
            'deleted' => TransactionModel::TYPE_LOAN_DELETED,
            'viewed' => TransactionModel::TYPE_LOAN_VIEWED
        ];

        $transactionType = $transactionTypes[$action] ?? TransactionModel::TYPE_LOAN_VIEWED;

        $details = array_merge([
            'action' => $action,
            'timestamp' => date('Y-m-d H:i:s'),
            'loan_id' => $loanId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ], $additionalData);

        return $this->transactionModel->create([
            'user_id' => $userId,
            'transaction_type' => $transactionType,
            'reference_id' => $loanId,
            'details' => $details
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
        $transactionTypes = [
            'created' => TransactionModel::TYPE_PAYMENT_CREATED,
            'recorded' => TransactionModel::TYPE_PAYMENT_RECORDED,
            'approved' => TransactionModel::TYPE_PAYMENT_APPROVED,
            'cancelled' => TransactionModel::TYPE_PAYMENT_CANCELLED,
            'overdue' => TransactionModel::TYPE_PAYMENT_OVERDUE,
            'viewed' => TransactionModel::TYPE_PAYMENT_VIEWED
        ];

        $transactionType = $transactionTypes[$action] ?? TransactionModel::TYPE_PAYMENT_VIEWED;

        $details = array_merge([
            'action' => $action,
            'timestamp' => date('Y-m-d H:i:s'),
            'payment_id' => $paymentId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ], $additionalData);

        return $this->transactionModel->create([
            'user_id' => $userId,
            'transaction_type' => $transactionType,
            'reference_id' => $paymentId,
            'details' => $details
        ]);
    }

    /**
     * Logs system events.
     * @param string $action Action type (backup, config_changed, maintenance)
     * @param int $userId User performing the action
     * @param array $additionalData Additional data
     * @return bool Success status
     */
    public function logSystemTransaction($action, $userId, $additionalData = []) {
        $transactionTypes = [
            'backup' => TransactionModel::TYPE_SYSTEM_BACKUP,
            'config_changed' => TransactionModel::TYPE_SYSTEM_CONFIG_CHANGED,
            'maintenance' => TransactionModel::TYPE_DATABASE_MAINTENANCE
        ];

        $transactionType = $transactionTypes[$action] ?? TransactionModel::TYPE_SYSTEM_CONFIG_CHANGED;

        $details = array_merge([
            'action' => $action,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ], $additionalData);

        return $this->transactionModel->create([
            'user_id' => $userId,
            'transaction_type' => $transactionType,
            'details' => $details
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
        return $this->transactionModel->getFilteredTransactions($filters, $limit, $offset);
    }

    /**
     * Gets transaction statistics.
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array
     */
    public function getTransactionStats($startDate, $endDate) {
        return $this->transactionModel->getTransactionStats($startDate, $endDate);
    }

    /**
     * Gets the count of filtered transactions for pagination.
     * @param array $filters Filter options
     * @return int
     */
    public function getTransactionCount($filters = []) {
        return $this->transactionModel->getFilteredCount($filters);
    }

    /**
     * Gets all available transaction types.
     * @return array
     */
    public function getTransactionTypes() {
        return TransactionModel::getTransactionTypes();
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
            ['header' => 'Action', 'width' => 40],
            ['header' => 'Type', 'width' => 25],
            ['header' => 'Reference ID', 'width' => 25],
            ['header' => 'Details', 'width' => 45]
        ];

        // Prepare data
        $tableData = [];
        $transactionTypes = $this->getTransactionTypes();

        foreach ($transactions as $transaction) {
            $details = json_decode($transaction['details'], true);
            $detailText = '';

            if ($details) {
                if (isset($details['amount'])) {
                    $detailText = '₱' . number_format($details['amount'], 2);
                } elseif (isset($details['principal'])) {
                    $detailText = '₱' . number_format($details['principal'], 2);
                } elseif (isset($details['message'])) {
                    $detailText = substr($details['message'], 0, 30);
                } elseif (isset($details['ip_address'])) {
                    $detailText = 'IP: ' . $details['ip_address'];
                }
            }

            $tableData[] = [
                date('M j, Y H:i', strtotime($transaction['created_at'])),
                $transaction['user_name'] ?? 'Unknown',
                $transactionTypes[$transaction['transaction_type']] ?? ucfirst(str_replace('_', ' ', $transaction['transaction_type'])),
                ucfirst($transaction['transaction_type']),
                $transaction['reference_id'] ?? '-',
                $detailText ?: '-'
            ];
        }

        $pdf->addTable($columns, $tableData);

        // Add summary
        $pdf->addSpace();
        $pdf->addSubHeader('Summary');
        $totalTransactions = count($transactions);
        $pdf->addLine("Total Transactions: $totalTransactions");

        if (!empty($filters['transaction_type'])) {
            $typeName = $transactionTypes[$filters['transaction_type']] ?? ucfirst(str_replace('_', ' ', $filters['transaction_type']));
            $pdf->addLine("Filtered by Type: $typeName");
        }

        return $pdf->output('D', 'transaction_history_' . date('Y-m-d') . '.pdf');
    }
}
