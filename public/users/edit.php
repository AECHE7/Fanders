<?php
/**
 * Edit Staff User page for the Fanders Microfinance Loan Management System
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
$userRole = $user['role'];

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
// Super Admin can edit any staff user, Admin can only edit operational staff or themselves
if ($userRole === 'admin' && !in_array($editUser['role'], ['manager', 'cashier', 'account-officer']) && $userId !== $user['id']) {
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
        // Get form data with fallbacks to existing user data
        $updatedUser = [
            'name' => isset($_POST['name']) ? trim($_POST['name']) : ($editUser['name'] ?? ''),
            'email' => isset($_POST['email']) ? trim($_POST['email']) : ($editUser['email'] ?? ''),
            'phone_number' => isset($_POST['phone_number']) ? trim($_POST['phone_number']) : ($editUser['phone_number'] ?? ''),
            'role' => isset($_POST['role']) ? trim($_POST['role']) : ($editUser['role'] ?? ''),
            'status' => isset($_POST['status']) ? trim($_POST['status']) : ($editUser['status'] ?? 'active')
        ];

        // Handle password update
        if (!empty($_POST['password'])) {
            $updatedUser['password'] = $_POST['password'];
            $updatedUser['password_confirmation'] = $_POST['password_confirmation'];
        }

        // Admin can only edit operational staff and can't change role
        if ($userRole === 'admin') {
            $updatedUser['role'] = $editUser['role']; // Keep existing role
        }

        // Update the user
        if ($userService->updateUser($userId, $updatedUser)) {
            // User updated successfully
            $session->setFlash('success', 'Staff user updated successfully.');
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

<main class="main-content">
    <div class="content-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Edit Staff User Information</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <?php if (in_array($userRole, ['manager', 'cashier', 'account-officer'])): ?>
                    <a href="<?= APP_URL ?>/public/users/view.php?id=<?= $userId ?>" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="arrow-left"></i> Back to Staff Profile
                    </a>
                <?php elseif (in_array($userRole, ['super-admin', 'admin'])): ?>
                    <a href="<?= APP_URL ?>/public/users/view.php?id=<?= $userId ?>" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="eye"></i> View Staff Profile
                    </a>
                    <a href="<?= APP_URL ?>/public/users/index.php" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="arrow-left"></i> Back to Staff
                    </a>
                <?php endif; ?>
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
            <?php
                global $userRole, $editUser, $userId;
                include_once BASE_PATH . '/templates/users/form.php';
            ?>
        </div>
    </div>
    </div>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
