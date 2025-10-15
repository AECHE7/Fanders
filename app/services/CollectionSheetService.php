<?php
/**
 * CollectionSheetService - Handles collection sheet management for account officers
 */
require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/../models/CollectionSheetModel.php';
require_once __DIR__ . '/../models/PaymentModel.php';
require_once __DIR__ . '/../models/LoanModel.php';
require_once __DIR__ . '/../models/ClientModel.php';

class CollectionSheetService extends BaseService {
    private $collectionSheetModel;
    private $paymentModel;
    private $loanModel;
    private $clientModel;

    public function __construct() {
        parent::__construct();
        $this->collectionSheetModel = new CollectionSheetModel();
        $this->paymentModel = new PaymentModel();
        $this->loanModel = new LoanModel();
        $this->clientModel = new ClientModel();
    }

    /**
     * Create a new collection sheet for an account officer
     * @param int $accountOfficerId Account officer ID
     * @param string $collectionDate Date for collection
     * @param array $loanIds Array of loan IDs to include
     * @return bool|int Collection sheet ID or false
     */
    public function createCollectionSheet($accountOfficerId, $collectionDate, $loanIds = []) {
        // Validate account officer
        $officer = $this->clientModel->findById($accountOfficerId);
        if (!$officer || $officer['role'] !== 'account_officer') {
            $this->setErrorMessage('Invalid account officer.');
            return false;
        }

        // Check if collection sheet already exists for this officer and date
        $existing = $this->collectionSheetModel->getByOfficerAndDate($accountOfficerId, $collectionDate);
        if ($existing) {
            $this->setErrorMessage('Collection sheet already exists for this officer and date.');
            return false;
        }

        // Create collection sheet
        $sheetData = [
            'account_officer_id' => $accountOfficerId,
            'collection_date' => $collectionDate,
            'status' => 'draft',
            'total_expected' => 0.00,
            'total_collected' => 0.00,
            'created_by' => $_SESSION['user_id'] ?? null
        ];

        $sheetId = $this->collectionSheetModel->create($sheetData);

        if ($sheetId && !empty($loanIds)) {
            $this->addLoansToSheet($sheetId, $loanIds);
        }

        return $sheetId;
    }

    /**
     * Add loans to an existing collection sheet
     * @param int $sheetId Collection sheet ID
     * @param array $loanIds Array of loan IDs
     * @return bool Success status
     */
    public function addLoansToSheet($sheetId, $loanIds) {
        $sheet = $this->collectionSheetModel->findById($sheetId);
        if (!$sheet) {
            $this->setErrorMessage('Collection sheet not found.');
            return false;
        }

        if ($sheet['status'] !== 'draft') {
            $this->setErrorMessage('Cannot modify a non-draft collection sheet.');
            return false;
        }

        $success = true;
        foreach ($loanIds as $loanId) {
            $loan = $this->loanModel->findById($loanId);
            if (!$loan) {
                continue; // Skip invalid loans
            }

            // Calculate expected payment for this loan
            $expectedPayment = $this->calculateExpectedPayment($loanId);

            $entryData = [
                'collection_sheet_id' => $sheetId,
                'loan_id' => $loanId,
                'client_id' => $loan['client_id'],
                'expected_payment' => $expectedPayment,
                'collected_payment' => 0.00,
                'status' => 'pending'
            ];

            if (!$this->collectionSheetModel->addEntry($entryData)) {
                $success = false;
            }
        }

        // Update sheet totals
        $this->updateSheetTotals($sheetId);

        return $success;
    }

