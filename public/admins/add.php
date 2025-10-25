<?php
/**
 * Add staff user page for the Fanders Microfinance Loan Management System
 *
 * Uses addUser from UserService, ensures fields and roles align with microfinance service and models.
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

// Initialize CSRF protection
$csrf = new CSRF();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    // Redirect to login page
    $session->setFlash('error', 'Please login to access this page.');
    header('Location: ' . APP_URL . '/public/auth/login.php');
    exit;
}

// Check for session timeout
if ($auth->checkSessionTimeout()) {
    // Session has timed out, redirect to login page with message
    $session->setFlash('error', 'Your session has expired. Please login again.');
    header('Location: ' . APP_URL . '/public/auth/login.php');
    exit;
}

// Get current user data
$user = $auth->getCurrentUser();
$userRole = $user['role'];

// Check if user has permission to add staff users (Super Admin can add any staff user, Admin can add limited staff roles)
if (!$auth->hasRole(['super-admin', 'admin'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard/index.php');
    exit;
}

// Initialize user service
$userService = new UserService();

// Initialize password hash utility for generating random password
$passwordHash = new PasswordHash();

// Default structure for the form
$newUser = [
    'name' => '',
    'email' => '',
    'phone_number' => '',
    'password' => '',
    'password_confirmation' => '',
    'role' => '',  // Will be set based on user role and form input
    'status' => 'active',
];

$error = '';
$formSubmitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formSubmitted = true;
    
    // Validate CSRF token
    if (!$csrf->validateRequest()) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Gather form input, ensure they match UserService expectations
        $newUser = [
            'name' => isset($_POST['name']) ? trim($_POST['name']) : '',
            'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
            'phone_number' => isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '',
            'password' => isset($_POST['password']) ? $_POST['password'] : '',
            'password_confirmation' => isset($_POST['password_confirmation']) ? $_POST['password_confirmation'] : '',
            'role' => isset($_POST['role']) ? strtolower(trim($_POST['role'])) : '',
            'status' => isset($_POST['status']) ? trim($_POST['status']) : 'active',
        ];
        
        // Role validation and restriction
        if ($userRole === 'admin') {
            // Admin can only add limited staff roles (account_officer, cashier)
            if (!in_array($newUser['role'], ['account_officer', 'cashier'])) {
                $error = 'Admins can only add Account Officer and Cashier accounts.';
            }
        } else if ($userRole === 'super-admin') {
            // Super Admin can add any staff role
            if (!in_array($newUser['role'], ['super-admin', 'admin', 'manager', 'account_officer', 'cashier'])) {
                $error = 'Invalid role selected.';
            }
        }
        
        if (empty($error)) {
            // Now call UserService's addUser to handle all roles properly
            $userId = $userService->addUser($newUser);
            
            if ($userId) {
                // User added successfully
                $session->setFlash('success', 'Staff user added successfully.');
                header('Location: ' . APP_URL . '/public/admins/index.php');
                exit;
            } else {
                // Failed to add user
                $error = $userService->getErrorMessage();
            }
        }
    }
}

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Add Staff User</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/admins/index.php" class="btn btn-sm btn-outline-secondary">
                <i data-feather="arrow-left"></i> Back to Staff Users
            </a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <?php if ($formSubmitted && !$error): ?>
        <div class="alert alert-success">
            Staff user added successfully.
        </div>
    <?php endif; ?>
    
    <!-- Admin Form -->
    <?php
    // The enhanced admin form template handles its own styling and layout
    $formPath = BASE_PATH . '/templates/admins/form.php';
    if (file_exists($formPath)) {
        include_once $formPath;
    } else {
        echo '<div class="alert alert-danger">Staff user form template is missing: ' . htmlspecialchars($formPath) . '</div>';
    }
    ?>
    </div>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
