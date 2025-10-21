<?php
/**
 * PaymentModel - Handles payment operations, managing data in the 'payments' table.
 * Provides lookup functions for payment records associated with loans.
 */
require_once __DIR__ . '/../core/BaseModel.php';

class PaymentModel extends BaseModel {
    protected $table = 'payments';
    protected $primaryKey = 'id';
    
    // Fillable fields strictly matching the database schema for Phase 1
    protected $fillable = [
        'loan_id', 
        'user_id', 
        'amount', 
        'payment_date',
        'created_at', 
        'updated_at'
    ];
    protected $hidden = [];

    /**
     * Retrieves a single payment record joined with loan and client data.
     * @param int $id Payment ID.
     * @return array|false
     */
    public function getPaymentWithDetails($id) {
        $sql = "SELECT p.*,
                l.principal, l.total_loan_amount,
                c.name AS client_name, c.phone_number,
                u.name AS recorded_by_name
                FROM {$this->table} p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON l.client_id = c.id -- Join via loan to get client
                JOIN users u ON p.user_id = u.id 
                WHERE p.id = ?";

        return $this->db->single($sql, [$id]);
    }

    /**
     * Retrieves all payments for a specific loan ID.
     * @param int $loanId
     * @return array
     */
    public function getPaymentsByLoan($loanId) {
        $sql = "SELECT p.*,
                u.name AS recorded_by_name
                FROM {$this->table} p
                JOIN users u ON p.user_id = u.id
                WHERE p.loan_id = ?
                ORDER BY p.payment_date DESC";

        return $this->db->resultSet($sql, [$loanId]);
    }

    /**
     * Calculates the total amount paid for a given loan.
     * @param int $loanId
     * @return float
     */
    public function getTotalPaymentsForLoan($loanId) {
        $sql = "SELECT COALESCE(SUM(amount), 0) AS total_paid FROM {$this->table} WHERE loan_id = ?";
        $result = $this->db->single($sql, [$loanId]);
        return (float)($result ? $result['total_paid'] : 0);
    }

