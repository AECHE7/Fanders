<?php
/**
 * SuperAdminModel - Represents a Super Admin user
 */
class SuperAdminModel extends UserModel {
    /**
     * Get all admins
     * 
     * @return array|bool
     */
    public function getAllAdmins() {
        return $this->getUsersByRole(ROLE_ADMIN);
    }
    
    /**
     * Create a new admin
     * 
     * @param array $data
     * @return int|bool
     */
    public function createAdmin($data) {
        // Set role to Admin
        $data['role_id'] = ROLE_ADMIN;
        $data['is_active'] = 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
    }
    
    /**
     * Get system statistics
     * 
     * @return array
     */
    public function getSystemStats() {
        $stats = [];
        
        // Total users
        $sql = "SELECT COUNT(*) as count FROM users";
        $result = $this->db->single($sql);
        $stats['total_users'] = $result ? $result['count'] : 0;
        
        // Total active users
        $sql = "SELECT COUNT(*) as count FROM users WHERE is_active = 1";
        $result = $this->db->single($sql);
        $stats['active_users'] = $result ? $result['count'] : 0;
        
        // Count by role
        $sql = "SELECT role_id, COUNT(*) as count FROM users GROUP BY role_id";
        $result = $this->db->resultSet($sql);
        
        if ($result) {
            $roleStats = [];
            foreach ($result as $row) {
                $roleName = 'Unknown';
                switch ($row['role_id']) {
                    case ROLE_SUPER_ADMIN:
                        $roleName = 'Super Admin';
                        break;
                    case ROLE_ADMIN:
                        $roleName = 'Admin';
                        break;
                    case ROLE_BORROWER:
                        $roleName = 'Borrower';
                        break;
                }
                $roleStats[$roleName] = $row['count'];
            }
            $stats['users_by_role'] = $roleStats;
        }
        
        // Total books
        $sql = "SELECT COUNT(*) as count FROM books";
        $result = $this->db->single($sql);
        $stats['total_books'] = $result ? $result['count'] : 0;
        
        // Total available books
        $sql = "SELECT COUNT(*) as count FROM books WHERE is_available = 1";
        $result = $this->db->single($sql);
        $stats['available_books'] = $result ? $result['count'] : 0;
        
        // Total borrowed books
        $sql = "SELECT COUNT(*) as count FROM transactions WHERE return_date IS NULL";
        $result = $this->db->single($sql);
        $stats['borrowed_books'] = $result ? $result['count'] : 0;
        
        // Total overdue books
        $sql = "SELECT COUNT(*) as count FROM transactions WHERE return_date IS NULL AND due_date < CURDATE()";
        $result = $this->db->single($sql);
        $stats['overdue_books'] = $result ? $result['count'] : 0;
        
        return $stats;
    }
    
    /**
     * Get audit logs
     * 
     * @param int $limit
     * @return array|bool
     */
    public function getAuditLogs($limit = 50) {
        $sql = "SELECT t.*, u.username, b.title as book_title
                FROM transactions t
                JOIN users u ON t.user_id = u.id
                JOIN books b ON t.book_id = b.id
                ORDER BY t.created_at DESC
                LIMIT ?";
        
        return $this->db->resultSet($sql, [$limit]);
    }
}
