<?php
/**
 * CashBlotterService - Handles daily cash flow tracking and blotter management
 */
require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/../models/CashBlotterModel.php';
require_once __DIR__ . '/../models/PaymentModel.php';

class CashBlotterService extends BaseService {
    private $cashBlotterModel;
    private $paymentModel;

    public function __construct() {
        parent::__construct();
        $this->cashBlotterModel = new CashBlotterModel();
        $this->paymentModel = new PaymentModel();
    }

    /**
     * Create or update daily cash blotter
     * @param string $date Date in Y-m-d format
     * @param array $data Cash blotter data
     * @return bool|int Blotter ID or false on failure
     */
    public function createOrUpdateBlotter($date, $data = []) {
        // Check if blotter already exists for this date
        $existingBlotter = $this->cashBlotterModel->getBlotterByDate($date);

        if ($existingBlotter) {
            // Update existing blotter
            return $this->cashBlotterModel->update($existingBlotter['id'], $data);
        } else {
            // Create new blotter with default values
            $blotterData = array_merge([
                'blotter_date' => $date,
                'opening_balance' => 0.00,
                'collections' => 0.00,
                'disbursements' => 0.00,
                'expenses' => 0.00,
                'closing_balance' => 0.00,
                'created_by' => $_SESSION['user_id'] ?? null
            ], $data);

            return $this->cashBlotterModel->create($blotterData);
        }
    }

    /**
     * Calculate blotter totals for a specific date
     * @param string $date Date in Y-m-d format
     * @return array Calculated totals
     */
    public function calculateBlotterTotals($date) {
        // Get all payments for the date
        $payments = $this->paymentModel->getPaymentsByDate($date);

        $collections = 0.00;
        $disbursements = 0.00;

        foreach ($payments as $payment) {
            if ($payment['payment_type'] === 'collection') {
                $collections += $payment['amount'];
            } elseif ($payment['payment_type'] === 'disbursement') {
                $disbursements += $payment['amount'];
            }
        }

        // Get previous day's closing balance
        $previousDate = date('Y-m-d', strtotime($date . ' -1 day'));
        $previousBlotter = $this->cashBlotterModel->getBlotterByDate($previousDate);
        $openingBalance = $previousBlotter ? $previousBlotter['closing_balance'] : 0.00;

        // Assume expenses are entered manually or from another source
        $expenses = 0.00; // This could be calculated from expense records

        $closingBalance = $openingBalance + $collections - $disbursements - $expenses;

        return [
            'opening_balance' => $openingBalance,
            'collections' => $collections,
            'disbursements' => $disbursements,
            'expenses' => $expenses,
            'closing_balance' => $closingBalance,
            'net_flow' => $collections - $disbursements - $expenses
        ];
    }

    /**
     * Get blotter summary for a date range
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Summary data
     */
    public function getBlotterSummary($startDate, $endDate) {
        $blotters = $this->cashBlotterModel->getBlottersByDateRange($startDate, $endDate);

        $summary = [
            'total_opening_balance' => 0.00,
            'total_collections' => 0.00,
            'total_disbursements' => 0.00,
            'total_expenses' => 0.00,
            'total_closing_balance' => 0.00,
            'period_days' => count($blotters),
            'blotters' => $blotters
        ];

        foreach ($blotters as $blotter) {
            $summary['total_opening_balance'] += $blotter['opening_balance'];
            $summary['total_collections'] += $blotter['collections'];
            $summary['total_disbursements'] += $blotter['disbursements'];
            $summary['total_expenses'] += $blotter['expenses'];
            $summary['total_closing_balance'] += $blotter['closing_balance'];
        }

        return $summary;
    }

    /**
     * Validate blotter data
     * @param array $data Blotter data
     * @return bool True if valid
     */
    public function validateBlotterData($data) {
        $requiredFields = ['blotter_date', 'opening_balance', 'collections', 'disbursements', 'expenses', 'closing_balance'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $this->setErrorMessage("{$field} is required.");
                return false;
            }

            if (!is_numeric($data[$field])) {
                $this->setErrorMessage("{$field} must be a valid number.");
                return false;
            }

            if ($data[$field] < 0) {
                $this->setErrorMessage("{$field} cannot be negative.");
                return false;
            }
        }

        // Validate date format
        if (!strtotime($data['blotter_date'])) {
            $this->setErrorMessage("Invalid date format.");
            return false;
        }

        // Validate closing balance calculation
        $expectedClosing = $data['opening_balance'] + $data['collections'] - $data['disbursements'] - $data['expenses'];
        if (abs($data['closing_balance'] - $expectedClosing) > 0.01) { // Allow for small rounding differences
            $this->setErrorMessage("Closing balance does not match calculated amount.");
            return false;
        }

