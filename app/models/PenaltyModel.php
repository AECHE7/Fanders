<?php
/**
 * PenaltyModel - Handles penalty records and calculations
 */
class PenaltyModel extends BaseModel {
    protected $table = 'penalties';
    protected $primaryKey = 'id';
    protected $fillable = [
        'user_id', 'transaction_id', 'penalty_amount', 'penalty_date', 'reason', 'status', 'paid_at', 'created_at', 'updated_at'
    ];

 
    public function getPenaltyByTransaction($transactionId) {
        $sql = "SELECT p.*, t.book_id, t.user_id, b.title as book_title, u.name as user_name
                FROM {$this->table} p
                JOIN transaction t ON p.transaction_id = t.id
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                WHERE p.transaction_id = ?";
                
        return $this->db->single($sql, [$transactionId]);
    }

    public function getPenaltiesByUser($userId) {
        $sql = "SELECT p.*, t.book_id, b.title as book_title, u.name as user_name
                FROM {$this->table} p
                JOIN transaction t ON p.transaction_id = t.id
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                WHERE p.user_id = ?
                ORDER BY p.penalty_date DESC";
        return $this->db->resultSet($sql, [$userId]);
    }


public function getPenaltiesForReports($startDate = null, $endDate = null, $isPaid = null) {
        $sql = "SELECT p.*, t.book_id, t.borrow_date, t.due_date, t.return_date, b.title as book_title, u.name as user_name,
                DATEDIFF(CURRENT_DATE, t.due_date) as days_overdue
                FROM {$this->table} p
                JOIN transaction t ON p.transaction_id = t.id
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                WHERE 1=1";
        $params = [];
        if ($startDate) {
            $sql .= " AND p.penalty_date >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $sql .= " AND p.penalty_date <= ?";
            $params[] = $endDate;
        }
        if ($isPaid !== null) {
            $sql .= " AND p.status = ?";
            $params[] = $isPaid;
        }
        $sql .= " ORDER BY p.penalty_date DESC";
        return $this->db->resultSet($sql, $params);
    }


    public function create($data) {
        $fields = [];
        $placeholders = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = $key;
            $placeholders[] = '?';
            $params[] = $value;
        }
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        if ($this->db->query($sql, $params)) {
            return $this->db->lastInsertId();
        }
        return false;
    }

  
    public function update($id, $data) {
        $fields = [];
        $params = [];
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        return $this->db->query($sql, $params) ? true : false;
    }
}
