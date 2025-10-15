<?php
/**
 * PaymentModel - Handles payment operations
 * Replaces TransactionModel functionality for loan payments
 */
require_once __DIR__ . '/../core/BaseModel.php';

class PaymentModel extends BaseModel {
    protected $table = 'payments';
    protected $primaryKey = 'id';
    protected $fillable = [
        'loan_id', 'client_id', 'payment_amount', 'payment_date', 'payment_method',
        'collected_by', 'recorded_by', 'week_number', 'principal_paid', 'interest_paid',
        'insurance_paid', 'savings_paid', 'notes', 'created_at', 'updated_at'
    ];
    protected $hidden = [];

    public function getPaymentWithDetails($id) {
        $sql = "SELECT p.*,
                l.loan_amount, l.total_amount, l.weekly_payment,
                c.name as client_name, c.phone_number,
                u1.name as collected_by_name,
                u2.name as recorded_by_name
                FROM {$this->table} p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON p.client_id = c.id
                LEFT JOIN users u1 ON p.collected_by = u1.id
                LEFT JOIN users u2 ON p.recorded_by = u2.id
                WHERE p.id = ?";

        return $this->db->single($sql, [$id]);
    }

    public function getPaymentsByLoan($loanId) {
        $sql = "SELECT p.*,
                c.name as client_name, c.phone_number,
                u1.name as collected_by_name,
                u2.name as recorded_by_name
                FROM {$this->table} p
                JOIN clients c ON p.client_id = c.id
                LEFT JOIN users u1 ON p.collected_by = u1.id
                LEFT JOIN users u2 ON p.recorded_by = u2.id
                WHERE p.loan_id = ?
                ORDER BY p.payment_date DESC, p.week_number DESC";

        return $this->db->resultSet($sql, [$loanId]);
    }

    public function getPaymentsByClient($clientId) {
        $sql = "SELECT p.*,
                l.loan_amount, l.total_amount,
                u1.name as collected_by_name,
                u2.name as recorded_by_name
                FROM {$this->table} p
                JOIN loans l ON p.loan_id = l.id
                LEFT JOIN users u1 ON p.collected_by = u1.id
                LEFT JOIN users u2 ON p.recorded_by = u2.id
                WHERE p.client_id = ?
                ORDER BY p.payment_date DESC";

        return $this->db->resultSet($sql, [$clientId]);
    }

    public function getPaymentsByDateRange($startDate, $endDate) {
        $sql = "SELECT p.*,
                l.loan_amount, l.total_amount,
                c.name as client_name, c.phone_number,
                u1.name as collected_by_name,
                u2.name as recorded_by_name
                FROM {$this->table} p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON p.client_id = c.id
                LEFT JOIN users u1 ON p.collected_by = u1.id
                LEFT JOIN users u2 ON p.recorded_by = u2.id
                WHERE DATE(p.payment_date) BETWEEN ? AND ?
                ORDER BY p.payment_date DESC";

        return $this->db->resultSet($sql, [$startDate, $endDate]);
    }

