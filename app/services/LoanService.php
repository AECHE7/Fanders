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

    public function getLoanWithClient($id) {
        return $this->loanModel->getLoanWithClient($id);
    }

    public function getAllLoansWithClients() {
        return $this->loanModel->getAllLoansWithClients();
    }

    public function searchLoans($term) {
        return $this->loanModel->searchLoans($term);
    }

    public function getLoansByClient($clientId) {
        return $this->loanModel->getLoansByClient($clientId);
    }

    public function getActiveLoans() {
        return $this->loanModel->getActiveLoans();
    }

    public function getAllActiveLoansWithClients() {
        return $this->loanModel->getAllActiveLoansWithClients();
    }
    
    public function getLoanStats() {
        return $this->loanModel->getLoanStats();
    }

    /**
     * Creates a new loan application.
     * Enforces business rule: Client cannot have an active or defaulted loan.
     * @param array $loanData Must contain 'client_id' and 'principal'.
     * @return int|false New loan ID on success.
     */
    public function applyForLoan(array $loanData) {
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
        return $this->transaction(function() use ($id) {
            // 1. Update loan status
            $success = $this->loanModel->disburseLoan($id);
            
            // 2. Add Transaction/Audit Log (Phase 2/3 - Placeholder for now)
            // if ($success) { $this->transactionService->log('LOAN_DISBURSEMENT', $id, $disbursedBy); }
            
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
        return $this->loanModel->completeLoan($id);
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
