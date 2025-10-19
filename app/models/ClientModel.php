<?php
/**
 * ClientModel - Handles client (borrower) operations
 * This model manages the static data of the client accounts for Fanders Microfinance Inc.
 */
require_once __DIR__ . '/../core/BaseModel.php';

class ClientModel extends BaseModel {
    protected $table = 'clients';
    protected $primaryKey = 'id';
    
    // Updated fillable fields to include updated_at for consistency
    protected $fillable = [
        'name', 'email', 'phone_number', 'address', 'date_of_birth',
        'identification_type', 'identification_number', 'status',
        'created_at', 'updated_at' 
    ];
    protected $hidden = [];

    // Status definitions
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_BLACKLISTED = 'blacklisted';

    /**
     * Retrieves a client record by phone number.
     * @param string $phoneNumber
     * @return array|false
     */
    public function getClientByPhone($phoneNumber) {
        return $this->findOneByField('phone_number', $phoneNumber);
    }

    /**
     * Retrieves a client record by email address.
     * @param string $email
     * @return array|false
     */
    public function getClientByEmail($email) {
        return $this->findOneByField('email', $email);
    }

    /**
     * Checks if a phone number exists, excluding an optional ID.
     * @param string $phoneNumber
     * @param int|null $excludeId
     * @return bool
     */
    public function phoneNumberExists($phoneNumber, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE phone_number = ?";
        $params = [$phoneNumber];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = $this->db->single($sql, $params);
        return $result && $result['count'] > 0;
    }

    /**
     * Checks if an email exists, excluding an optional ID.
     * @param string $email
     * @param int|null $excludeId
     * @return bool
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = ?";
        $params = [$email];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = $this->db->single($sql, $params);
        return $result && $result['count'] > 0;
    }

    /**
     * Checks if an identification number exists, excluding an optional ID.
     * @param string $identificationNumber
     * @param int|null $excludeId
     * @return bool
     */
    public function identificationExists($identificationNumber, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE identification_number = ?";
        $params = [$identificationNumber];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = $this->db->single($sql, $params);
        return $result && $result['count'] > 0;
    }

    /**
     * Enhanced method to get all clients with filtering support
     * @param array $filters Filter parameters from FilterUtility
     * @return array
     */
    public function getAllClients($filters = []) {
        require_once __DIR__ . '/../utilities/FilterUtility.php';

        // Base query with loan summary data
        $baseSql = "SELECT c.*,
                          COUNT(l.id) as total_loans,
                          COUNT(CASE WHEN l.status = 'Active' THEN 1 END) as active_loans_count,
                          SUM(CASE WHEN l.status = 'Active' THEN l.total_loan_amount ELSE 0 END) as total_active_loan_amount,
                          MAX(l.created_at) as last_loan_date
                    FROM {$this->table} c
                    LEFT JOIN loans l ON c.id = l.client_id";

        // Build WHERE clause using FilterUtility
        list($whereClause, $params) = FilterUtility::buildWhereClause($filters, 'clients');

        // Add GROUP BY to handle loan aggregations
        $groupClause = "GROUP BY c.id";

        // Build ORDER BY clause
        $allowedSortFields = ['c.name', 'c.created_at', 'c.status', 'total_loans', 'last_loan_date'];
        $orderClause = FilterUtility::buildOrderClause($filters, $allowedSortFields, 'c.name');

        // Build complete query
        $sql = "$baseSql $whereClause $groupClause $orderClause";

        // Add pagination if requested
        if (!empty($filters['limit'])) {
            $limitClause = FilterUtility::buildLimitClause($filters);
            $sql .= " $limitClause";
        }

        return $this->db->resultSet($sql, $params);
    }

