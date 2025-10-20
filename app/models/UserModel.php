<?php
/**
 * UserModel - Handles all user-related operations
 * Purpose: Manages staff access (Admin/Manager/Cashier) AND provides constants for Client types.
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

    // --- Definitive LMS Role Definitions (Staff & Client Types) ---
    public static $ROLE_SUPER_ADMIN = 'super-admin';
    public static $ROLE_ADMIN = 'admin';
    // Standardizing to ROLE_MANAGER (instead of the old ROLE_BRANCH_MANAGER)
    public static $ROLE_MANAGER = 'manager'; 
    public static $ROLE_CASHIER = 'cashier';
    public static $ROLE_ACCOUNT_OFFICER = 'account-officer';

    // Client/Borrower Roles (Used mainly for constants/stats reporting, but actual data is in `clients` table)
    public static $ROLE_CLIENT = 'client';
    public static $ROLE_STUDENT = 'student'; 
    public static $ROLE_STAFF = 'staff';     
    public static $ROLE_OTHER = 'other';     

    // Status definitions
    public static $STATUS_ACTIVE = 'active';
    public static $STATUS_INACTIVE = 'inactive';
    public static $STATUS_SUSPENDED = 'suspended';


    public function getUserByEmail($email) {
        return $this->findOneByField('email', $email);
    }

    public function getUserWithPassword($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        return $this->db->single($sql, [$email]);
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

    public function create($data) {
        $data['status'] = $data['status'] ?? self::$STATUS_ACTIVE;
        return parent::create($data);
    }

    /**
     * Get all users with readable role names.
     * @param array $roles Optional filter by specific roles.
     * @param int $limit Number of records to return
     * @param int $offset Number of records to skip
     * @return array
     */
    public function getAllUsersWithRoleNames($roles = [], $limit = null, $offset = 0) {
        $sql = "SELECT u.*, CASE
            WHEN u.role = 'super-admin' THEN 'Super Admin'
            WHEN u.role = 'admin' THEN 'Admin'
            WHEN u.role = 'manager' THEN 'Manager'
            WHEN u.role = 'cashier' THEN 'Cashier'
            WHEN u.role = 'account-officer' THEN 'Account Officer'
            WHEN u.role = 'client' THEN 'Client'
            WHEN u.role = 'student' THEN 'Student'
            WHEN u.role = 'staff' THEN 'Staff'
            WHEN u.role = 'other' THEN 'Other'
            ELSE u.role
        END as role_name FROM users u";

        $params = [];
        if (!empty($roles)) {
            $placeholders = str_repeat('?,', count($roles) - 1) . '?';
            $sql .= " WHERE u.role IN ($placeholders)";
            $params = $roles;
        }

        $sql .= " ORDER BY u.created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }

        return $this->query($sql, $params);
    }

    /**
     * Get all users with readable role names with pagination support.
     * @param array $roles Optional filter by specific roles.
     * @param int $limit Number of records per page
     * @param int $offset Number of records to skip
     * @return array
     */
    public function getAllUsersWithRoleNamesPaginated($roles = [], $limit = 20, $offset = 0) {
        return $this->getAllUsersWithRoleNames($roles, $limit, $offset);
    }

    /**
     * Get user stats for dashboard.
     * @return array
     */
    public function getUserStats() {
        $stats = [];

        // Total users
        $stats['total_users'] = $this->count();

        // Active users
        $stats['active_users'] = $this->count('status', self::$STATUS_ACTIVE);

        // Users by role
        $stats['role_counts'] = [];
        $roles = [
            self::$ROLE_SUPER_ADMIN,
            self::$ROLE_ADMIN,
            self::$ROLE_MANAGER,
            self::$ROLE_CASHIER,
            self::$ROLE_ACCOUNT_OFFICER,
            self::$ROLE_CLIENT,
            self::$ROLE_STUDENT,
            self::$ROLE_STAFF,
            self::$ROLE_OTHER
        ];

        foreach ($roles as $role) {
            $stats['role_counts'][$role] = $this->count('role', $role);
        }

        return $stats;
    }

    /**
     * Get all operational users with readable role names.
     * @param array $roles Optional filter by specific roles.
     * @param int $limit Number of records per page
     * @param int $offset Number of records to skip
     * @return array
     */
    public function getAllOperationalUsersWithRoleNames($roles = [], $limit = null, $offset = 0) {
        $operationalRoles = [
            self::$ROLE_SUPER_ADMIN,
            self::$ROLE_ADMIN,
            self::$ROLE_MANAGER,
            self::$ROLE_CASHIER,
            self::$ROLE_ACCOUNT_OFFICER
        ];

        if (empty($roles)) {
            $roles = $operationalRoles;
        } else {
            $roles = array_intersect($roles, $operationalRoles);
        }

        return $this->getAllUsersWithRoleNames($roles, $limit, $offset);
    }

    /**
     * Get all operational users with readable role names with pagination support.
     * @param array $roles Optional filter by specific roles.
     * @param int $limit Number of records per page
     * @param int $offset Number of records to skip
     * @return array
     */
    public function getAllOperationalUsersWithRoleNamesPaginated($roles = [], $limit = 20, $offset = 0) {
        return $this->getAllOperationalUsersWithRoleNames($roles, $limit, $offset);
    }

    /**
     * Get a single user with a readable role name.
     * @param int $id
     * @return array|false
     */
    public function getUserWithRoleName($id) {
        $sql = "SELECT u.*, CASE
            WHEN u.role = 'super-admin' THEN 'Super Admin'
            WHEN u.role = 'admin' THEN 'Admin'
            WHEN u.role = 'manager' THEN 'Manager'
            WHEN u.role = 'cashier' THEN 'Cashier'
            WHEN u.role = 'account-officer' THEN 'Account Officer'
            WHEN u.role = 'client' THEN 'Client'
            WHEN u.role = 'student' THEN 'Student'
            WHEN u.role = 'staff' THEN 'Staff'
            WHEN u.role = 'other' THEN 'Other'
            ELSE u.role
        END as role_name FROM users u WHERE u.id = ?";

        $result = $this->query($sql, [$id], true);
        return $result;
    }

    /**
     * Check if email exists (for uniqueness validation).
     * @param string $email
     * @param int|null $excludeId
     * @return bool
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = ?";
        $params = [$email];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = $this->query($sql, $params, true);
        return $result && $result['count'] > 0;
    }

    /**
     * Check if phone number exists (for uniqueness validation).
     * @param string $phoneNumber
     * @param int|null $excludeId
     * @return bool
     */
    public function phoneNumberExists($phoneNumber, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM users WHERE phone_number = ?";
        $params = [$phoneNumber];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = $this->query($sql, $params, true);
        return $result && $result['count'] > 0;
    }
}