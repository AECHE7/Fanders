<?php
/**
 * CashBlotterModel - Handles daily cash flow tracking
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

    public function getBlotterByDate($date) {
        return $this->findOneByField('blotter_date', $date);
    }

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

    public function getBlottersByStatus($status) {
        $sql = "SELECT cb.*,
                u.name as recorded_by_name
                FROM {$this->table} cb
                LEFT JOIN users u ON cb.recorded_by = u.id
                WHERE cb.status = ?
                ORDER BY cb.blotter_date DESC";

        return $this->db->resultSet($sql, [$status]);
    }

    public function createDailyBlotter($date, $recordedBy) {
        // Check if blotter already exists for this date
        $existing = $this->getBlotterByDate($date);
        if ($existing) {
            $this->setLastError('Cash blotter already exists for this date.');
            return false;
        }

        // Get previous day's closing balance as opening balance
        $previousDay = date('Y-m-d', strtotime($date . ' -1 day'));
        $previousBlotter = $this->getBlotterByDate($previousDay);
        $openingBalance = $previousBlotter ? $previousBlotter['closing_balance'] : 0;

        $data = [
            'blotter_date' => $date,
            'opening_balance' => $openingBalance,
            'total_collections' => 0,
            'total_loan_releases' => 0,
            'total_expenses' => 0,
            'closing_balance' => $openingBalance,
            'recorded_by' => $recordedBy,
            'status' => self::$STATUS_DRAFT,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->create($data);
    }

    public function updateBlotterCollections($date) {
        // Calculate total collections for the date
        $sql = "SELECT SUM(payment_amount) as total FROM payments WHERE DATE(payment_date) = ?";
        $result = $this->db->single($sql, [$date]);
        $totalCollections = $result ? $result['total'] : 0;

        // Update blotter
        $blotter = $this->getBlotterByDate($date);
        if ($blotter) {
            $closingBalance = $blotter['opening_balance'] + $totalCollections - $blotter['total_loan_releases'] - $blotter['total_expenses'];

            $this->update($blotter['id'], [
                'total_collections' => $totalCollections,
                'closing_balance' => $closingBalance,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    public function updateBlotterReleases($date) {
        // Calculate total loan releases for the date
        $sql = "SELECT SUM(loan_amount) as total FROM loans WHERE DATE(disbursement_date) = ?";
        $result = $this->db->single($sql, [$date]);
        $totalReleases = $result ? $result['total'] : 0;

        // Update blotter
        $blotter = $this->getBlotterByDate($date);
        if ($blotter) {
            $closingBalance = $blotter['opening_balance'] + $blotter['total_collections'] - $totalReleases - $blotter['total_expenses'];

            $this->update($blotter['id'], [
                'total_loan_releases' => $totalReleases,
                'closing_balance' => $closingBalance,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    public function addExpense($date, $amount, $description = null) {
        $blotter = $this->getBlotterByDate($date);
        if (!$blotter) {
            $this->setLastError('Cash blotter not found for this date.');
            return false;
        }

        $newTotalExpenses = $blotter['total_expenses'] + $amount;
        $closingBalance = $blotter['opening_balance'] + $blotter['total_collections'] - $blotter['total_loan_releases'] - $newTotalExpenses;

        return $this->update($blotter['id'], [
            'total_expenses' => $newTotalExpenses,
            'closing_balance' => $closingBalance,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function finalizeBlotter($blotterId) {
        return $this->update($blotterId, [
            'status' => self::$STATUS_FINALIZED,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function getBlotterSummary($startDate = null, $endDate = null) {
        $sql = "SELECT
                COUNT(*) as total_blotters,
                SUM(opening_balance) as total_opening,
                SUM(total_collections) as total_collections,
                SUM(total_loan_releases) as total_releases,
                SUM(total_expenses) as total_expenses,
                SUM(closing_balance) as total_closing
                FROM {$this->table}
                WHERE status = ?";

        $params = [self::$STATUS_FINALIZED];

        if ($startDate && $endDate) {
            $sql .= " AND blotter_date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        return $this->db->single($sql, $params);
    }

    public function getMonthlyCashFlow($year = null, $month = null) {
        $year = $year ?? date('Y');
        $month = $month ?? date('m');

        $sql = "SELECT
                DAY(blotter_date) as day,
                opening_balance,
                total_collections,
                total_loan_releases,
                total_expenses,
                closing_balance
                FROM {$this->table}
                WHERE YEAR(blotter_date) = ?
                AND MONTH(blotter_date) = ?
                AND status = ?
                ORDER BY blotter_date";

        return $this->db->resultSet($sql, [$year, $month, self::$STATUS_FINALIZED]);
    }

    public function getCashFlowTrends($days = 30) {
        $sql = "SELECT
                blotter_date,
                opening_balance,
                closing_balance,
                (closing_balance - opening_balance) as net_change,
                total_collections,
                total_loan_releases,
                total_expenses
                FROM {$this->table}
                WHERE blotter_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                AND status = ?
                ORDER BY blotter_date";

        return $this->db->resultSet($sql, [$days, self::$STATUS_FINALIZED]);
    }

    public function getCurrentCashPosition() {
        // Get latest finalized blotter
        $sql = "SELECT * FROM {$this->table}
                WHERE status = ?
                ORDER BY blotter_date DESC LIMIT 1";

        $latestBlotter = $this->db->single($sql, [self::$STATUS_FINALIZED]);

        if ($latestBlotter) {
            return $latestBlotter['closing_balance'];
        }

        // If no finalized blotter, get today's draft or create estimate
        $today = date('Y-m-d');
        $todayBlotter = $this->getBlotterByDate($today);

        return $todayBlotter ? $todayBlotter['closing_balance'] : 0;
    }

    public function getLatestBlotter() {
        $sql = "SELECT * FROM {$this->table}
                ORDER BY blotter_date DESC LIMIT 1";

        return $this->db->single($sql);
    }

    public function validateBlotterBalance($blotterId) {
        $blotter = $this->findById($blotterId);
        if (!$blotter) return false;

        $calculatedBalance = $blotter['opening_balance'] +
                           $blotter['total_collections'] -
                           $blotter['total_loan_releases'] -
                           $blotter['total_expenses'];

        return abs($calculatedBalance - $blotter['closing_balance']) < 0.01; // Allow for small rounding differences
    }

    public function getBlotterDiscrepancies() {
        $sql = "SELECT *,
                (opening_balance + total_collections - total_loan_releases - total_expenses) as calculated_balance,
                ABS((opening_balance + total_collections - total_loan_releases - total_expenses) - closing_balance) as discrepancy
                FROM {$this->table}
                WHERE ABS((opening_balance + total_collections - total_loan_releases - total_expenses) - closing_balance) > 0.01
                ORDER BY blotter_date DESC";

        return $this->db->resultSet($sql);
    }

    public function create($data) {
        // Validate required fields
        $requiredFields = ['blotter_date', 'recorded_by'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->setLastError("Field '{$field}' is required.");
                return false;
            }
        }

        // Check if blotter already exists for this date
        if ($this->getBlotterByDate($data['blotter_date'])) {
            $this->setLastError('Cash blotter already exists for this date.');
            return false;
        }

        // Set default values
        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        $data['total_collections'] = $data['total_collections'] ?? 0;
        $data['total_loan_releases'] = $data['total_loan_releases'] ?? 0;
        $data['total_expenses'] = $data['total_expenses'] ?? 0;
        $data['closing_balance'] = $data['closing_balance'] ?? ($data['opening_balance'] + $data['total_collections'] - $data['total_loan_releases'] - $data['total_expenses']);
        $data['status'] = $data['status'] ?? self::$STATUS_DRAFT;
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        return parent::create($data);
    }

    public function update($id, $data) {
        // If updating financial fields, recalculate closing balance
        if (isset($data['opening_balance']) || isset($data['total_collections']) || isset($data['total_loan_releases']) || isset($data['total_expenses'])) {
            $blotter = $this->findById($id);
            if ($blotter) {
                $opening = $data['opening_balance'] ?? $blotter['opening_balance'];
                $collections = $data['total_collections'] ?? $blotter['total_collections'];
                $releases = $data['total_loan_releases'] ?? $blotter['total_loan_releases'];
                $expenses = $data['total_expenses'] ?? $blotter['total_expenses'];

                $data['closing_balance'] = $opening + $collections - $releases - $expenses;
            }
        }

        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        return parent::update($id, $data);
    }
}
