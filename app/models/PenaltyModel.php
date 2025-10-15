<?php
/**
 * PenaltyModel - Handles penalty records and calculations for loan defaults
 * Updated for microfinance loan penalties
 */
require_once __DIR__ . '/../core/BaseModel.php';

class PenaltyModel extends BaseModel {
    protected $table = 'penalties';
    protected $primaryKey = 'id';
    protected $fillable = [
        'loan_id', 'payment_id', 'client_id', 'penalty_amount', 'penalty_date',
        'reason', 'status', 'paid_at', 'assessed_by', 'notes', 'created_at', 'updated_at'
    ];
    protected $hidden = [];

    // Status definitions
    public static $STATUS_PENDING = 'pending';
    public static $STATUS_PAID = 'paid';
    public static $STATUS_WAIVED = 'waived';

    public function getPenaltyWithDetails($id) {
        $sql = "SELECT p.*,
                l.loan_amount, l.total_amount,
                c.name as client_name, c.phone_number,
                u.name as assessed_by_name
                FROM {$this->table} p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON p.client_id = c.id
                LEFT JOIN users u ON p.assessed_by = u.id
                WHERE p.id = ?";

        return $this->db->single($sql, [$id]);
    }

    public function getPenaltiesByLoan($loanId) {
        $sql = "SELECT p.*,
                c.name as client_name, c.phone_number,
                u.name as assessed_by_name
                FROM {$this->table} p
                JOIN clients c ON p.client_id = c.id
                LEFT JOIN users u ON p.assessed_by = u.id
                WHERE p.loan_id = ?
                ORDER BY p.penalty_date DESC";

        return $this->db->resultSet($sql, [$loanId]);
    }

    public function getPenaltiesByClient($clientId) {
        $sql = "SELECT p.*,
                l.loan_amount, l.total_amount,
                u.name as assessed_by_name
                FROM {$this->table} p
                JOIN loans l ON p.loan_id = l.id
                LEFT JOIN users u ON p.assessed_by = u.id
                WHERE p.client_id = ?
                ORDER BY p.penalty_date DESC";

        return $this->db->resultSet($sql, [$clientId]);
    }

    public function getPenaltiesByStatus($status) {
        $sql = "SELECT p.*,
                l.loan_amount, l.total_amount,
                c.name as client_name, c.phone_number,
                u.name as assessed_by_name
                FROM {$this->table} p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON p.client_id = c.id
                LEFT JOIN users u ON p.assessed_by = u.id
                WHERE p.status = ?
                ORDER BY p.penalty_date DESC";

        return $this->db->resultSet($sql, [$status]);
    }

    public function getPenaltiesByDateRange($startDate, $endDate) {
        $sql = "SELECT p.*,
                l.loan_amount, l.total_amount,
                c.name as client_name, c.phone_number,
                u.name as assessed_by_name
                FROM {$this->table} p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON p.client_id = c.id
                LEFT JOIN users u ON p.assessed_by = u.id
                WHERE DATE(p.penalty_date) BETWEEN ? AND ?
                ORDER BY p.penalty_date DESC";

        return $this->db->resultSet($sql, [$startDate, $endDate]);
    }

