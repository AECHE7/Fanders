<?php
/**
 * SlrDocumentModel - Handles Summary of Loan Release (SLR) documents (FR-007)
 * Role: Tracks the documentation process for loan disbursement (from approved to active).
 */
require_once __DIR__ . '/../core/BaseModel.php';
require_once __DIR__ . '/../models/LoanModel.php'; // Required for disburseLoan function

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

    /**
     * Generates a unique SLR number in format: SLR-YYYY-NNNN.
     * @return string
     */
    public function generateSlrNumber() {
        // Generate SLR number in format: SLR-YYYY-NNNN
        $year = date('Y');
        $prefix = "SLR-{$year}-";

        // Find the highest number for this year
        $sql = "SELECT slr_number FROM {$this->table}
                WHERE slr_number LIKE ?
                ORDER BY slr_number DESC LIMIT 1";

        // Note: Using the LIKE '%prefix%' pattern is safe for simple auto-increment logic.
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
    
    /**
     * Overrides BaseModel create to enforce business rules and set default statuses.
     */
    public function create($data) {
        // Validation (simplified here, detailed in service)
        $requiredFields = ['loan_id', 'disbursement_amount', 'disbursed_by'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $this->setLastError("Field '{$field}' is required.");
                return false;
            }
        }

        // Generate SLR number if not provided
        if (empty($data['slr_number'])) {
            $data['slr_number'] = $this->generateSlrNumber();
        }
        
        // Ensure loan is approved before creating SLR (business rule)
        $loanModel = new LoanModel();
        $loan = $loanModel->findById($data['loan_id']);
        if (!$loan || $loan['status'] !== LoanModel::$STATUS_APPROVED) {
             $this->setLastError('Loan must be approved before generating SLR document.');
             return false;
        }

        // Check if SLR already exists for this loan
        $existingSlr = $this->getSlrByLoan($data['loan_id']);
        if (!empty($existingSlr)) {
            $this->setLastError('SLR document already exists for this loan.');
            return false;
        }

        // Set default values
        $data['disbursement_date'] = $data['disbursement_date'] ?? date('Y-m-d H:i:s');
        $data['client_present'] = $data['client_present'] ?? true;
        $data['status'] = $data['status'] ?? self::$STATUS_DRAFT;

        return parent::create($data);
    }

    /**
     * Completes the disbursement process: updates SLR status and activates the Loan.
     * Requires transactional integrity (handled in the service layer).
     */
    public function completeDisbursement($slrId, $clientPresent = true, $witnessName = null) {
        $slr = $this->findById($slrId);
        if (!$slr) {
            $this->setLastError('SLR document not found.');
            return false;
        }
        
        $loanModel = new LoanModel();

        // 1. Update SLR status
        $slrUpdateSuccess = $this->update($slrId, [
            'status' => self::$STATUS_DISBURSED,
            'client_present' => $clientPresent,
            'witness_name' => $witnessName,
            'disbursement_date' => date('Y-m-d H:i:s') // Set final disbursement date
        ]);

        if (!$slrUpdateSuccess) {
            $this->setLastError('Failed to update SLR status.');
            return false;
        }

        // 2. Update loan status to active (LoanModel handles the business logic)
        $loanDisburseSuccess = $loanModel->disburseLoan($slr['loan_id']);
        
        if (!$loanDisburseSuccess) {
            // NOTE: The service will handle the rollback if this fails
            $this->setLastError($loanModel->getLastError() ?: 'Failed to activate the associated loan.');
            return false;
        }

        return true;
    }
    
    // --- Data Retrieval Methods ---

    public function getSlrWithDetails($id) {
        $sql = "SELECT s.*,
                l.principal, l.total_loan_amount, l.interest_rate, l.insurance_fee,
                c.name as client_name, c.phone_number, c.email, c.address,
                u1.name as disbursed_by_name,
                u2.name as approved_by_name
                FROM {$this->table} s
                JOIN loans l ON s.loan_id = l.id
                JOIN clients c ON l.client_id = c.client_id
                LEFT JOIN users u1 ON s.disbursed_by = u1.id
                LEFT JOIN users u2 ON s.approved_by = u2.id
                WHERE s.id = ?";

        return $this->db->single($sql, [$id]);
    }
    
    public function getSlrByLoan($loanId) {
        // Simplified query to check existence
        return $this->findOneByField('loan_id', $loanId);
    }
    
    public function getPendingSlrs() {
        return $this->getSlrsByStatus(self::$STATUS_DRAFT);
    }
    
    public function searchSlrs($term) {
        $sql = "SELECT s.*,
                l.principal, l.total_loan_amount,
                c.name as client_name
                FROM {$this->table} s
                JOIN loans l ON s.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                WHERE s.slr_number LIKE ? OR c.name LIKE ?
                ORDER BY s.created_at DESC";

        $searchTerm = "%{$term}%";
        return $this->db->resultSet($sql, [$searchTerm, $searchTerm]);
    }
}