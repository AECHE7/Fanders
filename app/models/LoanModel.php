<?php
/**
 * LoanModel - Handles loan operations
 * Replaces BookModel functionality for loan management
 */
require_once __DIR__ . '/../core/BaseModel.php';

class LoanModel extends BaseModel {
    protected $table = 'loans';
    protected $primaryKey = 'id';
    protected $fillable = [
        'client_id', 'loan_amount', 'interest_rate', 'loan_term_months',
        'total_interest', 'total_amount', 'insurance_fee', 'weekly_payment',
        'status', 'application_date', 'approval_date', 'disbursement_date',
        'completion_date', 'created_at', 'updated_at'
    ];
    protected $hidden = [];

    // Status definitions
    public static $STATUS_APPLICATION = 'application';
    public static $STATUS_APPROVED = 'approved';
    public static $STATUS_ACTIVE = 'active';
    public static $STATUS_COMPLETED = 'completed';
    public static $STATUS_DEFAULTED = 'defaulted';

    public function getLoanWithClient($id) {
        $sql = "SELECT l.*, c.name as client_name, c.phone_number, c.email
                FROM {$this->table} l
                JOIN clients c ON l.client_id = c.id
                WHERE l.id = ?";

        return $this->db->single($sql, [$id]);
    }

    public function getAllLoansWithClients() {
        $sql = "SELECT l.*, c.name as client_name, c.phone_number, c.email
                FROM {$this->table} l
                JOIN clients c ON l.client_id = c.id
                ORDER BY l.created_at DESC";

        return $this->db->resultSet($sql);
    }

    public function getLoansByClient($clientId) {
        $sql = "SELECT l.*, c.name as client_name, c.phone_number, c.email
                FROM {$this->table} l
                JOIN clients c ON l.client_id = c.id
                WHERE l.client_id = ?
                ORDER BY l.created_at DESC";

        return $this->db->resultSet($sql, [$clientId]);
    }

    public function getLoansByStatus($status) {
        $sql = "SELECT l.*, c.name as client_name, c.phone_number, c.email
                FROM {$this->table} l
                JOIN clients c ON l.client_id = c.id
                WHERE l.status = ?
                ORDER BY l.created_at DESC";

        return $this->db->resultSet($sql, [$status]);
    }

    public function getActiveLoans() {
        return $this->getLoansByStatus(self::$STATUS_ACTIVE);
    }

    public function getPendingApplications() {
        return $this->getLoansByStatus(self::$STATUS_APPLICATION);
    }

    public function getApprovedLoans() {
        return $this->getLoansByStatus(self::$STATUS_APPROVED);
    }

    public function calculateLoanDetails($loanAmount, $interestRate = 5.00, $loanTermMonths = 4, $insuranceFee = 425.00) {
        $totalInterest = $loanAmount * ($interestRate / 100) * $loanTermMonths;
        $totalAmount = $loanAmount + $totalInterest + $insuranceFee;
        $weeklyPayment = $totalAmount / 17; // 17 weeks for 4 months

        return [
            'loan_amount' => $loanAmount,
            'interest_rate' => $interestRate,
            'loan_term_months' => $loanTermMonths,
            'total_interest' => $totalInterest,
            'insurance_fee' => $insuranceFee,
            'total_amount' => $totalAmount,
            'weekly_payment' => $weeklyPayment
        ];
    }