    /**
     * Record payment collection for a loan in the collection sheet
     * @param int $sheetId Collection sheet ID
     * @param int $loanId Loan ID
     * @param float $amount Amount collected
     * @param string $paymentMethod Payment method
     * @return bool Success status
     */
    public function recordPayment($sheetId, $loanId, $amount, $paymentMethod = 'cash') {
        $entry = $this->collectionSheetModel->getEntry($sheetId, $loanId);
        if (!$entry) {
            $this->setErrorMessage('Collection entry not found.');
            return false;
        }

        if ($entry['status'] === 'collected') {
            $this->setErrorMessage('Payment already recorded for this loan.');
            return false;
        }

        // Update the entry
        $updateData = [
            'collected_payment' => $amount,
            'payment_method' => $paymentMethod,
            'status' => 'collected',
            'collected_at' => date('Y-m-d H:i:s'),
            'collected_by' => $_SESSION['user_id'] ?? null
        ];

        $success = $this->collectionSheetModel->updateEntry($entry['id'], $updateData);

        if ($success) {
            // Update sheet totals
            $this->updateSheetTotals($sheetId);

            // Create payment record
            $this->createPaymentRecord($loanId, $amount, $paymentMethod, $sheetId);
        }

        return $success;
    }

    /**
     * Calculate expected payment for a loan
     * @param int $loanId Loan ID
     * @return float Expected payment amount
     */
    private function calculateExpectedPayment($loanId) {
        $loan = $this->loanModel->findById($loanId);
        if (!$loan) {
            return 0.00;
        }

        // For simplicity, return the weekly payment amount
        // In a real implementation, this would consider payment history
        return $loan['weekly_payment'];
    }

    /**
     * Update collection sheet totals
     * @param int $sheetId Collection sheet ID
     * @return void
     */
    private function updateSheetTotals($sheetId) {
        $entries = $this->collectionSheetModel->getEntries($sheetId);

        $totalExpected = 0.00;
        $totalCollected = 0.00;

        foreach ($entries as $entry) {
            $totalExpected += $entry['expected_payment'];
            $totalCollected += $entry['collected_payment'];
        }

        $this->collectionSheetModel->update($sheetId, [
            'total_expected' => $totalExpected,
            'total_collected' => $totalCollected
        ]);
    }

