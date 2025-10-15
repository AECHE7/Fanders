<?php
/**
 * CollectionSheetModel - Handles account officer collection sheets
 */
require_once __DIR__ . '/../core/BaseModel.php';

class CollectionSheetModel extends BaseModel {
    protected $table = 'collection_sheets';
    protected $primaryKey = 'id';
    protected $fillable = [
        'account_officer_id', 'collection_date', 'total_expected_payments',
        'total_collected', 'total_overdue', 'status', 'submitted_at',
        'approved_at', 'approved_by', 'notes', 'created_at', 'updated_at'
    ];
    protected $hidden = [];

    // Status definitions
    public static $STATUS_DRAFT = 'draft';
    public static $STATUS_SUBMITTED = 'submitted';
    public static $STATUS_APPROVED = 'approved';

    public function getCollectionSheetWithDetails($id) {
        $sql = "SELECT cs.*,
                u1.name as account_officer_name,
                u2.name as approved_by_name
                FROM {$this->table} cs
                LEFT JOIN users u1 ON cs.account_officer_id = u1.id
                LEFT JOIN users u2 ON cs.approved_by = u2.id
                WHERE cs.id = ?";

        return $this->db->single($sql, [$id]);
    }

    public function getCollectionSheetsByOfficer($officerId, $startDate = null, $endDate = null) {
        $sql = "SELECT cs.*,
                u.name as approved_by_name
                FROM {$this->table} cs
                LEFT JOIN users u ON cs.approved_by = u.id
                WHERE cs.account_officer_id = ?";

        $params = [$officerId];

        if ($startDate && $endDate) {
            $sql .= " AND cs.collection_date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        $sql .= " ORDER BY cs.collection_date DESC";

        return $this->db->resultSet($sql, $params);
    }

    public function getCollectionSheetsByDate($date) {
        $sql = "SELECT cs.*,
                u1.name as account_officer_name,
                u2.name as approved_by_name
                FROM {$this->table} cs
                LEFT JOIN users u1 ON cs.account_officer_id = u1.id
                LEFT JOIN users u2 ON cs.approved_by = u2.id
                WHERE cs.collection_date = ?
                ORDER BY cs.created_at DESC";

        return $this->db->resultSet($sql, [$date]);
    }

    public function getCollectionSheetsByStatus($status) {
        $sql = "SELECT cs.*,
                u1.name as account_officer_name,
                u2.name as approved_by_name
                FROM {$this->table} cs
                LEFT JOIN users u1 ON cs.account_officer_id = u1.id
                LEFT JOIN users u2 ON cs.approved_by = u2.id
                WHERE cs.status = ?
                ORDER BY cs.collection_date DESC";

        return $this->db->resultSet($sql, [$status]);
    }

    public function createCollectionSheet($officerId, $collectionDate) {
        // Check if collection sheet already exists for this officer and date
        $existing = $this->findOneByFields([
            'account_officer_id' => $officerId,
            'collection_date' => $collectionDate
        ]);

        if ($existing) {
            $this->setLastError('Collection sheet already exists for this officer and date.');
            return false;
        }

        $data = [
            'account_officer_id' => $officerId,
            'collection_date' => $collectionDate,
            'total_expected_payments' => 0,
            'total_collected' => 0,
            'total_overdue' => 0,
            'status' => self::$STATUS_DRAFT,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->create($data);
    }

    public function generateCollectionSheet($officerId, $collectionDate) {
        // Create collection sheet if it doesn't exist
        $sheetId = $this->createCollectionSheet($officerId, $collectionDate);
        if (!$sheetId) {
            return false;
        }

        // Get all active loans assigned to this officer
        $sql = "SELECT l.*,
                c.name as client_name, c.phone_number,
                COALESCE(p.last_payment_week, 0) as last_payment_week,
                l.weekly_payment as expected_payment
                FROM loans l
                JOIN clients c ON l.client_id = c.id
                LEFT JOIN (
                    SELECT loan_id, MAX(week_number) as last_payment_week
                    FROM payments
                    GROUP BY loan_id
                ) p ON l.id = p.loan_id
                WHERE l.status = 'active'
                AND l.disbursement_date IS NOT NULL
                AND DATEDIFF(?, l.disbursement_date) >= COALESCE(p.last_payment_week, 0) * 7
                ORDER BY c.name";

        $loans = $this->db->resultSet($sql, [$collectionDate]);

        $totalExpected = 0;
        $totalOverdue = 0;

        // Insert collection sheet details
        foreach ($loans as $loan) {
            $weeksDue = floor((strtotime($collectionDate) - strtotime($loan['disbursement_date'])) / (7 * 24 * 60 * 60));
            $expectedPayment = $loan['weekly_payment'];

            // Check if payment is overdue (more than 7 days past due date)
            $dueDate = date('Y-m-d', strtotime($loan['disbursement_date'] . ' +' . ($weeksDue) . ' weeks'));
            $isOverdue = strtotime($collectionDate) > strtotime($dueDate . ' +7 days');

            $detailData = [
                'collection_sheet_id' => $sheetId,
                'loan_id' => $loan['id'],
                'client_id' => $loan['client_id'],
                'expected_payment' => $expectedPayment,
                'actual_payment' => 0,
                'payment_status' => $isOverdue ? 'overdue' : 'unpaid',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->db->query("INSERT INTO collection_sheet_details (" .
                implode(', ', array_keys($detailData)) . ") VALUES (" .
                str_repeat('?, ', count($detailData) - 1) . "?)",
                array_values($detailData)
            );

            $totalExpected += $expectedPayment;
            if ($isOverdue) {
                $totalOverdue += $expectedPayment;
            }
        }

        // Update collection sheet totals
        $this->update($sheetId, [
            'total_expected_payments' => $totalExpected,
            'total_overdue' => $totalOverdue,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $sheetId;
    }

    public function submitCollectionSheet($sheetId, $submittedBy = null) {
        $data = [
            'status' => self::$STATUS_SUBMITTED,
            'submitted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->update($sheetId, $data);
    }

    public function approveCollectionSheet($sheetId, $approvedBy) {
        $data = [
            'status' => self::$STATUS_APPROVED,
            'approved_at' => date('Y-m-d H:i:s'),
            'approved_by' => $approvedBy,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->update($sheetId, $data);
    }

    public function getCollectionSheetDetails($sheetId) {
        $sql = "SELECT csd.*,
                l.loan_amount, l.total_amount, l.weekly_payment,
                c.name as client_name, c.phone_number, c.email
                FROM collection_sheet_details csd
                JOIN loans l ON csd.loan_id = l.id
                JOIN clients c ON csd.client_id = c.id
                WHERE csd.collection_sheet_id = ?
                ORDER BY c.name";

        return $this->db->resultSet($sql, [$sheetId]);
    }

    public function updatePaymentInSheet($sheetId, $loanId, $paymentAmount, $notes = null) {
        // Update collection sheet detail
        $sql = "UPDATE collection_sheet_details
                SET actual_payment = ?, payment_status = 'paid', notes = ?, updated_at = ?
                WHERE collection_sheet_id = ? AND loan_id = ?";

        $result = $this->db->query($sql, [
            $paymentAmount, $notes, date('Y-m-d H:i:s'), $sheetId, $loanId
        ]);

        if ($result) {
            // Recalculate sheet totals
            $this->recalculateSheetTotals($sheetId);
        }

        return $result;
    }

    public function recalculateSheetTotals($sheetId) {
        // Calculate new totals
        $sql = "SELECT
                SUM(expected_payment) as total_expected,
                SUM(actual_payment) as total_collected,
                SUM(CASE WHEN payment_status = 'overdue' THEN expected_payment ELSE 0 END) as total_overdue
                FROM collection_sheet_details
                WHERE collection_sheet_id = ?";

        $totals = $this->db->single($sql, [$sheetId]);

        if ($totals) {
            $this->update($sheetId, [
                'total_expected_payments' => $totals['total_expected'] ?? 0,
                'total_collected' => $totals['total_collected'] ?? 0,
                'total_overdue' => $totals['total_overdue'] ?? 0,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    public function getCollectionSheetSummary($startDate = null, $endDate = null) {
        $sql = "SELECT
                COUNT(*) as total_sheets,
                SUM(total_expected_payments) as total_expected,
                SUM(total_collected) as total_collected,
                SUM(total_overdue) as total_overdue,
                AVG(total_collected / total_expected_payments * 100) as average_collection_rate
                FROM {$this->table}
                WHERE status = ?";

        $params = [self::$STATUS_APPROVED];

        if ($startDate && $endDate) {
            $sql .= " AND collection_date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        return $this->db->single($sql, $params);
    }

    public function getOfficerPerformance($officerId, $startDate = null, $endDate = null) {
        $sql = "SELECT
                COUNT(*) as total_sheets,
                SUM(total_expected_payments) as total_expected,
                SUM(total_collected) as total_collected,
                SUM(total_overdue) as total_overdue,
                AVG(total_collected / NULLIF(total_expected_payments, 0) * 100) as collection_rate
                FROM {$this->table}
                WHERE account_officer_id = ? AND status = ?";

        $params = [$officerId, self::$STATUS_APPROVED];

        if ($startDate && $endDate) {
            $sql .= " AND collection_date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        return $this->db->single($sql, $params);
    }

    public function getPendingApprovals() {
        return $this->getCollectionSheetsByStatus(self::$STATUS_SUBMITTED);
    }

    public function getOverdueCollections() {
        $sql = "SELECT cs.*,
                u.name as account_officer_name,
                DATEDIFF(CURDATE(), cs.collection_date) as days_overdue
                FROM {$this->table} cs
                LEFT JOIN users u ON cs.account_officer_id = u.id
                WHERE cs.status IN (?, ?)
                AND DATEDIFF(CURDATE(), cs.collection_date) > 1
                ORDER BY cs.collection_date DESC";

        return $this->db->resultSet($sql, [self::$STATUS_DRAFT, self::$STATUS_SUBMITTED]);
    }

    public function getByOfficer($officerId, $filters = []) {
        $sql = "SELECT cs.*,
                u.name as approved_by_name
                FROM {$this->table} cs
                LEFT JOIN users u ON cs.approved_by = u.id
                WHERE cs.account_officer_id = ?";

        $params = [$officerId];

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

        $sql .= " ORDER BY cs.collection_date DESC, cs.created_at DESC";

        return $this->db->resultSet($sql, $params);
    }

    public function create($data) {
        // Validate required fields
        $requiredFields = ['account_officer_id', 'collection_date'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->setLastError("Field '{$field}' is required.");
                return false;
            }
        }

        // Check if collection sheet already exists
        $existing = $this->findOneByFields([
            'account_officer_id' => $data['account_officer_id'],
            'collection_date' => $data['collection_date']
        ]);

        if ($existing) {
            $this->setLastError('Collection sheet already exists for this officer and date.');
            return false;
        }

        // Set default values
        $data['total_expected_payments'] = $data['total_expected_payments'] ?? 0;
        $data['total_collected'] = $data['total_collected'] ?? 0;
        $data['total_overdue'] = $data['total_overdue'] ?? 0;
        $data['status'] = $data['status'] ?? self::$STATUS_DRAFT;
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        return parent::create($data);
    }
}
