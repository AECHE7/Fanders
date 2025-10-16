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
     * Gets the date and amount of the last payment made for a loan.
     * @param int $loanId
     * @return array|false
     */
    public function getLastPaymentForLoan($loanId) {
         $sql = "SELECT payment_date, amount FROM {$this->table} WHERE loan_id = ? ORDER BY payment_date DESC LIMIT 1";
         return $this->db->single($sql, [$loanId]);
    }
}