    public function getPaymentsByCollector($collectorId, $startDate = null, $endDate = null) {
        $sql = "SELECT p.*,
                l.loan_amount, l.total_amount,
                c.name as client_name, c.phone_number
                FROM {$this->table} p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON p.client_id = c.id
                WHERE p.collected_by = ?";

        $params = [$collectorId];

        if ($startDate && $endDate) {
            $sql .= " AND DATE(p.payment_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $sql .= " ORDER BY p.payment_date DESC";

        return $this->db->resultSet($sql, $params);
    }

    public function recordPayment($loanId, $paymentAmount, $weekNumber, $recordedBy, $collectedBy = null, $paymentMethod = 'cash', $notes = null) {
        // Get loan details
        $loanModel = new LoanModel();
        $loan = $loanModel->findById($loanId);

        if (!$loan) {
            $this->setLastError('Loan not found.');
            return false;
        }

        // Calculate payment breakdown (simplified - in real system this would be more complex)
        $weeklyPayment = $loan['weekly_payment'];
        $remainingBalance = $loan['total_amount'] - $this->getTotalPaymentsForLoan($loanId);

        // Ensure payment doesn't exceed remaining balance
        if ($paymentAmount > $remainingBalance) {
            $paymentAmount = $remainingBalance;
        }

        // Simple breakdown - in production, this would use amortization schedule
        $principalPaid = $paymentAmount * 0.7; // 70% to principal
        $interestPaid = $paymentAmount * 0.2;  // 20% to interest
        $insurancePaid = $paymentAmount * 0.05; // 5% to insurance
        $savingsPaid = $paymentAmount * 0.05;   // 5% to savings

        $data = [
            'loan_id' => $loanId,
            'client_id' => $loan['client_id'],
            'payment_amount' => $paymentAmount,
            'payment_date' => date('Y-m-d H:i:s'),
            'payment_method' => $paymentMethod,
            'collected_by' => $collectedBy,
            'recorded_by' => $recordedBy,
            'week_number' => $weekNumber,
            'principal_paid' => $principalPaid,
            'interest_paid' => $interestPaid,
            'insurance_paid' => $insurancePaid,
            'savings_paid' => $savingsPaid,
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $paymentId = $this->create($data);

        if ($paymentId) {
            // Check if loan is now complete
            $totalPaid = $this->getTotalPaymentsForLoan($loanId);
            if ($totalPaid >= $loan['total_amount']) {
                $loanModel->completeLoan($loanId);
            }
        }

        return $paymentId;
    }

    public function getTotalPaymentsForLoan($loanId) {
        $sql = "SELECT SUM(payment_amount) as total FROM {$this->table} WHERE loan_id = ?";
        $result = $this->db->single($sql, [$loanId]);
        return $result ? $result['total'] : 0;
    }

    public function getPaymentsForWeek($loanId, $weekNumber) {
        $sql = "SELECT * FROM {$this->table}
                WHERE loan_id = ? AND week_number = ?
                ORDER BY payment_date DESC";

        return $this->db->resultSet($sql, [$loanId, $weekNumber]);
    }

    public function getNextPaymentWeek($loanId) {
        $sql = "SELECT MAX(week_number) as last_week FROM {$this->table} WHERE loan_id = ?";
        $result = $this->db->single($sql, [$loanId]);
        return $result ? ($result['last_week'] + 1) : 1;
    }

    public function getOverduePayments() {
        $sql = "SELECT p.*,
                l.loan_amount, l.disbursement_date, l.weekly_payment,
                c.name as client_name, c.phone_number,
                DATEDIFF(CURDATE(), DATE_ADD(l.disbursement_date, INTERVAL (p.week_number - 1) WEEK)) as days_overdue
                FROM {$this->table} p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON p.client_id = c.id
                WHERE l.status = 'active'
                AND DATEDIFF(CURDATE(), DATE_ADD(l.disbursement_date, INTERVAL (p.week_number - 1) WEEK)) > 7
                ORDER BY days_overdue DESC";

        return $this->db->resultSet($sql);
    }

    public function getPaymentSummary($startDate = null, $endDate = null) {
        $sql = "SELECT
                COUNT(*) as total_payments,
                SUM(payment_amount) as total_amount,
                SUM(principal_paid) as total_principal,
                SUM(interest_paid) as total_interest,
                SUM(insurance_paid) as total_insurance,
                SUM(savings_paid) as total_savings,
                AVG(payment_amount) as average_payment
                FROM {$this->table}";

        $params = [];

        if ($startDate && $endDate) {
            $sql .= " WHERE DATE(payment_date) BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
        }

        return $this->db->single($sql, $params);
    }

    public function getPaymentsByPaymentMethod($startDate = null, $endDate = null) {
        $sql = "SELECT payment_method, COUNT(*) as count, SUM(payment_amount) as total
                FROM {$this->table}";

        $params = [];

        if ($startDate && $endDate) {
            $sql .= " WHERE DATE(payment_date) BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
        }

        $sql .= " GROUP BY payment_method ORDER BY total DESC";

        return $this->db->resultSet($sql, $params);
    }

    public function getRecentPayments($limit = 10) {
        $sql = "SELECT p.*,
                l.loan_amount,
                c.name as client_name, c.phone_number,
                u1.name as collected_by_name,
                u2.name as recorded_by_name
                FROM {$this->table} p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON p.client_id = c.id
                LEFT JOIN users u1 ON p.collected_by = u1.id
                LEFT JOIN users u2 ON p.recorded_by = u2.id
                ORDER BY p.payment_date DESC, p.created_at DESC
                LIMIT ?";

        return $this->db->resultSet($sql, [$limit]);
    }

    public function searchPayments($term) {
        $sql = "SELECT p.*,
                l.loan_amount,
                c.name as client_name, c.phone_number,
                u1.name as collected_by_name,
                u2.name as recorded_by_name
                FROM {$this->table} p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON p.client_id = c.id
                LEFT JOIN users u1 ON p.collected_by = u1.id
                LEFT JOIN users u2 ON p.recorded_by = u2.id
                WHERE c.name LIKE ? OR c.phone_number LIKE ?
                ORDER BY p.payment_date DESC";

        $searchTerm = "%{$term}%";
        $params = [$searchTerm, $searchTerm];

        return $this->db->resultSet($sql, $params);
    }

    public function getPaymentStats() {
        $stats = [];

        // Total payments
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->single($sql);
        $stats['total_payments'] = $result ? $result['count'] : 0;

        // Total amount collected
        $sql = "SELECT SUM(payment_amount) as total FROM {$this->table}";
        $result = $this->db->single($sql);
        $stats['total_collected'] = $result ? $result['total'] : 0;

        // Payments this month
        $sql = "SELECT SUM(payment_amount) as total FROM {$this->table}
                WHERE MONTH(payment_date) = MONTH(CURDATE())
                AND YEAR(payment_date) = YEAR(CURDATE())";
        $result = $this->db->single($sql);
        $stats['collected_this_month'] = $result ? $result['total'] : 0;

        // Payments today
        $sql = "SELECT SUM(payment_amount) as total FROM {$this->table}
                WHERE DATE(payment_date) = CURDATE()";
        $result = $this->db->single($sql);
        $stats['collected_today'] = $result ? $result['total'] : 0;

        // Average payment amount
        $sql = "SELECT AVG(payment_amount) as average FROM {$this->table}";
        $result = $this->db->single($sql);
        $stats['average_payment'] = $result ? $result['average'] : 0;

        return $stats;
    }

    public function create($data) {
        // Validate required fields
        $requiredFields = ['loan_id', 'client_id', 'payment_amount', 'recorded_by', 'week_number'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $this->setLastError("Field '{$field}' is required.");
                return false;
            }
        }

        // Set default values
        $data['payment_date'] = $data['payment_date'] ?? date('Y-m-d H:i:s');
        $data['payment_method'] = $data['payment_method'] ?? 'cash';
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        // Set breakdown amounts to 0 if not provided
        $data['principal_paid'] = $data['principal_paid'] ?? 0;
        $data['interest_paid'] = $data['interest_paid'] ?? 0;
        $data['insurance_paid'] = $data['insurance_paid'] ?? 0;
        $data['savings_paid'] = $data['savings_paid'] ?? 0;

        return parent::create($data);
    }
}
