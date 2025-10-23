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

        $cache = new CacheUtility();
        return $cache->remember($cacheKey, 300, function() {
            return $this->loanModel->getLoanStats();
        });
    }

    /**
     * Get recent loans
     * @param int $limit Number of loans to retrieve
     * @return array
     */
    public function getRecentLoans($limit = 5) {
        return $this->loanModel->getRecentLoans($limit);
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
        $client = $this->clientModel->findById($clientId);
        if (!$client) {
            $this->setErrorMessage('Selected client does not exist.');
            return false;
        }
        
        // Check if client is active
        if ($client['status'] !== 'active') {
            $this->setErrorMessage('Client must have active status to apply for loans. Current status: ' . ucfirst($client['status']));
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
            error_log("Loan validation failed for client_id=$clientId, principal=$principal");
            return false;
        }

        // 2. Get term from loanData or use default
        $termWeeks = $loanData['term_weeks'] ?? null;

        // 2. Calculate loan details using LoanCalculationService
        $calculation = $this->loanCalculationService->calculateLoan($principal, $termWeeks);
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
             $lastError = $this->loanModel->getLastError() ?: 'Unknown error during loan creation';
             $this->setErrorMessage('Failed to save loan application: ' . $lastError);
             error_log("Loan creation failed: " . $lastError . " Data: " . json_encode($dataToCreate));
             return false;
        }

        // 5. Log transaction for audit trail
        if (class_exists('TransactionService')) {
            $transactionService = new TransactionService();
            // Correct parameter order: (action, acting_user_id, loan_id)
            $transactionService->logLoanTransaction('created', $userId, $newId, [
                'principal' => $principal,
                'client_id' => $clientId
            ]);
        }

        return $newId;
    }

    public function updateLoan($id, $loanData) {
        // Validation check for status changes is handled in specific methods (approve, disburse, complete)
        // General update is for application stage adjustments only.
        $result = $this->loanModel->update($id, $loanData);
        
        // Log loan update transaction
        if ($result && class_exists('TransactionService')) {
            $transactionService = new TransactionService();
            // Correct parameter order: (action, acting_user_id, loan_id)
            $transactionService->logLoanTransaction('updated', $_SESSION['user_id'] ?? null, $id, [
                'loan_id' => $id,
                'updated_fields' => array_keys($loanData)
            ]);
        }
        
        return $result;
    }

    /**
     * Moves a loan from 'Application' to 'Approved'.
     * Generates PDF loan agreement and SLR document upon approval.
     * @param int $id Loan ID.
     * @param int $approvedBy User ID who approved the loan.
     * @return bool
     */
    public function approveLoan($id, $approvedBy = null) {
        $loan = $this->loanModel->findById($id);
        if (!$loan) {
            $this->setErrorMessage('Loan not found.');
            return false;
        }

        if (strcasecmp($loan['status'], LoanModel::STATUS_APPLICATION) !== 0) {
            $this->setErrorMessage('Only loan applications can be approved.');
            return false;
        }

        // Get loan with client information
        $loanWithClient = $this->loanModel->getLoanWithClient($id);
        if (!$loanWithClient) {
            $this->setErrorMessage('Failed to retrieve loan details.');
            return false;
        }

        // Generate payment schedule for PDF
        $calculation = $this->loanCalculationService->calculateLoan($loanWithClient['principal'], $loanWithClient['term_weeks']);
        if (!$calculation) {
            $this->setErrorMessage('Failed to calculate loan details for agreement.');
            return false;
        }

        // Generate PDF agreement
        require_once __DIR__ . '/../utilities/PDFGenerator.php';
        $pdfGenerator = new PDFGenerator();
        $approvedByName = 'Manager'; // Default, could be enhanced to get actual user name

        if ($approvedBy) {
            // Try to get user name if UserService exists
            if (class_exists('UserService')) {
                $userService = new UserService();
                $user = $userService->getUserWithRoleName($approvedBy);
                if ($user) {
                    $approvedByName = $user['first_name'] . ' ' . $user['last_name'];
                }
            }
        }

        // Generate and save PDF to file (you might want to store the path in database)
        $pdfDir = BASE_PATH . '/storage/agreements/';
        if (!is_dir($pdfDir)) {
            mkdir($pdfDir, 0755, true);
        }

        // Create descriptive filename: ClientName_LoanID_ApprovalDate.pdf
        $clientName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $loanWithClient['client_name']); // Sanitize for filename
        $approvalDate = date('Y-m-d', strtotime($loanWithClient['approval_date'] ?? date('Y-m-d')));
        $pdfPath = $pdfDir . $clientName . '_Loan' . $id . '_' . $approvalDate . '.pdf';

        // Generate PDF and save to file directly
        $pdfGenerator->generateLoanAgreementToFile($loanWithClient, $calculation['payment_schedule'], $approvedByName, $pdfPath);

        // Perform core loan operations within transaction (status update and SLR generation)
        $approvalSuccess = $this->transaction(function() use ($id, $approvedBy) {
            // 1. Update loan status to approved
            if (!$this->loanModel->approveLoan($id)) {
                $this->setErrorMessage('Failed to approve loan.');
                return false;
            }

            // 2. Auto-generate SLR document on approval
            if (class_exists('SLRService')) {
                try {
                    require_once __DIR__ . '/SLRService.php';
                    $slrService = new SLRService();

                    // Check if auto-generation is enabled for loan approval
                    $sql = "SELECT auto_generate FROM slr_generation_rules
                            WHERE trigger_event = 'loan_approval' AND is_active = true LIMIT 1";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute();
                    $rule = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($rule && $rule['auto_generate']) {
                        // Auto-generate SLR on approval
                        $slrDocument = $slrService->generateSLR($id, $approvedBy ?? 1, 'loan_approval');

                        if ($slrDocument) {
                            error_log('SLR document auto-generated on loan approval for loan ID ' . $id . ': ' . $slrDocument['document_number']);
                        } else {
                            error_log('Failed to auto-generate SLR on loan approval for loan ID ' . $id . ': ' . $slrService->getErrorMessage());
                        }
                    } else {
                        error_log('SLR auto-generation disabled for loan approval - loan ID ' . $id . ' requires manual generation');
                    }
                } catch (Exception $e) {
                    // Log the error but don't fail the approval
                    error_log('Exception while generating SLR on loan approval for loan ID ' . $id . ': ' . $e->getMessage());
                }
            }

            return true;
        });

        // Log loan approval transaction
        if ($approvalSuccess && class_exists('TransactionService')) {
            $transactionService = new TransactionService();
            // Correct parameter order: (action, acting_user_id, loan_id)
            $transactionService->logLoanTransaction('approved', $approvedBy, $id, [
                'loan_id' => $id,
                'approved_by' => $approvedBy,
                'principal' => $loanWithClient['principal'] ?? 0,
                'term_weeks' => $loanWithClient['term_weeks'] ?? 0
            ]);
        }

        return $approvalSuccess;
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

        if (strcasecmp($loan['status'], LoanModel::STATUS_APPROVED) !== 0) {
            $this->setErrorMessage('Only approved loans can be disbursed.');
            return false;
        }

        // Perform core loan operations within transaction (status update only)
        $disbursementSuccess = $this->transaction(function() use ($id) {
            // 1. Update loan status
            return $this->loanModel->disburseLoan($id);
        });

        // 2. Add Transaction/Audit Log outside transaction to prevent rollback if logging fails
        if ($disbursementSuccess && class_exists('TransactionService')) {
            try {
                $transactionService = new TransactionService();
                $transactionService->logLoanTransaction('disbursed', $disbursedBy, $id, [
                    'disbursement_date' => date('Y-m-d H:i:s'),
                    'disbursed_by' => $disbursedBy,
                    'loan_amount' => $loan['total_loan_amount']
                ]);
            } catch (Exception $e) {
                // Log the error but don't fail the disbursement
                error_log('Failed to log disbursement transaction for loan ID ' . $id . ': ' . $e->getMessage());
            }
        }

        if (!$disbursementSuccess) {
            return false;
        }

        // 3. Update cash blotter for disbursement (inflow) - outside transaction to prevent rollback
        if (class_exists('CashBlotterService')) {
            try {
                $cashBlotterService = new CashBlotterService();
                $cashBlotterService->updateBlotterForDate(date('Y-m-d'));
            } catch (Exception $e) {
                // Log the error but don't fail the disbursement
                error_log('Failed to update cash blotter for loan disbursement (ID: ' . $id . '): ' . $e->getMessage());
                // You might want to set a flash message to inform the user about blotter update failure
            }
        }

        // 4. Generate SLR (Summary of Loan Release) document - FR-007, FR-008
        // Use the enhanced SLRService for automatic generation on disbursement
        if (class_exists('SLRService')) {
            try {
                require_once __DIR__ . '/SLRService.php';
                $slrService = new SLRService();
                
                // Check if auto-generation is enabled for disbursement
                $sql = "SELECT auto_generate FROM slr_generation_rules 
                        WHERE trigger_event = 'loan_disbursement' AND is_active = true LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $rule = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($rule && $rule['auto_generate']) {
                    // Auto-generate SLR on disbursement
                    $slrDocument = $slrService->generateSLR($id, $_SESSION['user_id'] ?? 1, 'loan_disbursement');
                    
                    if ($slrDocument) {
                        error_log('SLR document auto-generated on disbursement for loan ID ' . $id . ': ' . $slrDocument['document_number']);
                    } else {
                        error_log('Failed to auto-generate SLR on disbursement for loan ID ' . $id . ': ' . $slrService->getErrorMessage());
                    }
                } else {
                    error_log('SLR auto-generation disabled for disbursement - loan ID ' . $id . ' requires manual generation');
                }
            } catch (Exception $e) {
                // Log the error but don't fail the disbursement
                error_log('Exception while generating SLR for loan ID ' . $id . ': ' . $e->getMessage());
            }
        }

        // Legacy SLR generation (fallback for compatibility)
        if (class_exists('LoanReleaseService')) {
            try {
                require_once __DIR__ . '/LoanReleaseService.php';
                $loanReleaseService = new LoanReleaseService();
                
                // Generate and save SLR document to storage
                $slrPath = $loanReleaseService->generateAndSaveSLR($id);
                
                if ($slrPath) {
                    error_log('Legacy SLR document generated successfully for loan ID ' . $id . ': ' . $slrPath);
                } else {
                    error_log('Failed to generate legacy SLR document for loan ID ' . $id . ': ' . $loanReleaseService->getErrorMessage());
                }
            } catch (Exception $e) {
                // Log the error but don't fail the disbursement
                error_log('Exception while generating legacy SLR for loan ID ' . $id . ': ' . $e->getMessage());
            }
        }

        return true;
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
            // Correct parameter order: (action, acting_user_id, loan_id)
            $transactionService->logLoanTransaction('completed', $_SESSION['user_id'] ?? null, $id, [
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

        if (strcasecmp($loan['status'], LoanModel::STATUS_APPLICATION) !== 0) {
            $this->setErrorMessage('Only loan applications can be cancelled.');
            return false;
        }

        $result = $this->loanModel->update($id, ['status' => 'cancelled']);
        
        // Log loan cancellation transaction
        if ($result && class_exists('TransactionService')) {
            $transactionService = new TransactionService();
            // Correct parameter order: (action, acting_user_id, loan_id)
            $transactionService->logLoanTransaction('cancelled', $cancelledBy, $id, [
                'loan_id' => $id,
                'cancelled_by' => $cancelledBy,
                'principal' => $loan['principal'] ?? 0
            ]);
        }
        
        return $result;
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

        $result = $this->loanModel->update($id, ['status' => LoanModel::STATUS_APPLICATION]);
        
        // Log loan restoration transaction
        if ($result && class_exists('TransactionService')) {
            $transactionService = new TransactionService();
            // Correct parameter order: (action, acting_user_id, loan_id)
            $transactionService->logLoanTransaction('restored', $_SESSION['user_id'] ?? null, $id, [
                'loan_id' => $id,
                'restored_by' => $_SESSION['user_id'] ?? null,
                'principal' => $loan['principal'] ?? 0
            ]);
        }
        
        return $result;
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
