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
$userRole = $user['role_id'];

// Check if user has permission to view users list (Super Admin or Admin)
if (!$auth->hasRole([ROLE_SUPER_ADMIN, ROLE_ADMIN])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Initialize user service
$userService = new UserService();

// Process filter
$roleFilter = isset($_GET['role']) ? (int)$_GET['role'] : 0;
$statusFilter = isset($_GET['status']) ? (int)$_GET['status'] : -1;

// Get users based on filters
if ($userRole == ROLE_SUPER_ADMIN) {
    // Super admin can see all users
    $users = $userService->getAllUsersWithRoleNames();
} else {
    // Admin can only see borrowers
    $users = $userService->getAllBorrowers();
}

// Apply role filter if specified
if ($roleFilter > 0 && $userRole == ROLE_SUPER_ADMIN) {
    $filteredUsers = [];
    foreach ($users as $u) {
        if ($u['role_id'] == $roleFilter) {
            $filteredUsers[] = $u;
        }
    }
    $users = $filteredUsers;
}

// Apply status filter if specified
if ($statusFilter !== -1) {
    $filteredUsers = [];
    foreach ($users as $u) {
        if ($u['is_active'] == $statusFilter) {
            $filteredUsers[] = $u;
        }
    }
    $users = $filteredUsers;
}

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Users</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <?php if ($userRole == ROLE_SUPER_ADMIN): ?>
                    <a href="<?= APP_URL ?>/public/users/add.php" class="btn btn-sm btn-outline-primary">
                        <i data-feather="user-plus"></i> Add User
                    </a>
                    <a href="<?= APP_URL ?>/public/admins/add.php" class="btn btn-sm btn-outline-primary">
                        <i data-feather="user-plus"></i> Add Admin
                    </a>
                <?php endif; ?>
                
                <?php if ($userRole == ROLE_ADMIN): ?>
                    <a href="<?= APP_URL ?>/public/users/add.php" class="btn btn-sm btn-outline-primary">
                        <i data-feather="user-plus"></i> Add Borrower
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
                <?php if ($userRole == ROLE_SUPER_ADMIN): ?>
                <div class="col-md-4">
                    <label for="role" class="form-label">Role</label>
                    <select name="role" id="role" class="form-select">
                        <option value="0" <?= $roleFilter == 0 ? 'selected' : '' ?>>All Roles</option>
                        <option value="<?= ROLE_SUPER_ADMIN ?>" <?= $roleFilter == ROLE_SUPER_ADMIN ? 'selected' : '' ?>>Super Admin</option>
                        <option value="<?= ROLE_ADMIN ?>" <?= $roleFilter == ROLE_ADMIN ? 'selected' : '' ?>>Admin</option>
                        <option value="<?= ROLE_BORROWER ?>" <?= $roleFilter == ROLE_BORROWER ? 'selected' : '' ?>>Borrower</option>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="-1" <?= $statusFilter == -1 ? 'selected' : '' ?>>All Statuses</option>
                        <option value="1" <?= $statusFilter == 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $statusFilter == 0 ? 'selected' : '' ?>>Inactive</option>
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
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
