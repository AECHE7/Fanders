<?php
/**
 * LoanService - Handles loan-related operations for Fanders Microfinance.
 * This service manages the loan lifecycle, integrates financial calculations,
 * and enforces business rules like preventing concurrent loans.
 */
require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/../models/LoanModel.php';
require_once __DIR__ . '/../models/ClientModel.php';
require_once __DIR__ . '/LoanCalculationService.php';

class LoanService extends BaseService {
    private $loanModel;
    private $clientModel;
    private $loanCalculationService;

    public function __construct() {
        parent::__construct();
        $this->loanModel = new LoanModel();
        $this->clientModel = new ClientModel();
        $this->loanCalculationService = new LoanCalculationService();
        $this->setModel($this->loanModel);
    }

    /**
     * Enhanced method to get loan with client information
     * @param int $id Loan ID
     * @return array|false
     */
    public function getLoanWithClient($id) {
        return $this->loanModel->getLoanWithClient($id);
    }

    /**
     * Enhanced method to get all loans with clients and filtering support
     * @param array $filters Filter parameters
     * @return array
     */
    public function getAllLoansWithClients($filters = []) {
        require_once __DIR__ . '/../utilities/FilterUtility.php';
        
        // Validate and sanitize filters
        $filters = FilterUtility::sanitizeFilters($filters, [
            'allowed_statuses' => [
                LoanModel::STATUS_APPLICATION,
                LoanModel::STATUS_APPROVED,
                LoanModel::STATUS_ACTIVE,
                LoanModel::STATUS_COMPLETED,
                LoanModel::STATUS_DEFAULTED
            ]
        ]);
        
        $filters = FilterUtility::validateDateRange($filters);
        
        return $this->loanModel->getAllLoansWithClients($filters);
    }

    /**
     * Enhanced search loans with filtering
     * @param string $term Search term
     * @param array $additionalFilters Additional filters
     * @return array
     */
    public function searchLoans($term, $additionalFilters = []) {
        return $this->loanModel->searchLoans($term, $additionalFilters);
    }

    /**
     * Enhanced method to get loans by client with filtering
     * @param int $clientId Client ID
     * @param array $filters Additional filters
     * @return array
     */
    public function getLoansByClient($clientId, $filters = []) {
        $filters['client_id'] = $clientId;
        return $this->getAllLoansWithClients($filters);
    }

    /**
     * Enhanced method to get active loans with filtering
     * @param array $filters Filter parameters
     * @return array
     */
    public function getActiveLoans($filters = []) {
        return $this->loanModel->getActiveLoans($filters);
    }

    /**
     * Enhanced method to get all active loans with clients and filtering
     * @param array $filters Filter parameters
     * @return array
     */
    public function getAllActiveLoansWithClients($filters = []) {
        return $this->loanModel->getAllActiveLoansWithClients($filters);
    }

    /**
     * Enhanced method to get loans by status with filtering
     * @param string $status Loan status
     * @param array $filters Additional filters
     * @return array
     */
    public function getLoansByStatus($status, $filters = []) {
        return $this->loanModel->getLoansByStatus($status, $filters);
    }
    
    /**
     * Get total count of loans for pagination
     * @param array $filters Filter parameters
     * @return int
     */
    public function getTotalLoansCount($filters = []) {
        require_once __DIR__ . '/../utilities/FilterUtility.php';
        
        $filters = FilterUtility::sanitizeFilters($filters);
        $filters = FilterUtility::validateDateRange($filters);
        
        return $this->loanModel->getTotalLoansCount($filters);
    }

    /**
     * Get paginated loan data with metadata
     * @param array $filters Filter parameters
     * @return array
     */
    public function getPaginatedLoans($filters = []) {
        require_once __DIR__ . '/../utilities/FilterUtility.php';
        
        // Get total count first
        $totalCount = $this->getTotalLoansCount($filters);
        
        // Get paginated data
        $loans = $this->getAllLoansWithClients($filters);
        
        // Get pagination info
        $paginationInfo = FilterUtility::getPaginationInfo($filters, $totalCount);
        
        return [
            'data' => $loans,
            'pagination' => $paginationInfo,
            'filters' => $filters
        ];
    }
    /**
     * Get loan statistics with caching
     * @param bool $useCache Whether to use cache
     * @return array
     */
    public function getLoanStats($useCache = true) {
        if (!$useCache) {
            return $this->loanModel->getLoanStats();
        }

        require_once __DIR__ . '/../utilities/CacheUtility.php';
        
        $cacheKey = CacheUtility::generateKey('loan_stats');
        
        return CacheUtility::remember($cacheKey, function() {
            return $this->loanModel->getLoanStats();
        }, 300); // Cache for 5 minutes
    }

