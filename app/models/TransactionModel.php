<?php
/**
 * TransactionModel - Handles database operations for system transactions/events
 * This model tracks all user actions and system events for audit and monitoring.
 */
require_once __DIR__ . '/../core/BaseModel.php';

class TransactionModel extends BaseModel {
    protected $table = 'transactions';
    protected $fillable = [
        'user_id',          // User who performed the action
        'transaction_type', // Type of transaction (login, logout, client_created, etc.)
        'reference_id',     // ID of the related entity (client_id, loan_id, etc.)
        'details',          // JSON string with additional details
        'created_at'        // Timestamp of the transaction
    ];

    // Transaction types constants
    const TYPE_LOGIN = 'login';
    const TYPE_LOGOUT = 'logout';
    const TYPE_SESSION_EXTENDED = 'session_extended';

    // User transaction types
    const TYPE_USER_CREATED = 'user_created';
    const TYPE_USER_UPDATED = 'user_updated';
    const TYPE_USER_DELETED = 'user_deleted';
    const TYPE_USER_VIEWED = 'user_viewed';

    // Client transaction types
    const TYPE_CLIENT_CREATED = 'client_created';
    const TYPE_CLIENT_UPDATED = 'client_updated';
    const TYPE_CLIENT_DELETED = 'client_deleted';
    const TYPE_CLIENT_VIEWED = 'client_viewed';

    // Loan transaction types
    const TYPE_LOAN_CREATED = 'loan_created';
    const TYPE_LOAN_UPDATED = 'loan_updated';
    const TYPE_LOAN_APPROVED = 'loan_approved';
    const TYPE_LOAN_DISBURSED = 'loan_disbursed';
    const TYPE_LOAN_COMPLETED = 'loan_completed';
    const TYPE_LOAN_CANCELLED = 'loan_cancelled';
    const TYPE_LOAN_DELETED = 'loan_deleted';
    const TYPE_LOAN_VIEWED = 'loan_viewed';

    // Payment transaction types
    const TYPE_PAYMENT_CREATED = 'payment_created';
    const TYPE_PAYMENT_RECORDED = 'payment_recorded';
    const TYPE_PAYMENT_APPROVED = 'payment_approved';
    const TYPE_PAYMENT_CANCELLED = 'payment_cancelled';
    const TYPE_PAYMENT_OVERDUE = 'payment_overdue';
    const TYPE_PAYMENT_VIEWED = 'payment_viewed';

    // System transaction types
    const TYPE_SYSTEM_BACKUP = 'system_backup';
    const TYPE_SYSTEM_CONFIG_CHANGED = 'system_config_changed';
    const TYPE_DATABASE_MAINTENANCE = 'database_maintenance';

    /**
     * Creates a new transaction entry.
     * @param array $data Transaction data
     * @return int|false New transaction ID on success
     */
    public function create($data) {
        // Ensure created_at is set
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        // Ensure details is JSON if it's an array
        if (isset($data['details']) && is_array($data['details'])) {
            $data['details'] = json_encode($data['details']);
        }

        return parent::create($data);
    }

    /**
     * Retrieves transactions for a specific user.
     * @param int $userId User ID
     * @param int $limit Number of records
     * @return array
     */
    public function getTransactionsByUser($userId, $limit = 50) {
        $sql = "SELECT t.*, u.name as user_name
                FROM {$this->table} t
                LEFT JOIN users u ON t.user_id = u.id
                WHERE t.user_id = ?
                ORDER BY t.created_at DESC
                LIMIT ?";

        return $this->query($sql, [$userId, $limit]);
    }

    /**
     * Retrieves transactions by type.
     * @param string $type Transaction type
     * @param int $limit Number of records
     * @return array
     */
    public function getTransactionsByType($type, $limit = 50) {
        $sql = "SELECT t.*, u.name as user_name
                FROM {$this->table} t
                LEFT JOIN users u ON t.user_id = u.id
                WHERE t.transaction_type = ?
                ORDER BY t.created_at DESC
                LIMIT ?";

        return $this->query($sql, [$type, $limit]);
    }

    /**
     * Retrieves transactions within a date range.
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @param int $limit Number of records
     * @return array
     */
    public function getTransactionsByDateRange($startDate, $endDate, $limit = 100) {
        $sql = "SELECT t.*, u.name as user_name
                FROM {$this->table} t
                LEFT JOIN users u ON t.user_id = u.id
                WHERE DATE(t.created_at) BETWEEN ? AND ?
                ORDER BY t.created_at DESC
                LIMIT ?";

        return $this->query($sql, [$startDate, $endDate, $limit]);
    }

