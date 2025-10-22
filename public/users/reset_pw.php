Try AI directly in your favorite apps … Use Gemini to generate drafts and refine content, plus get Gemini Advanced with access to Google’s next-gen AI for ₱1,100.00 ₱550.00 for 2 months
<?php
/**
 * DEPRECATED - Reset Staff User Password page
 * This feature has been replaced with direct password editing in the user edit form.
 * Super-admins can now change staff passwords directly through the edit user page.
 * 
 * Redirecting to the edit user page...
 */

// Include configuration
require_once '../../app/config/config.php';

// Include all required files
function autoload($className) {
    $directories = [
        'app/core/',
        'app/models/',
        'app/services/',
        'app/utilities/'
    ];
    
    foreach ($directories as $directory) {
        $file = BASE_PATH . '/' . $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}

spl_autoload_register('autoload');

// Initialize session management
$session = new Session();

// Initialize authentication service
$auth = new AuthService();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    $session->setFlash('error', 'Please login to access this page.');
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

// Get user ID from query parameter
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Set informational message
$session->setFlash('info', 'Password reset feature has been updated. You can now change user passwords directly in the edit form.');

// Redirect to edit page
if ($id > 0) {
    header('Location: ' . APP_URL . '/public/users/edit.php?id=' . $id);
} else {
    header('Location: ' . APP_URL . '/public/users/index.php');
}
exit;
?>