<?php
/**
 * CashBlotterModel - Handles cash flow management operations
 * This model manages the data for the 'cash_blotter' table and provides
 * functionality for tracking daily cash inflows and outflows.
 */
require_once __DIR__ . '/../core/BaseModel.php';

class CashBlotterModel extends BaseModel {
    protected $table = 'cash_blotter';
    protected $primaryKey = 'id';

    protected $fillable = [
        'blotter_date',
        'total_inflow',
        'total_outflow',
        'calculated_balance',
        'created_at',
        'updated_at'
    ];
    protected $hidden = [];

    /**
     * Gets or creates a cash blotter entry for a specific date.
     * @param string $date Date in Y-m-d format
     * @return array|false
     */
    public function getBlotterByDate($date) {
        $sql = "SELECT * FROM {$this->table} WHERE blotter_date = ?";
        return $this->db->single($sql, [$date]);
    }

    /**
     * Creates a new cash blotter entry for a date.
     * @param string $date Date in Y-m-d format
     * @return int|false
     */
    public function createBlotterForDate($date) {
        // Check if already exists
        if ($this->getBlotterByDate($date)) {
            return false;
        }

        $data = [
            'blotter_date' => $date,
            'total_inflow' => 0.00,
            'total_outflow' => 0.00,
            'calculated_balance' => 0.00
        ];

        return $this->create($data);
    }

    /**
     * Updates the cash blotter for a specific date with calculated totals.
     * @param string $date Date in Y-m-d format
     * @return bool
     */
    public function updateBlotterTotals($date) {
        // Calculate inflows (loan disbursements + other income)
        $inflowSql = "SELECT COALESCE(SUM(amount), 0) as total_inflow
                      FROM (
                          SELECT l.total_loan_amount as amount
                          FROM loans l
                          WHERE DATE(l.disbursement_date) = ?
                          UNION ALL
                          SELECT 0 as amount -- Placeholder for other income sources
                      ) as inflows";

        $inflowResult = $this->db->single($inflowSql, [$date]);
        $totalInflow = $inflowResult ? $inflowResult['total_inflow'] : 0;

        // Calculate outflows (payments made + expenses)
        $outflowSql = "SELECT COALESCE(SUM(amount), 0) as total_outflow
                       FROM payments
                       WHERE DATE(payment_date) = ?";

        $outflowResult = $this->db->single($outflowSql, [$date]);
        $totalOutflow = $outflowResult ? $outflowResult['total_outflow'] : 0;

        // Get previous day's balance
        $prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
        $prevBlotter = $this->getBlotterByDate($prevDate);
        $openingBalance = $prevBlotter ? $prevBlotter['calculated_balance'] : 0;

        $calculatedBalance = $openingBalance + $totalInflow - $totalOutflow;

        // Update or create blotter entry
        $existing = $this->getBlotterByDate($date);
        if ($existing) {
            return $this->update($existing['id'], [
                'total_inflow' => $totalInflow,
                'total_outflow' => $totalOutflow,
                'calculated_balance' => $calculatedBalance
            ]);
        } else {
            return $this->create([
                'blotter_date' => $date,
                'total_inflow' => $totalInflow,
                'total_outflow' => $totalOutflow,
                'calculated_balance' => $calculatedBalance
            ]);
        }
    }

    /**
     * Gets cash blotter entries for a date range.
     * @param string $startDate Start date in Y-m-d format
     * @param string $endDate End date in Y-m-d format
     * @return array
     */
    public function getBlotterRange($startDate, $endDate) {
        $sql = "SELECT * FROM {$this->table}
                WHERE blotter_date BETWEEN ? AND ?
                ORDER BY blotter_date ASC";

        return $this->db->resultSet($sql, [$startDate, $endDate]);
    }

    /**
     * Gets the current cash balance (latest calculated balance).
     * @return float
     */
    public function getCurrentBalance() {
        $sql = "SELECT calculated_balance FROM {$this->table}
                ORDER BY blotter_date DESC LIMIT 1";

        $result = $this->db->single($sql);
        return $result ? (float)$result['calculated_balance'] : 0.00;
    }

    /**
     * Gets cash flow summary for a period.
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getCashFlowSummary($startDate, $endDate) {
        $sql = "SELECT
                    SUM(total_inflow) as total_inflow,
                    SUM(total_outflow) as total_outflow,
                    (SUM(total_inflow) - SUM(total_outflow)) as net_flow
                FROM {$this->table}
                WHERE blotter_date BETWEEN ? AND ?";

        $result = $this->db->single($sql, [$startDate, $endDate]);

        return [
            'total_inflow' => $result ? (float)$result['total_inflow'] : 0,
            'total_outflow' => $result ? (float)$result['total_outflow'] : 0,
            'net_flow' => $result ? (float)$result['net_flow'] : 0
        ];
    }

    /**
     * Recalculates all blotter entries from a start date.
     * This is useful when historical data changes.
     * @param string $startDate
     * @return bool
     */
    public function recalculateFromDate($startDate) {
        $sql = "SELECT blotter_date FROM {$this->table}
                WHERE blotter_date >= ?
                ORDER BY blotter_date ASC";

        $dates = $this->db->resultSet($sql, [$startDate]);

        foreach ($dates as $dateRow) {
            $this->updateBlotterTotals($dateRow['blotter_date']);
        }

        return true;
    }
}
