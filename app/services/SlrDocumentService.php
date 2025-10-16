<?php
/**
 * SlrDocumentService - Handles Summary of Loan Release (SLR) document management (FR-007)
 * Role: Manages the lifecycle of the SLR document and ensures transactional integrity during final disbursement.
 */
require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/../models/SlrDocumentModel.php';
require_once __DIR__ . '/../models/LoanModel.php';
require_once __DIR__ . '/../models/ClientModel.php';

class SlrDocumentService extends BaseService {
    private $slrDocumentModel;
    private $loanModel;
    private $clientModel;

    public function __construct() {
        parent::__construct();
        $this->slrDocumentModel = new SlrDocumentModel();
        $this->loanModel = new LoanModel();
        $this->clientModel = new ClientModel();
        $this->setModel($this->slrDocumentModel);
    }
    
    /**
     * Creates an SLR document as DRAFT, linking it to an Approved Loan.
     * @param int $loanId Loan ID
     * @param array $disbursementData Disbursement details
     * @return bool|int SLR document ID or false
     */
    public function createSlrDocument($loanId, $disbursementData = []) {
        // 1. Validation: Loan must be approved
        $loan = $this->loanModel->findById($loanId);
        if (!$loan || $loan['status'] !== LoanModel::$STATUS_APPROVED) {
            $this->setErrorMessage('SLR document can only be created for approved loans.');
            return false;
        }
        
        // 2. Prepare data, ensuring the core disbursement amount is correct (Principal)
        $disbursementData['disbursement_amount'] = $loan['principal']; 
        $disbursementData['loan_id'] = $loanId;

        // 3. Create document (Model handles automatic SLR number generation and checks)
        return $this->slrDocumentModel->create($disbursementData);
    }
    
    /**
     * Approves a DRAFT SLR document.
     * @param int $slrId SLR document ID
     * @param int $approvedBy User ID who approved the document
     * @return bool Success status
     */
    public function approveSlrDocument($slrId, $approvedBy) {
        $slr = $this->slrDocumentModel->findById($slrId);
        if (!$slr || $slr['status'] !== SlrDocumentModel::$STATUS_DRAFT) {
            $this->setErrorMessage('SLR document not found or is not in DRAFT status.');
            return false;
        }

        $data = [
            'status' => SlrDocumentModel::$STATUS_APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => date('Y-m-d H:i:s'),
        ];

        return $this->slrDocumentModel->update($slrId, $data);
    }

    /**
     * Disburses the loan, transactionally activating both the SLR and the Loan.
     * This is the critical final step for activating the loan schedule.
     * @param int $slrId SLR document ID
     * @param array $disbursementDetails Final client confirmation details
     * @return bool Success status
     */
    public function completeDisbursementTransaction($slrId, $disbursementDetails = []) {
        $slr = $this->slrDocumentModel->findById($slrId);
        
        if (!$slr || $slr['status'] !== SlrDocumentModel::$STATUS_APPROVED) {
            $this->setErrorMessage('Disbursement can only proceed on an APPROVED SLR document.');
            return false;
        }

        // Use transactional wrapper to ensure both DB operations succeed or fail together
        return $this->transaction(function() use ($slrId, $disbursementDetails) {
            // 1. Attempt to update SLR status and finalize details (e.g., signatures)
            $success = $this->slrDocumentModel->completeDisbursement($slrId, 
                $disbursementDetails['client_present'] ?? true, 
                $disbursementDetails['witness_name'] ?? null
            );
            
            if (!$success) {
                // The error message is set inside the model's completeDisbursement method
                throw new Exception($this->slrDocumentModel->getLastError() ?: 'Failed to finalize SLR details.');
            }

            // 2. The Model's completeDisbursement method already calls LoanModel::disburseLoan internally,
            //    so if we reach here, both the SLR and the Loan status should be updated in the transaction.
            
            return true;
        });
    }
    
    /**
     * Cancels the SLR and its associated loan application (if loan status allows).
     */
    public function cancelSlrDocument($slrId, $reason = '') {
        $slr = $this->slrDocumentModel->findById($slrId);
        if (!$slr || $slr['status'] === SlrDocumentModel::$STATUS_DISBURSED) {
            $this->setErrorMessage('SLR not found or already disbursed/cancelled.');
            return false;
        }
        
        // Transactionally cancel both SLR and Loan
        return $this->transaction(function() use ($slr, $reason) {
            // 1. Cancel the loan (LoanService handles pre-cancellation checks)
            $loanService = new LoanService();
            $loanCancellationSuccess = $loanService->cancelLoan($slr['loan_id'], $reason);
            
            if (!$loanCancellationSuccess) {
                throw new Exception($loanService->getErrorMessage() ?: 'Loan could not be cancelled.');
            }
            
            // 2. Update SLR status to CANCELLED
            $slrUpdateSuccess = $this->slrDocumentModel->updateStatus($slr['id'], SlrDocumentModel::$STATUS_CANCELLED);
            
            if (!$slrUpdateSuccess) {
                throw new Exception('Failed to update SLR status to cancelled.');
            }
            
            return true;
        });
    }

    // --- Retrieval Methods ---

    public function getSlrDetails($slrId) {
         return $this->slrDocumentModel->getSlrWithDetails($slrId);
    }
    
    public function getPendingDisbursements() {
        // Fetch loans that are APPROVED but don't yet have a DISBURSED SLR.
        $sql = "SELECT l.id as loan_id, l.principal, l.client_name, l.total_loan_amount
                FROM loans l
                WHERE l.status = ?
                AND NOT EXISTS (
                    SELECT 1 FROM slr_documents s WHERE s.loan_id = l.id AND s.status IN (?, ?)
                )";
        
        return $this->db->resultSet($sql, [
            LoanModel::$STATUS_APPROVED, 
            SlrDocumentModel::$STATUS_DRAFT,
            SlrDocumentModel::$STATUS_APPROVED
        ]);
    }
    
    public function searchSlrDocuments($term) {
        return $this->slrDocumentModel->searchSlrs($term);
    }

    public function getSlrDocumentsByStatus($status) {
        return $this->slrDocumentModel->getSlrsByStatus($status);
    }
}