    /**
     * Invalidate loan-related cache entries
     */
    protected function invalidateCache() {
        require_once __DIR__ . '/../utilities/CacheUtility.php';
        
        // Invalidate loan statistics cache
        CacheUtility::forget(CacheUtility::generateKey('loan_stats'));
        
        // Clean expired entries
        CacheUtility::cleanExpired();
    }

    /**
     * Checks if a client is eligible to apply for a new loan.
     * @param int $clientId
     * @return bool True if eligible, false otherwise.
     */
    public function canClientApplyForLoan($clientId) {
        // Check if client exists
        if (!$this->clientModel->findById($clientId)) {
            $this->setErrorMessage('Selected client does not exist.');
            return false;
        }

        // Check for active loan
        if ($this->loanModel->getClientActiveLoan($clientId)) {
            $this->setErrorMessage('Client already has an active loan and cannot apply for another.');
            return false;
        }

        // Check for defaulted loan
        if ($this->loanModel->hasClientDefaultedLoan($clientId)) {
            $this->setErrorMessage('Client has defaulted loans and must settle their account before applying.');
            return false;
        }

        return true;
    }

    /**
     * Creates a new loan application.
     * Enforces business rule: Client cannot have an active or defaulted loan.
     * @param array $loanData Must contain 'client_id' and 'principal'.
     * @param int $userId User creating the application
     * @return int|false New loan ID on success.
     */
    public function applyForLoan(array $loanData, $userId) {
        $principal = $loanData['principal'] ?? null;
        $clientId = $loanData['client_id'] ?? null;

        // 1. Validate required fields and unique loan status
        if (!$this->validateLoanData(['client_id' => $clientId, 'principal' => $principal])) {
            return false;
        }

        // 2. Calculate loan details using LoanCalculationService
        $calculation = $this->loanCalculationService->calculateLoan($principal);
        if (!$calculation) {
            $this->setErrorMessage($this->loanCalculationService->getErrorMessage());
            return false;
        }

        // 3. Map calculation results to fillable fields for LoanModel::create()
        $dataToCreate = [
            'client_id' => $clientId,
            'principal' => $calculation['principal'],
            'interest_rate' => $calculation['interest_rate'],
            'term_weeks' => $calculation['term_weeks'],
            'total_interest' => $calculation['total_interest'],
            'insurance_fee' => $calculation['insurance_fee'],
            'total_loan_amount' => $calculation['total_loan_amount'],
            'status' => LoanModel::STATUS_APPLICATION,
            'application_date' => date('Y-m-d H:i:s'),
        ];

        // 4. Create loan application
        $newId = $this->loanModel->create($dataToCreate);

        if (!$newId) {
             $this->setErrorMessage($this->loanModel->getLastError() ?: 'Failed to save loan application.');
             return false;
        }

        // 5. Log transaction for audit trail
        if (class_exists('TransactionService')) {
            $transactionService = new TransactionService();
            $transactionService->logLoanTransaction('created', $newId, $userId, [
                'principal' => $principal,
                'client_id' => $clientId
            ]);
        }

        return $newId;
    }

    public function updateLoan($id, $loanData) {
        // Validation check for status changes is handled in specific methods (approve, disburse, complete)
        // General update is for application stage adjustments only.
        return $this->loanModel->update($id, $loanData);
    }

    /**
     * Moves a loan from 'Application' to 'Approved'.
     * @param int $id Loan ID.
     * @return bool
     */
    public function approveLoan($id) {
        $loan = $this->loanModel->findById($id);
        if (!$loan) {
            $this->setErrorMessage('Loan not found.');
            return false;
        }

        if ($loan['status'] !== LoanModel::STATUS_APPLICATION) {
            $this->setErrorMessage('Only loan applications can be approved.');
            return false;
        }

        return $this->loanModel->approveLoan($id);
    }