    public function createLatePaymentPenalty($loanId, $paymentId, $assessedBy, $penaltyAmount = null, $reason = null) {
        // Get loan and payment details
        $loanModel = new LoanModel();
        $loan = $loanModel->findById($loanId);

        if (!$loan) {
            $this->setLastError('Loan not found.');
            return false;
        }

        // Calculate penalty amount if not provided (default 2% of weekly payment)
        if ($penaltyAmount === null) {
            $penaltyAmount = $loan['weekly_payment'] * 0.02; // 2% penalty
        }

        $data = [
            'loan_id' => $loanId,
            'payment_id' => $paymentId,
            'client_id' => $loan['client_id'],
            'penalty_amount' => $penaltyAmount,
            'penalty_date' => date('Y-m-d H:i:s'),
            'reason' => $reason ?: 'Late payment penalty',
            'status' => self::$STATUS_PENDING,
            'assessed_by' => $assessedBy,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->create($data);
    }

    public function createDefaultPenalty($loanId, $assessedBy, $penaltyAmount, $reason = null) {
        // Get loan details
        $loanModel = new LoanModel();
        $loan = $loanModel->findById($loanId);

        if (!$loan) {
            $this->setLastError('Loan not found.');
            return false;
        }

        $data = [
            'loan_id' => $loanId,
            'client_id' => $loan['client_id'],
            'penalty_amount' => $penaltyAmount,
            'penalty_date' => date('Y-m-d H:i:s'),
            'reason' => $reason ?: 'Loan default penalty',
            'status' => self::$STATUS_PENDING,
            'assessed_by' => $assessedBy,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->create($data);
    }

    public function markPenaltyAsPaid($penaltyId, $paidAt = null) {
        $data = [
            'status' => self::$STATUS_PAID,
            'paid_at' => $paidAt ?: date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->update($penaltyId, $data);
    }

    public function waivePenalty($penaltyId, $notes = null) {
        $data = [
            'status' => self::$STATUS_WAIVED,
            'notes' => $notes,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->update($penaltyId, $data);
    }

    public function getTotalPenaltiesForLoan($loanId) {
        $sql = "SELECT SUM(penalty_amount) as total FROM {$this->table}
                WHERE loan_id = ? AND status != ?";

        $result = $this->db->single($sql, [$loanId, self::$STATUS_WAIVED]);
        return $result ? $result['total'] : 0;
    }

    public function getTotalUnpaidPenaltiesForClient($clientId) {
        $sql = "SELECT SUM(penalty_amount) as total FROM {$this->table}
                WHERE client_id = ? AND status = ?";

        $result = $this->db->single($sql, [$clientId, self::$STATUS_PENDING]);
        return $result ? $result['total'] : 0;
    }

    public function getPenaltyStats($startDate = null, $endDate = null) {
        $sql = "SELECT
                COUNT(*) as total_penalties,
                SUM(CASE WHEN status = ? THEN penalty_amount ELSE 0 END) as total_pending,
                SUM(CASE WHEN status = ? THEN penalty_amount ELSE 0 END) as total_paid,
                SUM(CASE WHEN status = ? THEN penalty_amount ELSE 0 END) as total_waived,
                AVG(penalty_amount) as average_penalty
                FROM {$this->table}";

        $params = [self::$STATUS_PENDING, self::$STATUS_PAID, self::$STATUS_WAIVED];

        if ($startDate && $endDate) {
            $sql .= " WHERE DATE(penalty_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        return $this->db->single($sql, $params);
    }

    public function getPenaltiesForReports($startDate = null, $endDate = null, $status = null) {
        $sql = "SELECT p.*,
                l.loan_amount, l.disbursement_date,
                c.name as client_name, c.phone_number, c.email,
                u.name as assessed_by_name,
                DATEDIFF(CURRENT_DATE, p.penalty_date) as days_since_assessment
                FROM {$this->table} p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON p.client_id = c.id
                LEFT JOIN users u ON p.assessed_by = u.id
                WHERE 1=1";

        $params = [];

        if ($startDate) {
            $sql .= " AND DATE(p.penalty_date) >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND DATE(p.penalty_date) <= ?";
            $params[] = $endDate;
        }

        if ($status) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY p.penalty_date DESC";

        return $this->db->resultSet($sql, $params);
    }

    public function searchPenalties($term) {
        $sql = "SELECT p.*,
                l.loan_amount, l.total_amount,
                c.name as client_name, c.phone_number,
                u.name as assessed_by_name
                FROM {$this->table} p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON p.client_id = c.id
                LEFT JOIN users u ON p.assessed_by = u.id
                WHERE c.name LIKE ?
                OR c.phone_number LIKE ?
                OR p.reason LIKE ?
                ORDER BY p.penalty_date DESC";

        $searchTerm = "%{$term}%";
        $params = [$searchTerm, $searchTerm, $searchTerm];

        return $this->db->resultSet($sql, $params);
    }

    public function getOverduePenalties($daysOverdue = 30) {
        $sql = "SELECT p.*,
                l.loan_amount, l.total_amount,
                c.name as client_name, c.phone_number,
                u.name as assessed_by_name,
                DATEDIFF(CURRENT_DATE, p.penalty_date) as days_overdue
                FROM {$this->table} p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON p.client_id = c.id
                LEFT JOIN users u ON p.assessed_by = u.id
                WHERE p.status = ?
                AND DATEDIFF(CURRENT_DATE, p.penalty_date) > ?
                ORDER BY days_overdue DESC";

        return $this->db->resultSet($sql, [self::$STATUS_PENDING, $daysOverdue]);
    }

    public function create($data) {
        // Validate required fields
        $requiredFields = ['loan_id', 'client_id', 'penalty_amount', 'assessed_by'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $this->setLastError("Field '{$field}' is required.");
                return false;
            }
        }

        // Set default values
        $data['penalty_date'] = $data['penalty_date'] ?? date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? self::$STATUS_PENDING;
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        return parent::create($data);
    }
}
