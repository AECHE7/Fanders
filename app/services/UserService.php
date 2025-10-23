<?php
/**
 * UserService - Handles business logic for user-related operations,
 * including validation, creation, updating, and status management for
 * operational staff (Admin, Manager, Cashier, AO).
 */
require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../core/PasswordHash.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../utilities/CacheUtility.php';

class UserService extends BaseService {
    private $userModel;
    private $passwordHash;
    private $session;
    private $validOperationalRoles;
    private $cache;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->passwordHash = new PasswordHash();
        $this->session = new Session();
        $this->cache = new CacheUtility();
        $this->setModel($this->userModel);

        // Define valid roles for operational staff (excluding 'Client')
        $this->validOperationalRoles = [
            UserModel::$ROLE_ADMIN,
            UserModel::$ROLE_MANAGER,
            UserModel::$ROLE_CASHIER,
            UserModel::$ROLE_ACCOUNT_OFFICER,
        ];
    }

    /**
     * Retrieves all operational users with readable role names.
     * @param array $roles Optional filter by specific roles.
     * @param int $page Page number for pagination
     * @param int $limit Number of records per page
     * @return array
     */
    public function getAllOperationalUsersWithRoleNames($roles = [], $page = 1, $limit = null) {
        $cacheKey = 'operational_users_' . md5(serialize($roles) . "_page{$page}_limit{$limit}");

        return $this->cache->remember($cacheKey, 1800, function() use ($roles, $page, $limit) { // Cache for 30 minutes
            if ($limit) {
                $offset = ($page - 1) * $limit;
                return $this->userModel->getAllOperationalUsersWithRoleNamesPaginated($roles, $limit, $offset);
            }
            return $this->userModel->getAllOperationalUsersWithRoleNames($roles);
        });
    }

    /**
     * Get total count of users with role names and filters applied
     * @param array $roles Optional filter by specific roles.
     * @return int
     */
    public function getTotalUsersWithRoleNamesCount($roles = []) {
        $cacheKey = 'total_users_' . md5(serialize($roles));

        return $this->cache->remember($cacheKey, 1800, function() use ($roles) { // Cache for 30 minutes
            return $this->userModel->getTotalUsersWithRoleNamesCount($roles);
        });
    }

    /**
     * Retrieves a single user with a readable role name.
     * @param int $id
     * @return array|false
     */
    public function getUserWithRoleName($id) {
        return $this->userModel->getUserWithRoleName($id);
    }


    /**
     * Adds a new operational user (Admin, Manager, Cashier, AO).
     * @param array $userData User data including raw password and role.
     * @param int $createdBy User ID who created this user
     * @return int|false New user ID on success.
     */
    public function addUser($userData, $createdBy = null) {
        try {
            // 1. Get creator's role to determine allowed roles
            $creatorRole = null;
            if ($createdBy) {
                $creator = $this->userModel->findById($createdBy);
                $creatorRole = $creator ? $creator['role'] : null;
            }

            // 2. Determine allowed roles based on creator's role
            $allowedRoles = [];
            if ($creatorRole === UserModel::$ROLE_SUPER_ADMIN) {
                // Super Admin can create any operational role
                $allowedRoles = [
                    UserModel::$ROLE_SUPER_ADMIN,
                    UserModel::$ROLE_ADMIN,
                    UserModel::$ROLE_MANAGER,
                    UserModel::$ROLE_CASHIER,
                    UserModel::$ROLE_ACCOUNT_OFFICER
                ];
            } elseif ($creatorRole === UserModel::$ROLE_ADMIN) {
                // Admin can create operational staff only
                $allowedRoles = [
                    UserModel::$ROLE_MANAGER,
                    UserModel::$ROLE_CASHIER,
                    UserModel::$ROLE_ACCOUNT_OFFICER
                ];
            } else {
                // Fallback to original logic for backward compatibility
                // Allow super-admin creation when no creator is specified (for initial setup)
                $allowedRoles = array_merge($this->validOperationalRoles, [UserModel::$ROLE_SUPER_ADMIN]);
            }

            // 3. Ensure role is valid for the creator
            if (!isset($userData['role']) || !in_array($userData['role'], $allowedRoles)) {
                $this->setErrorMessage('Invalid or missing operational user role for your permission level.');
                return false;
            }

            // 4. Validate user data and check for unique email/phone
            if (!$this->validateUserData($userData)) {
                return false;
            }

            // 5. Hash password (Security Feature)
            $userData['password'] = $this->passwordHash->hash($userData['password']);

            // 6. Set default status and cleanup confirmation field
            $userData['status'] = UserModel::$STATUS_ACTIVE;
            unset($userData['password_confirmation']);

            // 7. Create user
            $newId = $this->userModel->create($userData);

            if (!$newId) {
                 $this->setErrorMessage($this->userModel->getLastError() ?: 'Failed to create user due to unknown database error.');
                 return false;
            }

            // 8. Clear relevant caches
            $this->cache->delete('user_stats');
            $this->cache->delete('staff_stats');

            // 9. Log transaction for audit trail
            if (class_exists('TransactionService')) {
                $transactionService = new TransactionService();
                $transactionService->logUserTransaction('created', $newId, $createdBy, [
                    'user_data' => array_diff_key($userData, ['password' => '']) // Exclude password from logs
                ]);
            }

            return $newId;

        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
            return false;
        }
    }


    /**
     * Updates an existing operational user's profile.
     * @param int $id User ID.
     * @param array $userData Updated user data.
     * @return bool
     */
    public function updateUser($id, $userData) {
        try {
            $existingUser = $this->userModel->findById($id);

            if (!$existingUser) {
                $this->setErrorMessage('User not found.');
                return false;
            }

            // 1. Validate user data (including unique checks, excluding current user ID)
            if (!$this->validateUserDataForUpdate($userData, $id)) {
                return false;
            }
            
            // 2. Hash password if provided, otherwise unset it.
            if (isset($userData['password']) && !empty($userData['password'])) {
                $userData['password'] = $this->passwordHash->hash($userData['password']);
            } else {
                unset($userData['password']); 
            }
            unset($userData['password_confirmation']); // Always remove confirmation field

            // 3. Update user
            $result = $this->userModel->update($id, $userData);
            
            // 4. Log user update transaction
            if ($result && class_exists('TransactionService')) {
                $transactionService = new TransactionService();
                $updatedFields = array_keys($userData);
                $transactionService->logGeneric('user_updated', $_SESSION['user_id'] ?? null, $id, [
                    'user_id' => $id,
                    'updated_fields' => $updatedFields,
                    'password_changed' => in_array('password', $updatedFields)
                ]);
            }
            
            return $result;

        } catch (\Exception $e) {
            $this->setErrorMessage($e->getMessage());
            return false;
        }
    }

    
    /**
     * Deactivates a user account to preserve historical audit trail (Section 6).
     * @param int $id
     * @return bool
     */
    public function deactivateUser($id) {
        $result = $this->userModel->updateStatus($id, UserModel::$STATUS_INACTIVE);
        if ($result) {
            $this->cache->delete('user_stats');
            $this->cache->delete('staff_stats');
        }
        return $result;
    }

    /**
     * Activates a user account.
     * @param int $id
     * @return bool
     */
    public function activateUser($id) {
        $result = $this->userModel->updateStatus($id, UserModel::$STATUS_ACTIVE);
        if ($result) {
            $this->cache->delete('user_stats');
            $this->cache->delete('staff_stats');
        }
        return $result;
    }

    /**
     * Generates a random secure password, hashes it, and updates the user record.
     * @param int $id User ID
     * @return array|false Returns ['success' => true, 'password' => 'new_password'] or false on failure.
     */
    public function resetPassword($id) {
        try {
            $user = $this->userModel->findById($id);
            
            if (!$user) {
                $this->setErrorMessage('User not found.');
                return false;
            }
            
            // Generate random password (Section 6 Requirement)
            $newPassword = $this->generateRandomPassword();
            
            // Hash and update
            $hashedPassword = $this->passwordHash->hash($newPassword);
            $result = $this->userModel->updatePassword($id, $hashedPassword);
            
            if ($result) {
                // Log password reset transaction
                if (class_exists('TransactionService')) {
                    $transactionService = new TransactionService();
                    $transactionService->logGeneric('password_reset', $_SESSION['user_id'] ?? null, $id, [
                        'target_user_id' => $id,
                        'target_username' => $user['username'] ?? 'Unknown',
                        'reset_by' => $_SESSION['user_id'] ?? null
                    ]);
                }
                
                return [
                    'success' => true, 
                    'password' => $newPassword,
                    'message' => 'Password reset successful.'
                ];
            }
                
            $this->setErrorMessage('Failed to update password in database.');
            return false;
    
        } catch (\Exception $e) {
            $this->setErrorMessage('Error resetting password: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generates a secure, random password string for resets.
     * @param int $length
     * @return string
     */
    private function generateRandomPassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';
        
        try {
            $max = strlen($chars) - 1;
            for ($i = 0; $i < $length; $i++) {
                $password .= $chars[random_int(0, $max)];
            }
        } catch (Exception $e) {
            // Fallback if random_int fails, though highly unlikely
            $password = substr(str_shuffle($chars), 0, $length);
        }
        
        return $password;
    }


    /**
     * Global user statistics helper (for dashboards).
     * @return array
     */
    public function getUserStats() {
        $cacheKey = 'user_stats';

        return $this->cache->remember($cacheKey, 900, function() { // Cache for 15 minutes
            return $this->userModel->getUserStats();
        });
    }


    // --- Validation Logic ---

    /**
     * Validates data for a new user creation.
     * @param array $userData
     * @return bool
     */
    private function validateUserData($userData) {
        // Base required fields
        $requiredFields = ['name', 'email', 'password', 'role', 'phone_number'];
        foreach ($requiredFields as $field) {
            if (!isset($userData[$field]) || trim($userData[$field]) === '') {
                $this->setErrorMessage(ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                return false;
            }
        }
        
        // Validate email format and uniqueness
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $this->setErrorMessage('Invalid email format.');
            return false;
        }
        if ($this->userModel->emailExists($userData['email'])) {
            $this->setErrorMessage('Email already exists.');
            return false;
        }
        
        // Validate phone number format and uniqueness
        if (!preg_match('/^[0-9]{8,15}$/', $userData['phone_number'])) {
            $this->setErrorMessage('Phone Number must be numeric and 8-15 digits.');
            return false;
        }
        if ($this->userModel->phoneNumberExists($userData['phone_number'])) {
            $this->setErrorMessage('Phone Number already exists.');
            return false;
        }
        
        // Validate password (at least 8 characters)
        if (strlen($userData['password']) < 8) {
            $this->setErrorMessage('Password must be at least 8 characters long.');
            return false;
        }
        
        // Password Confirmation
        if (!isset($userData['password_confirmation']) || $userData['password'] !== $userData['password_confirmation']) {
            $this->setErrorMessage('Password and Password Confirmation do not match.');
            return false;
        }
        
        return true;
    }


    /**
     * Validates data for updating an existing user.
     * @param array $userData
     * @param int $userId
     * @return bool
     */
    private function validateUserDataForUpdate($userData, $userId) {
        // Required fields check (must be present in the update payload)
        $requiredFields = ['name', 'email', 'role', 'phone_number'];
        foreach ($requiredFields as $field) {
            if (!isset($userData[$field]) || trim($userData[$field]) === '') {
                $this->setErrorMessage(ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                return false;
            }
        }
        
        // Validate email format and uniqueness (excluding current user ID)
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $this->setErrorMessage('Invalid email format.');
            return false;
        }
        if ($this->userModel->emailExists($userData['email'], $userId)) {
            $this->setErrorMessage('Email already exists.');
            return false;
        }
        
        // Validate phone number format and uniqueness (excluding current user ID)
        if (!preg_match('/^[0-9]{8,15}$/', $userData['phone_number'])) {
            $this->setErrorMessage('Phone Number must be numeric and 8-15 digits.');
            return false;
        }
        if ($this->userModel->phoneNumberExists($userData['phone_number'], $userId)) {
            $this->setErrorMessage('Phone Number already exists.');
            return false;
        }
        
        // Validate password if provided
        if (isset($userData['password']) && !empty($userData['password'])) {
            if (strlen($userData['password']) < 8) {
                $this->setErrorMessage('Password must be at least 8 characters long.');
                return false;
            }
            if (!isset($userData['password_confirmation']) || $userData['password'] !== $userData['password_confirmation']) {
                $this->setErrorMessage('Password and Password Confirmation do not match.');
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get all users with role names (for dashboard stats)
     * @param array $roles Optional filter by specific roles.
     * @param int $page Page number for pagination
     * @param int $limit Number of records per page
     * @return array
     */
    public function getAllUsersWithRoleNames($roles = [], $page = 1, $limit = null) {
        $cacheKey = 'all_users_' . md5(serialize($roles) . "_page{$page}_limit{$limit}");

        return $this->cache->remember($cacheKey, 1800, function() use ($roles, $page, $limit) { // Cache for 30 minutes
            if ($limit) {
                $offset = ($page - 1) * $limit;
                return $this->userModel->getAllUsersWithRoleNamesPaginated($roles, $limit, $offset);
            }
            return $this->userModel->getAllUsersWithRoleNames($roles);
        });
    }

    /**
     * Get staff statistics for the index page
     * @return array
     */
    public function getStaffStats() {
        $cacheKey = 'staff_stats';

        return $this->cache->remember($cacheKey, 900, function() { // Cache for 15 minutes
            $stats = [];

            // Total staff (operational roles only)
            $operationalRoles = [
                UserModel::$ROLE_ADMIN,
                UserModel::$ROLE_MANAGER,
                UserModel::$ROLE_CASHIER,
                UserModel::$ROLE_ACCOUNT_OFFICER
            ];

            $sql = "SELECT COUNT(*) as count FROM {$this->userModel->getTable()} WHERE role IN ('" . implode("','", $operationalRoles) . "')";
            $result = $this->userModel->query($sql, [], true);
            $stats['total_staff'] = $result ? $result['count'] : 0;

            // Active staff
            $sql = "SELECT COUNT(*) as count FROM {$this->userModel->getTable()} WHERE role IN ('" . implode("','", $operationalRoles) . "') AND status = ?";
            $result = $this->userModel->query($sql, [UserModel::$STATUS_ACTIVE], true);
            $stats['active_staff'] = $result ? $result['count'] : 0;

            // Inactive staff
            $sql = "SELECT COUNT(*) as count FROM {$this->userModel->getTable()} WHERE role IN ('" . implode("','", $operationalRoles) . "') AND status = ?";
            $result = $this->userModel->query($sql, [UserModel::$STATUS_INACTIVE], true);
            $stats['inactive_staff'] = $result ? $result['count'] : 0;

            // Staff added this month
            $sql = "SELECT COUNT(*) as count FROM {$this->userModel->getTable()} WHERE role IN ('" . implode("','", $operationalRoles) . "') AND EXTRACT(MONTH FROM created_at) = EXTRACT(MONTH FROM CURRENT_DATE) AND EXTRACT(YEAR FROM created_at) = EXTRACT(YEAR FROM CURRENT_DATE)";
            $result = $this->userModel->query($sql, [], true);
            $stats['recent_staff'] = $result ? $result['count'] : 0;

            // Staff by role
            $stats['staff_by_role'] = [];
            foreach ($operationalRoles as $role) {
                $sql = "SELECT COUNT(*) as count FROM {$this->userModel->getTable()} WHERE role = ?";
                $result = $this->userModel->query($sql, [$role], true);
                $stats['staff_by_role'][$role] = $result ? $result['count'] : 0;
            }

            return $stats;
        });
    }

    /**
     * Get recent staff users
     * @param int $limit
     * @return array
     */
    public function getRecentStaffUsers($limit = 5) {
        $cacheKey = 'recent_staff_' . $limit;

        return $this->cache->remember($cacheKey, 1800, function() use ($limit) { // Cache for 30 minutes
            $operationalRoles = [
                UserModel::$ROLE_ADMIN,
                UserModel::$ROLE_MANAGER,
                UserModel::$ROLE_CASHIER,
                UserModel::$ROLE_ACCOUNT_OFFICER
            ];

            $sql = "SELECT id, name, email, role, status, created_at FROM {$this->userModel->getTable()} WHERE role IN ('" . implode("','", $operationalRoles) . "') ORDER BY created_at DESC LIMIT ?";
            return $this->userModel->query($sql, [$limit]);
        });
    }

    /**
     * Get staff activity statistics for a specific user
     * @param int $userId
     * @return array
     */
    public function getStaffActivityStats($userId) {
        $stats = [];

        // Loans processed (created/approved) - simplified for now
        $sql = "SELECT COUNT(*) as count FROM loans WHERE created_at >= NOW() - INTERVAL '30 days'";
        $result = $this->userModel->query($sql, [], true);
        $stats['loans_processed'] = $result ? $result['count'] : 0;

        // Payments recorded
        $sql = "SELECT COUNT(*) as count FROM payments WHERE user_id = ? AND created_at >= NOW() - INTERVAL '30 days'";
        $result = $this->userModel->query($sql, [$userId], true);
        $stats['payments_recorded'] = $result ? $result['count'] : 0;

        // Clients served (unique clients from loans/payments)
        $sql = "SELECT COUNT(DISTINCT client_id) as count FROM loans WHERE created_at >= NOW() - INTERVAL '30 days'";
        $result = $this->userModel->query($sql, [], true);
        $stats['clients_served'] = $result ? $result['count'] : 0;

        // Active loans (simplified - loans that are active)
        $sql = "SELECT COUNT(*) as count FROM loans WHERE status = 'Active'";
        $result = $this->userModel->query($sql, [], true);
        $stats['active_loans'] = $result ? $result['count'] : 0;

        return $stats;
    }
}