    public function createLoanApplication($clientId, $loanAmount) {
        $calculations = $this->calculateLoanDetails($loanAmount);

        $data = array_merge($calculations, [
            'client_id' => $clientId,
            'status' => self::$STATUS_APPLICATION,
            'application_date' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $this->create($data);
    }

    public function approveLoan($loanId, $approvedBy = null) {
        $data = [
            'status' => self::$STATUS_APPROVED,
            'approval_date' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->update($loanId, $data);
    }

    public function disburseLoan($loanId, $disbursedBy = null) {
        $data = [
            'status' => self::$STATUS_ACTIVE,
            'disbursement_date' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->update($loanId, $data);
    }

    public function completeLoan($loanId) {
        $data = [
            'status' => self::$STATUS_COMPLETED,
            'completion_date' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->update($loanId, $data);
    }

    public function getLoanPaymentSummary($loanId) {
        $sql = "SELECT l.*,
                COUNT(p.id) as payments_made,
                SUM(p.payment_amount) as total_paid,
                SUM(p.principal_paid) as principal_paid,
                SUM(p.interest_paid) as interest_paid,
                SUM(p.insurance_paid) as insurance_paid,
                SUM(p.savings_paid) as savings_paid,
                l.total_amount - COALESCE(SUM(p.payment_amount), 0) as outstanding_balance,
                MAX(p.week_number) as last_payment_week
                FROM {$this->table} l
                LEFT JOIN payments p ON l.id = p.loan_id
                WHERE l.id = ?
                GROUP BY l.id";

        return $this->db->single($sql, [$loanId]);
    }

    public function getLoanPaymentSchedule($loanId) {
        $loan = $this->findById($loanId);
        if (!$loan) return [];

        $schedule = [];
        $startDate = strtotime($loan['disbursement_date']);
        $weeklyPayment = $loan['weekly_payment'];

        for ($week = 1; $week <= 17; $week++) {
            $dueDate = date('Y-m-d', strtotime("+".($week-1)." weeks", $startDate));

            // Get actual payment for this week if exists
            $sql = "SELECT * FROM payments WHERE loan_id = ? AND week_number = ?";
            $payment = $this->db->single($sql, [$loanId, $week]);

            $schedule[] = [
                'week_number' => $week,
                'due_date' => $dueDate,
                'expected_payment' => $weeklyPayment,
                'actual_payment' => $payment ? $payment['payment_amount'] : 0,
                'payment_date' => $payment ? $payment['payment_date'] : null,
                'status' => $payment ? 'paid' : (strtotime($dueDate) < time() ? 'overdue' : 'pending')
            ];
        }

        return $schedule;
    }

    public function getOverdueLoans() {
        $sql = "SELECT l.*, c.name as client_name, c.phone_number, c.email,
                DATEDIFF(CURDATE(), DATE_ADD(l.disbursement_date, INTERVAL (p.max_week - 1) WEEK)) as days_overdue,
                l.weekly_payment * (17 - COALESCE(p.payments_made, 0)) as outstanding_amount
                FROM {$this->table} l
                JOIN clients c ON l.client_id = c.id
                LEFT JOIN (
                    SELECT loan_id, COUNT(*) as payments_made, MAX(week_number) as max_week
                    FROM payments
                    GROUP BY loan_id
                ) p ON l.id = p.loan_id
                WHERE l.status = ?
                AND l.disbursement_date IS NOT NULL
                AND DATEDIFF(CURDATE(), l.disbursement_date) > (COALESCE(p.max_week, 0) * 7)
                ORDER BY days_overdue DESC";

        return $this->db->resultSet($sql, [self::$STATUS_ACTIVE]);
    }

    public function getLoansDueThisWeek() {
        $sql = "SELECT l.*, c.name as client_name, c.phone_number, c.email,
                COUNT(p.id) as payments_made,
                l.weekly_payment as due_amount
                FROM {$this->table} l
                JOIN clients c ON l.client_id = c.id
                LEFT JOIN payments p ON l.id = p.loan_id
                WHERE l.status = ?
                AND l.disbursement_date IS NOT NULL
                AND WEEK(l.disbursement_date) = WEEK(CURDATE())
                AND YEAR(l.disbursement_date) = YEAR(CURDATE())
                GROUP BY l.id
                HAVING payments_made < WEEK(CURDATE()) - WEEK(l.disbursement_date) + 1";

        return $this->db->resultSet($sql, [self::$STATUS_ACTIVE]);
    }

    public function getLoanStats() {
        $stats = [];

        // Total loans
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->single($sql);
        $stats['total_loans'] = $result ? $result['count'] : 0;

        // Loans by status
        $sql = "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status";
        $result = $this->db->resultSet($sql);
        $stats['loans_by_status'] = $result ?: [];

        // Total loan amount disbursed
        $sql = "SELECT SUM(loan_amount) as total FROM {$this->table} WHERE status IN (?, ?)";
        $result = $this->db->single($sql, [self::$STATUS_ACTIVE, self::$STATUS_COMPLETED]);
        $stats['total_disbursed'] = $result ? $result['total'] : 0;

        // Total outstanding amount
        $sql = "SELECT SUM(l.total_amount - COALESCE(p.total_paid, 0)) as total
                FROM {$this->table} l
                LEFT JOIN (
                    SELECT loan_id, SUM(payment_amount) as total_paid
                    FROM payments
                    GROUP BY loan_id
                ) p ON l.id = p.loan_id
                WHERE l.status = ?";
        $result = $this->db->single($sql, [self::$STATUS_ACTIVE]);
        $stats['total_outstanding'] = $result ? $result['total'] : 0;

        // Overdue loans count
        $overdueLoans = $this->getOverdueLoans();
        $stats['overdue_loans_count'] = count($overdueLoans);

        return $stats;
    }

    public function searchLoans($term) {
        $sql = "SELECT l.*, c.name as client_name, c.phone_number, c.email
                FROM {$this->table} l
                JOIN clients c ON l.client_id = c.id
                WHERE c.name LIKE ? OR c.phone_number LIKE ? OR c.email LIKE ?
                ORDER BY l.created_at DESC";

        $searchTerm = "%{$term}%";
        $params = [$searchTerm, $searchTerm, $searchTerm];

        return $this->db->resultSet($sql, $params);
    }

    public function getLoansByDateRange($startDate, $endDate) {
        $sql = "SELECT l.*, c.name as client_name, c.phone_number, c.email
                FROM {$this->table} l
                JOIN clients c ON l.client_id = c.id
                WHERE l.created_at BETWEEN ? AND ?
                ORDER BY l.created_at DESC";

        return $this->db->resultSet($sql, [$startDate, $endDate]);
    }

    public function getClientActiveLoan($clientId) {
        $sql = "SELECT * FROM {$this->table}
                WHERE client_id = ? AND status = ?
                ORDER BY created_at DESC LIMIT 1";

        return $this->db->single($sql, [$clientId, self::$STATUS_ACTIVE]);
    }

    public function canClientApplyForLoan($clientId) {
        // Check if client has any active loans
        $activeLoan = $this->getClientActiveLoan($clientId);
        if ($activeLoan) {
            $this->setLastError('Client already has an active loan.');
            return false;
        }

        // Check if client has any overdue loans
        $sql = "SELECT COUNT(*) as count FROM {$this->table}
                WHERE client_id = ? AND status = ?";
        $result = $this->db->single($sql, [$clientId, self::$STATUS_DEFAULTED]);
        if ($result && $result['count'] > 0) {
            $this->setLastError('Client has defaulted loans and cannot apply for new loans.');
            return false;
        }

        return true;
    }

    public function create($data) {
        // Validate required fields
        $requiredFields = ['client_id', 'loan_amount'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->setLastError("Field '{$field}' is required.");
                return false;
            }
        }

        // Check if client can apply for loan
        if (!$this->canClientApplyForLoan($data['client_id'])) {
            return false;
        }

        // Calculate loan details if not provided
        if (!isset($data['total_amount'])) {
            $calculations = $this->calculateLoanDetails(
                $data['loan_amount'],
                $data['interest_rate'] ?? 5.00,
                $data['loan_term_months'] ?? 4,
                $data['insurance_fee'] ?? 425.00
            );
            $data = array_merge($data, $calculations);
        }

        // Set default values
        $data['status'] = $data['status'] ?? self::$STATUS_APPLICATION;
        $data['application_date'] = $data['application_date'] ?? date('Y-m-d H:i:s');
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        return parent::create($data);
    }
}
