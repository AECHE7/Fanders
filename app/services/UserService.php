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

class UserService extends BaseService {
    private $userModel;
    private $passwordHash;
    private $session;
    private $validOperationalRoles;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->passwordHash = new PasswordHash();
        $this->session = new Session();
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
     * @return array
     */
    public function getAllOperationalUsersWithRoleNames($roles = []) {
        return $this->userModel->getAllOperationalUsersWithRoleNames($roles);
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
     * @return int|false New user ID on success.
     */
    public function addUser($userData) {
        try {
            // 1. Ensure role is valid for operational staff
            if (!isset($userData['role']) || !in_array($userData['role'], $this->validOperationalRoles)) {
                $this->setErrorMessage('Invalid or missing operational user role.');
                return false;
            }

            // 2. Validate user data and check for unique email/phone
            if (!$this->validateUserData($userData)) {
                return false;
            }

            // 3. Hash password (Security Feature)
            $userData['password'] = $this->passwordHash->hash($userData['password']);
            
            // 4. Set default status and cleanup confirmation field
            $userData['status'] = UserModel::$STATUS_ACTIVE;
            unset($userData['password_confirmation']);

            // 5. Create user
            $newId = $this->userModel->create($userData);

            if (!$newId) {
                 $this->setErrorMessage($this->userModel->getLastError() ?: 'Failed to create user due to unknown database error.');
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
            return $this->userModel->update($id, $userData);

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
        return $this->userModel->updateStatus($id, UserModel::STATUS_INACTIVE);
    }
    
    /**
     * Activates a user account.
     * @param int $id
     * @return bool
     */
    public function activateUser($id) {
        return $this->userModel->updateStatus($id, UserModel::STATUS_ACTIVE);
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
        return $this->userModel->getUserStats();
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
}
