<?php
/**
 * Staff Users list page for the Fanders Microfinance Loan Management System
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
$csrfToken = $csrf->generateToken();

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

// Check if user has permission to view staff users list (Super Admin or Admin)
if (!$auth->hasRole(['super-admin', 'admin'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard/index.php');
    exit;
}

// Initialize user service
$userService = new UserService();

// Process filter
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Get operational staff users based on filters
$allowedRoles = [
    UserModel::$ROLE_ADMIN,
    UserModel::$ROLE_MANAGER,
    UserModel::$ROLE_CASHIER,
    UserModel::$ROLE_ACCOUNT_OFFICER
];
$users = $userService->getAllOperationalUsersWithRoleNames($allowedRoles);

// Apply role filter if specified
if (!empty($roleFilter) && $userRole == 'super-admin') {
    $filteredUsers = [];
    foreach ($users as $u) {
        if ($u['role'] == $roleFilter) {
            $filteredUsers[] = $u;
        }
    }
    $users = $filteredUsers;
}

// Apply status filter if specified
if (!empty($statusFilter) &&  $userRole == 'super-admin') {
    $filteredUsers = [];
    foreach ($users as $u) {
        if ($u['status'] == $statusFilter) {
            $filteredUsers[] = $u;
        }
    }
    $users = $filteredUsers;
}

// Handle delete user form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!$csrf->validateRequest()) {
        $session->setFlash('error', 'Invalid CSRF token.');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    $deleteUserId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($deleteUserId > 0) {
        if ($userService->deleteUser($deleteUserId)) {
            $session->setFlash('success', 'User deleted successfully.');
        } else {
            $session->setFlash('error', $userService->getErrorMessage());
        }
    } else {
        $session->setFlash('error', 'Invalid user ID.');
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Pass CSRF token to template
// $csrfToken is available for templates/users/list.php
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manage Staff Users</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <?php if ($userRole == 'super-admin' || $userRole == 'admin'): ?>
                    <a href="<?= APP_URL ?>/public/users/add.php" class="btn btn-sm btn-outline-primary">
                        <i data-feather="user-plus"></i> Add Staff Account
                    </a>
                <?php endif; ?>

                <a href="<?= APP_URL ?>/public/reports/users.php" class="btn btn-sm btn-outline-secondary">
                    <i data-feather="file-text"></i> Generate Report
                </a>
            </div>
        </div>
    </div>
    
    <?php if ($session->hasFlash('success')): ?>
        <div class="alert alert-success">
            <?= $session->getFlash('success') ?>
        </div>
    <?php endif; ?>
    
    <?php if ($session->hasFlash('error')): ?>
        <div class="alert alert-danger">
            <?= $session->getFlash('error') ?>
        </div>
    <?php endif; ?>
    
    <!-- Filter Options -->
    <div class="row mb-3">
        <div class="col-md-12">
            <form action="<?= $_SERVER['PHP_SELF'] ?>" method="get" class="row g-3">
                <!-- <?php if ($userRole == 'super-admin'): ?>
                <div class="col-md-4">
                    <label for="role" class="form-label">Role</label>
                    <select name="role" id="role" class="form-select">
                        <option value="" <?= $roleFilter == '' ? 'selected' : '' ?>>All Roles</option>
                        <option value="super-admin" <?= $roleFilter == 'super-admin' ? 'selected' : '' ?>>Super Admin</option>
                        <option value="admin" <?= $roleFilter == 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="student" <?= $roleFilter == 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="staff" <?= $roleFilter == 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="other" <?= $roleFilter == 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <?php endif; ?> -->

                
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="" <?= $statusFilter == '' ? 'selected' : '' ?>>All Statuses</option>
                        <option value="active" <?= $statusFilter == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $statusFilter == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Users List -->
    <?php include_once BASE_PATH . '/templates/users/list.php'; ?>
    </div>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
