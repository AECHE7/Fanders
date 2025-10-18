<?php
/**
 * TransactionModel - Handles audit log operations
 * This model manages the data for the 'transactions' table and provides
 * logging functionality for system audit trails.
 */
require_once __DIR__ . '/../core/BaseModel.php';

class TransactionModel extends BaseModel {
    protected $table = 'transactions';
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'transaction_type',
        'reference_id',
        'details',
        'created_at'
    ];
    protected $hidden = [];

    // Transaction types
    public const TYPE_LOAN_CREATED = 'LOAN_CREATED';
    public const TYPE_LOAN_APPROVED = 'LOAN_APPROVED';
    public const TYPE_LOAN_DISBURSED = 'LOAN_DISBURSED';
    public const TYPE_LOAN_CANCELLED = 'LOAN_CANCELLED';
    public const TYPE_LOAN_RESTORED = 'LOAN_RESTORED';
    public const TYPE_LOAN_COMPLETED = 'LOAN_COMPLETED';
    public const TYPE_PAYMENT_RECORDED = 'PAYMENT_RECORDED';
    public const TYPE_CLIENT_CREATED = 'CLIENT_CREATED';
    public const TYPE_CLIENT_UPDATED = 'CLIENT_UPDATED';
    public const TYPE_CLIENT_DEACTIVATED = 'CLIENT_DEACTIVATED';
    public const TYPE_CLIENT_BLACKLISTED = 'CLIENT_BLACKLISTED';
    public const TYPE_USER_CREATED = 'USER_CREATED';
    public const TYPE_USER_UPDATED = 'USER_UPDATED';
    public const TYPE_USER_LOGIN = 'USER_LOGIN';
    public const TYPE_USER_LOGOUT = 'USER_LOGOUT';

    /**
     * Logs a transaction to the audit trail.
     * @param int $userId User performing the action
     * @param string $type Transaction type constant
     * @param int|null $referenceId ID of affected record
     * @param array|null $details Additional details as array (will be JSON encoded)
     * @return int|false New transaction ID on success
     */
    public function logTransaction($userId, $type, $referenceId = null, $details = null) {
        $data = [
            'user_id' => $userId,
            'transaction_type' => $type,
            'reference_id' => $referenceId,
            'details' => $details ? json_encode($details) : null
        ];

        return $this->create($data);
    }

    /**
     * Retrieves transactions with user information.
     * @param int $limit Number of records to retrieve
     * @param int $offset Offset for pagination
     * @return array
     */
    public function getTransactionsWithUsers($limit = 50, $offset = 0) {
        $sql = "SELECT t.*, u.name as user_name, u.role as user_role
                FROM {$this->table} t
                JOIN users u ON t.user_id = u.id
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?";

        return $this->db->resultSet($sql, [$limit, $offset]);
    }

    /**
     * Retrieves transactions for a specific reference record.
     * @param int $referenceId
     * @return array
     */
    public function getTransactionsByReference($referenceId) {
        $sql = "SELECT t.*, u.name as user_name, u.role as user_role
                FROM {$this->table} t
                JOIN users u ON t.user_id = u.id
                WHERE t.reference_id = ?
                ORDER BY t.created_at DESC";

        return $this->db->resultSet($sql, [$referenceId]);
    }

    /**
     * Retrieves transactions by type.
     * @param string $type
     * @param int $limit
     * @return array
     */
    public function getTransactionsByType($type, $limit = 100) {
        $sql = "SELECT t.*, u.name as user_name, u.role as user_role
                FROM {$this->table} t
                JOIN users u ON t.user_id = u.id
                WHERE t.transaction_type = ?
                ORDER BY t.created_at DESC
                LIMIT ?";

        return $this->db->resultSet($sql, [$type, $limit]);
    }

    /**
     * Retrieves transaction statistics.
     * @return array
     */
    public function getTransactionStats() {
        $stats = [];

        // Total transactions
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->single($sql);
        $stats['total_transactions'] = $result ? $result['count'] : 0;

        // Transactions by type
        $sql = "SELECT transaction_type, COUNT(*) as count FROM {$this->table} GROUP BY transaction_type";
        $result = $this->db->resultSet($sql);
        $stats['transactions_by_type'] = $result ?: [];

        // Recent transactions (last 7 days)
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $result = $this->db->single($sql);
        $stats['recent_transactions'] = $result ? $result['count'] : 0;

        return $stats;
    }

    /**
     * Searches transactions by type, user, or reference.
     * @param string $term Search term
     * @param int $limit
     * @return array
     */
    public function searchTransactions($term, $limit = 50) {
        $sql = "SELECT t.*, u.name as user_name, u.role as user_role
                FROM {$this->table} t
                JOIN users u ON t.user_id = u.id
                WHERE t.transaction_type LIKE ?
                OR u.name LIKE ?
                OR t.reference_id = ?
                ORDER BY t.created_at DESC
                LIMIT ?";

        $searchTerm = '%' . $term . '%';
        return $this->db->resultSet($sql, [$searchTerm, $searchTerm, $term, $limit]);
    }
}
