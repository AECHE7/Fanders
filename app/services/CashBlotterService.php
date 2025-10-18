<?php
/**
 * CashBlotterService - Handles cash flow management operations for Fanders Microfinance.
 * This service manages daily cash blotter entries, tracking inflows and outflows
 * for financial reporting and cash position management.
 */
require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/../models/CashBlotterModel.php';

class CashBlotterService extends BaseService {
    private $cashBlotterModel;

    public function __construct() {
        parent::__construct();
        $this->cashBlotterModel = new CashBlotterModel();
        $this->setModel($this->cashBlotterModel);
    }

    /**
     * Gets the cash blotter entry for a specific date.
     * Creates one if it doesn't exist.
     * @param string $date Date in Y-m-d format
     * @return array|false
     */
    public function getBlotterForDate($date) {
        $blotter = $this->cashBlotterModel->getBlotterByDate($date);

        if (!$blotter) {
            // Create blotter for the date
            $this->cashBlotterModel->createBlotterForDate($date);
            $blotter = $this->cashBlotterModel->getBlotterByDate($date);
        }

        return $blotter;
    }

    /**
     * Updates cash blotter totals for a specific date.
     * @param string $date Date in Y-m-d format
     * @return bool
     */
    public function updateBlotterForDate($date) {
        return $this->cashBlotterModel->updateBlotterTotals($date);
    }

    /**
     * Gets cash blotter entries for a date range.
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @return array
     */
    public function getBlotterRange($startDate, $endDate) {
        return $this->cashBlotterModel->getBlotterRange($startDate, $endDate);
    }

    /**
     * Gets the current cash balance.
     * @return float
     */
    public function getCurrentBalance() {
        return $this->cashBlotterModel->getCurrentBalance();
    }

    /**
     * Gets cash flow summary for a period.
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getCashFlowSummary($startDate, $endDate) {
        return $this->cashBlotterModel->getCashFlowSummary($startDate, $endDate);
    }

    /**
     * Recalculates blotter entries from a start date.
     * @param string $startDate
     * @return bool
     */
    public function recalculateFromDate($startDate) {
        return $this->cashBlotterModel->recalculateFromDate($startDate);
    }

    /**
     * Gets daily cash flow data for charting.
     * @param int $days Number of days to look back
     * @return array
     */
    public function getDailyCashFlow($days = 30) {
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $blotters = $this->getBlotterRange($startDate, $endDate);

        $data = [];
        foreach ($blotters as $blotter) {
            $data[] = [
                'date' => $blotter['blotter_date'],
                'inflow' => (float)$blotter['total_inflow'],
                'outflow' => (float)$blotter['total_outflow'],
                'balance' => (float)$blotter['calculated_balance']
            ];
        }

        return $data;
    }

    /**
     * Gets monthly cash flow summary.
     * @param int $months Number of months to look back
     * @return array
     */
    public function getMonthlyCashFlow($months = 12) {
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $startDate = date('Y-m-01', strtotime($month . '-01'));
            $endDate = date('Y-m-t', strtotime($month . '-01'));

            $summary = $this->getCashFlowSummary($startDate, $endDate);

            $data[] = [
                'month' => date('M Y', strtotime($month . '-01')),
                'inflow' => $summary['total_inflow'],
                'outflow' => $summary['total_outflow'],
                'net_flow' => $summary['net_flow']
            ];
        }

        return $data;
    }

    /**
     * Processes daily cash blotter updates.
     * This should be called daily to maintain accurate cash positions.
     * @param string|null $date Specific date to process, defaults to today
     * @return bool
     */
    public function processDailyBlotter($date = null) {
        $date = $date ?: date('Y-m-d');
        return $this->updateBlotterForDate($date);
    }

    /**
     * Gets cash position alerts (low balance warnings).
     * @param float $threshold Minimum balance threshold
     * @return array
     */
    public function getCashAlerts($threshold = 1000.00) {
        $currentBalance = $this->getCurrentBalance();

        $alerts = [];

        if ($currentBalance < $threshold) {
            $alerts[] = [
                'type' => 'low_balance',
                'message' => "Current cash balance (₱" . number_format($currentBalance, 2) . ") is below threshold (₱" . number_format($threshold, 2) . ")",
                'severity' => 'warning'
            ];
        }

        // Check for negative balance
        if ($currentBalance < 0) {
            $alerts[] = [
                'type' => 'negative_balance',
                'message' => "Cash balance is negative (₱" . number_format($currentBalance, 2) . ")",
                'severity' => 'critical'
            ];
        }

        return $alerts;
    }
}
