<?php
/**
 * Edit admin page for the Library Management System
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
$userRole = $user['role_id'];

// Check if user has permission to edit admins (only Super Admin)
if ($userRole != ROLE_SUPER_ADMIN) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Check if admin ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to admins page with error message
    $session->setFlash('error', 'Admin ID is required.');
    header('Location: ' . APP_URL . '/public/admins/index.php');
    exit;
}

$adminId = (int)$_GET['id'];

// Initialize user service
$userService = new UserService();

// Get admin data
$editAdmin = $userService->getUserWithRoleName($adminId);

if (!$editAdmin) {
    // Admin not found
    $session->setFlash('error', 'Administrator not found.');
    header('Location: ' . APP_URL . '/public/admins/index.php');
    exit;
}

// Verify that the user is an admin
if ($editAdmin['role_id'] != ROLE_ADMIN) {
    $session->setFlash('error', 'The specified user is not an administrator.');
    header('Location: ' . APP_URL . '/public/admins/index.php');
    exit;
}

// Process form submission
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrf->validateRequest()) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Get form data
        $updatedAdmin = [
            'username' => isset($_POST['username']) ? trim($_POST['username']) : '',
            'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
            'password' => isset($_POST['password']) ? $_POST['password'] : '', // Leave empty to keep current password
            'first_name' => isset($_POST['first_name']) ? trim($_POST['first_name']) : '',
            'last_name' => isset($_POST['last_name']) ? trim($_POST['last_name']) : '',
            'role_id' => ROLE_ADMIN, // Always set to Admin role
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        
        // Update the admin
        if ($userService->updateUser($adminId, $updatedAdmin)) {
            // Admin updated successfully
            $session->setFlash('success', 'Administrator updated successfully.');
            header('Location: ' . APP_URL . '/public/users/view.php?id=' . $adminId);
            exit;
        } else {
            // Failed to update admin
            $error = $userService->getErrorMessage();
        }
    }
}

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Edit Administrator</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="<?= APP_URL ?>/public/users/view.php?id=<?= $adminId ?>" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="eye"></i> View Admin
                </a>
                <a href="<?= APP_URL ?>/public/admins/index.php" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="arrow-left"></i> Back to Administrators
                </a>
            </div>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <!-- Admin Edit Form -->
    <div class="card">
        <div class="card-body">
            <?php include_once BASE_PATH . '/templates/admins/form.php'; ?>
        </div>
    </div>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
