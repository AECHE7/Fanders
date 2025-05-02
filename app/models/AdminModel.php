<?php
/**
 * AdminModel - Represents an Admin user
 */
class AdminModel extends UserModel {
    /**
     * Get all borrowers
     * 
     * @return array|bool
     */
    public function getAllBorrowers() {
        return $this->getUsersByRole(ROLE_BORROWER);
    }
    
    /**
     * Create a new borrower
     * 
     * @param array $data
     * @return int|bool
     */
    public function createBorrower($data) {
        // Set role to Borrower
        $data['role_id'] = ROLE_BORROWER;
        $data['is_active'] = 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
    }
    
    /**
     * Get borrowers with active loans
     * 
     * @return array|bool
     */
    public function getBorrowersWithActiveLoans() {
        $sql = "SELECT DISTINCT u.* 
                FROM users u
                JOIN transactions t ON u.id = t.user_id
                WHERE u.role_id = ? AND t.return_date IS NULL";
                
        return $this->db->resultSet($sql, [ROLE_BORROWER]);
    }
    
    /**
     * Get borrowers with overdue books
     * 
     * @return array|bool
     */
    public function getBorrowersWithOverdueBooks() {
        $sql = "SELECT DISTINCT u.* 
                FROM users u
                JOIN transactions t ON u.id = t.user_id
                WHERE u.role_id = ? AND t.return_date IS NULL AND t.due_date < CURDATE()";
                
        return $this->db->resultSet($sql, [ROLE_BORROWER]);
    }
    
    /**
     * Get admin statistics
     * 
     * @return array
     */
    public function getAdminStats() {
        $stats = [];
        
        // Total borrowers
        $sql = "SELECT COUNT(*) as count FROM users WHERE role_id = ?";
        $result = $this->db->single($sql, [ROLE_BORROWER]);
        $stats['total_borrowers'] = $result ? $result['count'] : 0;
        
        // Active borrowers
        $sql = "SELECT COUNT(*) as count FROM users WHERE role_id = ? AND is_active = 1";
        $result = $this->db->single($sql, [ROLE_BORROWER]);
        $stats['active_borrowers'] = $result ? $result['count'] : 0;
        
        // Books currently borrowed
        $sql = "SELECT COUNT(*) as count FROM transactions WHERE return_date IS NULL";
        $result = $this->db->single($sql);
        $stats['borrowed_books'] = $result ? $result['count'] : 0;
        
        // Overdue books
        $sql = "SELECT COUNT(*) as count FROM transactions WHERE return_date IS NULL AND due_date < CURDATE()";
        $result = $this->db->single($sql);
        $stats['overdue_books'] = $result ? $result['count'] : 0;
        
        // Total amount of penalties
        $sql = "SELECT SUM(amount) as total FROM penalties";
        $result = $this->db->single($sql);
        $stats['total_penalties'] = $result ? $result['total'] : 0;
        
        // Unpaid penalties
        $sql = "SELECT SUM(amount) as total FROM penalties WHERE is_paid = 0";
        $result = $this->db->single($sql);
        $stats['unpaid_penalties'] = $result ? $result['total'] : 0;
        
        return $stats;
    }
    
    /**
     * Get recent transactions
     * 
     * @param int $limit
     * @return array|bool
     */
    public function getRecentTransactions($limit = 10) {
        $sql = "SELECT t.*, u.username, b.title as book_title
                FROM transactions t
                JOIN users u ON t.user_id = u.id
                JOIN books b ON t.book_id = b.id
                ORDER BY t.created_at DESC
                LIMIT ?";
                
        return $this->db->resultSet($sql, [$limit]);
    }
}