    /**
     * Get all payments with pagination support.
     * @param int $limit Number of records per page
     * @param int $offset Number of records to skip
     * @param array $filters Additional filters
     * @return array
     */
    public function getAllPaymentsPaginated($limit = 20, $offset = 0, $filters = []) {
        $sql = "SELECT p.*, l.principal, l.total_loan_amount,
                c.name AS client_name, c.phone_number,
                u.name AS recorded_by_name
                FROM {$this->table} p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                JOIN users u ON p.user_id = u.id";

        $conditions = [];
        $params = [];

        // Apply filters
        if (!empty($filters['search'])) {
            $conditions[] = "(c.name LIKE ? OR c.phone_number LIKE ? OR l.id = ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $filters['search'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "p.payment_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "p.payment_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['client_id'])) {
            $conditions[] = "l.client_id = ?";
            $params[] = $filters['client_id'];
        }

        if (!empty($filters['loan_id'])) {
            $conditions[] = "p.loan_id = ?";
            $params[] = $filters['loan_id'];
        }

        if (!empty($filters['recorded_by'])) {
            $conditions[] = "p.user_id = ?";
            $params[] = $filters['recorded_by'];
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY p.payment_date DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->resultSet($sql, $params);
    }
    
    /**
     * Enhanced method to get all payments with filtering support
     * @param array $filters Filter parameters from FilterUtility
     * @return array
     */
    public function getAllPayments($filters = []) {
        require_once __DIR__ . '/../utilities/FilterUtility.php';

        // Base query with proper joins
        $baseSql = "SELECT p.*,
                          l.principal, 
                          l.total_loan_amount,
                          l.status as loan_status,
                          c.name AS client_name, 
                          c.phone_number,
                          c.id as client_id,
                          u.name AS recorded_by_name
                    FROM {$this->table} p
                    JOIN loans l ON p.loan_id = l.id
                    JOIN clients c ON l.client_id = c.id
                    JOIN users u ON p.user_id = u.id";

        // Build WHERE clause using FilterUtility with custom mappings
        $customMappings = [
            'search_fields' => ['c.name', 'c.phone_number', 'p.id'],
            'date_field' => 'p.payment_date',
            'loan_field' => 'p.loan_id',
            'client_field' => 'l.client_id',
            'recorded_by_field' => 'p.user_id',
            'amount_field' => 'p.amount'
        ];
        
        list($whereClause, $params) = FilterUtility::buildWhereClause($filters, 'payments', $customMappings);

        // Build ORDER BY clause
        $allowedSortFields = ['p.payment_date', 'p.amount', 'c.name', 'p.created_at'];
        $orderClause = FilterUtility::buildOrderClause($filters, $allowedSortFields, 'p.payment_date');

        // Build complete query
        $sql = "$baseSql $whereClause $orderClause";

        // Add pagination if requested
        if (!empty($filters['limit'])) {
            $limitClause = FilterUtility::buildLimitClause($filters);
            $sql .= " $limitClause";
        }

        return $this->db->resultSet($sql, $params);
    }

    /**
     * Get total count of payments matching filters (for pagination)
     * @param array $filters Filter parameters
     * @return int
     */
    public function getTotalPaymentsCount($filters = []) {
        require_once __DIR__ . '/../utilities/FilterUtility.php';

        $baseSql = "SELECT COUNT(p.id) as count
                    FROM {$this->table} p
                    JOIN loans l ON p.loan_id = l.id
                    JOIN clients c ON l.client_id = c.id
                    JOIN users u ON p.user_id = u.id";

        $customMappings = [
            'search_fields' => ['c.name', 'c.phone_number', 'p.id'],
            'date_field' => 'p.payment_date',
            'loan_field' => 'p.loan_id',
            'client_field' => 'l.client_id',
            'recorded_by_field' => 'p.user_id',
            'amount_field' => 'p.amount'
        ];

        list($whereClause, $params) = FilterUtility::buildWhereClause($filters, 'payments', $customMappings);
        $sql = "$baseSql $whereClause";

        $result = $this->db->single($sql, $params);
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Enhanced method to search payments with filtering
     * @param string $searchTerm Search term
     * @param array $additionalFilters Additional filters to apply
     * @return array
     */
    public function searchPayments($searchTerm, $additionalFilters = []) {
        $filters = array_merge($additionalFilters, ['search' => $searchTerm]);
        return $this->getAllPayments($filters);
    }

    /**
     * Get recent payments with limit and filtering
     * @param int $limit Number of recent payments to retrieve
     * @param array $additionalFilters Additional filters
     * @return array
     */
    public function getRecentPayments($limit = 10, $additionalFilters = []) {
        $filters = array_merge($additionalFilters, [
            'limit' => $limit,
            'page' => 1,
            'sort_by' => 'p.payment_date',
            'sort_order' => 'DESC'
        ]);
        return $this->getAllPayments($filters);
    }

    /**
     * Get overdue payments (simplified version for now)
     * @param array $filters Additional filters
     * @return array
     */
    public function getOverduePayments($filters = []) {
        // This is a simplified version. In a real system, you'd need more complex logic
        // to determine overdue payments based on payment schedules
        $baseSql = "SELECT p.*,
                          l.principal, 
                          l.total_loan_amount,
                          l.status as loan_status,
                          c.name AS client_name, 
                          c.phone_number,
                          c.id as client_id,
                          u.name AS recorded_by_name,
                          (CURRENT_DATE - p.payment_date::date) as days_since_last_payment
                    FROM {$this->table} p
                    JOIN loans l ON p.loan_id = l.id
                    JOIN clients c ON l.client_id = c.id
                    JOIN users u ON p.user_id = u.id
                    WHERE l.status = 'Active'
                    AND p.payment_date = (
                        SELECT MAX(payment_date) 
                        FROM payments 
                        WHERE loan_id = p.loan_id
                    )
                    AND (CURRENT_DATE - p.payment_date::date) > 30
                    ORDER BY days_since_last_payment DESC";

        return $this->db->resultSet($baseSql);
    }
}
