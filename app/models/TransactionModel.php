<?php
/**
 * TransactionModel - Represents a Book Transaction (borrow/return)
 */
class TransactionModel extends BaseModel {
    protected $table = 'transaction';
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_id', 'book_id', 'borrow_date', 'due_date', 'return_date', 
        'status', 'created_at', 'updated_at'
    ];

    public function getTransactionDetails($id) {
        $sql = "SELECT t.*, 
                b.title as book_title, b.author, 
                u.name, u.email,
                DATEDIFF(t.due_date, CURDATE()) as days_remaining,
                CASE 
                    WHEN t.return_date IS NULL AND t.due_date < CURDATE() THEN 'overdue'
                    WHEN t.return_date IS NULL THEN 'borrowed'
                    ELSE 'returned'
                END as status_label
                FROM {$this->table} t
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                WHERE t.id = ?";
                
        return $this->db->single($sql, [$id]);
    }


    public function getAllTransactionsWithDetails() {
        $sql = "SELECT t.*, 
                b.title as book_title, b.author, 
                u.name, u.email,
                (t.due_date::date - CURRENT_DATE) as days_remaining,
                CASE 
                    WHEN t.return_date IS NULL AND t.due_date < CURRENT_DATE THEN 'overdue'
                    WHEN t.return_date IS NULL THEN 'borrowed'
                    ELSE 'returned'
                END as status_label
                FROM {$this->table} t
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                ORDER BY t.created_at DESC";
                
        return $this->db->resultSet($sql);
    }

    public function getActiveLoans($userId = null) {
        $sql = "SELECT t.*, 
                b.title as book_title, b.author, 
                u.name, u.email,
                (t.due_date::date - CURRENT_DATE) as days_remaining,
                CASE 
                    WHEN t.due_date < CURRENT_DATE THEN 'overdue'
                    ELSE 'borrowed'
                END as status_label
                FROM {$this->table} t
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                WHERE t.return_date IS NULL";
        
        $params = [];
        if ($userId !== null) {
            $sql .= " AND t.user_id = ?";
            $params[] = $userId;
        }
        
        $sql .= " ORDER BY t.due_date ASC";
                
        return $this->db->resultSet($sql, $params);
    }


    public function getOverdueLoans() {
        $sql = "SELECT t.*, 
                b.title as book_title, b.author, 
                u.name, u.email,
                DATEDIFF(CURRENT_DATE, t.due_date) as days_overdue
                FROM {$this->table} t
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                WHERE t.status = 'borrowed'
                AND t.due_date < CURRENT_DATE
                AND t.return_date IS NULL
                AND NOT EXISTS (
                    SELECT 1 FROM penalties p WHERE p.transaction_id = t.id
                )
                ORDER BY days_overdue DESC";
                
        return $this->db->resultSet($sql);
    }

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

    public function returnBook($id) {
        $sql = "UPDATE {$this->table} SET 
                return_date = CURRENT_DATE,
                status = 'returned',
                updated_at = NOW()
                WHERE id = ? AND return_date IS NULL";
                
        return $this->db->query($sql, [$id]) ? true : false;
    }


    public function hasUserBorrowedBook($userId, $bookId) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE user_id = ? AND book_id = ? AND return_date IS NULL";
                
        $result = $this->db->single($sql, [$userId, $bookId]);
        return $result && $result['count'] > 0;
    }


    public function getUserTransactionHistory($userId) {
        $sql = "SELECT t.*, 
                b.title as book_title, b.author as book_author,
                CASE 
                    WHEN t.return_date IS NULL AND t.due_date < CURRENT_DATE THEN 'overdue'
                    WHEN t.return_date IS NULL THEN 'borrowed'
                    ELSE 'returned'
                END as status_label
                FROM {$this->table} t
                JOIN books b ON t.book_id = b.id
                WHERE t.user_id = ?
                ORDER BY t.created_at DESC";
                
        return $this->db->resultSet($sql, [$userId]);
    }


    public function getBookTransactionHistory($bookId) {
        $sql = "SELECT t.*, 
                u.name, u.email,
                CASE 
                    WHEN t.return_date IS NULL AND DATE(t.due_date) < CURRENT_DATE THEN 'overdue'
                    WHEN t.return_date IS NULL THEN 'borrowed'
                    ELSE 'returned'
                END as status_label
                FROM {$this->table} t
                JOIN users u ON t.user_id = u.id
                WHERE t.book_id = ?
                ORDER BY t.created_at DESC";
                
        return $this->db->resultSet($sql, [$bookId]);
    }


    public function getBookTransactionHistoryByUser($bookId, $userId) {
        $sql = "SELECT t.*, 
                u.name, u.email,
                CASE 
                    WHEN t.return_date IS NULL AND DATE(t.due_date) < CURRENT_DATE THEN 'overdue'
                    WHEN t.return_date IS NULL THEN 'borrowed'
                    ELSE 'returned'
                END as status_label
                FROM {$this->table} t
                JOIN users u ON t.user_id = u.id
                WHERE t.book_id = ? AND t.user_id = ?
                ORDER BY t.created_at DESC";
                
        return $this->db->resultSet($sql, [$bookId, $userId]);
    }


    public function getTransactionByUserAndBook($userId, $bookId) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = ? AND book_id = ? AND return_date IS NULL";
                
        return $this->db->single($sql, [$userId, $bookId]);
    }


    public function getTransactionsForReports($startDate = null, $endDate = null, $status = null) {
        $sql = "SELECT t.*, 
                b.title as book_title, b.author, 
                u.name, u.email,
                CASE 
                    WHEN t.return_date IS NULL AND t.due_date < CURRENT_DATE THEN 'overdue'
                    WHEN t.return_date IS NULL THEN 'borrowed'
                    ELSE 'returned'
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
            if ($status == 'borrowed') {
                $sql .= " AND t.return_date IS NULL AND t.due_date >= CURRENT_DATE";
            } else if ($status == 'overdue') {
                $sql .= " AND t.return_date IS NULL AND t.due_date < CURRENT_DATE";
            } else if ($status == 'returned') {
                $sql .= " AND t.return_date IS NOT NULL";
            }
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        return $this->db->resultSet($sql, $params);
    }

 
    public function getUserCurrentBorrows($userId) {
        $sql = "SELECT t.*, 
                b.title as book_title, b.author as book_author,
                (t.due_date::date - CURRENT_DATE) as days_remaining
                FROM {$this->table} t
                JOIN books b ON t.book_id = b.id
                WHERE t.user_id = ? 
                AND t.return_date IS NULL
                ORDER BY t.due_date ASC";
        
        error_log("Executing getUserCurrentBorrows SQL: " . $sql . " with userId: " . $userId);
        $result = $this->db->resultSet($sql, [$userId]);
        error_log("getUserCurrentBorrows returned " . (is_array($result) ? count($result) : 0) . " rows");
        return $result;
    }

    public function getUserOverdueLoans($userId) {
        $sql = "SELECT t.*, 
                b.title as book_title, b.author,
                (CURRENT_DATE - t.due_date::date) as days_overdue
                FROM {$this->table} t
                JOIN books b ON t.book_id = b.id
                WHERE t.user_id = ? 
                AND t.return_date IS NULL 
                AND t.due_date < CURRENT_DATE
                ORDER BY t.due_date ASC";
                
        return $this->db->resultSet($sql, [$userId]);
    }

    public function getFilteredTransactions($filters = []) {
        $sql = "SELECT t.*, b.title as book_title, u.name, u.email
                FROM {$this->table} t
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['start_date'])) {
            $sql .= " AND t.borrow_date >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (isset($filters['end_date'])) {
            $sql .= " AND t.borrow_date <= ?";
            $params[] = $filters['end_date'];
        }
        
        if (isset($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY t.borrow_date DESC";
        
        return $this->db->resultSet($sql, $params);
    }


    public function getOverdueLoansCount() {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE return_date IS NULL 
                AND due_date < CURRENT_DATE()";
        $result = $this->db->single($sql);
        return $result ? $result['count'] : 0;
    }

    public function getTotalUnpaidPenalties($userId = null) {
        $sql = "SELECT COALESCE(SUM(p.amount), 0) as total 
                FROM penalties p
                JOIN transaction t ON p.transaction_id = t.id
                WHERE p.status = 'unpaid' AND t.status = 'borrowed'";
        $params = [];
        
        if ($userId !== null) {
            $sql .= " AND p.user_id = ?";
            $params[] = $userId;
        }
        
        $result = $this->db->single($sql, $params);
        return $result ? $result['total'] : 0;
    }


    public function getRecentTransactions($limit = 5) {
        $sql = "SELECT t.*, b.title as book_title, u.name as borrower_name,
                CASE 
                    WHEN t.return_date IS NOT NULL THEN 'return'
                    ELSE 'borrow'
                END as type,
                COALESCE(t.return_date, t.borrow_date) as date
                FROM {$this->table} t
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                ORDER BY date DESC
                LIMIT ?";
        return $this->db->resultSet($sql, [$limit]);
    }

    public function getTotalBorrowedBooks($userId) {
        $sql = "SELECT COUNT(*) as total FROM transaction WHERE user_id = ?";
        $result = $this->db->single($sql, [$userId]);
        return $result ? $result['total'] : 0;
    }

    public function getCurrentBorrowedBooks($userId) {
        $sql = "SELECT COUNT(*) as total FROM transaction WHERE user_id = ? AND return_date IS NULL";
        $result = $this->db->single($sql, [$userId]);
        return $result ? $result['total'] : 0;
    }

    public function getOverdueBooksCount($userId) {
        $sql = "SELECT COUNT(*) as total FROM transaction 
                WHERE user_id = ? 
                AND return_date IS NULL 
                AND due_date < CURRENT_DATE()";
        $result = $this->db->single($sql, [$userId]);
        return $result ? $result['total'] : 0;
    }

    public function getLoanHistory($userId) {
        $sql = "SELECT t.*, b.title as book_title, b.author as book_author 
                FROM transaction t 
                JOIN books b ON t.book_id = b.id 
                WHERE t.user_id = ? 
                ORDER BY t.borrow_date DESC 
                LIMIT 10";
        return $this->db->resultSet($sql, [$userId]);
    }

    public function updateTransaction($transactionId, $data) {
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $transactionId;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        return $this->db->query($sql, $params) ? true : false;
    }
}
