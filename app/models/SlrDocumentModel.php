<?php
/**
 * SlrDocumentModel - Handles Summary of Loan Release (SLR) documents
 */
require_once __DIR__ . '/../core/BaseModel.php';

class SlrDocumentModel extends BaseModel {
    protected $table = 'slr_documents';
    protected $primaryKey = 'id';
    protected $fillable = [
        'loan_id', 'slr_number', 'disbursement_amount', 'disbursement_date',
        'disbursed_by', 'approved_by', 'client_present', 'client_signature',
        'witness_name', 'witness_signature', 'notes', 'status',
        'created_at', 'updated_at'
    ];
    protected $hidden = [];

    // Status definitions
    public static $STATUS_DRAFT = 'draft';
    public static $STATUS_APPROVED = 'approved';
    public static $STATUS_DISBURSED = 'disbursed';
    public static $STATUS_CANCELLED = 'cancelled';

    public function getSlrWithDetails($id) {
        $sql = "SELECT s.*,
                l.loan_amount, l.total_amount, l.interest_rate, l.insurance_fee,
                c.name as client_name, c.phone_number, c.email, c.address,
                u1.name as disbursed_by_name,
                u2.name as approved_by_name
                FROM {$this->table} s
                JOIN loans l ON s.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                LEFT JOIN users u1 ON s.disbursed_by = u1.id
                LEFT JOIN users u2 ON s.approved_by = u2.id
                WHERE s.id = ?";

        return $this->db->single($sql, [$id]);
    }

    public function getSlrByLoan($loanId) {
        $sql = "SELECT s.*,
                u1.name as disbursed_by_name,
                u2.name as approved_by_name
                FROM {$this->table} s
                LEFT JOIN users u1 ON s.disbursed_by = u1.id
                LEFT JOIN users u2 ON s.approved_by = u2.id
                WHERE s.loan_id = ?
                ORDER BY s.created_at DESC";

        return $this->db->resultSet($sql, [$loanId]);
    }

    public function getSlrByNumber($slrNumber) {
        return $this->findOneByField('slr_number', $slrNumber);
    }

    public function getSlrsByStatus($status) {
        $sql = "SELECT s.*,
                l.loan_amount, l.total_amount,
                c.name as client_name, c.phone_number,
                u1.name as disbursed_by_name,
                u2.name as approved_by_name
                FROM {$this->table} s
                JOIN loans l ON s.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                LEFT JOIN users u1 ON s.disbursed_by = u1.id
                LEFT JOIN users u2 ON s.approved_by = u2.id
                WHERE s.status = ?
                ORDER BY s.created_at DESC";

        return $this->db->resultSet($sql, [$status]);
    }

    public function getSlrsByDateRange($startDate, $endDate) {
        $sql = "SELECT s.*,
                l.loan_amount, l.total_amount,
                c.name as client_name, c.phone_number,
                u1.name as disbursed_by_name,
                u2.name as approved_by_name
                FROM {$this->table} s
                JOIN loans l ON s.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                LEFT JOIN users u1 ON s.disbursed_by = u1.id
                LEFT JOIN users u2 ON s.approved_by = u2.id
                WHERE DATE(s.disbursement_date) BETWEEN ? AND ?
                ORDER BY s.disbursement_date DESC";

        return $this->db->resultSet($sql, [$startDate, $endDate]);
    }

