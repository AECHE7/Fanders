<?php
/**
 * AuthService - Handles authentication, login, logout, and session management
 */
require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../core/PasswordHash.php';

class AuthService extends BaseService {
    private $userModel;
    private $session;
    private $passwordHash;

    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->session = new Session();
        $this->passwordHash = new PasswordHash();
    }


    public function login($email, $password) {
        // Get user by email (username is actually email in the new schema)
        $user = $this->userModel->getUserByEmail($email);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }
        
        // Verify password
        if (!$this->passwordHash->verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }
        
        // Check if user is active
        if ($user['status'] !== 'active') {
            return [
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact an administrator.'
            ];
        }

        // Create session
        $this->createUserSession($user);
        
        return [
            'success' => true,
            'message' => 'Login successful.'
        ];
    }


    public function logout() {
        $this->session->destroy();
    }


    public function isLoggedIn() {
        return $this->session->get('user_id') !== null;
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $userId = $this->session->get('user_id');
        return $this->userModel->getUserWithRoleName($userId);
    }

    public function hasRole($roles) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $userRole = $this->session->get('user_role');
        
        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }
        
        return $userRole == $roles;
    }

    private function createUserSession($user) {
        $this->session->set('user_id', $user['id']);
        $this->session->set('user_name', $user['name']);
        $this->session->set('user_role', $user['role']);
        $this->session->set('user_email', $user['email']);
        // Split the name into first and last for backward compatibility
        $nameParts = explode(' ', $user['name'], 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
        $this->session->set('user_first_name', $firstName);
        $this->session->set('user_last_name', $lastName);
        $this->session->set('last_activity', time());
    }

    public function register($userData) {
        // Validate user data
        if (!$this->validateUserData($userData)) {
            return false;
        }
        
        // Hash password
        $userData['password'] = $this->passwordHash->hash($userData['password']);
        
        // Set default role to students if not provided
        if (!isset($userData['role'])) {
            $userData['role'] = 'students';
        }
        
        // Set active status and timestamps
        $userData['status'] = 'active';
        $userData['created_at'] = date('Y-m-d H:i:s');
        $userData['updated_at'] = date('Y-m-d H:i:s');
        
        // Create user
        return $this->userModel->create($userData);
    }


    public function changePassword($userId, $currentPassword, $newPassword) {
        // Get user
        $user = $this->userModel->findById($userId);
        
        if (!$user) {
            $this->setErrorMessage('User not found.');
            return false;
        }
        
        // Verify current password
        if (!$this->passwordHash->verify($currentPassword, $user['password'])) {
            $this->setErrorMessage('Current password is incorrect.');
            return false;
        }
        
        // Validate new password
        if (strlen($newPassword) < 8) {
            $this->setErrorMessage('New password must be at least 8 characters long.');
            return false;
        }
        
        // Hash new password
        $hashedPassword = $this->passwordHash->hash($newPassword);
        
        // Update password
        return $this->userModel->updatePassword($userId, $hashedPassword);
    }

    public function resetPassword($userId, $newPassword) {
        // Validate new password
        if (strlen($newPassword) < 8) {
            $this->setErrorMessage('New password must be at least 8 characters long.');
            return false;
        }
        
        // Hash new password
        $hashedPassword = $this->passwordHash->hash($newPassword);
        
        // Update password
        return $this->userModel->updatePassword($userId, $hashedPassword);
    }


    private function validateUserData($userData) {
        // Check required fields
        $requiredFields = ['name', 'email', 'password', 'phone_number'];
        foreach ($requiredFields as $field) {
            if (!isset($userData[$field]) || empty($userData[$field])) {
                $this->setErrorMessage(ucfirst($field) . ' is required.');
                return false;
            }
        }
        
        // Validate email
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $this->setErrorMessage('Invalid email format.');
            return false;
        }
        
        // Check if email already exists
        if ($this->userModel->emailExists($userData['email'])) {
            $this->setErrorMessage('Email already exists.');
            return false;
        }
        
        // Check if phone number already exists
        if ($this->userModel->phoneNumberExists($userData['phone_number'])) {
            $this->setErrorMessage('Phone number already exists.');
            return false;
        }
        
        // Validate password (at least 8 characters)
        if (strlen($userData['password']) < 8) {
            $this->setErrorMessage('Password must be at least 8 characters long.');
            return false;
        }
        
        return true;
    }

    public function checkSessionTimeout() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $lastActivity = $this->session->get('last_activity');
        if ($lastActivity === null) {
            return false;
        }
        
        // If last activity is older than SESSION_LIFETIME, session has timed out
        if (time() - $lastActivity > SESSION_LIFETIME) {
            $this->logout();
            return true;
        }
        
        // Update last activity
        $this->session->set('last_activity', time());
        return false;
    }


    public function isAdmin() {
        return $this->hasRole(['admin', 'super-admin']);
    }
}
