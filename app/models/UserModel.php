<?php
/**
 * UserModel - Base class for all user types
 */
class UserModel extends BaseModel {
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = [
        'username', 'email', 'password', 'first_name', 'last_name', 
        'role_id', 'is_active', 'created_at', 'updated_at'
    ];
    protected $hidden = ['password'];

    /**
     * Get user by username
     * 
     * @param string $username
     * @return array|bool
     */
    public function getUserByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE username = ?";
        return $this->db->single($sql, [$username]);
    }

    /**
     * Get user by email
     * 
     * @param string $email
     * @return array|bool
     */
    public function getUserByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        return $this->db->single($sql, [$email]);
    }

    /**
     * Check if username exists
     * 
     * @param string $username
     * @param int $excludeId
     * @return bool
     */
    public function usernameExists($username, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE username = ?";
        $params = [$username];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->single($sql, $params);
        return $result && $result['count'] > 0;
    }

    /**
     * Check if email exists
     * 
     * @param string $email
     * @param int $excludeId
     * @return bool
     */
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

    /**
     * Get users by role
     * 
     * @param int $roleId
     * @return array|bool
     */
    public function getUsersByRole($roleId) {
        $sql = "SELECT * FROM {$this->table} WHERE role_id = ?";
        return $this->db->resultSet($sql, [$roleId]);
    }

    /**
     * Deactivate user (soft delete)
     * 
     * @param int $id
     * @return bool
     */
    public function deactivateUser($id) {
        $sql = "UPDATE {$this->table} SET is_active = 0 WHERE id = ?";
        return $this->db->query($sql, [$id]) ? true : false;
    }

    /**
     * Activate user
     * 
     * @param int $id
     * @return bool
     */
    public function activateUser($id) {
        $sql = "UPDATE {$this->table} SET is_active = 1 WHERE id = ?";
        return $this->db->query($sql, [$id]) ? true : false;
    }

    /**
     * Get active users
     * 
     * @return array|bool
     */
    public function getActiveUsers() {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1";
        return $this->db->resultSet($sql);
    }

    /**
     * Update user password
     * 
     * @param int $id
     * @param string $newPassword
     * @return bool
     */
    public function updatePassword($id, $newPassword) {
        $sql = "UPDATE {$this->table} SET password = ? WHERE id = ?";
        return $this->db->query($sql, [$newPassword, $id]) ? true : false;
    }

    /**
     * Get user with role name
     * 
     * @param int $id
     * @return array|bool
     */
    public function getUserWithRoleName($id) {
        $sql = "SELECT u.*, 
                CASE 
                    WHEN u.role_id = 1 THEN 'Super Admin'
                    WHEN u.role_id = 2 THEN 'Admin'
                    WHEN u.role_id = 3 THEN 'Borrower'
                    ELSE 'Unknown'
                END as role_name
                FROM {$this->table} u
                WHERE u.id = ?";
        return $this->db->single($sql, [$id]);
    }

    /**
     * Get all users with role names
     * 
     * @return array|bool
     */
    public function getAllUsersWithRoleNames() {
        $sql = "SELECT u.*, 
                CASE 
                    WHEN u.role_id = 1 THEN 'Super Admin'
                    WHEN u.role_id = 2 THEN 'Admin'
                    WHEN u.role_id = 3 THEN 'Borrower'
                    ELSE 'Unknown'
                END as role_name
                FROM {$this->table} u
                ORDER BY u.created_at DESC";
        return $this->db->resultSet($sql);
    }
}
