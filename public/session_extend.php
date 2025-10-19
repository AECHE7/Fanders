<?php
/**
 * Session Extension Handler (AJAX endpoint)
 * Handles AJAX requests for session extension actions
 */

// Include configuration
require_once '../app/config/config.php';

// Start output buffering
ob_start();

// Include all required files
function autoload($className) {
    // Define the directories to look in
    $directories = [
        'app/core/',
        'app/models/',
        'app/services/',
        'app/utilities/'
    ];

    // Try to find the class file
    foreach ($directories as $directory) {
        $file = BASE_PATH . '/' . $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}

// Register autoloader
spl_autoload_register('autoload');

// Initialize session management
$session = new Session();

// Initialize authentication service
$auth = new AuthService();

// Initialize CSRF protection
$csrf = new CSRF();

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get current user data
$user = $auth->getCurrentUser();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrf->validateToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'extend') {
        // Extend the session by updating last activity
        $session->updateLastActivity();
        $session->remove('session_timed_out');

        // Log the session extension
        $transactionService = new TransactionService();
        $transactionService->logUserTransaction('session_extended', $user['id'], $user['id'], [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        echo json_encode([
            'success' => true,
            'action' => 'extend',
            'message' => 'Session extended successfully'
        ]);
        exit;

    } elseif ($action === 'logout') {
        // Logout the user
        $auth->logout();
        echo json_encode([
            'success' => true,
            'action' => 'logout',
            'message' => 'Logged out successfully'
        ]);
        exit;
    }
}

// Handle session timeout check requests
if (isset($_POST['check_session_timeout'])) {
    $timedOut = $auth->checkSessionTimeout();
    echo json_encode(['timed_out' => $timedOut]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
