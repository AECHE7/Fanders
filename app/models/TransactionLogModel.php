<?php
/**
 * TransactionLogModel - Handles database operations for transaction audit logs.
 * This model stores all financial and user operations for compliance and audit trails.
 */
require_once __DIR__ . '/../core/BaseModel.php';

class TransactionLogModel extends BaseModel {
    protected $table = 'transaction_logs';
    protected $fillable = [
        'entity_type',    // loan, payment, client, user
        'entity_id',      // ID of the entity
        'action',         // created, updated, deleted, disbursed, etc.
        'user_id',        // User who performed the action (nullable)
        'details',        // JSON string with additional details
        'timestamp',      // When the action occurred
        'ip_address'      // IP address of the user
    ];

    /**
     * Creates a new transaction log entry.
     * @param array $data Transaction log data
     * @return int|false New log ID on success
     */
    public function create($data) {
        // Ensure timestamp is set
        if (!isset($data['timestamp'])) {
            $data['timestamp'] = date('Y-m-d H:i:s');
        }

        return parent::create($data);
    }

    /**
     * Retrieves logs for a specific entity.
     * @param string $entityType Entity type
     * @param int $entityId Entity ID
     * @param int $limit Number of records
     * @return array
     */
    public function getLogsByEntity($entityType, $entityId, $limit = 50) {
        $sql = "SELECT tl.*, u.name as user_name
                FROM {$this->table} tl
                LEFT JOIN users u ON tl.user_id = u.id
                WHERE tl.entity_type = ? AND tl.entity_id = ?
                ORDER BY tl.timestamp DESC
                LIMIT ?";

        return $this->query($sql, [$entityType, $entityId, $limit]);
    }

    /**
     * Retrieves logs for a specific user.
     * @param int $userId User ID
     * @param int $limit Number of records
     * @return array
     */
    public function getLogsByUser($userId, $limit = 50) {
        $sql = "SELECT tl.*, u.name as user_name
                FROM {$this->table} tl
                LEFT JOIN users u ON tl.user_id = u.id
                WHERE tl.user_id = ?
                ORDER BY tl.timestamp DESC
                LIMIT ?";

        return $this->query($sql, [$userId, $limit]);
    }

    /**
     * Retrieves logs within a date range.
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @param string|null $entityType Filter by entity type
     * @return array
     */
    public function getLogsByDateRange($startDate, $endDate, $entityType = null) {
        $sql = "SELECT tl.*, u.name as user_name
                FROM {$this->table} tl
                LEFT JOIN users u ON tl.user_id = u.id
                WHERE DATE(tl.timestamp) BETWEEN ? AND ?";

        $params = [$startDate, $endDate];

        if ($entityType) {
            $sql .= " AND tl.entity_type = ?";
            $params[] = $entityType;
        }

        $sql .= " ORDER BY tl.timestamp DESC";

        return $this->query($sql, $params);
    }

    /**
     * Retrieves recent transaction logs.
     * @param int $limit Number of records
     * @return array
     */
    public function getRecentLogs($limit = 100) {
        $sql = "SELECT tl.*, u.name as user_name
                FROM {$this->table} tl
                LEFT JOIN users u ON tl.user_id = u.id
                ORDER BY tl.timestamp DESC
                LIMIT ?";

        return $this->query($sql, [$limit]);
    }

    /**
     * Gets transaction statistics for reporting.
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array
     */
    public function getTransactionStats($startDate, $endDate) {
        $stats = [];

        // Total transactions by entity type
        $sql = "SELECT entity_type, COUNT(*) as count
                FROM {$this->table}
                WHERE DATE(timestamp) BETWEEN ? AND ?
                GROUP BY entity_type";

        $stats['by_entity_type'] = $this->query($sql, [$startDate, $endDate]);

        // Total transactions by action
        $sql = "SELECT action, COUNT(*) as count
                FROM {$this->table}
                WHERE DATE(timestamp) BETWEEN ? AND ?
                GROUP BY action";

        $stats['by_action'] = $this->query($sql, [$startDate, $endDate]);

        // Transactions by user
        $sql = "SELECT u.name, COUNT(*) as count
                FROM {$this->table} tl
                LEFT JOIN users u ON tl.user_id = u.id
                WHERE DATE(tl.timestamp) BETWEEN ? AND ?
                GROUP BY tl.user_id, u.name
                ORDER BY count DESC";

        $stats['by_user'] = $this->query($sql, [$startDate, $endDate]);

        // Daily transaction counts
        $sql = "SELECT DATE(timestamp) as date, COUNT(*) as count
                FROM {$this->table}
                WHERE DATE(timestamp) BETWEEN ? AND ?
                GROUP BY DATE(timestamp)
                ORDER BY date";

        $stats['daily_counts'] = $this->query($sql, [$startDate, $endDate]);

        return $stats;
    }

    /**
     * Searches transaction logs by term.
     * @param string $term Search term
     * @param int $limit Number of records
     * @return array
     */
    public function searchLogs($term, $limit = 50) {
        $sql = "SELECT tl.*, u.name as user_name
                FROM {$this->table} tl
                LEFT JOIN users u ON tl.user_id = u.id
                WHERE tl.details LIKE ? OR tl.action LIKE ? OR tl.entity_type LIKE ?
                ORDER BY tl.timestamp DESC
                LIMIT ?";

        $searchTerm = '%' . $term . '%';
        return $this->query($sql, [$searchTerm, $searchTerm, $searchTerm, $limit]);
    }
}