    public function generateSlrNumber() {
        // Generate SLR number in format: SLR-YYYY-NNNN
        $year = date('Y');
        $prefix = "SLR-{$year}-";

        // Find the highest number for this year
        $sql = "SELECT slr_number FROM {$this->table}
                WHERE slr_number LIKE ?
                ORDER BY slr_number DESC LIMIT 1";

        $result = $this->db->single($sql, [$prefix . '%']);

        if ($result) {
            // Extract the number part and increment
            $lastNumber = intval(substr($result['slr_number'], -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function createSlrDocument($loanId, $disbursementAmount, $disbursedBy, $approvedBy = null) {
        // Get loan details
        $loanModel = new LoanModel();
        $loan = $loanModel->findById($loanId);

        if (!$loan) {
            $this->setLastError('Loan not found.');
            return false;
        }

        // Check if loan is approved
        if ($loan['status'] !== LoanModel::$STATUS_APPROVED) {
            $this->setLastError('Loan must be approved before creating SLR document.');
            return false;
        }

        // Check if SLR already exists for this loan
        $existing = $this->getSlrByLoan($loanId);
        if (!empty($existing)) {
            $this->setLastError('SLR document already exists for this loan.');
            return false;
        }

        $slrNumber = $this->generateSlrNumber();

        $data = [
            'loan_id' => $loanId,
            'slr_number' => $slrNumber,
            'disbursement_amount' => $disbursementAmount,
            'disbursement_date' => date('Y-m-d H:i:s'),
            'disbursed_by' => $disbursedBy,
            'approved_by' => $approvedBy,
            'client_present' => true, // Default to true
            'status' => self::$STATUS_DRAFT,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->create($data);
    }

    public function approveSlrDocument($slrId, $approvedBy) {
        $data = [
            'status' => self::$STATUS_APPROVED,
            'approved_by' => $approvedBy,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->update($slrId, $data);
    }

    public function disburseLoan($slrId, $clientPresent = true, $witnessName = null) {
        $slr = $this->findById($slrId);
        if (!$slr) {
            $this->setLastError('SLR document not found.');
            return false;
        }

        // Update SLR status
        $this->update($slrId, [
            'status' => self::$STATUS_DISBURSED,
            'client_present' => $clientPresent,
            'witness_name' => $witnessName,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Update loan status to active
        $loanModel = new LoanModel();
        $loanModel->disburseLoan($slr['loan_id']);

        return true;
    }

    public function cancelSlrDocument($slrId, $reason = null) {
        $data = [
            'status' => self::$STATUS_CANCELLED,
            'notes' => $reason,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->update($slrId, $data);
    }

    public function addClientSignature($slrId, $signaturePath) {
        return $this->update($slrId, [
            'client_signature' => $signaturePath,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function addWitnessSignature($slrId, $signaturePath, $witnessName) {
        return $this->update($slrId, [
            'witness_signature' => $signaturePath,
            'witness_name' => $witnessName,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getSlrStats($startDate = null, $endDate = null) {
        $sql = "SELECT
                COUNT(*) as total_slr,
                SUM(disbursement_amount) as total_disbursed,
                AVG(disbursement_amount) as average_disbursement
                FROM {$this->table}
                WHERE status = ?";

        $params = [self::$STATUS_DISBURSED];

        if ($startDate && $endDate) {
            $sql .= " AND DATE(disbursement_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        return $this->db->single($sql, $params);
    }

    public function getMonthlyDisbursements($year = null, $month = null) {
        $year = $year ?? date('Y');
        $month = $month ?? date('m');

        $sql = "SELECT
                DAY(disbursement_date) as day,
                SUM(disbursement_amount) as total_disbursed,
                COUNT(*) as loans_disbursed
                FROM {$this->table}
                WHERE YEAR(disbursement_date) = ?
                AND MONTH(disbursement_date) = ?
                AND status = ?
                GROUP BY DAY(disbursement_date)
                ORDER BY day";

        return $this->db->resultSet($sql, [$year, $month, self::$STATUS_DISBURSED]);
    }

    public function getDisbursementsByOfficer($officerId, $startDate = null, $endDate = null) {
        $sql = "SELECT s.*,
                l.loan_amount, l.total_amount,
                c.name as client_name, c.phone_number
                FROM {$this->table} s
                JOIN loans l ON s.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                WHERE s.disbursed_by = ? AND s.status = ?";

        $params = [$officerId, self::$STATUS_DISBURSED];

        if ($startDate && $endDate) {
            $sql .= " AND DATE(s.disbursement_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $sql .= " ORDER BY s.disbursement_date DESC";

        return $this->db->resultSet($sql, $params);
    }

    public function getPendingSlrs() {
        return $this->getSlrsByStatus(self::$STATUS_DRAFT);
    }

    public function getApprovedSlrs() {
        return $this->getSlrsByStatus(self::$STATUS_APPROVED);
    }

    public function searchSlrs($term) {
        $sql = "SELECT s.*,
                l.loan_amount, l.total_amount,
                c.name as client_name, c.phone_number,
                u1.name as disbursed_by_name,
                u2.name as approved_by_name
                FROM {$this->table} s
                JOIN loans l ON s.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                LEFT JOIN users u1 ON s.disbursed_by = u1.id
                LEFT JOIN users u2 ON s.approved_by = u2.id
                WHERE s.slr_number LIKE ?
                OR c.name LIKE ?
                OR c.phone_number LIKE ?
                ORDER BY s.created_at DESC";

        $searchTerm = "%{$term}%";
        $params = [$searchTerm, $searchTerm, $searchTerm];

        return $this->db->resultSet($sql, $params);
    }

    public function getSlrDocumentPath($slrId) {
        // This would typically return the path to the generated PDF document
        // For now, return a placeholder path
        return "documents/slr/SLR-{$slrId}.pdf";
    }

    public function create($data) {
        // Validate required fields
        $requiredFields = ['loan_id', 'disbursement_amount', 'disbursed_by'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $this->setLastError("Field '{$field}' is required.");
                return false;
            }
        }

        // Generate SLR number if not provided
        if (!isset($data['slr_number']) || empty($data['slr_number'])) {
            $data['slr_number'] = $this->generateSlrNumber();
        }

        // Check if SLR number already exists
        if ($this->getSlrByNumber($data['slr_number'])) {
            $this->setLastError('SLR number already exists.');
            return false;
        }

        // Check if SLR already exists for this loan
        $existing = $this->getSlrByLoan($data['loan_id']);
        if (!empty($existing)) {
            $this->setLastError('SLR document already exists for this loan.');
            return false;
        }

        // Set default values
        $data['disbursement_date'] = $data['disbursement_date'] ?? date('Y-m-d H:i:s');
        $data['client_present'] = $data['client_present'] ?? true;
        $data['status'] = $data['status'] ?? self::$STATUS_DRAFT;
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        return parent::create($data);
    }
}
