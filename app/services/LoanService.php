<?php
/**
 * LoanService - Handles loan-related operations for Fanders Microfinance
 */
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

    public function getActiveLoans($limit = 5) {
        return $this->loanModel->getActiveLoans($limit);
    }

    public function getAllActiveLoansWithClients() {
        return $this->loanModel->getAllActiveLoansWithClients();
    }

    public function applyForLoan($loanData, $appliedBy) {
        // Validate loan data
        if (!$this->validateLoanData($loanData)) {
            return false;
        }

        // Calculate loan details using LoanCalculationService
        $calculation = $this->loanCalculationService->calculateLoan($loanData['loan_amount']);
        if (!$calculation) {
            $this->setErrorMessage($this->loanCalculationService->getErrorMessage());
            return false;
        }

        // Prepare loan data
        $loanData['interest_rate'] = $calculation['interest_rate'];
        $loanData['loan_term_months'] = $calculation['loan_term_months'];
        $loanData['total_interest'] = $calculation['total_interest'];
        $loanData['insurance_fee'] = $calculation['insurance_fee'];
        $loanData['total_amount'] = $calculation['total_amount'];
        $loanData['weekly_payment'] = $calculation['weekly_payment'];
        $loanData['status'] = 'application';
        $loanData['application_date'] = date('Y-m-d H:i:s');
        $loanData['created_at'] = date('Y-m-d H:i:s');
        $loanData['updated_at'] = date('Y-m-d H:i:s');

        // Create loan application
        return $this->loanModel->create($loanData);
    }

    public function updateLoan($id, $loanData) {
        // Get existing loan
        $existingLoan = $this->loanModel->findById($id);

        if (!$existingLoan) {
            $this->setErrorMessage('Loan not found.');
            return false;
        }

        // Validate loan data
        if (!$this->validateLoanData($loanData, $id)) {
            return false;
        }

        // Set update timestamp
        $loanData['updated_at'] = date('Y-m-d H:i:s');

        // Update loan
        return $this->loanModel->update($id, $loanData);
    }

    public function approveLoan($id, $approvedBy) {
        $loan = $this->loanModel->findById($id);
        if (!$loan) {
            $this->setErrorMessage('Loan not found.');
            return false;
        }

        if ($loan['status'] !== 'application') {
            $this->setErrorMessage('Only loan applications can be approved.');
            return false;
        }

        $updateData = [
            'status' => 'approved',
            'approval_date' => date('Y-m-d H:i:s'),
            'approved_by' => $approvedBy,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->loanModel->update($id, $updateData);
    }

    public function disburseLoan($id, $disbursedBy) {
        $loan = $this->loanModel->findById($id);
        if (!$loan) {
            $this->setErrorMessage('Loan not found.');
            return false;
        }

        if ($loan['status'] !== 'approved') {
            $this->setErrorMessage('Only approved loans can be disbursed.');
            return false;
        }

        $updateData = [
            'status' => 'active',
            'disbursement_date' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->loanModel->update($id, $updateData);
    }

    public function completeLoan($id) {
        $loan = $this->loanModel->findById($id);
        if (!$loan) {
            $this->setErrorMessage('Loan not found.');
            return false;
        }

        $updateData = [
            'status' => 'completed',
            'completion_date' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->loanModel->update($id, $updateData);
    }

    public function getAllClients() {
        return $this->clientModel->getAll('name', 'ASC');
    }

    public function getAllClientsForSelect() {
        return $this->clientModel->getAllForSelect();
    }

    public function getRecentLoans($limit = 10) {
        return $this->loanModel->getRecentLoans($limit);
    }

    public function getLoansByStatus($status) {
        return $this->loanModel->getLoansByStatus($status);
    }

    public function getLoanStats() {
        $stats = [];

        // Total loans
        $sql = "SELECT COUNT(*) as count FROM {$this->loanModel->getTable()}";
        $result = $this->loanModel->query($sql);
        $stats['total_loans'] = $result ? $result[0]['count'] : 0;

        // Active loans
        $sql = "SELECT COUNT(*) as count FROM {$this->loanModel->getTable()} WHERE status = 'active'";
        $result = $this->loanModel->query($sql);
        $stats['active_loans'] = $result ? $result[0]['count'] : 0;

        // Total loan amount disbursed
        $sql = "SELECT SUM(loan_amount) as total FROM {$this->loanModel->getTable()} WHERE status IN ('active', 'completed')";
        $result = $this->loanModel->query($sql);
        $stats['total_disbursed'] = $result ? $result[0]['total'] : 0;

        // Loans this month
        $sql = "SELECT COUNT(*) as count FROM {$this->loanModel->getTable()} WHERE MONTH(application_date) = MONTH(CURDATE()) AND YEAR(application_date) = YEAR(CURDATE())";
        $result = $this->loanModel->query($sql);
        $stats['loans_this_month'] = $result ? $result[0]['count'] : 0;

        return $stats;
    }

    private function validateLoanData($loanData, $excludeId = null) {
        // Check required fields
        $requiredFields = ['client_id', 'loan_amount'];
        foreach ($requiredFields as $field) {
            if (!isset($loanData[$field]) || $loanData[$field] === '') {
                $this->setErrorMessage(ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                return false;
            }
        }

        // Validate client exists
        if (!$this->clientModel->findById($loanData['client_id'])) {
            $this->setErrorMessage('Selected client does not exist.');
            return false;
        }

        // Validate loan amount
        if (!is_numeric($loanData['loan_amount']) || $loanData['loan_amount'] <= 0) {
            $this->setErrorMessage('Loan amount must be a positive number.');
            return false;
        }

        // Use LoanCalculationService to validate amount
        if (!$this->loanCalculationService->validateLoanAmount($loanData['loan_amount'])) {
            $this->setErrorMessage($this->loanCalculationService->getErrorMessage());
            return false;
        }

        return true;
    }
}
