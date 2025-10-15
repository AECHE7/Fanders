<?php
/**
 * ClientModel - Handles client (borrower) operations
 * Replaces borrower functionality from UserModel
 */
require_once __DIR__ . '/../core/BaseModel.php';

class ClientModel extends BaseModel {
    protected $table = 'clients';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name', 'email', 'phone_number', 'address', 'date_of_birth',
        'identification_type', 'identification_number', 'status',
        'created_at', 'updated_at'
    ];
    protected $hidden = [];

    // Status definitions
    public static $STATUS_ACTIVE = 'active';
    public static $STATUS_INACTIVE = 'inactive';
    public static $STATUS_BLACKLISTED = 'blacklisted';

    public function getClientByPhone($phoneNumber) {
        return $this->findOneByField('phone_number', $phoneNumber);
    }

    public function getClientByEmail($email) {
        return $this->findOneByField('email', $email);
    }

    public function phoneNumberExists($phoneNumber, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE phone_number = ?";
        $params = [$phoneNumber];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = $this->db->single($sql, $params);
        return $result && $result['count'] > 0;
    }

    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = ?";
        $params = [$email];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = $this->db->single($sql, $params);
        return $result && $result['count'] > 0;
    }

    public function identificationExists($identificationNumber, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE identification_number = ?";
        $params = [$identificationNumber];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = $this->db->single($sql, $params);
        return $result && $result['count'] > 0;
    }

    public function getClientsByStatus($status) {
        return $this->findByField('status', $status);
    }

    public function getActiveClients() {
        return $this->findByField('status', self::$STATUS_ACTIVE);
    }

    public function searchClients($term) {
        $sql = "SELECT * FROM {$this->table}
                WHERE name LIKE ? OR email LIKE ? OR phone_number LIKE ?
                ORDER BY name ASC";

        $searchTerm = "%{$term}%";
        $params = [$searchTerm, $searchTerm, $searchTerm];

        return $this->db->resultSet($sql, $params);
    }

    public function getClientWithLoans($id) {
        $sql = "SELECT c.*,
                COUNT(l.id) as total_loans,
                COUNT(CASE WHEN l.status = 'active' THEN 1 END) as active_loans,
                SUM(CASE WHEN l.status = 'active' THEN l.loan_amount ELSE 0 END) as total_active_loan_amount
                FROM {$this->table} c
                LEFT JOIN loans l ON c.id = l.client_id
                WHERE c.id = ?
                GROUP BY c.id";

        return $this->db->single($sql, [$id]);
    }

    public function getClientLoanHistory($clientId) {
        $sql = "SELECT l.*,
                COUNT(p.id) as payments_made,
                SUM(p.payment_amount) as total_paid,
                l.total_amount - COALESCE(SUM(p.payment_amount), 0) as outstanding_balance
                FROM loans l
                LEFT JOIN payments p ON l.id = p.loan_id
                WHERE l.client_id = ?
                GROUP BY l.id
                ORDER BY l.created_at DESC";

        return $this->db->resultSet($sql, [$clientId]);
    }

    public function getClientCurrentLoans($clientId) {
        $sql = "SELECT l.*,
                COUNT(p.id) as payments_made,
                SUM(p.payment_amount) as total_paid,
                l.total_amount - COALESCE(SUM(p.payment_amount), 0) as outstanding_balance
                FROM loans l
                LEFT JOIN payments p ON l.id = p.loan_id
                WHERE l.client_id = ? AND l.status = 'active'
                GROUP BY l.id
                ORDER BY l.created_at DESC";

        return $this->db->resultSet($sql, [$clientId]);
    }

    public function getClientPaymentHistory($clientId) {
        $sql = "SELECT p.*, l.loan_amount, l.total_amount
                FROM payments p
                JOIN loans l ON p.loan_id = l.id
                WHERE p.client_id = ?
                ORDER BY p.payment_date DESC";

        return $this->db->resultSet($sql, [$clientId]);
    }

    public function getClientStats() {
        $stats = [];

        // Total clients
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->single($sql);
        $stats['total_clients'] = $result ? $result['count'] : 0;

        // Active clients
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE status = ?";
        $result = $this->db->single($sql, [self::$STATUS_ACTIVE]);
        $stats['active_clients'] = $result ? $result['count'] : 0;

        // Clients by status
        $sql = "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status";
        $result = $this->db->resultSet($sql);
        $stats['clients_by_status'] = $result ?: [];

        // Recently added clients
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT 5";
        $stats['recent_clients'] = $this->db->resultSet($sql);

        return $stats;
    }

    public function create($data) {
        // Ensure required fields are present
        $requiredFields = ['name', 'phone_number'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->setLastError("Field '{$field}' is required.");
                return false;
            }
        }

        // Set default values if not provided
        $data['status'] = $data['status'] ?? self::$STATUS_ACTIVE;
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        // Validate email format if provided
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->setLastError('Invalid email format.');
            return false;
        }

        // Check if phone number already exists
        if ($this->phoneNumberExists($data['phone_number'])) {
            $this->setLastError('Phone number already exists.');
            return false;
        }

        // Check if email already exists (if provided)
        if (!empty($data['email']) && $this->emailExists($data['email'])) {
            $this->setLastError('Email already exists.');
            return false;
        }

        // Check if identification number already exists (if provided)
        if (!empty($data['identification_number']) && $this->identificationExists($data['identification_number'])) {
            $this->setLastError('Identification number already exists.');
            return false;
        }

        // Call parent create method
        return parent::create($data);
    }

    public function getClientsWithOutstandingLoans() {
        $sql = "SELECT DISTINCT c.*,
                COUNT(l.id) as active_loans,
                SUM(l.total_amount - COALESCE(p.total_paid, 0)) as total_outstanding
                FROM {$this->table} c
                JOIN loans l ON c.id = l.client_id
                LEFT JOIN (
                    SELECT loan_id, SUM(payment_amount) as total_paid
                    FROM payments
                    GROUP BY loan_id
                ) p ON l.id = p.loan_id
                WHERE c.status = ? AND l.status = 'active'
                AND (l.total_amount - COALESCE(p.total_paid, 0)) > 0
                GROUP BY c.id
                ORDER BY total_outstanding DESC";

        return $this->db->resultSet($sql, [self::$STATUS_ACTIVE]);
    }

    public function getOverdueClients() {
        $sql = "SELECT DISTINCT c.*,
                COUNT(DISTINCT l.id) as overdue_loans,
                SUM(l.weekly_payment) as total_weekly_payment
                FROM {$this->table} c
                JOIN loans l ON c.id = l.client_id
                LEFT JOIN payments p ON l.id = p.loan_id
                WHERE c.status = ?
                AND l.status = 'active'
                AND l.disbursement_date IS NOT NULL
                AND DATEDIFF(CURDATE(), l.disbursement_date) > (p.week_number * 7)
                GROUP BY c.id";

        return $this->db->resultSet($sql, [self::$STATUS_ACTIVE]);
    }

    public function getAllForSelect() {
        $sql = "SELECT id, name FROM {$this->table} WHERE status = ? ORDER BY name ASC";
        return $this->db->resultSet($sql, [self::$STATUS_ACTIVE]);
    }
}
