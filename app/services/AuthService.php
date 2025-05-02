<?php
/**
 * AuthService - Handles authentication, login, logout, and session management
 */
class AuthService extends BaseService {
    private $userModel;
    private $session;
    private $passwordHash;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->session = new Session();
        $this->passwordHash = new PasswordHash();
    }

    /**
     * Login user
     * 
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function login($username, $password) {
        // Get user by username
        $user = $this->userModel->getUserByUsername($username);
        
        if (!$user) {
            $this->setErrorMessage('Invalid username or password.');
            return false;
        }
        
        // Check if user is active
        if (!$user['is_active']) {
            $this->setErrorMessage('Your account has been deactivated. Please contact an administrator.');
            return false;
        }
        
        // Verify password
        if (!$this->passwordHash->verify($password, $user['password'])) {
            $this->setErrorMessage('Invalid username or password.');
            return false;
        }
        
        // Create session
        $this->createUserSession($user);
        
        return true;
    }

    /**
     * Logout user
     * 
     * @return void
     */
    public function logout() {
        $this->session->destroy();
    }

    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    public function isLoggedIn() {
        return $this->session->get('user_id') !== null;
    }

    /**
     * Get current user
     * 
     * @return array|null
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $userId = $this->session->get('user_id');
        return $this->userModel->getUserWithRoleName($userId);
    }

    /**
     * Check if current user has the role
     * 
     * @param int|array $roles
     * @return bool
     */
    public function hasRole($roles) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $userRoleId = $this->session->get('user_role');
        
        if (is_array($roles)) {
            return in_array($userRoleId, $roles);
        }
        
        return $userRoleId == $roles;
    }

    /**
     * Create user session
     * 
     * @param array $user
     * @return void
     */
    private function createUserSession($user) {
        $this->session->set('user_id', $user['id']);
        $this->session->set('user_name', $user['username']);
        $this->session->set('user_role', $user['role_id']);
        $this->session->set('user_email', $user['email']);
        $this->session->set('user_first_name', $user['first_name']);
        $this->session->set('user_last_name', $user['last_name']);
        $this->session->set('last_activity', time());
    }

    /**
     * Register new user
     * 
     * @param array $userData
     * @return int|bool
     */
    public function register($userData) {
        // Validate user data
        if (!$this->validateUserData($userData)) {
            return false;
        }
        
        // Hash password
        $userData['password'] = $this->passwordHash->hash($userData['password']);
        
        // Set default role to Borrower if not provided
        if (!isset($userData['role_id'])) {
            $userData['role_id'] = ROLE_BORROWER;
        }
        
        // Set active status and timestamps
        $userData['is_active'] = 1;
        $userData['created_at'] = date('Y-m-d H:i:s');
        $userData['updated_at'] = date('Y-m-d H:i:s');
        
        // Create user
        return $this->userModel->create($userData);
    }

    /**
     * Change user password
     * 
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @return bool
     */
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

    /**
     * Reset user password (for admin use)
     * 
     * @param int $userId
     * @param string $newPassword
     * @return bool
     */
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

    /**
     * Validate user data
     * 
     * @param array $userData
     * @return bool
     */
    private function validateUserData($userData) {
        // Check required fields
        $requiredFields = ['username', 'email', 'password', 'first_name', 'last_name'];
        foreach ($requiredFields as $field) {
            if (!isset($userData[$field]) || empty($userData[$field])) {
                $this->setErrorMessage(ucfirst($field) . ' is required.');
                return false;
            }
        }
        
        // Validate username (alphanumeric and at least 4 characters)
        if (!preg_match('/^[a-zA-Z0-9]{4,}$/', $userData['username'])) {
            $this->setErrorMessage('Username must be alphanumeric and at least 4 characters.');
            return false;
        }
        
        // Check if username already exists
        if ($this->userModel->usernameExists($userData['username'])) {
            $this->setErrorMessage('Username already exists.');
            return false;
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
        
        // Validate password (at least 8 characters)
        if (strlen($userData['password']) < 8) {
            $this->setErrorMessage('Password must be at least 8 characters long.');
            return false;
        }
        
        return true;
    }

    /**
     * Check if session has timed out
     * 
     * @return bool
     */
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
}
