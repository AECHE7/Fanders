<?php
/**
 * PenaltyModel - Represents a Penalty for late book returns
 */
class PenaltyModel extends BaseModel {
    protected $table = 'penalties';
    protected $primaryKey = 'id';
    protected $fillable = [
        'transaction_id', 'amount', 'description', 'is_paid', 
        'payment_date', 'created_at', 'updated_at'
    ];

    /**
     * Get penalty with transaction details
     * 
     * @param int $id
     * @return array|bool
     */
    public function getPenaltyWithDetails($id) {
        $sql = "SELECT p.*, 
                t.borrow_date, t.due_date, t.return_date, 
                b.title as book_title, b.isbn,
                u.username, u.first_name, u.last_name,
                DATEDIFF(t.return_date, t.due_date) as days_overdue
                FROM {$this->table} p
                JOIN transactions t ON p.transaction_id = t.id
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                WHERE p.id = ?";
                
        return $this->db->single($sql, [$id]);
    }

    /**
     * Get all penalties with details
     * 
     * @return array|bool
     */
    public function getAllPenaltiesWithDetails() {
        $sql = "SELECT p.*, 
                t.borrow_date, t.due_date, t.return_date, 
                b.title as book_title, b.isbn,
                u.username, u.first_name, u.last_name,
                DATEDIFF(t.return_date, t.due_date) as days_overdue
                FROM {$this->table} p
                JOIN transactions t ON p.transaction_id = t.id
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                ORDER BY p.created_at DESC";
                
        return $this->db->resultSet($sql);
    }

    /**
     * Create a penalty for overdue book
     * 
     * @param int $transactionId
     * @param int $daysOverdue
     * @return int|bool
     */
    public function createPenalty($transactionId, $daysOverdue) {
        // Calculate penalty amount: base amount + (daily increment * days overdue)
        $amount = PENALTY_BASE_AMOUNT + (PENALTY_DAILY_INCREMENT * $daysOverdue);
        $description = "Late return penalty: {$daysOverdue} days overdue";
        
        $data = [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'description' => $description,
            'is_paid' => 0,
            'payment_date' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->create($data);
    }

    /**
     * Mark penalty as paid
     * 
     * @param int $id
     * @return bool
     */
    public function markAsPaid($id) {
        $sql = "UPDATE {$this->table} SET 
                is_paid = 1,
                payment_date = CURDATE(),
                updated_at = NOW()
                WHERE id = ?";
                
        return $this->db->query($sql, [$id]) ? true : false;
    }

    /**
     * Get unpaid penalties
     * 
     * @return array|bool
     */
    public function getUnpaidPenalties() {
        $sql = "SELECT p.*, 
                t.borrow_date, t.due_date, t.return_date, 
                b.title as book_title, b.isbn,
                u.username, u.first_name, u.last_name,
                DATEDIFF(t.return_date, t.due_date) as days_overdue
                FROM {$this->table} p
                JOIN transactions t ON p.transaction_id = t.id
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                WHERE p.is_paid = 0
                ORDER BY p.created_at DESC";
                
        return $this->db->resultSet($sql);
    }

    /**
     * Get user's penalties
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getUserPenalties($userId) {
        $sql = "SELECT p.*, 
                t.borrow_date, t.due_date, t.return_date, 
                b.title as book_title, b.isbn,
                DATEDIFF(t.return_date, t.due_date) as days_overdue
                FROM {$this->table} p
                JOIN transactions t ON p.transaction_id = t.id
                JOIN books b ON t.book_id = b.id
                WHERE t.user_id = ?
                ORDER BY p.created_at DESC";
                
        return $this->db->resultSet($sql, [$userId]);
    }

    /**
     * Get user's unpaid penalties total
     * 
     * @param int $userId
     * @return float
     */
    public function getUserUnpaidPenaltiesTotal($userId) {
        $sql = "SELECT SUM(p.amount) as total
                FROM {$this->table} p
                JOIN transactions t ON p.transaction_id = t.id
                WHERE t.user_id = ? AND p.is_paid = 0";
                
        $result = $this->db->single($sql, [$userId]);
        return $result ? $result['total'] : 0;
    }

    /**
     * Check if transaction already has a penalty
     * 
     * @param int $transactionId
     * @return bool
     */
    public function transactionHasPenalty($transactionId) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE transaction_id = ?";
        $result = $this->db->single($sql, [$transactionId]);
        return $result && $result['count'] > 0;
    }

    /**
     * Get penalties for reports
     * 
     * @param string $startDate
     * @param string $endDate
     * @param bool $isPaid
     * @return array|bool
     */
    public function getPenaltiesForReports($startDate = null, $endDate = null, $isPaid = null) {
        $sql = "SELECT p.*, 
                t.borrow_date, t.due_date, t.return_date, 
                b.title as book_title, b.isbn,
                u.username, u.first_name, u.last_name,
                DATEDIFF(t.return_date, t.due_date) as days_overdue
                FROM {$this->table} p
                JOIN transactions t ON p.transaction_id = t.id
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        if ($startDate) {
            $sql .= " AND p.created_at >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND p.created_at <= ?";
            $params[] = $endDate;
        }
        
        if ($isPaid !== null) {
            $sql .= " AND p.is_paid = ?";
            $params[] = $isPaid ? 1 : 0;
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        return $this->db->resultSet($sql, $params);
    }
}
