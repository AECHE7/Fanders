<?php
/**
 * PenaltyService - Handles penalties for late book returns
 */
class PenaltyService extends BaseService {
    private $penaltyModel;
    private $transactionModel;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->penaltyModel = new PenaltyModel();
        $this->transactionModel = new TransactionModel();
        $this->setModel($this->penaltyModel);
    }

    /**
     * Get penalty with details
     * 
     * @param int $id
     * @return array|bool
     */
    public function getPenaltyWithDetails($id) {
        return $this->penaltyModel->getPenaltyWithDetails($id);
    }

    /**
     * Get all penalties with details
     * 
     * @return array|bool
     */
    public function getAllPenaltiesWithDetails() {
        return $this->penaltyModel->getAllPenaltiesWithDetails();
    }

    /**
     * Create penalty for overdue book
     * 
     * @param int $transactionId
     * @param int $daysOverdue
     * @return int|bool
     */
    public function createPenalty($transactionId, $daysOverdue) {
        // Check if transaction exists
        $transaction = $this->transactionModel->findById($transactionId);
        
        if (!$transaction) {
            $this->setErrorMessage('Transaction not found.');
            return false;
        }
        
        // Check if penalty already exists for this transaction
        if ($this->penaltyModel->transactionHasPenalty($transactionId)) {
            $this->setErrorMessage('Penalty already exists for this transaction.');
            return false;
        }
        
        // Create penalty
        return $this->penaltyModel->createPenalty($transactionId, $daysOverdue);
    }

    /**
     * Mark penalty as paid
     * 
     * @param int $id
     * @return bool
     */
    public function markAsPaid($id) {
        // Check if penalty exists
        $penalty = $this->penaltyModel->findById($id);
        
        if (!$penalty) {
            $this->setErrorMessage('Penalty not found.');
            return false;
        }
        
        if ($penalty['is_paid']) {
            $this->setErrorMessage('Penalty is already paid.');
            return false;
        }
        
        // Mark as paid
        return $this->penaltyModel->markAsPaid($id);
    }

    /**
     * Get unpaid penalties
     * 
     * @return array|bool
     */
    public function getUnpaidPenalties() {
        return $this->penaltyModel->getUnpaidPenalties();
    }

    /**
     * Get user's penalties
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getUserPenalties($userId) {
        return $this->penaltyModel->getUserPenalties($userId);
    }

    /**
     * Get user's unpaid penalties total
     * 
     * @param int $userId
     * @return float
     */
    public function getUserUnpaidPenaltiesTotal($userId) {
        return $this->penaltyModel->getUserUnpaidPenaltiesTotal($userId);
    }

    /**
     * Calculate overdue penalty amount
     * 
     * @param int $daysOverdue
     * @return float
     */
    public function calculatePenaltyAmount($daysOverdue) {
        return PENALTY_BASE_AMOUNT + (PENALTY_DAILY_INCREMENT * $daysOverdue);
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
        return $this->penaltyModel->getPenaltiesForReports($startDate, $endDate, $isPaid);
    }

    /**
     * Get penalty statistics
     * 
     * @return array
     */
    public function getPenaltyStatistics() {
        $stats = [];
        
        // Total penalties
        $sql = "SELECT COUNT(*) as count, SUM(amount) as total FROM penalties";
        $result = $this->db->single($sql);
        $stats['total_count'] = $result ? $result['count'] : 0;
        $stats['total_amount'] = $result ? $result['total'] : 0;
        
        // Paid penalties
        $sql = "SELECT COUNT(*) as count, SUM(amount) as total FROM penalties WHERE is_paid = 1";
        $result = $this->db->single($sql);
        $stats['paid_count'] = $result ? $result['count'] : 0;
        $stats['paid_amount'] = $result ? $result['total'] : 0;
        
        // Unpaid penalties
        $sql = "SELECT COUNT(*) as count, SUM(amount) as total FROM penalties WHERE is_paid = 0";
        $result = $this->db->single($sql);
        $stats['unpaid_count'] = $result ? $result['count'] : 0;
        $stats['unpaid_amount'] = $result ? $result['total'] : 0;
        
        return $stats;
    }
}
