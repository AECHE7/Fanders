<?php
/**
 * PenaltyService - Business logic for penalties
 */
class PenaltyService extends BaseService {
    private $penaltyModel;

    public function __construct() {
        parent::__construct();
        $this->penaltyModel = new PenaltyModel();
        $this->setModel($this->penaltyModel);
    }

 
    public function getUserPenalties($userId) {
        return $this->penaltyModel->getPenaltiesByUser($userId);
    }

    public function getAllUnpaidPenalties() {
        // Access protected properties via getter methods or reflection if available
        $reflection = new ReflectionClass($this->penaltyModel);
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);
        $table = $tableProperty->getValue($this->penaltyModel);

        $dbProperty = $reflection->getProperty('db');
        $dbProperty->setAccessible(true);
        $db = $dbProperty->getValue($this->penaltyModel);

        $sql = "SELECT p.*, t.borrow_date, t.due_date, t.return_date, 
                       b.title as book_title, u.name, u.email
                FROM {$table} p
                JOIN transaction t ON p.transaction_id = t.id
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                WHERE p.status = 'unpaid'
                AND t.status = 'borrowed'
                AND t.due_date < CURRENT_DATE
                AND t.return_date IS NULL
                ORDER BY p.penalty_date DESC";

        return $db->resultSet($sql);
    }

    public function getPenaltyByTransaction($transactionId) {
        return $this->penaltyModel->getPenaltyByTransaction($transactionId);
    }

 
    public function createOrUpdatePenalty($userId, $transactionId, $penaltyAmount) {
        $existingPenalty = $this->penaltyModel->getPenaltyByTransaction($transactionId);
        $data = [
            'user_id' => $userId,
            'transaction_id' => $transactionId,
            'penalty_amount' => $penaltyAmount,
            'penalty_date' => date('Y-m-d H:i:s')
        ];

        if ($existingPenalty) {
            if ($penaltyAmount > $existingPenalty['amount']) {
                $updated = $this->penaltyModel->update($existingPenalty['id'], $data);
                return $updated ? true : false;
            }
            return true;
        } else {
            $created = $this->penaltyModel->create($data);
            return $created ? true : false;
        }
    }


    public function getPenaltiesForReports($startDate = null, $endDate = null, $status = null) {
        // Access protected properties via getter methods or reflection if available
        $reflection = new ReflectionClass($this->penaltyModel);
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);
        $table = $tableProperty->getValue($this->penaltyModel);

        $dbProperty = $reflection->getProperty('db');
        $dbProperty->setAccessible(true);
        $db = $dbProperty->getValue($this->penaltyModel);

        $sql = "SELECT p.*, t.borrow_date, t.due_date, t.return_date, 
                       b.title as book_title, u.name, u.email
                FROM {$table} p
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

        if ($status) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY p.penalty_date DESC";

        return $db->resultSet($sql, $params);
    }

 
    public function getTotalPenalties() {
        $reflection = new ReflectionClass($this->penaltyModel);
        $tableProperty = $reflection->getProperty('table');
        $tableProperty->setAccessible(true);
        $table = $tableProperty->getValue($this->penaltyModel);

        $dbProperty = $reflection->getProperty('db');
        $dbProperty->setAccessible(true);
        $db = $dbProperty->getValue($this->penaltyModel);

        $sql = "SELECT COALESCE(SUM(penalty_amount), 0) as total FROM {$table}";

        $result = $db->single($sql);
        return $result ? floatval($result['total']) : 0;
    }
}
