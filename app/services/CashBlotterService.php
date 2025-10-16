<?php
/**
 * CashBlotterService - Handles daily cash flow tracking and blotter management (FR-004)
 * Role: Manages the creation, updating, and finalization of the daily Cash Blotter.
 */
require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/../models/CashBlotterModel.php';
require_once __DIR__ . '/../models/LoanModel.php'; // Required to trigger related updates
require_once __DIR__ . '/../models/PaymentModel.php'; // Required for transaction linking

class CashBlotterService extends BaseService {
    private $cashBlotterModel;
    // Note: PaymentModel and LoanModel are initialized implicitly via the database or called via their services

    public function __construct() {
        parent::__construct();
        $this->cashBlotterModel = new CashBlotterModel();
        $this->setModel($this->cashBlotterModel);
    }

    /**
     * Finds or creates today's blotter and ensures collections/releases are up-to-date.
     * This is the main operational method for staff to check the blotter.
     * @param int $recordedBy The user ID of the staff checking/initializing the blotter.
     * @return array|false Today's updated blotter data, or false on failure.
     */
    public function getAndUpdateTodayBlotter($recordedBy) {
        $today = date('Y-m-d');
        $blotter = $this->cashBlotterModel->getBlotterByDate($today);

        if (!$blotter) {
            // 1. Create blotter if it doesn't exist (handles opening balance automatically)
            $blotterId = $this->cashBlotterModel->createDailyBlotter($today, $recordedBy);
            if (!$blotterId) {
                $this->setErrorMessage($this->cashBlotterModel->getLastError());
                return false;
            }
            $blotter = $this->cashBlotterModel->findById($blotterId);
        }

        // 2. Refresh financial metrics for today's blotter against payment/loan tables
        // These calls automatically update the collections, releases, and recalculate closing_balance
        $this->cashBlotterModel->updateBlotterCollections($today);
        $this->cashBlotterModel->updateBlotterReleases($today);
        
        // 3. Fetch the fully updated blotter data
        return $this->cashBlotterModel->getBlotterWithDetails($blotter['id']);
    }

    /**
     * Finalizes the cash blotter entry. (FR-004 completion)
     * @param int $blotterId Blotter ID
     * @return bool Success status
     */
    public function finalizeBlotter($blotterId) {
        $blotter = $this->cashBlotterModel->findById($blotterId);

        if (!$blotter) {
            $this->setErrorMessage('Cash blotter not found.');
            return false;
        }

        if ($blotter['status'] === CashBlotterModel::$STATUS_FINALIZED) {
            $this->setErrorMessage('Cash blotter is already finalized.');
            return false;
        }

        // Must ensure the calculated balance matches the closing balance before finalizing
        if (!$this->cashBlotterModel->validateBlotterBalance($blotterId)) {
            $this->setErrorMessage('Cannot finalize: There is a discrepancy between the calculated balance and the closing balance. Re-check expenses.');
            return false;
        }

        // Use the model to update the status
        return $this->cashBlotterModel->finalizeBlotter($blotterId);
    }
    
    /**
     * Adds a manual expense to a specific blotter (e.g., utility payment).
     * @param string $date Date of the expense
     * @param float $amount Amount of the expense
     * @param string|null $description Optional description
     * @return bool
     */
    public function addExpense($date, $amount, $description = null) {
        $blotter = $this->cashBlotterModel->getBlotterByDate($date);
        
        if (!$blotter) {
            $this->setErrorMessage("Blotter not yet initialized for {$date}. Please initialize today's blotter first.");
            return false;
        }
        
        if ($blotter['status'] === CashBlotterModel::$STATUS_FINALIZED) {
            $this->setErrorMessage('Cannot add expenses to a finalized blotter.');
            return false;
        }
        
        // Use the model's specialized expense update method
        $result = $this->cashBlotterModel->addExpense($date, $amount, $description);

        if ($result) {
            // NOTE: We should log this expense transaction separately, but for now, the blotter update suffices.
            return true;
        } else {
            $this->setErrorMessage($this->cashBlotterModel->getLastError() ?: 'Failed to record expense.');
            return false;
        }
    }
    
    // --- Retrieval Methods ---

    public function getBlottersByDateRange($startDate, $endDate) {
        return $this->cashBlotterModel->getBlottersByDateRange($startDate, $endDate);
    }
    
    public function getCurrentCashPosition() {
        return $this->cashBlotterModel->getCurrentCashPosition();
    }
    
    public function getBlotterById($id) {
        return $this->cashBlotterModel->getBlotterWithDetails($id);
    }
}