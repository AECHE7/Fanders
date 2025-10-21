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

class CollectionSheetService extends BaseService {
    public function __construct() {
        parent::__construct();
        // Model(s) will be wired in subsequent iterations (CollectionSheetModel, etc.)
    }

    /**
     * Create a draft collection sheet for an Account Officer on a given date.
     * @param int $officerId
     * @param string $date Y-m-d
     * @return array|false Newly created sheet data or false on error
     */
    public function createDraftSheet($officerId, $date) {
        // TODO: Implement insert into collection_sheets with status 'draft'
        $this->setErrorMessage('Not implemented yet.');
        return false;
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
        // TODO: Validate and insert into collection_sheet_items with status 'draft'
        $this->setErrorMessage('Not implemented yet.');
        return false;
    }

    /**
     * Submit a draft sheet for cashier review.
     * @param int $sheetId
     * @return bool
     */
    public function submitSheet($sheetId) {
        // TODO: Update sheet and all draft items to 'submitted'
        $this->setErrorMessage('Not implemented yet.');
        return false;
    }

    /**
     * List sheets with optional filters (role-aware: AO sees own).
     * @param array $filters
     * @return array
     */
    public function listSheets($filters = []) {
        // TODO: Implement SELECT with filters (officer_id, status, date range)
        return [];
    }

    /**
     * Get a sheet with its items.
     * @param int $sheetId
     * @return array|false
     */
    public function getSheetDetails($sheetId) {
        // TODO: Implement join fetch
        $this->setErrorMessage('Not implemented yet.');
        return false;
    }

    /**
     * Cashier action: approve and post a submitted sheet.
     * Creates Payment entries, updates Cash Blotter, logs transactions.
     * @param int $sheetId
     * @param int $cashierUserId
     * @return bool
     */
    public function approveAndPost($sheetId, $cashierUserId) {
        // TODO: Validate sheet state, iterate items, call PaymentService, update blotter, set posted timestamps
        $this->setErrorMessage('Not implemented yet.');
        return false;
    }
}