    /**
     * Get total count of clients matching filters (for pagination)
     * @param array $filters Filter parameters
     * @return int
     */
    public function getTotalClientsCount($filters = []) {
        require_once __DIR__ . '/../utilities/FilterUtility.php';

        $baseSql = "SELECT COUNT(DISTINCT c.id) as count
                    FROM {$this->table} c
                    LEFT JOIN loans l ON c.id = l.client_id";

        list($whereClause, $params) = FilterUtility::buildWhereClause($filters, 'clients');
        $sql = "$baseSql $whereClause";

        $result = $this->db->single($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Enhanced method to get clients by status with additional filtering
     * @param string $status Client status
     * @param array $additionalFilters Additional filters to apply
     * @return array
     */
    public function getClientsByStatus($status, $additionalFilters = []) {
        $filters = array_merge($additionalFilters, ['status' => $status]);
        return $this->getAllClients($filters);
    }

    /**
     * Enhanced method to get active clients with filtering
     * @param array $filters Additional filters to apply
     * @return array
     */
    public function getActiveClients($filters = []) {
        $filters['status'] = self::STATUS_ACTIVE;
        return $this->getAllClients($filters);
    }

    /**
     * Enhanced search clients method with better performance
     * @param string $searchTerm Search term
     * @param array $additionalFilters Additional filters to apply
     * @return array
     */
    public function searchClients($searchTerm, $additionalFilters = []) {
        $filters = array_merge($additionalFilters, ['search' => $searchTerm]);
        return $this->getAllClients($filters);
    }

    /**
     * Fetches client details aggregated with loan count and total active loan amounts.
     * NOTE: total_active_loan_amount in this model returns raw loan amount. Calculations should be done in service layer.
     * @param int $id
     * @return array|false
     */
    public function getClientWithLoanSummary($id) {
        $sql = "SELECT c.*,
                COUNT(l.id) as total_loans,
                COUNT(CASE WHEN l.status = 'Active' THEN 1 END) as active_loans_count,
                SUM(CASE WHEN l.status = 'Active' THEN l.total_loan_amount ELSE 0 END) as total_active_loan_amount
                FROM {$this->table} c
                LEFT JOIN loans l ON c.id = l.client_id
                WHERE c.id = ?
                GROUP BY c.id";

        return $this->db->single($sql, [$id]);
    }

    /**
     * Fetches all loan records for a given client (history).
     * @param int $clientId
     * @return array
     */
    public function getClientLoanHistory($clientId) {
        $sql = "SELECT l.*
                FROM loans l
                WHERE l.client_id = ?
                ORDER BY l.created_at DESC";

        return $this->db->resultSet($sql, [$clientId]);
    }

    /**
     * Fetches currently active loan records for a given client.
     * @param int $clientId
     * @return array
     */
    public function getClientCurrentLoans($clientId) {
        $sql = "SELECT l.*
                FROM loans l
                WHERE l.client_id = ? AND l.status = 'Active'
                ORDER BY l.created_at DESC";

        return $this->db->resultSet($sql, [$clientId]);
    }


    /**
     * Retrieves general statistics about the client base.
     * @return array
     */
    public function getClientStats() {
        $stats = [];

        // Total clients
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->single($sql);
        $stats['total_clients'] = $result ? $result['count'] : 0;

        // Active clients
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE status = ?";
        $result = $this->db->single($sql, [self::STATUS_ACTIVE]);
        $stats['active_clients'] = $result ? $result['count'] : 0;

        // Clients by status
        $sql = "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status";
        $result = $this->db->resultSet($sql);
        $stats['clients_by_status'] = $result ?: [];

        // Recently added clients
        $sql = "SELECT id, name, created_at, status FROM {$this->table} ORDER BY created_at DESC LIMIT 5";
        $stats['recent_clients'] = $this->db->resultSet($sql);

        return $stats;
    }

    /**
     * Overrides BaseModel create to add default logic.
     * NOTE: Validation for uniqueness should primarily be done in the service layer.
     * @param array $data
     * @return int|false
     */
    public function create($data) {
        // Set default values if not provided
        $data['status'] = $data['status'] ?? self::STATUS_ACTIVE;
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');
        
        // Call parent create method
        return parent::create($data);
    }

    /**
     * Retrieves all active clients for use in HTML <select> elements.
     * @return array
     */
    public function getAllForSelect() {
        $sql = "SELECT id, name FROM {$this->table} WHERE status = ? ORDER BY name ASC";
        return $this->db->resultSet($sql, [self::STATUS_ACTIVE]);
    }
}
