<?php
/**
 * TransactionService - Handles audit logging for all financial and user operations.
 * This service provides comprehensive transaction logging for compliance and audit trails.
 */
require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/../models/TransactionLogModel.php';

class TransactionService extends BaseService {
    private $transactionLogModel;

    public function __construct() {
        parent::__construct();
        $this->transactionLogModel = new TransactionLogModel();
        $this->setModel($this->transactionLogModel);
    }

    /**
     * Logs a loan-related transaction.
     * @param string $action Action performed (created, approved, disbursed, completed, etc.)
     * @param int $loanId Loan ID
     * @param int|null $userId User performing the action
     * @param array $details Additional transaction details
     * @return bool
     */
    public function logLoanTransaction($action, $loanId, $userId = null, $details = []) {
        $logData = [
            'entity_type' => 'loan',
            'entity_id' => $loanId,
            'action' => $action,
            'user_id' => $userId,
            'details' => json_encode($details),
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ];

        return $this->transactionLogModel->create($logData);
    }

    /**
     * Logs a payment-related transaction.
     * @param int $paymentId Payment ID
     * @param int|null $userId User performing the action
     * @param array $details Additional transaction details
     * @return bool
     */
    public function logPaymentTransaction($paymentId, $userId = null, $details = []) {
        $logData = [
            'entity_type' => 'payment',
            'entity_id' => $paymentId,
            'action' => 'recorded',
            'user_id' => $userId,
            'details' => json_encode($details),
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ];

        return $this->transactionLogModel->create($logData);
    }

    /**
     * Logs a client-related transaction.
     * @param string $action Action performed (created, updated, deactivated, etc.)
     * @param int $clientId Client ID
     * @param int|null $userId User performing the action
     * @param array $details Additional transaction details
     * @return bool
     */
    public function logClientTransaction($action, $clientId, $userId = null, $details = []) {
        $logData = [
            'entity_type' => 'client',
            'entity_id' => $clientId,
            'action' => $action,
            'user_id' => $userId,
            'details' => json_encode($details),
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ];

        return $this->transactionLogModel->create($logData);
    }

    /**
     * Logs a user-related transaction.
     * @param string $action Action performed (created, updated, deactivated, etc.)
     * @param int $userId User ID
     * @param int|null $performedBy User performing the action
     * @param array $details Additional transaction details
     * @return bool
     */
    public function logUserTransaction($action, $userId, $performedBy = null, $details = []) {
        $logData = [
            'entity_type' => 'user',
            'entity_id' => $userId,
            'action' => $action,
            'user_id' => $performedBy,
            'details' => json_encode($details),
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ];

        return $this->transactionLogModel->create($logData);
    }

    /**
     * Retrieves transaction logs for a specific entity.
     * @param string $entityType Entity type (loan, payment, client, user)
     * @param int $entityId Entity ID
     * @param int $limit Number of records to retrieve
     * @return array
     */
    public function getEntityLogs($entityType, $entityId, $limit = 50) {
        return $this->transactionLogModel->getLogsByEntity($entityType, $entityId, $limit);
    }

    /**
     * Retrieves transaction logs for a specific user.
     * @param int $userId User ID
     * @param int $limit Number of records to retrieve
     * @return array
     */
    public function getUserLogs($userId, $limit = 50) {
        return $this->transactionLogModel->getLogsByUser($userId, $limit);
    }

    /**
     * Retrieves transaction logs within a date range.
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @param string|null $entityType Filter by entity type
     * @return array
     */
    public function getLogsByDateRange($startDate, $endDate, $entityType = null) {
        return $this->transactionLogModel->getLogsByDateRange($startDate, $endDate, $entityType);
    }

    /**
     * Retrieves recent transaction logs.
     * @param int $limit Number of records to retrieve
     * @return array
     */
    public function getRecentLogs($limit = 100) {
        return $this->transactionLogModel->getRecentLogs($limit);
    }

    /**
     * Gets transaction statistics for reporting.
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array
     */
    public function getTransactionStats($startDate = null, $endDate = null) {
        if ($startDate === null) {
            $startDate = date('Y-m-d', strtotime('-30 days'));
        }
        if ($endDate === null) {
            $endDate = date('Y-m-d');
        }
        return $this->transactionLogModel->getTransactionStats($startDate, $endDate);
    }

    /**
     * Gets transaction history with pagination.
     * @param int $limit Number of records per page
     * @param int $offset Offset for pagination
     * @return array
     */
    public function getTransactionHistory($limit = 50, $offset = 0) {
        $sql = "SELECT tl.*, u.name as user_name, u.role as user_role
                FROM transaction_logs tl
                LEFT JOIN users u ON tl.user_id = u.id
                ORDER BY tl.timestamp DESC
                LIMIT ? OFFSET ?";

        return $this->db->resultSet($sql, [$limit, $offset]);
    }

    /**
     * Gets total transaction count.
     * @return int
     */
    public function getTotalTransactionCount() {
        $sql = "SELECT COUNT(*) as count FROM transaction_logs";
        $result = $this->db->single($sql);
        return $result['count'] ?? 0;
    }

    /**
     * Searches transactions by term.
     * @param string $searchTerm Search term
     * @param int $limit Number of records to return
     * @return array
     */
    public function searchTransactions($searchTerm, $limit = 50) {
        $sql = "SELECT tl.*, u.name as user_name, u.role as user_role
                FROM transaction_logs tl
                LEFT JOIN users u ON tl.user_id = u.id
                WHERE tl.details LIKE ? OR tl.action LIKE ? OR tl.entity_type LIKE ?
                ORDER BY tl.timestamp DESC
                LIMIT ?";

        $searchPattern = '%' . $searchTerm . '%';
        return $this->db->resultSet($sql, [$searchPattern, $searchPattern, $searchPattern, $limit]);
    }

    /**
     * Gets transactions by type.
     * @param string $type Transaction type
     * @param int $limit Number of records to return
     * @return array
     */
    public function getTransactionsByType($type, $limit = 50) {
        $sql = "SELECT tl.*, u.name as user_name, u.role as user_role
                FROM transaction_logs tl
                LEFT JOIN users u ON tl.user_id = u.id
                WHERE tl.entity_type = ?
                ORDER BY tl.timestamp DESC
                LIMIT ?";

        return $this->db->resultSet($sql, [$type, $limit]);
    }
}
