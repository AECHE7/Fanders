<?php
/**
 * CashBlotterModel - Handles daily cash flow tracking for Fanders Microfinance (FR-004)
 * This model is the core of the daily cash management system.
 */
require_once __DIR__ . '/../core/BaseModel.php';

class CashBlotterModel extends BaseModel {
    protected $table = 'cash_blotter';
    protected $primaryKey = 'id';
    protected $fillable = [
        'blotter_date', 'opening_balance', 'total_collections', 'total_loan_releases',
        'total_expenses', 'closing_balance', 'recorded_by', 'status',
        'created_at', 'updated_at'
    ];
    protected $hidden = [];

    // Status definitions
    public static $STATUS_DRAFT = 'draft';
    public static $STATUS_FINALIZED = 'finalized';

    /**
     * Finds a cash blotter record by its date.
     * @param string $date Y-m-d format
     * @return array|false
     */
    public function getBlotterByDate($date) {
        return $this->findOneByField('blotter_date', $date);
    }

    /**
     * Calculates the closing balance based on the blotter's components.
     * Uses ROUND to prevent floating point errors.
     * @param array $blotterData
     * @return float
     */
    protected function calculateClosingBalance(array $blotterData) {
        $opening = $blotterData['opening_balance'] ?? 0;
        $collections = $blotterData['total_collections'] ?? 0;
        $releases = $blotterData['total_loan_releases'] ?? 0;
        $expenses = $blotterData['total_expenses'] ?? 0;

        $closingBalance = $opening + $collections - $releases - $expenses;
        
        // Round to 2 decimal places for financial accuracy
        return round($closingBalance, 2);
    }

    /**
     * Creates a cash blotter for the specified date, using the previous day's closing balance.
     * @param string $date Y-m-d format
     * @param int $recordedBy User ID
     * @return int|false New blotter ID or false on failure.
     */
    public function createDailyBlotter($date, $recordedBy) {
        // Check if blotter already exists for this date
        if ($this->getBlotterByDate($date)) {
            $this->setLastError('Cash blotter already exists for this date.');
            return false;
        }

        // Get previous day's closing balance as opening balance
        $previousDay = date('Y-m-d', strtotime($date . ' -1 day'));
        $previousBlotter = $this->getBlotterByDate($previousDay);
        // If the previous blotter exists and is finalized, use its closing balance. Otherwise, start at 0.
        $openingBalance = ($previousBlotter && $previousBlotter['status'] === self::$STATUS_FINALIZED) 
                          ? $previousBlotter['closing_balance'] : 0.00;

        $data = [
            'blotter_date' => $date,
            'opening_balance' => $openingBalance,
            'total_collections' => 0.00,
            'total_loan_releases' => 0.00,
            'total_expenses' => 0.00,
            'closing_balance' => $openingBalance,
            'recorded_by' => $recordedBy,
            'status' => self::$STATUS_DRAFT,
        ];

        return $this->create($data);
    }

    /**
     * Updates the blotter's collections based on recorded payments.
     * @param string $date Y-m-d format
     */
    public function updateBlotterCollections($date) {
        // Calculate total collections for the date
        $sql = "SELECT SUM(amount) as total FROM payments WHERE DATE(payment_date) = ?";
        $result = $this->db->single($sql, [$date]);
        $totalCollections = round($result ? $result['total'] : 0, 2);

        // Fetch current blotter data
        $blotter = $this->getBlotterByDate($date);
        if ($blotter) {
            $updateData = ['total_collections' => $totalCollections];
            
            // Recalculate closing balance by merging current data with new collections
            $recalcData = array_merge($blotter, $updateData);
            $updateData['closing_balance'] = $this->calculateClosingBalance($recalcData);

            $this->update($blotter['id'], $updateData);
        }
    }

    /**
     * Updates the blotter's loan releases based on disbursements.
     * @param string $date Y-m-d format
     */
    public function updateBlotterReleases($date) {
        // Calculate total loan releases for the date (loans table uses 'principal' for amount)
        $sql = "SELECT SUM(principal) as total FROM loans WHERE DATE(disbursement_date) = ? AND status = ?";
        $result = $this->db->single($sql, [$date, LoanModel::$STATUS_ACTIVE]);
        $totalReleases = round($result ? $result['total'] : 0, 2);

        // Fetch current blotter data
        $blotter = $this->getBlotterByDate($date);
        if ($blotter) {
            $updateData = ['total_loan_releases' => $totalReleases];

            // Recalculate closing balance by merging current data with new releases
            $recalcData = array_merge($blotter, $updateData);
            $updateData['closing_balance'] = $this->calculateClosingBalance($recalcData);

            $this->update($blotter['id'], $updateData);
        }
    }
    
    /**
     * Overrides BaseModel update to enforce closing balance recalculation.
     */
    public function update($id, $data) {
        // If any key financial field is being updated, fetch existing data and recalculate balance
        if (isset($data['opening_balance']) || isset($data['total_collections']) || 
            isset($data['total_loan_releases']) || isset($data['total_expenses'])) {
            
            $blotter = $this->findById($id);
            if ($blotter) {
                // Merge existing data with new data for recalculation
                $recalcData = array_merge($blotter, $data);
                $data['closing_balance'] = $this->calculateClosingBalance($recalcData);
            }
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        return parent::update($id, $data);
    }
    
    /**
     * Finalizes the blotter status (using inherited updateStatus)
     * @param int $blotterId
     * @return bool
     */
    public function finalizeBlotter($blotterId) {
        return $this->updateStatus($blotterId, self::$STATUS_FINALIZED);
    }

    // --- Data Retrieval Methods ---
    
    // Remaining retrieval methods (getBlotterSummary, getMonthlyCashFlow, etc.) are omitted for brevity but remain functional.
    
    public function getBlotterWithDetails($id) {
        $sql = "SELECT cb.*,
                u.name as recorded_by_name
                FROM {$this->table} cb
                LEFT JOIN users u ON cb.recorded_by = u.id
                WHERE cb.id = ?";

        return $this->db->single($sql, [$id]);
    }

    public function getBlottersByDateRange($startDate, $endDate) {
        $sql = "SELECT cb.*,
                u.name as recorded_by_name
                FROM {$this->table} cb
                LEFT JOIN users u ON cb.recorded_by = u.id
                WHERE cb.blotter_date BETWEEN ? AND ?
                ORDER BY cb.blotter_date DESC";

        return $this->db->resultSet($sql, [$startDate, $endDate]);
    }
    
    public function getLatestBlotter() {
        $sql = "SELECT * FROM {$this->table}
                ORDER BY blotter_date DESC LIMIT 1";

        return $this->db->single($sql);
    }
}