    /**
     * Submit collection sheet for approval
     * @param int $sheetId Collection sheet ID
     * @return bool Success status
     */
    public function submitForApproval($sheetId) {
        $sheet = $this->collectionSheetModel->findById($sheetId);
        if (!$sheet) {
            $this->setErrorMessage('Collection sheet not found.');
            return false;
        }

        if ($sheet['status'] !== 'draft') {
            $this->setErrorMessage('Only draft sheets can be submitted for approval.');
            return false;
        }

        return $this->collectionSheetModel->update($sheetId, [
            'status' => 'submitted',
            'submitted_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Approve collection sheet
     * @param int $sheetId Collection sheet ID
     * @param int $approvedBy User ID of approver
     * @return bool Success status
     */
    public function approveSheet($sheetId, $approvedBy) {
        $sheet = $this->collectionSheetModel->findById($sheetId);
        if (!$sheet) {
            $this->setErrorMessage('Collection sheet not found.');
            return false;
        }

        if ($sheet['status'] !== 'submitted') {
            $this->setErrorMessage('Only submitted sheets can be approved.');
            return false;
        }

        return $this->collectionSheetModel->update($sheetId, [
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get collection sheet summary
     * @param int $sheetId Collection sheet ID
     * @return array Sheet summary
     */
    public function getSheetSummary($sheetId) {
        $sheet = $this->collectionSheetModel->findById($sheetId);
        if (!$sheet) {
            return null;
        }

        $entries = $this->collectionSheetModel->getEntries($sheetId);

        // Get officer details
        $officer = $this->clientModel->findById($sheet['account_officer_id']);

        return [
            'sheet' => $sheet,
            'officer' => $officer,
            'entries' => $entries,
            'summary' => [
                'total_loans' => count($entries),
                'total_expected' => array_sum(array_column($entries, 'expected_payment')),
                'total_collected' => array_sum(array_column($entries, 'collected_payment')),
                'collection_rate' => count($entries) > 0 ?
                    (array_sum(array_column($entries, 'collected_payment')) / array_sum(array_column($entries, 'expected_payment'))) * 100 : 0
            ]
        ];
    }

    /**
     * Get collection sheets for an account officer
     * @param int $officerId Account officer ID
     * @param array $filters Optional filters
     * @return array Collection sheets
     */
    public function getOfficerSheets($officerId, $filters = []) {
        return $this->collectionSheetModel->getByOfficer($officerId, $filters);
    }

    /**
     * Get all collection sheets with optional filters
     * @param array $filters Optional filters (start_date, end_date, status, officer_id)
     * @return array Collection sheets
     */
    public function getAllSheets($filters = []) {
        $sql = "SELECT cs.*,
                u1.name as account_officer_name,
                u2.name as approved_by_name
                FROM {$this->collectionSheetModel->table} cs
                LEFT JOIN users u1 ON cs.account_officer_id = u1.id
                LEFT JOIN users u2 ON cs.approved_by = u2.id
                WHERE 1=1";

        $params = [];

        if (isset($filters['start_date']) && !empty($filters['start_date'])) {
            $sql .= " AND cs.collection_date >= ?";
            $params[] = $filters['start_date'];
        }

        if (isset($filters['end_date']) && !empty($filters['end_date'])) {
            $sql .= " AND cs.collection_date <= ?";
            $params[] = $filters['end_date'];
        }

        if (isset($filters['status']) && !empty($filters['status'])) {
            $sql .= " AND cs.status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['officer_id']) && !empty($filters['officer_id'])) {
            $sql .= " AND cs.account_officer_id = ?";
            $params[] = $filters['officer_id'];
        }

        $sql .= " ORDER BY cs.collection_date DESC, cs.created_at DESC";

        return $this->collectionSheetModel->db->resultSet($sql, $params);
    }

    /**
     * Generate collection sheet report
     * @param int $sheetId Collection sheet ID
     * @return array Report data
     */
    public function generateSheetReport($sheetId) {
        $summary = $this->getSheetSummary($sheetId);

        if (!$summary) {
            return null;
        }

        return [
            'title' => 'Collection Sheet Report',
            'sheet_id' => $sheetId,
            'generated_date' => date('Y-m-d H:i:s'),
            'officer_name' => $summary['officer']['name'] ?? 'Unknown',
            'collection_date' => $summary['sheet']['collection_date'],
            'status' => $summary['sheet']['status'],
            'entries' => $summary['entries'],
            'totals' => $summary['summary']
        ];
    }

    /**
     * Create payment record when payment is collected
     * @param int $loanId Loan ID
     * @param float $amount Payment amount
     * @param string $paymentMethod Payment method
     * @param int $sheetId Collection sheet ID
     * @return bool Success status
     */
    private function createPaymentRecord($loanId, $amount, $paymentMethod, $sheetId) {
        $loan = $this->loanModel->findById($loanId);
        if (!$loan) {
            return false;
        }

        $paymentData = [
            'loan_id' => $loanId,
            'client_id' => $loan['client_id'],
            'amount' => $amount,
            'payment_date' => date('Y-m-d'),
            'payment_method' => $paymentMethod,
            'payment_type' => 'collection',
            'collection_sheet_id' => $sheetId,
            'recorded_by' => $_SESSION['user_id'] ?? null
        ];

        return $this->paymentModel->create($paymentData);
    }

    /**
     * Get pending collection sheets for approval
     * @return array Pending sheets
     */
    public function getPendingApprovals() {
        return $this->collectionSheetModel->getByStatus('submitted');
    }

    /**
     * Validate collection sheet data
     * @param array $data Sheet data
     * @return bool True if valid
     */
    public function validateSheetData($data) {
        if (!isset($data['account_officer_id']) || empty($data['account_officer_id'])) {
            $this->setErrorMessage('Account officer is required.');
            return false;
        }

        if (!isset($data['collection_date']) || empty($data['collection_date'])) {
            $this->setErrorMessage('Collection date is required.');
            return false;
        }

        // Validate date format
        if (!strtotime($data['collection_date'])) {
            $this->setErrorMessage('Invalid collection date format.');
            return false;
        }

        // Check if date is not in the future
        if (strtotime($data['collection_date']) > time()) {
            $this->setErrorMessage('Collection date cannot be in the future.');
            return false;
        }

        return true;
    }
}
