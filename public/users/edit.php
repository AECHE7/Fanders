<?php
/**
 * Edit user page for the Library Management System
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

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to users page with error message
    $session->setFlash('error', 'User ID is required.');
    header('Location: ' . APP_URL . '/public/users/index.php');
    exit;
}

$userId = (int)$_GET['id'];

// Initialize user service
$userService = new UserService();

// Get user data
$editUser = $userService->getUserWithRoleName($userId);

if (!$editUser) {
    // User not found
    $session->setFlash('error', 'User not found.');
    header('Location: ' . APP_URL . '/public/users/index.php');
    exit;
}

// Check if user has permission to edit this user
// Super Admin can edit any user, Admin can only edit borrowers
if ($userRole == ROLE_ADMIN && $editUser['role_id'] != ROLE_BORROWER) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to edit this user.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
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
        $updatedUser = [
            'username' => isset($_POST['username']) ? trim($_POST['username']) : '',
            'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
            'password' => isset($_POST['password']) ? $_POST['password'] : '',
            'first_name' => isset($_POST['first_name']) ? trim($_POST['first_name']) : '',
            'last_name' => isset($_POST['last_name']) ? trim($_POST['last_name']) : '',
            'role_id' => $userRole == ROLE_SUPER_ADMIN && isset($_POST['role_id']) ? (int)$_POST['role_id'] : $editUser['role_id'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        
        // Admin can only edit borrowers and can't change role
        if ($userRole == ROLE_ADMIN) {
            $updatedUser['role_id'] = ROLE_BORROWER;
        }
        
        // Update the user
        if ($userService->updateUser($userId, $updatedUser)) {
            // User updated successfully
            $session->setFlash('success', 'User updated successfully.');
            header('Location: ' . APP_URL . '/public/users/view.php?id=' . $userId);
            exit;
        } else {
            // Failed to update user
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
        <h1 class="h2">Edit User</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="<?= APP_URL ?>/public/users/view.php?id=<?= $userId ?>" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="eye"></i> View User
                </a>
                <a href="<?= APP_URL ?>/public/users/index.php" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="arrow-left"></i> Back to Users
                </a>
            </div>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <!-- User Edit Form -->
    <div class="card">
        <div class="card-body">
            <?php include_once BASE_PATH . '/templates/users/form.php'; ?>
        </div>
    </div>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
