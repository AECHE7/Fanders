<?php
/**
 * CollectionSheetService - Scaffold for Collection Sheet processing per FR-006/UR-006/FR-007.
 * Responsibilities:
 *  - Draft and submission handling for Account Officers
 *  - Cashier review and posting, producing Payment entries
 *  - Cash Blotter integration for posted collections
 *  - Audit trail logging for submissions and postings
 */

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/../models/CollectionSheetModel.php';
require_once __DIR__ . '/../models/CollectionSheetItemModel.php';
require_once __DIR__ . '/../models/LoanModel.php';
require_once __DIR__ . '/PaymentService.php';

class CollectionSheetService extends BaseService {
    private $sheetModel;
    private $itemModel;
    private $loanModel;

    public function __construct() {
        parent::__construct();
        $this->sheetModel = new CollectionSheetModel();
        $this->itemModel = new CollectionSheetItemModel();
        $this->loanModel = new LoanModel();
    }

    /**
     * Create a draft collection sheet for an Account Officer on a given date.
     * @param int $officerId
     * @param string $date Y-m-d
     * @return array|false Newly created sheet data or false on error
     */
    public function createDraftSheet($officerId, $date) {
        // Look for existing draft for officer and date
        $existing = $this->sheetModel->getByOfficerAndDate($officerId, $date);
        if ($existing && in_array($existing['status'], ['draft','submitted'])) {
            return $existing;
        }

        $data = [
            'officer_id' => $officerId,
            'sheet_date' => $date,
            'status' => 'draft',
            'total_amount' => 0.00,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $newId = $this->sheetModel->create($data);
        if (!$newId) { $this->setErrorMessage('Failed to create draft sheet.'); return false; }
        return $this->sheetModel->findById($newId);
    }

    /**
     * Add an item (client/loan/amount) to a draft sheet.
     * @param int $sheetId
     * @param int $clientId
     * @param int $loanId
     * @param float $amount
     * @param string|null $notes
     * @return bool
     */
    public function addItem($sheetId, $clientId, $loanId, $amount, $notes = null) {
        // Basic validation
        if (!$this->validate([
            'sheet_id' => $sheetId,
            'client_id' => $clientId,
            'loan_id' => $loanId,
            'amount' => $amount
        ], [
            'sheet_id' => 'required|numeric|positive',
            'client_id' => 'required|numeric|positive',
            'loan_id' => 'required|numeric|positive',
            'amount' => 'required|numeric|positive'
        ])) { return false; }

        // Ensure loan belongs to client and is active
        $loan = $this->loanModel->findById($loanId);
        if (!$loan) { $this->setErrorMessage('Loan not found.'); return false; }
        if ((int)$loan['client_id'] !== (int)$clientId) {
            $this->setErrorMessage('Loan does not belong to the selected client.');
            return false;
        }
        if ($loan['status'] !== LoanModel::STATUS_ACTIVE) {
            $this->setErrorMessage('Loan is not active.'); return false;
        }

        // Insert item
        $id = $this->itemModel->create([
            'sheet_id' => $sheetId,
            'client_id' => $clientId,
            'loan_id' => $loanId,
            'amount' => $amount,
            'notes' => $notes,
            'status' => 'draft',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        if (!$id) { $this->setErrorMessage('Failed to add item.'); return false; }

        // Recalculate sheet total
        $this->sheetModel->recalcTotal($sheetId);
        return true;
    }

    /**
     * Submit a draft sheet for cashier review.
     * @param int $sheetId
     * @return bool
     */
    public function submitSheet($sheetId) {
        return $this->transaction(function() use ($sheetId) {
            $sheet = $this->sheetModel->findById($sheetId);
            if (!$sheet) { throw new Exception('Sheet not found.'); }
            if ($sheet['status'] !== 'draft') { throw new Exception('Only draft sheets can be submitted.'); }
            $this->itemModel->updateStatusBySheet($sheetId, 'submitted');
            if (!$this->sheetModel->updateStatus($sheetId, 'submitted')) {
                throw new Exception('Failed to submit sheet.');
            }
            return true;
        });
    }

    /**
     * List sheets with optional filters (role-aware: AO sees own).
     * @param array $filters
     * @return array
     */
    public function listSheets($filters = []) {
        return $this->sheetModel->listSheets($filters, $filters['limit'] ?? 20);
    }

    /**
     * Get a sheet with its items.
     * @param int $sheetId
     * @return array|false
     */
    public function getSheetDetails($sheetId) {
        $sheet = $this->sheetModel->findById($sheetId);
        if (!$sheet) { return false; }
        $items = $this->itemModel->getItemsBySheet($sheetId);
        return ['sheet' => $sheet, 'items' => $items];
    }

    /**
     * Cashier action: approve and post a submitted sheet.
     * Creates Payment entries, updates Cash Blotter, logs transactions.
     * @param int $sheetId
     * @param int $cashierUserId
     * @return bool
     */
    public function approveAndPost($sheetId, $cashierUserId) {
        $paymentService = new PaymentService();
        return $this->transaction(function() use ($sheetId, $cashierUserId, $paymentService) {
            $details = $this->getSheetDetails($sheetId);
            if (!$details) { throw new Exception('Sheet not found.'); }
            $sheet = $details['sheet'];
            if ($sheet['status'] !== 'submitted') {
                throw new Exception('Only submitted sheets can be posted.');
            }
            $items = $details['items'];
            foreach ($items as $item) {
                if ($item['status'] !== 'submitted') { continue; }
                $paymentId = $paymentService->recordPayment((int)$item['loan_id'], (float)$item['amount'], (int)$cashierUserId);
                if (!$paymentId) {
                    throw new Exception('Failed to record payment for loan #' . $item['loan_id'] . ': ' . $paymentService->getErrorMessage());
                }
                // Mark item as posted
                $this->itemModel->update($item['id'], [
                    'status' => 'posted',
                    'posted_at' => date('Y-m-d H:i:s'),
                    'posted_by' => $cashierUserId,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
            // Recalc and mark sheet posted
            $this->sheetModel->recalcTotal($sheetId);
            if (!$this->sheetModel->updateStatus($sheetId, 'posted')) {
                throw new Exception('Failed to update sheet status to posted.');
            }

            // Optional audit log
            if (class_exists('TransactionService')) {
                $ts = new TransactionService();
                $ts->logGeneric('collection_sheet_posted', $cashierUserId, $sheetId, [
                    'total_amount' => $details['sheet']['total_amount'] ?? null,
                    'posted_items' => count($items)
                ]);
            }

            return true;
        });
    }
}
