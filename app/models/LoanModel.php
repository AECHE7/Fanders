<?php
/**
 * LoanModel - Handles loan operations
 * This model manages the data for the 'loans' table and provides query methods
 * to retrieve loan and related client data. Calculation logic is handled in LoanCalculationService.
 */
require_once __DIR__ . '/../core/BaseModel.php';

class LoanModel extends BaseModel {
    protected $table = 'loans';
    protected $primaryKey = 'id';
    
    // Updated to match the finalized 'loans' table schema
    protected $fillable = [
        'client_id', 'principal', 'interest_rate', 'term_weeks',
        'total_interest', 'insurance_fee', 'total_loan_amount',
        'status', 'application_date', 'approval_date', 'disbursement_date',
        'completion_date', 'created_at', 'updated_at'
    ];
    protected $hidden = [];

    // Status definitions (Matching the loan lifecycle states)
    public const STATUS_APPLICATION = 'Application'; // Loan is pending review
    public const STATUS_APPROVED = 'Approved';     // Loan is approved, pending disbursement
    public const STATUS_ACTIVE = 'Active';         // Loan is disbursed and payments are due
    public const STATUS_COMPLETED = 'Completed';   // Loan is fully paid
    public const STATUS_DEFAULTED = 'Defaulted';   // Loan has been marked as written off/defaulted

    /**
     * Retrieves a single loan record joined with basic client information.
     * @param int $id Loan ID.
     * @return array|false
     */
    public function getLoanWithClient($id) {
        $sql = "SELECT l.*, c.name as client_name, c.phone_number, c.email, c.status as client_status
                FROM {$this->table} l
                JOIN clients c ON l.client_id = c.id
                WHERE l.id = ?";

        return $this->db->single($sql, [$id]);
    }

    /**
     * Retrieves all loan records joined with basic client information.
     * @return array
     */
    public function getAllLoansWithClients() {
        $sql = "SELECT l.*, c.name as client_name, c.phone_number, c.email
                FROM {$this->table} l
                JOIN clients c ON l.client_id = c.id
                ORDER BY l.created_at DESC";

        return $this->db->resultSet($sql);
    }

