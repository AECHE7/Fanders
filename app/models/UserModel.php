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
}