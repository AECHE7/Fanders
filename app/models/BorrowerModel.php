<?php
/**
 * BorrowerModel - Represents a Borrower user
 */
class BorrowerModel extends UserModel {
    /**
     * Get active loans for a borrower
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getActiveLoans($userId) {
        $sql = "SELECT t.*, b.title as book_title, b.isbn, b.author,
                DATEDIFF(t.due_date, CURDATE()) as days_remaining,
                c.name as category_name
                FROM transactions t
                JOIN books b ON t.book_id = b.id
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE t.user_id = ? AND t.return_date IS NULL
                ORDER BY t.due_date ASC";
                
        return $this->db->resultSet($sql, [$userId]);
    }

    /**
     * Get loan history for a borrower
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getLoanHistory($userId) {
        $sql = "SELECT t.*, b.title as book_title, b.isbn, b.author,
                DATEDIFF(t.return_date, t.borrow_date) as days_borrowed,
                c.name as category_name
                FROM transactions t
                JOIN books b ON t.book_id = b.id
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE t.user_id = ? AND t.return_date IS NOT NULL
                ORDER BY t.return_date DESC";
                
        return $this->db->resultSet($sql, [$userId]);
    }

    /**
     * Get overdue books for a borrower
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getOverdueBooks($userId) {
        $sql = "SELECT t.*, b.title as book_title, b.isbn, b.author,
                DATEDIFF(CURDATE(), t.due_date) as days_overdue,
                c.name as category_name
                FROM transactions t
                JOIN books b ON t.book_id = b.id
                LEFT JOIN categories c ON b.category_id = c.id
                WHERE t.user_id = ? AND t.return_date IS NULL AND t.due_date < CURDATE()
                ORDER BY t.due_date ASC";
                
        return $this->db->resultSet($sql, [$userId]);
    }

    /**
     * Get penalties for a borrower
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getPenalties($userId) {
        $sql = "SELECT p.*, t.borrow_date, t.due_date, t.return_date,
                b.title as book_title, b.isbn
                FROM penalties p
                JOIN transactions t ON p.transaction_id = t.id
                JOIN books b ON t.book_id = b.id
                WHERE t.user_id = ?
                ORDER BY p.created_at DESC";
                
        return $this->db->resultSet($sql, [$userId]);
    }

    /**
     * Get borrower statistics
     * 
     * @param int $userId
     * @return array
     */
    public function getBorrowerStats($userId) {
        $stats = [];
        
        // Total books borrowed
        $sql = "SELECT COUNT(*) as count FROM transactions WHERE user_id = ?";
        $result = $this->db->single($sql, [$userId]);
        $stats['total_borrowed'] = $result ? $result['count'] : 0;
        
        // Books currently borrowed
        $sql = "SELECT COUNT(*) as count FROM transactions WHERE user_id = ? AND return_date IS NULL";
        $result = $this->db->single($sql, [$userId]);
        $stats['currently_borrowed'] = $result ? $result['count'] : 0;
        
        // Overdue books
        $sql = "SELECT COUNT(*) as count FROM transactions WHERE user_id = ? AND return_date IS NULL AND due_date < CURDATE()";
        $result = $this->db->single($sql, [$userId]);
        $stats['overdue_books'] = $result ? $result['count'] : 0;
        
        // Total penalties
        $sql = "SELECT SUM(p.amount) as total 
                FROM penalties p 
                JOIN transactions t ON p.transaction_id = t.id 
                WHERE t.user_id = ?";
        $result = $this->db->single($sql, [$userId]);
        $stats['total_penalties'] = $result ? $result['total'] : 0;
        
        // Unpaid penalties
        $sql = "SELECT SUM(p.amount) as total 
                FROM penalties p 
                JOIN transactions t ON p.transaction_id = t.id 
                WHERE t.user_id = ? AND p.is_paid = 0";
        $result = $this->db->single($sql, [$userId]);
        $stats['unpaid_penalties'] = $result ? $result['total'] : 0;
        
        return $stats;
    }

    /**
     * Check if borrower has reached the maximum allowed loans
     * 
     * @param int $userId
     * @param int $maxAllowed
     * @return bool
     */
    public function hasReachedMaxLoans($userId, $maxAllowed = 3) {
        $sql = "SELECT COUNT(*) as count FROM transactions WHERE user_id = ? AND return_date IS NULL";
        $result = $this->db->single($sql, [$userId]);
        return $result && $result['count'] >= $maxAllowed;
    }

    /**
     * Check if borrower has any overdue books
     * 
     * @param int $userId
     * @return bool
     */
    public function hasOverdueBooks($userId) {
        $sql = "SELECT COUNT(*) as count FROM transactions WHERE user_id = ? AND return_date IS NULL AND due_date < CURDATE()";
        $result = $this->db->single($sql, [$userId]);
        return $result && $result['count'] > 0;
    }
}
