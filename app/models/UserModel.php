<?php
/**
 * UserModel - Handles all user-related operations
 * Inherits from BaseModel for core CRUD operations
 */
require_once __DIR__ . '/../core/BaseModel.php';

class UserModel extends BaseModel {
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name', 'email', 'phone_number', 'password', 'role',
        'status', 'last_login', 'password_changed_at', 'created_at', 'updated_at'
    ];
    protected $hidden = ['password'];

    // Role definitions
    public static $ROLE_SUPER_ADMIN = 'super-admin';
    public static $ROLE_ADMIN = 'admin';
    public static $ROLE_BRANCH_MANAGER = 'branch-manager';
    public static $ROLE_ACCOUNT_OFFICER = 'account-officer';
    public static $ROLE_CASHIER = 'cashier';
    public static $ROLE_CLIENT = 'client';
    public static $ROLE_STUDENT = 'student';
    public static $ROLE_STAFF = 'staff';
    public static $ROLE_OTHER = 'other';
    public static $ROLE_BORROWER = 'borrower';

    // Status definitions
    public static $STATUS_ACTIVE = 'active';
    public static $STATUS_INACTIVE = 'inactive';
    public static $STATUS_SUSPENDED = 'suspended';

    public function getUserByEmail($email) {
        return $this->findOneByField('email', $email);
    }

 
    public function getUserByPhone($phoneNumber) {
        return $this->findOneByField('phone_number', $phoneNumber);
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

    public function getUsersByRole($role) {
        return $this->findByField('role', $role);
    }

 
    public function getActiveUsers() {
        return $this->findByField('status', self::$STATUS_ACTIVE);
    }

 
    public function updateLastLogin($userId) {
        return $this->update($userId, [
            'last_login' => date('Y-m-d H:i:s')
        ]);
    }

    public function updatePassword($userId, $hashedPassword) {
        return $this->update($userId, [
            'password' => $hashedPassword,
            'password_changed_at' => date('Y-m-d H:i:s')
        ]);
    }


    public function getUserStats() {
        $stats = [];
        
        // Total users
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->single($sql);
        $stats['total_users'] = $result ? $result['count'] : 0;
        
        // Active users
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE status = ?";
        $result = $this->db->single($sql, [self::$STATUS_ACTIVE]);
        $stats['active_users'] = $result ? $result['count'] : 0;
        
        // Users by role
        $sql = "SELECT role, COUNT(*) as count FROM {$this->table} GROUP BY role";
        $result = $this->db->resultSet($sql);
        $stats['users_by_role'] = $result ?: [];
        
        // Users by status
        $sql = "SELECT status, COUNT(*) as count FROM {$this->table} GROUP BY status";
        $result = $this->db->resultSet($sql);
        $stats['users_by_status'] = $result ?: [];
        
        // Recently registered users
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT 5";
        $stats['recent_users'] = $this->db->resultSet($sql);
        
        return $stats;
    }


    public function searchUsers($term) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE name LIKE ? 
                OR email LIKE ? 
                OR phone_number LIKE ?
                ORDER BY name ASC";
                
        $searchTerm = "%{$term}%";
        $params = [$searchTerm, $searchTerm, $searchTerm];
        
        return $this->db->resultSet($sql, $params);
    }

    public function getUserTransactionHistory($userId) {
        $sql = "SELECT t.*, b.title as book_title, b.author
                FROM transactions t
                JOIN books b ON t.book_id = b.id
                WHERE t.user_id = ?
                ORDER BY t.borrow_date DESC";
                
        return $this->db->resultSet($sql, [$userId]);
    }

    public function getUserCurrentBorrows($userId) {
        $sql = "SELECT t.*, b.title as book_title, b.author
                FROM transactions t
                JOIN books b ON t.book_id = b.id
                WHERE t.user_id = ? 
                AND t.return_date IS NULL
                ORDER BY t.due_date ASC";
                
        return $this->db->resultSet($sql, [$userId]);
    }


    public function getUserPenaltyHistory($userId) {
        $sql = "SELECT p.*, t.borrow_date, t.due_date, b.title as book_title
                FROM penalties p
                JOIN transactions t ON p.transaction_id = t.id
                JOIN books b ON t.book_id = b.id
                WHERE t.user_id = ?
                ORDER BY p.created_at DESC";
                
        return $this->db->resultSet($sql, [$userId]);
    }

 
    public function create($data) {
        // Ensure required fields are present
        $requiredFields = ['name', 'email', 'password', 'role'];
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

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->setLastError('Invalid email format.');
            return false;
        }

        // Check if email already exists
        if ($this->emailExists($data['email'])) {
            $this->setLastError('Email already exists.');
            return false;
        }

        // Validate role
        if (!in_array($data['role'], [
            self::$ROLE_SUPER_ADMIN,
            self::$ROLE_ADMIN,
            self::$ROLE_BRANCH_MANAGER,
            self::$ROLE_ACCOUNT_OFFICER,
            self::$ROLE_CASHIER,
            self::$ROLE_CLIENT,
            self::$ROLE_STUDENT,
            self::$ROLE_STAFF,
            self::$ROLE_OTHER
        ])) {
            $this->setLastError('Invalid role.');
            return false;
        }

        // Call parent create method
        return parent::create($data);
    }

    public function createAdmin($data) {
        $data['role'] = self::$ROLE_ADMIN;
        $data['status'] = self::$STATUS_ACTIVE;
        return $this->create($data);
    }

 
    public function createBorrower($data) {
        // Set default role to student if not specified
        if (!isset($data['role'])) {
            $data['role'] = self::$ROLE_STUDENT;
        } else {
            // Validate role is a borrower role
            if (!in_array($data['role'], [
                self::$ROLE_STUDENT,
                self::$ROLE_STAFF,
                self::$ROLE_OTHER
            ])) {
                $this->setLastError('Invalid borrower role.');
                return false;
            }
        }
        
        $data['status'] = self::$STATUS_ACTIVE;
        return $this->create($data);
    }

   
    public function getAllAdmins() {
        return $this->getUsersByRole(self::$ROLE_ADMIN);
    }


    public function getAllBorrowers() {
        $sql = "SELECT * FROM {$this->table}
                WHERE role IN (?, ?, ?, ?, ?, ?)
                ORDER BY name ASC";

        return $this->db->resultSet($sql, [
            self::$ROLE_CLIENT,
            self::$ROLE_STUDENT,
            self::$ROLE_STAFF,
            self::$ROLE_OTHER,
            self::$ROLE_BORROWER,
            self::$ROLE_BRANCH_MANAGER
        ]);
    }

    public function getUsersByRoles(array $roles) {
        if (empty($roles)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $sql = "SELECT * FROM {$this->table} WHERE role IN ({$placeholders}) ORDER BY name ASC";
        return $this->db->resultSet($sql, $roles);
    }

   
    public function getSystemStats() {
        $stats = $this->getUserStats();
        
        // Add additional system-wide statistics
        $stats['total_admins'] = count($this->getAllAdmins());
        $stats['total_borrowers'] = count($this->getAllBorrowers());
        
        // Get borrowers by role
        $sql = "SELECT role, COUNT(*) as count
                FROM {$this->table}
                WHERE role IN (?, ?, ?, ?, ?, ?)
                GROUP BY role";

        $result = $this->db->resultSet($sql, [
            self::$ROLE_CLIENT,
            self::$ROLE_STUDENT,
            self::$ROLE_STAFF,
            self::$ROLE_OTHER,
            self::$ROLE_BORROWER,
            self::$ROLE_BRANCH_MANAGER
        ]);

        $stats['borrowers_by_role'] = $result ?: [];
        
        return $stats;
    }

   
    public function getAdminStats() {
        $stats = [];
        
        // Get total borrowers count
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table} 
                WHERE role IN (?, ?, ?)";
                
        $result = $this->db->single($sql, [
            self::$ROLE_STUDENT,
            self::$ROLE_STAFF,
            self::$ROLE_OTHER
        ]);
        
        $stats['total_borrowers'] = $result ? $result['count'] : 0;
        
        // Get active borrowers count
        $sql = "SELECT COUNT(*) as count
                FROM {$this->table}
                WHERE role IN (?, ?, ?, ?, ?, ?)
                AND status = ?";

        $result = $this->db->single($sql, [
            self::$ROLE_CLIENT,
            self::$ROLE_STUDENT,
            self::$ROLE_STAFF,
            self::$ROLE_OTHER,
            self::$ROLE_BORROWER,
            self::$ROLE_BRANCH_MANAGER,
            self::$STATUS_ACTIVE
        ]);

        $stats['active_borrowers'] = $result ? $result['count'] : 0;

        // Get borrowers by role
        $borrowerRoles = [self::$ROLE_CLIENT, self::$ROLE_STUDENT, self::$ROLE_STAFF, self::$ROLE_OTHER, self::$ROLE_BORROWER, self::$ROLE_BRANCH_MANAGER];
        foreach ($borrowerRoles as $role) {
            $stats['borrowers_by_role'][$role] = $this->count('role', $role);
        }

        // Get borrowers by status
        $statuses = [self::$STATUS_ACTIVE, self::$STATUS_INACTIVE, self::$STATUS_SUSPENDED];
        foreach ($statuses as $status) {
            $sql = "SELECT COUNT(*) as count
                    FROM {$this->table}
                    WHERE role IN (?, ?, ?, ?, ?, ?)
                    AND status = ?";

            $result = $this->db->single($sql, [
                self::$ROLE_CLIENT,
                self::$ROLE_STUDENT,
                self::$ROLE_STAFF,
                self::$ROLE_OTHER,
                self::$ROLE_BORROWER,
                self::$ROLE_BRANCH_MANAGER,
                $status
            ]);

            $stats['borrowers_by_status'][$status] = $result ? $result['count'] : 0;
        }

        // Get recently registered borrowers
        $sql = "SELECT * FROM {$this->table}
                WHERE role IN (?, ?, ?, ?, ?, ?)
                ORDER BY created_at DESC LIMIT 5";

        $stats['recent_borrowers'] = $this->db->resultSet($sql, [
            self::$ROLE_CLIENT,
            self::$ROLE_STUDENT,
            self::$ROLE_STAFF,
            self::$ROLE_OTHER,
            self::$ROLE_BORROWER,
            self::$ROLE_BRANCH_MANAGER
        ]);
        
        return $stats;
    }


    public function getUserWithRoleName($id) {
        $sql = "SELECT u.*,
                CASE
                    WHEN u.role = ? THEN 'Super Admin'
                    WHEN u.role = ? THEN 'Admin'
                    WHEN u.role = ? THEN 'Branch Manager'
                    WHEN u.role = ? THEN 'Account Officer'
                    WHEN u.role = ? THEN 'Cashier'
                    WHEN u.role = ? THEN 'Client'
                    WHEN u.role = ? THEN 'Student'
                    WHEN u.role = ? THEN 'Staff'
                    WHEN u.role = ? THEN 'Other'
                    WHEN u.role = ? THEN 'Borrower'
                    ELSE 'Unknown'
                END as role_display
                FROM {$this->table} u
                WHERE u.id = ?";

        return $this->db->single($sql, [
            self::$ROLE_SUPER_ADMIN,
            self::$ROLE_ADMIN,
            self::$ROLE_BRANCH_MANAGER,
            self::$ROLE_ACCOUNT_OFFICER,
            self::$ROLE_CASHIER,
            self::$ROLE_CLIENT,
            self::$ROLE_STUDENT,
            self::$ROLE_STAFF,
            self::$ROLE_OTHER,
            self::$ROLE_BORROWER,
            $id
        ]);
    }

    public function getAllUsersWithRoleNames($roles = []) {
        $sql = "SELECT u.*,
                CASE
                    WHEN u.role = ? THEN 'Super Admin'
                    WHEN u.role = ? THEN 'Admin'
                    WHEN u.role = ? THEN 'Branch Manager'
                    WHEN u.role = ? THEN 'Account Officer'
                    WHEN u.role = ? THEN 'Cashier'
                    WHEN u.role = ? THEN 'Client'
                    WHEN u.role = ? THEN 'Student'
                    WHEN u.role = ? THEN 'Staff'
                    WHEN u.role = ? THEN 'Other'
                    WHEN u.role = ? THEN 'Borrower'
                    ELSE 'Unknown'
                END as role_display
                FROM {$this->table} u";

        $params = [
            self::$ROLE_SUPER_ADMIN,
            self::$ROLE_ADMIN,
            self::$ROLE_BRANCH_MANAGER,
            self::$ROLE_ACCOUNT_OFFICER,
            self::$ROLE_CASHIER,
            self::$ROLE_CLIENT,
            self::$ROLE_STUDENT,
            self::$ROLE_STAFF,
            self::$ROLE_OTHER,
            self::$ROLE_BORROWER
        ];

        if (!empty($roles)) {
            $placeholders = str_repeat('?,', count($roles) - 1) . '?';
            $sql .= " WHERE u.role IN ({$placeholders})";
            $params = array_merge($params, $roles);
        }

        $sql .= " ORDER BY u.created_at DESC";

        return $this->db->resultSet($sql, $params);
    }


    public function getUsersCountByRole($role) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE role = ? AND status = ?";
        $result = $this->db->single($sql, [$role, self::$STATUS_ACTIVE]);
        return $result ? $result['count'] : 0;
    }

    public function getTotalBorrowersCount() {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}
                WHERE role IN (?, ?, ?, ?, ?, ?)
                AND status = ?";
        $params = [
            self::$ROLE_CLIENT,
            self::$ROLE_STUDENT,
            self::$ROLE_STAFF,
            self::$ROLE_OTHER,
            self::$ROLE_BORROWER,
            self::$ROLE_BRANCH_MANAGER,
            self::$STATUS_ACTIVE
        ];
        $result = $this->db->single($sql, $params);
        return $result ? $result['count'] : 0;
    }


    public function getUsersByStatus($status) {
        return $this->findByField('status', $status);
    }
}
