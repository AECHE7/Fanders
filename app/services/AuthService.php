<?php
/**
 * AuthService - Handles user authentication, session management, and role checks.
 */
require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../core/PasswordHash.php';
require_once __DIR__ . '/../core/Session.php';

class AuthService extends BaseService {
    private $userModel;
    private $passwordHash;
    private $session;

    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->passwordHash = new PasswordHash();
        $this->session = new Session();
    }

    public function isLoggedIn() {
        return $this->session->has('user_id');
    }

    public function checkSessionTimeout() {
        // Check if user is logged in first
        if (!$this->isLoggedIn()) {
            return false;
        }

        // Check if session has expired based on last activity
        return $this->session->isExpired();
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $userId = $this->session->get('user_id');
        $user = $this->userModel->findById($userId);

        if (!$user) {
            $this->logout();
            return false;
        }

        return $user;
    }

    public function login($email, $password) {

        // FIX: Use the model's method to retrieve user with password field
        $user = $this->userModel->getUserWithPassword($email);

        // --- Core Authentication Logic ---

        // 1. Check for user existence
        if (!$user) {
            $this->setErrorMessage('Invalid email or password.');
            return false;
        }

        // 2. Check account status
        if ($user['status'] !== UserModel::$STATUS_ACTIVE) {
            $this->setErrorMessage('Account is inactive. Please contact an administrator.');
            return false;
        }

        // 3. Verify password (ensure password field exists and is not null)
        if (!isset($user['password']) || is_null($user['password'])) {
            $this->setErrorMessage('Invalid email or password.');
            return false;
        }
        if (!$this->passwordHash->verify($password, $user['password'])) {
            $this->setErrorMessage('Invalid email or password.');
            return false;
        }
        // --- End Core Authentication Logic ---

        // Authentication successful
        $this->session->set('user_id', $user['id']);
        $this->session->set('user_role', $user['role']);
        $this->session->set('user_first_name', $user['first_name']);
        $this->session->set('user_last_name', $user['last_name']);

        // Update last login time
        $this->userModel->updateLastLogin($user['id']);

        // Log successful login
        $transactionService = new TransactionService();
        $transactionService->logUserLogin($user['id'], [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        return true;
    }

    public function logout() {
        // Get current user before destroying session
        $currentUser = $this->getCurrentUser();

        // Log logout if user was logged in
        if ($currentUser) {
            $transactionService = new TransactionService();
            $transactionService->logUserLogout($currentUser['id'], [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        }

        // Clear all session data and destroy session
        $this->session->clear();
        $this->session->destroy();

        // Force deletion of session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/', '', false, true);
        }

        return true;
    }

    public function hasRole($allowedRoles) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $userRole = $this->session->get('user_role');

        if (is_array($allowedRoles)) {
            return in_array($userRole, $allowedRoles);
        }

        return $userRole === $allowedRoles;
    }

    public function checkRoleAccess($allowedRoles) {
        if (!$this->hasRole($allowedRoles)) {
            $this->session->setFlash('error', 'Access denied. You do not have permission to view this page.');
            header('Location: ' . APP_URL . '/public/dashboard/index.php');
            exit;
        }
    }
}
