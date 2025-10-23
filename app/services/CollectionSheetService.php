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
        
        // Log collection sheet creation
        if (class_exists('TransactionService')) {
            $ts = new TransactionService();
            $ts->logGeneric('collection_sheet_created', $officerId, $newId, [
                'sheet_id' => $newId,
                'officer_id' => $officerId,
                'sheet_date' => $date
            ]);
        }
        
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

        // Log collection sheet item addition
        if (class_exists('TransactionService')) {
            $sheet = $this->sheetModel->findById($sheetId);
            $ts = new TransactionService();
            $ts->logGeneric('collection_sheet_item_added', $sheet['officer_id'] ?? null, $sheetId, [
                'sheet_id' => $sheetId,
                'item_id' => $id,
                'client_id' => $clientId,
                'loan_id' => $loanId,
                'amount' => $amount
            ]);
        }

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
            
            // Log collection sheet submission
            if (class_exists('TransactionService')) {
                $ts = new TransactionService();
                $ts->logGeneric('collection_sheet_submitted', $sheet['officer_id'] ?? null, $sheetId, [
                    'sheet_id' => $sheetId,
                    'officer_id' => $sheet['officer_id'] ?? null,
                    'total_amount' => $sheet['total_amount'] ?? 0
                ]);
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

    /**
     * Quick add loan to an existing draft collection sheet for an Account Officer
     * @param int $userId Account Officer user ID
     * @param int $loanId Loan ID to add
     * @param float|null $amount Payment amount (defaults to weekly payment)
     * @param string $notes Optional notes
     * @return array|false Sheet info with item added or false on failure
     */
    public function quickAddLoanToSheet($userId, $loanId, $amount = null, $notes = '') {
        // Get or create today's draft sheet for this AO
        $sheetDate = date('Y-m-d');
        $draft = $this->sheetModel->findTodaysDraft($userId, $sheetDate);
        
        if (!$draft) {
            $draft = $this->createDraftSheet($userId, $sheetDate);
            if (!$draft) {
                $this->setErrorMessage('Failed to create collection sheet.');
                return false;
            }
        }

        // Get loan details
        $loan = $this->loanModel->getLoanWithClient($loanId);
        if (!$loan) {
            $this->setErrorMessage('Loan not found.');
            return false;
        }

        // Default amount to weekly payment if not provided
        if ($amount === null) {
            $amount = $loan['total_loan_amount'] / ($loan['term_weeks'] ?? 17); // Weekly payment
        }

        // Add item to sheet
        $success = $this->addItem($draft['id'], $loan['client_id'], $loanId, $amount, $notes);
        if (!$success) {
            return false;
        }

        // Return updated sheet details
        return $this->getSheetDetails($draft['id']);
    }

    /**
     * Get Account Officer's current draft sheet (today)
     * @param int $userId Account Officer user ID
     * @return array|false Current draft sheet or false if none exists
     */
    public function getCurrentDraftSheet($userId) {
        $sheetDate = date('Y-m-d');
        return $this->sheetModel->findTodaysDraft($userId, $sheetDate);
    }

    /**
     * Get loan eligibility for collection sheets
     * @param int $loanId
     * @return array|false Loan details if eligible, false if not
     */
    public function getLoanCollectionEligibility($loanId) {
        $loan = $this->loanModel->getLoanWithClient($loanId);
        if (!$loan) {
            return false;
        }

        // Only active loans can be collected
        if ($loan['status'] !== LoanModel::STATUS_ACTIVE) {
            return false;
        }

        // Add calculated weekly payment amount
        $loan['weekly_payment'] = $loan['total_loan_amount'] / ($loan['term_weeks'] ?? 17);
        $loan['collection_eligible'] = true;

        return $loan;
    }

    /**
     * Approve a submitted collection sheet (Cashier workflow)
     * @param int $sheetId
     * @param int $approverUserId
     * @return bool
     */
    public function approveSheet($sheetId, $approverUserId) {
        return $this->transaction(function() use ($sheetId, $approverUserId) {
            $sheet = $this->sheetModel->findById($sheetId);
            if (!$sheet) {
                throw new Exception('Collection sheet not found.');
            }
            
            if ($sheet['status'] !== 'submitted') {
                throw new Exception('Only submitted sheets can be approved.');
            }

            // Update sheet status to approved
            $updateData = [
                'status' => 'approved',
                'approved_by' => $approverUserId,
                'approved_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (!$this->sheetModel->update($sheetId, $updateData)) {
                throw new Exception('Failed to approve collection sheet.');
            }

            // Update all items to approved status
            $this->itemModel->updateStatusBySheet($sheetId, 'approved');
            
            // Log collection sheet approval
            if (class_exists('TransactionService')) {
                $ts = new TransactionService();
                $ts->logGeneric('collection_sheet_approved', $approverUserId, $sheetId, [
                    'sheet_id' => $sheetId,
                    'approved_by' => $approverUserId,
                    'total_amount' => $sheet['total_amount'] ?? 0
                ]);
            }

            return true;
        });
    }

    /**
     * Reject a submitted collection sheet and return to draft (Cashier workflow)
     * @param int $sheetId
     * @param int $rejectorUserId
     * @param string $reason
     * @return bool
     */
    public function rejectSheet($sheetId, $rejectorUserId, $reason = '') {
        return $this->transaction(function() use ($sheetId, $rejectorUserId, $reason) {
            $sheet = $this->sheetModel->findById($sheetId);
            if (!$sheet) {
                throw new Exception('Collection sheet not found.');
            }
            
            if ($sheet['status'] !== 'submitted') {
                throw new Exception('Only submitted sheets can be rejected.');
            }

            // Update sheet status back to draft with rejection notes
            $updateData = [
                'status' => 'draft',
                'notes' => 'REJECTED: ' . $reason,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (!$this->sheetModel->update($sheetId, $updateData)) {
                throw new Exception('Failed to reject collection sheet.');
            }

            // Update all items back to draft status
            $this->itemModel->updateStatusBySheet($sheetId, 'draft');

            // Optional audit log
            if (class_exists('TransactionService')) {
                $ts = new TransactionService();
                $ts->logGeneric('collection_sheet_rejected', $rejectorUserId, $sheetId, [
                    'rejection_reason' => $reason,
                    'officer_id' => $sheet['officer_id']
                ]);
            }

            return true;
        });
    }

    /**
     * Post all approved collection sheet items as payments (Cashier workflow)
     * @param int $sheetId
     * @param int $cashierUserId
     * @return bool
     */
    public function postSheetPayments($sheetId, $cashierUserId) {
        $paymentService = new PaymentService();
        return $this->transaction(function() use ($sheetId, $cashierUserId, $paymentService) {
            $details = $this->getSheetDetails($sheetId);
            if (!$details) {
                throw new Exception('Collection sheet not found.');
            }
            
            $sheet = $details['sheet'];
            if ($sheet['status'] !== 'approved') {
                throw new Exception('Only approved sheets can have payments posted.');
            }

            $items = $details['items'];
            $postedCount = 0;

            foreach ($items as $item) {
                if ($item['status'] !== 'approved') {
                    continue;
                }

                // Record payment for this loan using transaction-safe method
                $paymentId = $paymentService->recordPaymentWithoutTransaction(
                    (int)$item['loan_id'], 
                    (float)$item['amount'], 
                    (int)$cashierUserId,
                    'collection_sheet'
                );

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

                $postedCount++;
            }

            if ($postedCount === 0) {
                throw new Exception('No items were available for posting.');
            }

            // Update sheet status to posted
            $updateData = [
                'status' => 'posted',
                'posted_at' => date('Y-m-d H:i:s'),
                'posted_by' => $cashierUserId,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (!$this->sheetModel->update($sheetId, $updateData)) {
                throw new Exception('Failed to update sheet status to posted.');
            }

            // Recalculate total
            $this->sheetModel->recalcTotal($sheetId);

            // Optional audit log
            if (class_exists('TransactionService')) {
                $ts = new TransactionService();
                $ts->logGeneric('collection_sheet_posted', $cashierUserId, $sheetId, [
                    'total_amount' => $sheet['total_amount'],
                    'posted_items' => $postedCount
                ]);
            }

            return true;
        });
    }

    /**
     * Add loan with automatic calculation and lock preferences
     * @param int $sheetId
     * @param int $loanId
     * @param array $options ['auto_calculate' => bool, 'lock_form' => bool, 'auto_notes' => bool]
     * @return array|false Result with item data or false on error
     */
    public function addLoanAutomated($sheetId, $loanId, $options = []) {
        // Default options
        $options = array_merge([
            'auto_calculate' => true,
            'lock_form' => false,
            'auto_notes' => true
        ], $options);

        // Get loan details
        $loan = $this->loanModel->findById($loanId);
        if (!$loan) { 
            $this->setErrorMessage('Loan not found.'); 
            return false; 
        }

        if ($loan['status'] !== LoanModel::STATUS_ACTIVE) {
            $this->setErrorMessage('Only active loans can be added to collection sheets.'); 
            return false;
        }

        // Auto-calculate weekly payment amount
        $amount = $options['auto_calculate'] ?
            ($loan['total_loan_amount'] / ($loan['term_weeks'] ?? 17)) :
            $loan['weekly_payment'] ?? 0;

        // Generate automatic notes
        $notes = $options['auto_notes'] ? 
            "Auto-generated: Weekly payment collection for Loan #{$loanId}" : 
            null;

        // Add the item
        $success = $this->addItem($sheetId, $loan['client_id'], $loanId, $amount, $notes);
        
        if ($success) {
            // Return detailed item information
            return [
                'success' => true,
                'item_id' => $this->itemModel->getLastInsertId(),
                'loan_id' => $loanId,
                'client_id' => $loan['client_id'],
                'client_name' => $loan['client_name'] ?? 'Unknown',
                'amount' => $amount,
                'weekly_payment' => $amount,
                'principal' => $loan['principal'],
                'notes' => $notes,
                'locked' => $options['lock_form']
            ];
        }

        return false;
    }

    /**
     * Batch add multiple loans with automatic processing
     * @param int $sheetId
     * @param array $loanIds
     * @param array $options
     * @return array Results for each loan
     */
    public function addMultipleLoansAutomated($sheetId, $loanIds, $options = []) {
        $results = [];
        
        foreach ($loanIds as $loanId) {
            $result = $this->addLoanAutomated($sheetId, $loanId, $options);
            $results[$loanId] = $result;
        }
        
        return $results;
    }

    /**
     * Auto-collect payments for all active loans of specified clients
     * @param int $sheetId
     * @param array $clientIds
     * @param array $options
     * @return array Collection results
     */
    public function autoCollectForClients($sheetId, $clientIds, $options = []) {
        $options = array_merge([
            'only_due_payments' => false,
            'max_per_client' => 1,
            'auto_submit' => false
        ], $options);

        $results = [];
        
        foreach ($clientIds as $clientId) {
            // Get active loans for client
            $activeLoans = $this->loanModel->getActiveLoansForClient($clientId);
            $clientResults = [];
            
            $count = 0;
            foreach ($activeLoans as $loan) {
                if ($count >= $options['max_per_client']) break;
                
                // Check if payment is due (optional)
                if ($options['only_due_payments'] && !$this->isPaymentDue($loan)) {
                    continue;
                }
                
                $result = $this->addLoanAutomated($sheetId, $loan['id'], $options);
                if ($result) {
                    $clientResults[] = $result;
                    $count++;
                }
            }
            
            $results[$clientId] = $clientResults;
        }
        
        // Auto-submit sheet if requested
        if ($options['auto_submit'] && !empty($results)) {
            $this->submitSheet($sheetId);
        }
        
        return $results;
    }

    /**
     * Check if a payment is due for a loan
     * @param array $loan
     * @return bool
     */
    private function isPaymentDue($loan) {
        // Simple check: payment is due if it's been a week since last payment
        // This can be enhanced with more sophisticated logic
        if (!isset($loan['last_payment_date'])) return true;
        
        $lastPayment = new DateTime($loan['last_payment_date']);
        $now = new DateTime();
        $daysSincePayment = $now->diff($lastPayment)->days;
        
        return $daysSincePayment >= 7; // Weekly payment cycle
    }

    /**
     * Enable automated collection mode for a sheet
     * @param int $sheetId
     * @param array $settings
     * @return bool
     */
    public function enableAutomatedMode($sheetId, $settings = []) {
        $settings = array_merge([
            'auto_calculate' => true,
            'lock_after_add' => true,
            'auto_submit_when_complete' => false,
            'prevent_manual_entry' => true
        ], $settings);

        // Store automation settings in sheet metadata or separate table
        // For now, we'll use a simple approach with sheet notes/metadata field
        $metadata = json_encode([
            'automated_mode' => true,
            'automation_settings' => $settings,
            'enabled_at' => date('Y-m-d H:i:s')
        ]);

        return $this->sheetModel->updateMetadata($sheetId, $metadata);
    }

    /**
     * Direct post collection sheet for super-admin (bypasses approval workflow)
     * Creates Payment entries, updates Cash Blotter, logs transactions immediately.
     * @param int $sheetId
     * @param int $superAdminUserId
     * @return bool
     */
    public function directPost($sheetId, $superAdminUserId) {
        $paymentService = new PaymentService();
        return $this->transaction(function() use ($sheetId, $superAdminUserId, $paymentService) {
            $details = $this->getSheetDetails($sheetId);
            if (!$details) { throw new Exception('Sheet not found.'); }
            $sheet = $details['sheet'];

            // Allow direct posting for draft sheets by super-admin
            if (!in_array($sheet['status'], ['draft', 'submitted'])) {
                throw new Exception('Only draft or submitted sheets can be directly posted by super-admin.');
            }

            $items = $details['items'];
            $postedCount = 0;

            foreach ($items as $item) {
                if (!in_array($item['status'], ['draft', 'submitted'])) { continue; }

                // Record payment for this loan using transaction-safe method
                $paymentId = $paymentService->recordPaymentWithoutTransaction(
                    (int)$item['loan_id'],
                    (float)$item['amount'],
                    (int)$superAdminUserId,
                    'direct_collection_sheet'
                );

                if (!$paymentId) {
                    throw new Exception('Failed to record payment for loan #' . $item['loan_id'] . ': ' . $paymentService->getErrorMessage());
                }

                // Mark item as posted
                $this->itemModel->update($item['id'], [
                    'status' => 'posted',
                    'posted_at' => date('Y-m-d H:i:s'),
                    'posted_by' => $superAdminUserId,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                $postedCount++;
            }

            if ($postedCount === 0) {
                throw new Exception('No items were available for posting.');
            }

            // Update sheet status to posted directly
            $updateData = [
                'status' => 'posted',
                'posted_at' => date('Y-m-d H:i:s'),
                'posted_by' => $superAdminUserId,
                'approved_by' => $superAdminUserId, // Mark as self-approved
                'approved_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'notes' => ($sheet['notes'] ? $sheet['notes'] . ' | ' : '') . 'DIRECT POST by Super-Admin'
            ];

            if (!$this->sheetModel->update($sheetId, $updateData)) {
                throw new Exception('Failed to update sheet status to posted.');
            }

            // Recalculate total
            $this->sheetModel->recalcTotal($sheetId);

            // Audit log for direct posting
            if (class_exists('TransactionService')) {
                $ts = new TransactionService();
                $ts->logGeneric('collection_sheet_direct_posted', $superAdminUserId, $sheetId, [
                    'total_amount' => $sheet['total_amount'],
                    'posted_items' => $postedCount,
                    'bypassed_approval' => true
                ]);
            }

            return true;
        });
    }

    /**
     * Get collection sheet statistics with optional filters
     * @param array $filters Optional filters: date_range, officer_id, status, etc.
     * @return array Statistics array with totals and breakdowns
     */
    public function getCollectionSheetStatistics($filters = []) {
        $stats = [
            'total_sheets' => 0,
            'total_amount' => 0.00,
            'sheets_by_status' => [],
            'amounts_by_officer' => [],
            'sheets_by_officer' => [],
            'monthly_totals' => [],
            'status_distribution' => []
        ];

        // Get all sheets with filters
        $sheets = $this->listSheets($filters);

        foreach ($sheets as $sheet) {
            $stats['total_sheets']++;
            $stats['total_amount'] += (float)$sheet['total_amount'];

            // Count by status
            $status = $sheet['status'];
            if (!isset($stats['sheets_by_status'][$status])) {
                $stats['sheets_by_status'][$status] = 0;
            }
            $stats['sheets_by_status'][$status]++;

            // Count by officer
            $officerId = $sheet['officer_id'];
            if (!isset($stats['sheets_by_officer'][$officerId])) {
                $stats['sheets_by_officer'][$officerId] = 0;
                $stats['amounts_by_officer'][$officerId] = 0.00;
            }
            $stats['sheets_by_officer'][$officerId]++;
            $stats['amounts_by_officer'][$officerId] += (float)$sheet['total_amount'];

            // Monthly totals
            $month = date('Y-m', strtotime($sheet['sheet_date']));
            if (!isset($stats['monthly_totals'][$month])) {
                $stats['monthly_totals'][$month] = [
                    'sheets' => 0,
                    'amount' => 0.00
                ];
            }
            $stats['monthly_totals'][$month]['sheets']++;
            $stats['monthly_totals'][$month]['amount'] += (float)$sheet['total_amount'];
        }

        // Calculate status distribution percentages
        foreach ($stats['sheets_by_status'] as $status => $count) {
            $stats['status_distribution'][$status] = [
                'count' => $count,
                'percentage' => $stats['total_sheets'] > 0 ? round(($count / $stats['total_sheets']) * 100, 2) : 0
            ];
        }

        return $stats;
    }
}