    /**
     * Retrieves transactions with filtering options.
     * @param array $filters Filter options
     * @param int $limit Number of records
     * @param int $offset Offset for pagination
     * @return array
     */
    public function getFilteredTransactions($filters = [], $limit = 50, $offset = 0) {
        $sql = "SELECT t.*, u.name as user_name, u.email as user_email
                FROM {$this->table} t
                LEFT JOIN users u ON t.user_id = u.id
                WHERE 1=1";

        $params = [];

        // Apply filters
        if (!empty($filters['user_id'])) {
            $sql .= " AND t.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['transaction_type'])) {
            $sql .= " AND t.transaction_type = ?";
            $params[] = $filters['transaction_type'];
        }

        if (!empty($filters['reference_id'])) {
            $sql .= " AND t.reference_id = ?";
            $params[] = $filters['reference_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(t.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(t.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (t.details ILIKE ? OR t.transaction_type ILIKE ? OR u.name ILIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->query($sql, $params);
    }

    /**
     * Gets transaction statistics.
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array
     */
    public function getTransactionStats($startDate, $endDate) {
        $stats = [];

        // Total transactions
        $sql = "SELECT COUNT(*) as total FROM {$this->table}
                WHERE DATE(created_at) BETWEEN ? AND ?";
        $result = $this->query($sql, [$startDate, $endDate]);
        $stats['total'] = $result[0]['total'] ?? 0;

        // Transactions by type
        $sql = "SELECT transaction_type, COUNT(*) as count
                FROM {$this->table}
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY transaction_type
                ORDER BY count DESC";
        $stats['by_type'] = $this->query($sql, [$startDate, $endDate]);

        // Transactions by user
        $sql = "SELECT u.name, COUNT(*) as count
                FROM {$this->table} t
                LEFT JOIN users u ON t.user_id = u.id
                WHERE DATE(t.created_at) BETWEEN ? AND ?
                GROUP BY t.user_id, u.name
                ORDER BY count DESC
                LIMIT 10";
        $stats['by_user'] = $this->query($sql, [$startDate, $endDate]);

        // Daily transaction counts
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as count
                FROM {$this->table}
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY date";
        $stats['daily'] = $this->query($sql, [$startDate, $endDate]);

        return $stats;
    }

    /**
     * Gets the count of filtered transactions for pagination.
     * @param array $filters Filter options
     * @return int
     */
    public function getFilteredCount($filters = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} t WHERE 1=1";
        $params = [];

        // Apply same filters as getFilteredTransactions
        if (!empty($filters['user_id'])) {
            $sql .= " AND t.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['transaction_type'])) {
            $sql .= " AND t.transaction_type = ?";
            $params[] = $filters['transaction_type'];
        }

        if (!empty($filters['reference_id'])) {
            $sql .= " AND t.reference_id = ?";
            $params[] = $filters['reference_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(t.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(t.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (t.details ILIKE ? OR t.transaction_type ILIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $result = $this->query($sql, $params);
        return $result[0]['count'] ?? 0;
    }

    /**
     * Gets all available transaction types.
     * @return array
     */
    public static function getTransactionTypes() {
        return [
            // User events
            self::TYPE_LOGIN => 'User Login',
            self::TYPE_LOGOUT => 'User Logout',
            self::TYPE_SESSION_EXTENDED => 'Session Extended',

            // User CRUD
            self::TYPE_USER_CREATED => 'User Created',
            self::TYPE_USER_UPDATED => 'User Updated',
            self::TYPE_USER_DELETED => 'User Deleted',
            self::TYPE_USER_VIEWED => 'User Viewed',

            // Client CRUD
            self::TYPE_CLIENT_CREATED => 'Client Created',
            self::TYPE_CLIENT_UPDATED => 'Client Updated',
            self::TYPE_CLIENT_DELETED => 'Client Deleted',
            self::TYPE_CLIENT_VIEWED => 'Client Viewed',

            // Loan CRUD
            self::TYPE_LOAN_CREATED => 'Loan Created',
            self::TYPE_LOAN_UPDATED => 'Loan Updated',
            self::TYPE_LOAN_APPROVED => 'Loan Approved',
            self::TYPE_LOAN_DISBURSED => 'Loan Disbursed',
            self::TYPE_LOAN_COMPLETED => 'Loan Completed',
            self::TYPE_LOAN_CANCELLED => 'Loan Cancelled',
            self::TYPE_LOAN_DELETED => 'Loan Deleted',
            self::TYPE_LOAN_VIEWED => 'Loan Viewed',

            // Payment CRUD
            self::TYPE_PAYMENT_CREATED => 'Payment Created',
            self::TYPE_PAYMENT_RECORDED => 'Payment Recorded',
            self::TYPE_PAYMENT_APPROVED => 'Payment Approved',
            self::TYPE_PAYMENT_CANCELLED => 'Payment Cancelled',
            self::TYPE_PAYMENT_OVERDUE => 'Payment Overdue',
            self::TYPE_PAYMENT_VIEWED => 'Payment Viewed',

            // System events
            self::TYPE_SYSTEM_BACKUP => 'System Backup',
            self::TYPE_SYSTEM_CONFIG_CHANGED => 'System Configuration Changed',
            self::TYPE_DATABASE_MAINTENANCE => 'Database Maintenance'
        ];
    }
}
