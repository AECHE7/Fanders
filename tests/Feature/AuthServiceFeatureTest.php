<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Services\AuthService;
use App\Models\UserModel;
use PDO;

class AuthServiceFeatureTest extends TestCase
{
    private $pdo;
    private $authService;
    private $userModel;

    protected function setUp(): void
    {
        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Load test schema
        $this->loadTestSchema();

        $this->authService = new AuthService($this->pdo);
        $this->userModel = new UserModel($this->pdo);
    }

    private function loadTestSchema()
    {
        // Create users table
        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role ENUM('super-admin', 'admin', 'manager', 'cashier', 'account-officer') NOT NULL,
                status ENUM('active', 'inactive') DEFAULT 'active',
                last_login TIMESTAMP NULL,
                session_token VARCHAR(255) NULL,
                session_expires TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
    }

    public function testUserRegistrationAndLogin()
    {
        // Register a new user
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'admin'
        ];

        $userId = $this->authService->register($userData);

        $this->assertIsInt($userId);
        $this->assertGreaterThan(0, $userId);

        // Attempt login with correct credentials
        $loginResult = $this->authService->login('test@example.com', 'password123');

        $this->assertTrue($loginResult);

        // Verify user is logged in
        $this->assertTrue($this->authService->isLoggedIn());

        // Get current user
        $currentUser = $this->authService->getCurrentUser();

        $this->assertIsArray($currentUser);
        $this->assertEquals('Test User', $currentUser['name']);
        $this->assertEquals('test@example.com', $currentUser['email']);
        $this->assertEquals('admin', $currentUser['role']);
    }

    public function testLoginWithInvalidCredentials()
    {
        // Register a user
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'admin'
        ];

        $this->authService->register($userData);

        // Attempt login with wrong password
        $loginResult = $this->authService->login('test@example.com', 'wrongpassword');

        $this->assertFalse($loginResult);
        $this->assertFalse($this->authService->isLoggedIn());

        // Attempt login with non-existent email
        $loginResult = $this->authService->login('nonexistent@example.com', 'password123');

        $this->assertFalse($loginResult);
        $this->assertFalse($this->authService->isLoggedIn());
    }

    public function testRoleBasedAccessControl()
    {
        // Register users with different roles
        $adminData = [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'admin'
        ];

        $managerData = [
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => 'password123',
            'role' => 'manager'
        ];

        $cashierData = [
            'name' => 'Cashier User',
            'email' => 'cashier@example.com',
            'password' => 'password123',
            'role' => 'cashier'
        ];

        $this->authService->register($adminData);
        $this->authService->register($managerData);
        $this->authService->register($cashierData);

        // Test admin access
        $this->authService->login('admin@example.com', 'password123');
        $this->assertTrue($this->authService->hasRole(['admin']));
        $this->assertTrue($this->authService->hasRole(['admin', 'manager']));
        $this->assertFalse($this->authService->hasRole(['super-admin']));

        // Test manager access
        $this->authService->logout();
        $this->authService->login('manager@example.com', 'password123');
        $this->assertTrue($this->authService->hasRole(['manager']));
        $this->assertTrue($this->authService->hasRole(['admin', 'manager']));
        $this->assertFalse($this->authService->hasRole(['admin']));

        // Test cashier access
        $this->authService->logout();
        $this->authService->login('cashier@example.com', 'password123');
        $this->assertTrue($this->authService->hasRole(['cashier']));
        $this->assertFalse($this->authService->hasRole(['admin']));
        $this->assertFalse($this->authService->hasRole(['manager']));
    }

    public function testSessionManagement()
    {
        // Register and login user
        $userData = [
            'name' => 'Session User',
            'email' => 'session@example.com',
            'password' => 'password123',
            'role' => 'admin'
        ];

        $this->authService->register($userData);
        $this->authService->login('session@example.com', 'password123');

        // Verify session is active
        $this->assertTrue($this->authService->isLoggedIn());

        // Check session timeout (should not be timed out immediately)
        $this->assertFalse($this->authService->checkSessionTimeout());

        // Logout
        $this->authService->logout();

        // Verify user is logged out
        $this->assertFalse($this->authService->isLoggedIn());
        $this->assertNull($this->authService->getCurrentUser());
    }

    public function testPasswordHashing()
    {
        $password = 'testpassword123';

        // Hash password
        $hashedPassword = $this->authService->hashPassword($password);

        $this->assertIsString($hashedPassword);
        $this->assertNotEquals($password, $hashedPassword);

        // Verify password
        $this->assertTrue($this->authService->verifyPassword($password, $hashedPassword));
        $this->assertFalse($this->authService->verifyPassword('wrongpassword', $hashedPassword));
    }

    public function testUserStatusManagement()
    {
        // Register active user
        $userData = [
            'name' => 'Active User',
            'email' => 'active@example.com',
            'password' => 'password123',
            'role' => 'cashier'
        ];

        $userId = $this->authService->register($userData);

        // Login should work for active user
        $loginResult = $this->authService->login('active@example.com', 'password123');
        $this->assertTrue($loginResult);

        $this->authService->logout();

        // Deactivate user
        $this->userModel->update($userId, ['status' => 'inactive']);

        // Login should fail for inactive user
        $loginResult = $this->authService->login('active@example.com', 'password123');
        $this->assertFalse($loginResult);
    }

    public function testGetUserByEmail()
    {
        // Register a user
        $userData = [
            'name' => 'Email Test User',
            'email' => 'emailtest@example.com',
            'password' => 'password123',
            'role' => 'manager'
        ];

        $this->authService->register($userData);

        // Get user by email
        $user = $this->authService->getUserByEmail('emailtest@example.com');

        $this->assertIsArray($user);
        $this->assertEquals('Email Test User', $user['name']);
        $this->assertEquals('emailtest@example.com', $user['email']);
        $this->assertEquals('manager', $user['role']);

        // Test non-existent email
        $user = $this->authService->getUserByEmail('nonexistent@example.com');
        $this->assertNull($user);
    }

    public function testUpdateLastLogin()
    {
        // Register and login user
        $userData = [
            'name' => 'Login Test User',
            'email' => 'logintest@example.com',
            'password' => 'password123',
            'role' => 'admin'
        ];

        $userId = $this->authService->register($userData);
        $this->authService->login('logintest@example.com', 'password123');

        // Get user before logout
        $userBefore = $this->userModel->findById($userId);
        $this->assertNull($userBefore['last_login']);

        // Logout (should update last_login)
        $this->authService->logout();

        // Get user after logout
        $userAfter = $this->userModel->findById($userId);
        $this->assertNotNull($userAfter['last_login']);
    }

    public function testConcurrentSessions()
    {
        // Register a user
        $userData = [
            'name' => 'Concurrent User',
            'email' => 'concurrent@example.com',
            'password' => 'password123',
            'role' => 'admin'
        ];

        $this->authService->register($userData);

        // Login from first session
        $this->authService->login('concurrent@example.com', 'password123');
        $session1User = $this->authService->getCurrentUser();

        // Create second auth service instance (simulating second session)
        $authService2 = new AuthService($this->pdo);
        $authService2->login('concurrent@example.com', 'password123');
        $session2User = $authService2->getCurrentUser();

        // Both sessions should work independently
        $this->assertEquals($session1User['id'], $session2User['id']);
        $this->assertEquals($session1User['email'], $session2User['email']);
    }
}
