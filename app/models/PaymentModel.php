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
     * Gets the date and amount of the last payment made for a loan.
     * @param int $loanId
     * @return array|false
     */
    public function getLastPaymentForLoan($loanId) {
         $sql = "SELECT payment_date, amount FROM {$this->table} WHERE loan_id = ? ORDER BY payment_date DESC LIMIT 1";
         return $this->db->single($sql, [$loanId]);
    }
}
