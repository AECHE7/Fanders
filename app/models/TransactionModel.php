<?php
/**
 * TransactionModel - Represents a Book Transaction (borrow/return)
 */
class TransactionModel extends BaseModel {
    protected $table = 'transactions';
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_id', 'book_id', 'borrow_date', 'due_date', 'return_date', 
        'status', 'created_at', 'updated_at'
    ];

    /**
     * Get transaction with book and user details
     * 
     * @param int $id
     * @return array|bool
     */
    public function getTransactionDetails($id) {
        $sql = "SELECT t.*, 
                b.title as book_title, b.author, b.isbn, 
                u.username, u.first_name, u.last_name,
                DATEDIFF(t.due_date, CURDATE()) as days_remaining,
                CASE 
                    WHEN t.return_date IS NULL AND t.due_date < CURDATE() THEN 'Overdue'
                    WHEN t.return_date IS NULL THEN 'Borrowed'
                    ELSE 'Returned'
                END as status_label
                FROM {$this->table} t
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                WHERE t.id = ?";
                
        return $this->db->single($sql, [$id]);
    }

    /**
     * Get all transactions with details
     * 
     * @return array|bool
     */
    public function getAllTransactionsWithDetails() {
        $sql = "SELECT t.*, 
                b.title as book_title, b.author, b.isbn, 
                u.username, u.first_name, u.last_name,
                DATEDIFF(t.due_date, CURDATE()) as days_remaining,
                CASE 
                    WHEN t.return_date IS NULL AND t.due_date < CURDATE() THEN 'Overdue'
                    WHEN t.return_date IS NULL THEN 'Borrowed'
                    ELSE 'Returned'
                END as status_label
                FROM {$this->table} t
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                ORDER BY t.created_at DESC";
                
        return $this->db->resultSet($sql);
    }

    /**
     * Get active loans (not returned)
     * 
     * @return array|bool
     */
    public function getActiveLoans() {
        $sql = "SELECT t.*, 
                b.title as book_title, b.author, b.isbn, 
                u.username, u.first_name, u.last_name,
                DATEDIFF(t.due_date, CURDATE()) as days_remaining,
                CASE 
                    WHEN t.due_date < CURDATE() THEN 'Overdue'
                    ELSE 'Borrowed'
                END as status_label
                FROM {$this->table} t
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                WHERE t.return_date IS NULL
                ORDER BY t.due_date ASC";
                
        return $this->db->resultSet($sql);
    }

    /**
     * Get overdue loans
     * 
     * @return array|bool
     */
    public function getOverdueLoans() {
        $sql = "SELECT t.*, 
                b.title as book_title, b.author, b.isbn, 
                u.username, u.first_name, u.last_name,
                DATEDIFF(CURDATE(), t.due_date) as days_overdue
                FROM {$this->table} t
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                WHERE t.return_date IS NULL AND t.due_date < CURDATE()
                ORDER BY days_overdue DESC";
                
        return $this->db->resultSet($sql);
    }

    /**
     * Create a new loan
     * 
     * @param int $userId
     * @param int $bookId
     * @param int $durationDays
     * @return int|bool
     */
    public function createLoan($userId, $bookId, $durationDays = 14) {
        $borrowDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime("+{$durationDays} days"));
        
        $data = [
            'user_id' => $userId,
            'book_id' => $bookId,
            'borrow_date' => $borrowDate,
            'due_date' => $dueDate,
            'return_date' => null,
            'status' => 'borrowed',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->create($data);
    }

    /**
     * Return a book
     * 
     * @param int $id
     * @return bool
     */
    public function returnBook($id) {
        $sql = "UPDATE {$this->table} SET 
                return_date = CURDATE(),
                status = 'returned',
                updated_at = NOW()
                WHERE id = ? AND return_date IS NULL";
                
        return $this->db->query($sql, [$id]) ? true : false;
    }

    /**
     * Check if user has already borrowed a specific book
     * 
     * @param int $userId
     * @param int $bookId
     * @return bool
     */
    public function hasUserBorrowedBook($userId, $bookId) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE user_id = ? AND book_id = ? AND return_date IS NULL";
                
        $result = $this->db->single($sql, [$userId, $bookId]);
        return $result && $result['count'] > 0;
    }

    /**
     * Get user's transaction history
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getUserTransactionHistory($userId) {
        $sql = "SELECT t.*, 
                b.title as book_title, b.author, b.isbn,
                CASE 
                    WHEN t.return_date IS NULL AND t.due_date < CURDATE() THEN 'Overdue'
                    WHEN t.return_date IS NULL THEN 'Borrowed'
                    ELSE 'Returned'
                END as status_label
                FROM {$this->table} t
                JOIN books b ON t.book_id = b.id
                WHERE t.user_id = ?
                ORDER BY t.created_at DESC";
                
        return $this->db->resultSet($sql, [$userId]);
    }

    /**
     * Get book's transaction history
     * 
     * @param int $bookId
     * @return array|bool
     */
    public function getBookTransactionHistory($bookId) {
        $sql = "SELECT t.*, 
                u.username, u.first_name, u.last_name,
                CASE 
                    WHEN t.return_date IS NULL AND t.due_date < CURDATE() THEN 'Overdue'
                    WHEN t.return_date IS NULL THEN 'Borrowed'
                    ELSE 'Returned'
                END as status_label
                FROM {$this->table} t
                JOIN users u ON t.user_id = u.id
                WHERE t.book_id = ?
                ORDER BY t.created_at DESC";
                
        return $this->db->resultSet($sql, [$bookId]);
    }

    /**
     * Get transaction by user and book
     * 
     * @param int $userId
     * @param int $bookId
     * @return array|bool
     */
    public function getTransactionByUserAndBook($userId, $bookId) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = ? AND book_id = ? AND return_date IS NULL";
                
        return $this->db->single($sql, [$userId, $bookId]);
    }

    /**
     * Get transactions for reports
     * 
     * @param string $startDate
     * @param string $endDate
     * @param string $status
     * @return array|bool
     */
    public function getTransactionsForReports($startDate = null, $endDate = null, $status = null) {
        $sql = "SELECT t.*, 
                b.title as book_title, b.author, b.isbn, 
                u.username, u.first_name, u.last_name,
                CASE 
                    WHEN t.return_date IS NULL AND t.due_date < CURDATE() THEN 'Overdue'
                    WHEN t.return_date IS NULL THEN 'Borrowed'
                    ELSE 'Returned'
                END as status_label
                FROM {$this->table} t
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        if ($startDate) {
            $sql .= " AND t.created_at >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND t.created_at <= ?";
            $params[] = $endDate;
        }
        
        if ($status) {
            if ($status == 'Borrowed') {
                $sql .= " AND t.return_date IS NULL AND t.due_date >= CURDATE()";
            } else if ($status == 'Overdue') {
                $sql .= " AND t.return_date IS NULL AND t.due_date < CURDATE()";
            } else if ($status == 'Returned') {
                $sql .= " AND t.return_date IS NOT NULL";
            }
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        return $this->db->resultSet($sql, $params);
    }
}