    /**
     * Moves a loan from 'Approved' to 'Active' (Fund Disbursement).
     * @param int $id Loan ID.
     * @param int $disbursedBy The user ID of the staff member disbursing the fund.
     * @return bool
     */
    public function disburseLoan($id, $disbursedBy) {
        $loan = $this->loanModel->findById($id);
        if (!$loan) {
            $this->setErrorMessage('Loan not found.');
            return false;
        }

        if ($loan['status'] !== LoanModel::STATUS_APPROVED) {
            $this->setErrorMessage('Only approved loans can be disbursed.');
            return false;
        }

        // This is a crucial financial transaction, so we use the transactional wrapper
        return $this->transaction(function() use ($id, $disbursedBy) {
            // 1. Update loan status
            $success = $this->loanModel->disburseLoan($id);

            // 2. Add Transaction/Audit Log
            if ($success && class_exists('TransactionService')) {
                $transactionService = new TransactionService();
                $transactionService->logLoanTransaction('disbursed', $id, $disbursedBy, [
                    'disbursement_date' => date('Y-m-d H:i:s'),
                    'disbursed_by' => $disbursedBy,
                    'loan_amount' => $loan['total_loan_amount']
                ]);
            }

            // 3. Update cash blotter for disbursement (inflow)
            if ($success && class_exists('CashBlotterService')) {
                $cashBlotterService = new CashBlotterService();
                $cashBlotterService->updateBlotterForDate(date('Y-m-d'));
            }

            return $success;
        });
    }

    /**
     * Finalizes a loan, moving status to 'Completed'.
     * This should only be called by PaymentService when the outstanding balance is zero.
     * @param int $id Loan ID.
     * @return bool
     */
    public function completeLoan($id) {
        $success = $this->loanModel->completeLoan($id);

        // Log transaction for audit trail
        if ($success && class_exists('TransactionService')) {
            $transactionService = new TransactionService();
            $transactionService->logLoanTransaction('completed', $id, null, [
                'completion_date' => date('Y-m-d H:i:s')
            ]);
        }

        return $success;
    }

    /**
     * Cancels a loan application (moves to 'cancelled' status).
     * Only loan applications can be cancelled.
     * @param int $id Loan ID.
     * @param int $cancelledBy User ID who cancelled the loan.
     * @return bool
     */
    public function cancelLoan($id, $cancelledBy) {
        $loan = $this->loanModel->findById($id);
        if (!$loan) {
            $this->setErrorMessage('Loan not found.');
            return false;
        }

        if ($loan['status'] !== LoanModel::STATUS_APPLICATION) {
            $this->setErrorMessage('Only loan applications can be cancelled.');
            return false;
        }

        return $this->loanModel->update($id, ['status' => 'cancelled']);
    }

    /**
     * Restores a cancelled loan application back to application status.
     * @param int $id Loan ID.
     * @return bool
     */
    public function restoreLoan($id) {
        $loan = $this->loanModel->findById($id);
        if (!$loan) {
            $this->setErrorMessage('Loan not found.');
            return false;
        }

        if ($loan['status'] !== 'cancelled') {
            $this->setErrorMessage('Only cancelled loan applications can be restored.');
            return false;
        }

        return $this->loanModel->update($id, ['status' => LoanModel::STATUS_APPLICATION]);
    }

    /**
     * Validates client and loan amount before creating a loan application.
     * @param array $loanData Must contain 'client_id' and 'principal'.
     * @param int $excludeId Not used here, maintained for consistency.
     * @return bool True if validation passes.
     */
    private function validateLoanData(array $loanData, $excludeId = null) {
        $clientId = $loanData['client_id'] ?? null;
        $principal = $loanData['principal'] ?? null;

        // Use BaseService validation for basic requirements
        if (!$this->validate(['client_id' => $clientId, 'principal' => $principal], [
            'client_id' => 'required|numeric',
            'principal' => 'required|numeric|positive'
        ])) {
            return false;
        }

        // Check if client exists
        if (!$this->clientModel->findById($clientId)) {
            $this->setErrorMessage('Selected client does not exist.');
            return false;
        }

        // --- CORE BUSINESS RULE ENFORCEMENT ---

        // 1. Check for active loan
        if ($this->loanModel->getClientActiveLoan($clientId)) {
            $this->setErrorMessage('Client already has an active loan and cannot apply for another.');
            return false;
        }

        // 2. Check for defaulted loan
        if ($this->loanModel->hasClientDefaultedLoan($clientId)) {
            $this->setErrorMessage('Client has defaulted loans and must settle their account before applying.');
            return false;
        }

        // 3. Validate loan amount against business range rules
        if (!$this->loanCalculationService->validateLoanAmount($principal)) {
            $this->setErrorMessage($this->loanCalculationService->getErrorMessage());
            return false;
        }

        return true;
    }
}
