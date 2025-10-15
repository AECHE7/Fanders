<?php
/**
 * SlrDocumentService - Handles Summary of Loan Release (SLR) document management
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
    }

    /**
     * Create SLR document for a loan disbursement
     * @param int $loanId Loan ID
     * @param array $disbursementData Disbursement details
     * @return bool|int SLR document ID or false
     */
    public function createSlrDocument($loanId, $disbursementData = []) {
        // Validate loan
        $loan = $this->loanModel->findById($loanId);
        if (!$loan) {
            $this->setErrorMessage('Loan not found.');
            return false;
        }

        // Check if loan is approved
        if ($loan['status'] !== 'approved') {
            $this->setErrorMessage('SLR document can only be created for approved loans.');
            return false;
        }

        // Check if SLR already exists for this loan
        $existing = $this->slrDocumentModel->getByLoanId($loanId);
        if ($existing) {
            $this->setErrorMessage('SLR document already exists for this loan.');
            return false;
        }

        // Get client details
        $client = $this->clientModel->findById($loan['client_id']);
        if (!$client) {
            $this->setErrorMessage('Client not found.');
            return false;
        }

        // Prepare SLR data
        $slrData = array_merge([
            'loan_id' => $loanId,
            'client_id' => $loan['client_id'],
            'loan_amount' => $loan['loan_amount'],
            'disbursement_date' => date('Y-m-d'),
            'disbursement_amount' => $loan['loan_amount'],
            'processing_fee' => 0.00, // Can be adjusted based on requirements
            'insurance_fee' => $loan['insurance_fee'],
            'net_disbursement' => $loan['loan_amount'] - $loan['insurance_fee'], // Amount actually given to client
            'disbursed_by' => $_SESSION['user_id'] ?? null,
            'status' => 'draft'
        ], $disbursementData);

        return $this->slrDocumentModel->create($slrData);
    }

    /**
     * Update SLR document
     * @param int $slrId SLR document ID
     * @param array $updateData Update data
     * @return bool Success status
     */
    public function updateSlrDocument($slrId, $updateData) {
        $slr = $this->slrDocumentModel->findById($slrId);
        if (!$slr) {
            $this->setErrorMessage('SLR document not found.');
            return false;
        }

        // Prevent updates if already disbursed
        if ($slr['status'] === 'disbursed') {
            $this->setErrorMessage('Cannot update a disbursed SLR document.');
            return false;
        }

        return $this->slrDocumentModel->update($slrId, $updateData);
    }

    /**
     * Mark SLR document as disbursed
     * @param int $slrId SLR document ID
     * @param array $disbursementDetails Additional disbursement details
     * @return bool Success status
     */
    public function markAsDisbursed($slrId, $disbursementDetails = []) {
        $slr = $this->slrDocumentModel->findById($slrId);
        if (!$slr) {
            $this->setErrorMessage('SLR document not found.');
            return false;
        }

        if ($slr['status'] === 'disbursed') {
            $this->setErrorMessage('SLR document is already marked as disbursed.');
            return false;
        }

        $updateData = array_merge([
            'status' => 'disbursed',
            'disbursement_date' => date('Y-m-d H:i:s'),
            'disbursed_by' => $_SESSION['user_id'] ?? null
        ], $disbursementDetails);

        $success = $this->slrDocumentModel->update($slrId, $updateData);

        if ($success) {
            // Update loan status to disbursed
            $this->loanModel->update($slr['loan_id'], [
                'status' => 'disbursed',
                'disbursement_date' => date('Y-m-d')
            ]);
        }

        return $success;
    }

    /**
     * Get SLR document details with related data
     * @param int $slrId SLR document ID
     * @return array|null SLR document details
     */
    public function getSlrDetails($slrId) {
        $slr = $this->slrDocumentModel->findById($slrId);
        if (!$slr) {
            return null;
        }

        // Get loan details
        $loan = $this->loanModel->findById($slr['loan_id']);

        // Get client details
        $client = $this->clientModel->findById($slr['client_id']);

        // Get disbursed by user details
        $disbursedBy = null;
        if ($slr['disbursed_by']) {
            $disbursedBy = $this->clientModel->findById($slr['disbursed_by']);
        }

        return [
            'slr' => $slr,
            'loan' => $loan,
            'client' => $client,
            'disbursed_by' => $disbursedBy
        ];
    }

    /**
     * Generate SLR document number
     * @param int $loanId Loan ID
     * @return string SLR document number
     */
    public function generateSlrNumber($loanId) {
        $date = date('Ymd');
        return 'SLR-' . $date . '-' . str_pad($loanId, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get SLR documents by status
     * @param string $status Status filter
     * @return array SLR documents
     */
    public function getSlrDocumentsByStatus($status) {
        return $this->slrDocumentModel->getByStatus($status);
    }

    /**
     * Get SLR documents for a client
     * @param int $clientId Client ID
     * @return array SLR documents
     */
    public function getClientSlrDocuments($clientId) {
        return $this->slrDocumentModel->getByClientId($clientId);
    }

    /**
     * Calculate disbursement summary
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Summary data
     */
    public function getDisbursementSummary($startDate, $endDate) {
        $slrs = $this->slrDocumentModel->getByDateRange($startDate, $endDate);

        $summary = [
            'total_disbursements' => 0,
            'total_amount' => 0.00,
            'total_insurance' => 0.00,
            'total_net_disbursement' => 0.00,
            'disbursements_count' => count($slrs)
        ];

        foreach ($slrs as $slr) {
            if ($slr['status'] === 'disbursed') {
                $summary['total_disbursements']++;
                $summary['total_amount'] += $slr['loan_amount'];
                $summary['total_insurance'] += $slr['insurance_fee'];
                $summary['total_net_disbursement'] += $slr['net_disbursement'];
            }
        }

        return $summary;
    }

    /**
     * Validate SLR document data
     * @param array $data SLR data
     * @return bool True if valid
     */
    public function validateSlrData($data) {
        $requiredFields = ['loan_id', 'client_id', 'loan_amount', 'disbursement_amount'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $this->setErrorMessage("{$field} is required.");
                return false;
            }
        }

        // Validate amounts are numeric and positive
        $amountFields = ['loan_amount', 'disbursement_amount', 'processing_fee', 'insurance_fee', 'net_disbursement'];
        foreach ($amountFields as $field) {
            if (isset($data[$field]) && (!is_numeric($data[$field]) || $data[$field] < 0)) {
                $this->setErrorMessage("{$field} must be a valid positive number.");
                return false;
            }
        }

        // Validate disbursement date if provided
        if (isset($data['disbursement_date']) && !empty($data['disbursement_date'])) {
            if (!strtotime($data['disbursement_date'])) {
                $this->setErrorMessage('Invalid disbursement date format.');
                return false;
            }
        }

        return true;
    }

    /**
     * Generate SLR document PDF
     * @param int $slrId SLR document ID
     * @return string PDF content or false on failure
     */
    public function generateSlrPdf($slrId) {
        $details = $this->getSlrDetails($slrId);
        if (!$details) {
            $this->setErrorMessage('SLR document not found.');
            return false;
        }

        // This would integrate with a PDF generation library
        // For now, return structured data that can be used by a PDF generator
        return [
            'document_type' => 'SLR',
            'document_number' => $this->generateSlrNumber($details['slr']['loan_id']),
            'client_name' => $details['client']['name'],
            'client_address' => $details['client']['address'] ?? 'N/A',
            'loan_amount' => '₱' . number_format($details['slr']['loan_amount'], 2),
            'disbursement_date' => date('F d, Y', strtotime($details['slr']['disbursement_date'])),
            'disbursement_amount' => '₱' . number_format($details['slr']['disbursement_amount'], 2),
            'insurance_fee' => '₱' . number_format($details['slr']['insurance_fee'], 2),
            'net_disbursement' => '₱' . number_format($details['slr']['net_disbursement'], 2),
            'disbursed_by' => $details['disbursed_by']['name'] ?? 'N/A',
            'generated_date' => date('F d, Y H:i:s')
        ];
    }

    /**
     * Cancel SLR document
     * @param int $slrId SLR document ID
     * @param string $reason Cancellation reason
     * @return bool Success status
     */
    public function cancelSlrDocument($slrId, $reason = '') {
        $slr = $this->slrDocumentModel->findById($slrId);
        if (!$slr) {
            $this->setErrorMessage('SLR document not found.');
            return false;
        }

        if ($slr['status'] === 'disbursed') {
            $this->setErrorMessage('Cannot cancel a disbursed SLR document.');
            return false;
        }

        if ($slr['status'] === 'cancelled') {
            $this->setErrorMessage('SLR document is already cancelled.');
            return false;
        }

        return $this->slrDocumentModel->update($slrId, [
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancelled_by' => $_SESSION['user_id'] ?? null
        ]);
    }

    /**
     * Get pending disbursements
     * @return array Pending SLR documents
     */
    public function getPendingDisbursements() {
        return $this->slrDocumentModel->getByStatus('draft');
    }

    /**
     * Search SLR documents
     * @param array $filters Search filters
     * @return array SLR documents
     */
    public function searchSlrDocuments($filters = []) {
        return $this->slrDocumentModel->search($filters);
    }
}