    /**
     * Retrieves all loans with clients and pagination support.
     * @param int $limit Number of records per page
     * @param int $offset Number of records to skip
     * @param array $filters Additional filters
     * @return array
     */
    public function getAllLoansWithClientsPaginated($limit = 20, $offset = 0, $filters = []) {
        $sql = "SELECT l.*, c.name as client_name, c.phone_number, c.email
                FROM {$this->table} l
                JOIN clients c ON l.client_id = c.id";

        $conditions = [];
        $params = [];

        // Apply filters
        if (!empty($filters['status'])) {
            $conditions[] = "l.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $conditions[] = "(c.name LIKE ? OR c.phone_number LIKE ? OR c.email LIKE ? OR l.id = ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $filters['search'];
        }
        if (!empty($filters['client_id'])) {
            $conditions[] = "l.client_id = ?";
            $params[] = $filters['client_id'];
        }
        if (!empty($filters['date_from'])) {
            $conditions[] = "l.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = "l.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->resultSet($sql, $params);
    }

    /**
     * Get total count of loans with filters applied
     * @param array $filters Additional filters
     * @return int
     */
    public function getTotalLoansCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} l
                JOIN clients c ON l.client_id = c.id";

        $conditions = [];
        $params = [];

        // Apply same filters as getAllLoansWithClientsPaginated
        if (!empty($filters['status'])) {
            $conditions[] = "l.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $conditions[] = "(c.name LIKE ? OR c.phone_number LIKE ? OR c.email LIKE ? OR l.id = ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $filters['search'];
        }
        if (!empty($filters['client_id'])) {
            $conditions[] = "l.client_id = ?";
            $params[] = $filters['client_id'];
        }
        if (!empty($filters['date_from'])) {
            $conditions[] = "l.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = "l.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $result = $this->db->single($sql, $params);
        return (int)($result ? $result['total'] : 0);
    }

    /**
     * Retrieves all loans associated with a specific client.
     * @param int $clientId
     * @return array
     */
    public function getLoansByClient($clientId) {
        $sql = "SELECT l.*, c.name as client_name, c.phone_number, c.email
                FROM {$this->table} l
                JOIN clients c ON l.client_id = c.id
                WHERE l.client_id = ?
                ORDER BY l.created_at DESC";

        return $this->db->resultSet($sql, [$clientId]);
    }

    /**
     * Retrieves loans filtered by status, joined with client information.
     * @param string $status
     * @return array
     */
    public function getLoansByStatus($status) {
        $sql = "SELECT l.*, c.name as client_name, c.phone_number, c.email
                FROM {$this->table} l
                JOIN clients c ON l.client_id = c.id
                WHERE l.status = ?
                ORDER BY l.created_at DESC";

        return $this->db->resultSet($sql, [$status]);
    }

    public function getActiveLoans() {
        return $this->getLoansByStatus(self::STATUS_ACTIVE);
    }

    public function getAllActiveLoansWithClients() {
        $sql = "SELECT l.*, c.name as client_name, c.phone_number, c.email
                FROM {$this->table} l
                JOIN clients c ON l.client_id = c.id
                WHERE l.status = ?
                ORDER BY l.created_at DESC";

        return $this->db->resultSet($sql, [self::STATUS_ACTIVE]);
    }

    public function getPendingApplications() {
        return $this->getLoansByStatus(self::STATUS_APPLICATION);
    }

    public function getApprovedLoans() {
        return $this->getLoansByStatus(self::STATUS_APPROVED);
    }

    /**
     * Updates the loan status and sets the approval timestamp.
     * @param int $loanId
     * @return bool
     */
    public function approveLoan($loanId) {
        $data = [
            'status' => self::STATUS_APPROVED,
            'approval_date' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->update($loanId, $data);
    }

    /**
     * Updates the loan status to active and sets the disbursement timestamp.
     * @param int $loanId
     * @return bool
     */
    public function disburseLoan($loanId) {
        $data = [
            'status' => self::STATUS_ACTIVE,
            'disbursement_date' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->update($loanId, $data);
    }

    /**
     * Updates the loan status to completed and sets the completion timestamp.
     * @param int $loanId
     * @return bool
     */
    public function completeLoan($loanId) {
        $data = [
            'status' => self::STATUS_COMPLETED,
            'completion_date' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->update($loanId, $data);
    }
    
    /**
     * Checks if a client currently has an active loan.
     * @param int $clientId
     * @return array|false The active loan details, or false.
     */
    public function getClientActiveLoan($clientId) {
        $sql = "SELECT * FROM {$this->table}
                WHERE client_id = ? AND status = ?
                ORDER BY created_at DESC LIMIT 1";

        return $this->db->single($sql, [$clientId, self::STATUS_ACTIVE]);
    }

    /**
     * Checks if a client has any loans currently flagged as defaulted.
     * @param int $clientId
     * @return bool
     */
    public function hasClientDefaultedLoan($clientId) {
         $sql = "SELECT COUNT(*) as count FROM {$this->table}
                WHERE client_id = ? AND status = ?";
        $result = $this->db->single($sql, [$clientId, self::STATUS_DEFAULTED]);
        return $result && $result['count'] > 0;
    }
    
    // --- Loan Statistics & Summary ---

    /**
     * Retrieves payment summary data for a single loan.
     * NOTE: This assumes the 'payments' table fields match the old structure; we'll rely on LoanService to adjust this.
     * @param int $loanId
     * @return array|false
     */
    public function getLoanPaymentSummary($loanId) {
        $sql = "SELECT l.*,
                COUNT(p.id) as payments_made,
                SUM(p.amount) as total_paid,
                l.total_loan_amount - COALESCE(SUM(p.amount), 0) as outstanding_balance
                FROM {$this->table} l
                LEFT JOIN payments p ON l.id = p.loan_id
                WHERE l.id = ?
                GROUP BY l.id";

        return $this->db->single($sql, [$loanId]);
    }

    /**
     * Retrieves overall statistics for the loan portfolio.
     * @return array
     */
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

        // Total loan amount disbursed (Active or Completed)
        $sql = "SELECT SUM(principal) as total FROM {$this->table} WHERE status IN (?, ?)";
        $result = $this->db->single($sql, [self::STATUS_ACTIVE, self::STATUS_COMPLETED]);
        $stats['total_principal_disbursed'] = $result ? $result['total'] : 0;

        // Total outstanding amount (Active loans only)
        // NOTE: Outstanding calculation here is an estimate and should be refined in LoanService.
        $sql = "SELECT SUM(l.total_loan_amount - COALESCE(p.total_paid, 0)) as total_outstanding
                FROM {$this->table} l
                LEFT JOIN (
                    SELECT loan_id, SUM(amount) as total_paid
                    FROM payments
                    GROUP BY loan_id
                ) p ON l.id = p.loan_id
                WHERE l.status = ?";
        $result = $this->db->single($sql, [self::STATUS_ACTIVE]);
        $stats['total_outstanding'] = $result ? $result['total_outstanding'] : 0;
        
        // Overdue loans count (We will rely on LoanService for this complexity)
        $stats['overdue_loans_count'] = 0;

        return $stats;
    }
    
    // --- Loan Creation Helper ---
    
    /**
     * Overrides BaseModel create to add default logic.
     * NOTE: Calculation of amounts must happen in the Service layer before calling this method.
     * @param array $data
     * @return int|false
     */
    public function create($data) {
        // Set default values if not provided
        $data['status'] = $data['status'] ?? self::STATUS_APPLICATION;
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');
        
        // Call parent create method
        return parent::create($data);
    }
}
