Try AI directly in your favorite apps … Use Gemini to generate drafts and refine content, plus get Gemini Advanced with access to Google’s next-gen AI for ₱1,100.00 ₱550.00 for 2 months
<?php
/**
 * Users list page for the Library Management System
 */

// Include configuration
require_once '../../app/config/config.php';

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

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    // Redirect to login page
    $session->setFlash('error', 'Please login to access this page.');
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

// Check for session timeout
if ($auth->checkSessionTimeout()) {
    // Session has timed out, redirect to login page with message
    $session->setFlash('error', 'Your session has expired. Please login again.');
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

// Get current user data
$user = $auth->getCurrentUser();
$userRole = $user['role'];

// Check if user has permission to view users list (Super Admin or Admin)
if (!$auth->hasRole(['super-admin', 'admin'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Initialize user service
$userService = new UserService();

//reset password
$id = $_GET['id'];
$result = $userService->resetPassword($id);

if ($result['success']) {
    $newPassword = $result['password']; // Get the new password
    echo "<script>alert('Password reset successfully! New password: $newPassword'); window.location.href = 'index.php';</script>";
} 
else {
    echo "<script>alert('Failed to reset password. Please try again.'); window.history.back();</script>";
}
// Include footer
include_once BASE_PATH . '/app/views/footer.php';
// End output buffering and flush output
ob_end_flush();
?>