<?php
/**
 * PHPUnit Bootstrap File
 * Sets up the testing environment for the Fanders Microfinance LMS
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Define test constants
define('TESTING', true);
define('BASE_PATH', __DIR__ . '/../');
define('APP_URL', 'http://localhost:8080');

// Set up error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Prevent autoloading of application classes during unit tests
// Unit tests should create their own isolated instances
spl_autoload_register(function ($className) {
    // Only load classes that are not application models/services during unit tests
    if (strpos($className, 'App\\') === 0 && !defined('ALLOW_APP_CLASSES')) {
        // Skip autoloading App classes for unit tests to avoid conflicts
        return;
    }

    // For integration and feature tests, allow App classes
    $testType = getenv('TEST_TYPE') ?: 'unit';
    if ($testType !== 'unit' && strpos($className, 'App\\') === 0) {
        $file = BASE_PATH . 'app/' . str_replace('\\', '/', $className) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Mock database connection for unit tests
class TestDatabase {
    private static $pdo = null;

    public static function getConnection() {
        if (self::$pdo === null) {
            // Use SQLite in-memory database for testing
            self::$pdo = new PDO('sqlite::memory:');
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$pdo;
    }

    public static function reset() {
        self::$pdo = null;
    }
}

// Helper function to get test database
function getTestDatabase() {
    return TestDatabase::getConnection();
}

// Clean up function for tests
function cleanupTestData() {
    $pdo = getTestDatabase();
    $tables = ['transactions', 'payments', 'loans', 'clients', 'users'];

    foreach ($tables as $table) {
        try {
            $pdo->exec("DELETE FROM $table");
        } catch (Exception $e) {
            // Table might not exist, continue
        }
    }
}

// Mock session for testing
class MockSession {
    private $data = [];

    public function set($key, $value) {
        $this->data[$key] = $value;
    }

    public function get($key, $default = null) {
        return $this->data[$key] ?? $default;
    }

    public function has($key) {
        return isset($this->data[$key]);
    }

    public function remove($key) {
        unset($this->data[$key]);
    }

    public function clear() {
        $this->data = [];
    }
}

// Mock authentication for testing
class MockAuth {
    private $user = null;

    public function login($email, $password) {
        // Mock login - always succeeds for test user
        if ($email === 'test@example.com' && $password === 'password') {
            $this->user = [
                'id' => 1,
                'name' => 'Test User',
                'email' => $email,
                'role' => 'admin'
            ];
            return true;
        }
        return false;
    }

    public function logout() {
        $this->user = null;
    }

    public function isLoggedIn() {
        return $this->user !== null;
    }

    public function getCurrentUser() {
        return $this->user;
    }

    public function hasRole($roles) {
        if (!$this->user) return false;
        return in_array($this->user['role'], (array)$roles);
    }
}