        return true;
    }

    /**
     * Generate blotter report
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Report data
     */
    public function generateBlotterReport($startDate, $endDate) {
        $summary = $this->getBlotterSummary($startDate, $endDate);

        return [
            'title' => 'Cash Blotter Report',
            'period' => "From {$startDate} to {$endDate}",
            'generated_date' => date('Y-m-d H:i:s'),
            'summary' => $summary,
            'daily_breakdown' => $summary['blotters']
        ];
    }

    /**
     * Get blotter for today
     * @return array|null Today's blotter data
     */
    public function getTodayBlotter() {
        $today = date('Y-m-d');
        return $this->cashBlotterModel->getBlotterByDate($today);
    }

    /**
     * Get current cash position (latest closing balance)
     * @return float Current cash position
     */
    public function getCurrentCashPosition() {
        // Get the most recent blotter
        $latestBlotter = $this->cashBlotterModel->getLatestBlotter();
        return $latestBlotter ? $latestBlotter['closing_balance'] : 0.00;
    }

    /**
     * Get blotter by ID with full details
     * @param int $id Blotter ID
     * @return array|null Blotter data
     */
    public function getBlotterById($id) {
        return $this->cashBlotterModel->getBlotterWithDetails($id);
    }

    /**
     * Get blotter by date
     * @param string $date Date in Y-m-d format
     * @return array|null Blotter data
     */
    public function getBlotterByDate($date) {
        return $this->cashBlotterModel->getBlotterByDate($date);
    }

    /**
     * Create a new cash blotter entry
     * @param array $data Blotter data
     * @return bool|int Blotter ID or false on failure
     */
    public function createBlotter($data) {
        // Validate data
        if (!$this->validateBlotterData($data)) {
            return false;
        }

        // Check if blotter already exists for this date
        if ($this->cashBlotterModel->getBlotterByDate($data['blotter_date'])) {
            $this->setErrorMessage('Cash blotter already exists for this date.');
            return false;
        }

        return $this->cashBlotterModel->create($data);
    }

    /**
     * Update an existing cash blotter entry
     * @param int $id Blotter ID
     * @param array $data Updated blotter data
     * @return bool Success status
     */
    public function updateBlotter($id, $data) {
        // Get existing blotter
        $existingBlotter = $this->cashBlotterModel->findById($id);
        if (!$existingBlotter) {
            $this->setErrorMessage('Cash blotter not found.');
            return false;
        }

        // Check if finalized
        if ($existingBlotter['status'] === 'finalized') {
            $this->setErrorMessage('Cannot update a finalized cash blotter.');
            return false;
        }

        // Validate data
        if (!$this->validateBlotterData($data)) {
            return false;
        }

        return $this->cashBlotterModel->update($id, $data);
    }

    /**
     * Finalize a cash blotter entry
     * @param int $id Blotter ID
     * @return bool Success status
     */
    public function finalizeBlotter($id) {
        // Get existing blotter
        $blotter = $this->cashBlotterModel->findById($id);
        if (!$blotter) {
            $this->setErrorMessage('Cash blotter not found.');
            return false;
        }

        // Check if already finalized
        if ($blotter['status'] === 'finalized') {
            $this->setErrorMessage('Cash blotter is already finalized.');
            return false;
        }

        // Validate balance before finalizing
        $calculatedBalance = $blotter['opening_balance'] + $blotter['total_collections'] - $blotter['total_loan_releases'] - $blotter['total_expenses'];
        if (abs($calculatedBalance - $blotter['closing_balance']) > 0.01) {
            $this->setErrorMessage('Cannot finalize: Balance calculation does not match.');
            return false;
        }

        return $this->cashBlotterModel->finalizeBlotter($id);
    }

    /**
     * Initialize blotter for today if it doesn't exist
     * @return bool|int Blotter ID
     */
    public function initializeTodayBlotter() {
        $today = date('Y-m-d');
        $existing = $this->getTodayBlotter();

        if ($existing) {
            return $existing['id'];
        }

        // Calculate opening balance from yesterday's closing balance
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $yesterdayBlotter = $this->cashBlotterModel->getBlotterByDate($yesterday);
        $openingBalance = $yesterdayBlotter ? $yesterdayBlotter['closing_balance'] : 0.00;

        return $this->createOrUpdateBlotter($today, [
            'opening_balance' => $openingBalance,
            'collections' => 0.00,
            'disbursements' => 0.00,
            'expenses' => 0.00,
            'closing_balance' => $openingBalance
        ]);
    }

    /**
     * Record a transaction in the blotter
     * @param string $date Transaction date
     * @param float $amount Transaction amount
     * @param string $type 'collection', 'disbursement', or 'expense'
     * @return bool Success status
     */
    public function recordTransaction($date, $amount, $type) {
        $blotter = $this->cashBlotterModel->getBlotterByDate($date);

        if (!$blotter) {
            // Create new blotter for the date
            $this->initializeBlotterForDate($date);
            $blotter = $this->cashBlotterModel->getBlotterByDate($date);
        }

        $updateData = [];

        switch ($type) {
            case 'collection':
                $updateData['collections'] = $blotter['collections'] + $amount;
                break;
            case 'disbursement':
                $updateData['disbursements'] = $blotter['disbursements'] + $amount;
                break;
            case 'expense':
                $updateData['expenses'] = $blotter['expenses'] + $amount;
                break;
            default:
                $this->setErrorMessage("Invalid transaction type.");
                return false;
        }

        // Recalculate closing balance
        $updateData['closing_balance'] = $blotter['opening_balance'] +
                                        ($updateData['collections'] ?? $blotter['collections']) -
                                        ($updateData['disbursements'] ?? $blotter['disbursements']) -
                                        ($updateData['expenses'] ?? $blotter['expenses']);

        return $this->cashBlotterModel->update($blotter['id'], $updateData);
    }

    /**
     * Initialize blotter for a specific date
     * @param string $date Date to initialize
     * @return bool|int Blotter ID
     */
    private function initializeBlotterForDate($date) {
        // Get previous day's closing balance
        $previousDate = date('Y-m-d', strtotime($date . ' -1 day'));
        $previousBlotter = $this->cashBlotterModel->getBlotterByDate($previousDate);
        $openingBalance = $previousBlotter ? $previousBlotter['closing_balance'] : 0.00;

        return $this->createOrUpdateBlotter($date, [
            'opening_balance' => $openingBalance,
            'collections' => 0.00,
            'disbursements' => 0.00,
            'expenses' => 0.00,
            'closing_balance' => $openingBalance
        ]);
    }
}
