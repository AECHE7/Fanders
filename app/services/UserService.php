<?php
/**
 * UserService - Handles user-related operations
 */
class UserService extends BaseService {
    private $userModel;
    private $superAdminModel;
    private $adminModel;
    private $borrowerModel;
    private $passwordHash;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->superAdminModel = new SuperAdminModel();
        $this->adminModel = new AdminModel();
        $this->borrowerModel = new BorrowerModel();
        $this->passwordHash = new PasswordHash();
        $this->setModel($this->userModel);
    }

    /**
     * Get all users with role names
     * 
     * @return array|bool
     */
    public function getAllUsersWithRoleNames() {
        return $this->userModel->getAllUsersWithRoleNames();
    }

    /**
     * Get user with role name
     * 
     * @param int $id
     * @return array|bool
     */
    public function getUserWithRoleName($id) {
        return $this->userModel->getUserWithRoleName($id);
    }

    /**
     * Add new user
     * 
     * @param array $userData
     * @return int|bool
     */
    public function addUser($userData) {
        // Validate user data
        if (!$this->validateUserData($userData)) {
            return false;
        }
        
        // Hash password
        $userData['password'] = $this->passwordHash->hash($userData['password']);
        
        // Set timestamps and active status
        $userData['is_active'] = 1;
        $userData['created_at'] = date('Y-m-d H:i:s');
        $userData['updated_at'] = date('Y-m-d H:i:s');
        
        // Create user
        return $this->userModel->create($userData);
    }

    /**
     * Update user
     * 
     * @param int $id
     * @param array $userData
     * @return bool
     */
    public function updateUser($id, $userData) {
        // Get existing user
        $existingUser = $this->userModel->findById($id);
        
        if (!$existingUser) {
            $this->setErrorMessage('User not found.');
            return false;
        }
        
        // Validate user data for update
        if (!$this->validateUserDataForUpdate($userData, $id)) {
            return false;
        }
        
        // Set timestamps
        $userData['updated_at'] = date('Y-m-d H:i:s');
        
        // Remove password from update data if empty
        if (isset($userData['password']) && empty($userData['password'])) {
            unset($userData['password']);
        } elseif (isset($userData['password'])) {
            // Hash password if provided
            $userData['password'] = $this->passwordHash->hash($userData['password']);
        }
        
        // Update user
        return $this->userModel->update($id, $userData);
    }

    /**
     * Delete user (deactivate)
     * 
     * @param int $id
     * @return bool
     */
    public function deleteUser($id) {
        // Get existing user
        $existingUser = $this->userModel->findById($id);
        
        if (!$existingUser) {
            $this->setErrorMessage('User not found.');
            return false;
        }
        
        // Check if user is a Super Admin
        if ($existingUser['role_id'] == ROLE_SUPER_ADMIN) {
            $this->setErrorMessage('Cannot delete a Super Admin user.');
            return false;
        }
        
        // Check if user has active loans
        if ($existingUser['role_id'] == ROLE_BORROWER) {
            $borrowerModel = new BorrowerModel();
            $activeLoans = $borrowerModel->getActiveLoans($id);
            
            if ($activeLoans && count($activeLoans) > 0) {
                $this->setErrorMessage('Cannot delete user with active loans.');
                return false;
            }
        }
        
        // Deactivate user (soft delete)
        return $this->userModel->deactivateUser($id);
    }

    /**
     * Activate user
     * 
     * @param int $id
     * @return bool
     */
    public function activateUser($id) {
        return $this->userModel->activateUser($id);
    }

    /**
     * Get all admins
     * 
     * @return array|bool
     */
    public function getAllAdmins() {
        return $this->superAdminModel->getAllAdmins();
    }

    /**
     * Add new admin
     * 
     * @param array $userData
     * @return int|bool
     */
    public function addAdmin($userData) {
        // Validate user data
        if (!$this->validateUserData($userData)) {
            return false;
        }
        
        // Hash password
        $userData['password'] = $this->passwordHash->hash($userData['password']);
        
        // Create admin
        return $this->superAdminModel->createAdmin($userData);
    }

    /**
     * Get all borrowers
     * 
     * @return array|bool
     */
    public function getAllBorrowers() {
        return $this->adminModel->getAllBorrowers();
    }

    /**
     * Add new borrower
     * 
     * @param array $userData
     * @return int|bool
     */
    public function addBorrower($userData) {
        // Validate user data
        if (!$this->validateUserData($userData)) {
            return false;
        }
        
        // Hash password
        $userData['password'] = $this->passwordHash->hash($userData['password']);
        
        // Create borrower
        return $this->adminModel->createBorrower($userData);
    }

    /**
     * Get borrower statistics
     * 
     * @param int $userId
     * @return array
     */
    public function getBorrowerStats($userId) {
        return $this->borrowerModel->getBorrowerStats($userId);
    }

    /**
     * Get borrower active loans
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getBorrowerActiveLoans($userId) {
        return $this->borrowerModel->getActiveLoans($userId);
    }

    /**
     * Get borrower loan history
     * 
     * @param int $userId
     * @return array|bool
     */
    public function getBorrowerLoanHistory($userId) {
        return $this->borrowerModel->getLoanHistory($userId);
    }

    /**
     * Get system statistics
     * 
     * @return array
     */
    public function getSystemStats() {
        return $this->superAdminModel->getSystemStats();
    }

    /**
     * Get admin statistics
     * 
     * @return array
     */
    public function getAdminStats() {
        return $this->adminModel->getAdminStats();
    }

    /**
     * Validate user data
     * 
     * @param array $userData
     * @return bool
     */
    private function validateUserData($userData) {
        // Check required fields
        $requiredFields = ['username', 'email', 'password', 'first_name', 'last_name', 'role_id'];
        foreach ($requiredFields as $field) {
            if (!isset($userData[$field]) || empty($userData[$field])) {
                $this->setErrorMessage(ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                return false;
            }
        }
        
        // Validate username (alphanumeric and at least 4 characters)
        if (!preg_match('/^[a-zA-Z0-9]{4,}$/', $userData['username'])) {
            $this->setErrorMessage('Username must be alphanumeric and at least 4 characters.');
            return false;
        }
        
        // Check if username exists
        if ($this->userModel->usernameExists($userData['username'])) {
            $this->setErrorMessage('Username already exists.');
            return false;
        }
        
        // Validate email
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $this->setErrorMessage('Invalid email format.');
            return false;
        }
        
        // Check if email exists
        if ($this->userModel->emailExists($userData['email'])) {
            $this->setErrorMessage('Email already exists.');
            return false;
        }
        
        // Validate password (at least 8 characters)
        if (strlen($userData['password']) < 8) {
            $this->setErrorMessage('Password must be at least 8 characters long.');
            return false;
        }
        
        // Validate role
        $validRoles = [ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_BORROWER];
        if (!in_array($userData['role_id'], $validRoles)) {
            $this->setErrorMessage('Invalid role.');
            return false;
        }
        
        return true;
    }

    /**
     * Validate user data for update
     * 
     * @param array $userData
     * @param int $userId
     * @return bool
     */
    private function validateUserDataForUpdate($userData, $userId) {
        // Check required fields
        $requiredFields = ['username', 'email', 'first_name', 'last_name', 'role_id'];
        foreach ($requiredFields as $field) {
            if (!isset($userData[$field]) || empty($userData[$field])) {
                $this->setErrorMessage(ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                return false;
            }
        }
        
        // Validate username (alphanumeric and at least 4 characters)
        if (!preg_match('/^[a-zA-Z0-9]{4,}$/', $userData['username'])) {
            $this->setErrorMessage('Username must be alphanumeric and at least 4 characters.');
            return false;
        }
        
        // Check if username exists (excluding current user)
        if ($this->userModel->usernameExists($userData['username'], $userId)) {
            $this->setErrorMessage('Username already exists.');
            return false;
        }
        
        // Validate email
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $this->setErrorMessage('Invalid email format.');
            return false;
        }
        
        // Check if email exists (excluding current user)
        if ($this->userModel->emailExists($userData['email'], $userId)) {
            $this->setErrorMessage('Email already exists.');
            return false;
        }
        
        // Validate password if provided
        if (isset($userData['password']) && !empty($userData['password']) && strlen($userData['password']) < 8) {
            $this->setErrorMessage('Password must be at least 8 characters long.');
            return false;
        }
        
        // Validate role
        $validRoles = [ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_BORROWER];
        if (!in_array($userData['role_id'], $validRoles)) {
            $this->setErrorMessage('Invalid role.');
            return false;
        }
        
        return true;
    }
}
