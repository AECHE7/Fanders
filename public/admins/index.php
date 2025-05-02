<?php
/**
 * Admins list page for the Library Management System
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

// Check if user has permission to view admins list (only Super Admin)
if ($userRole != ROLE_SUPER_ADMIN) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Initialize user service
$userService = new UserService();

// Get all admins
$admins = $userService->getAllAdmins();

// Process status filter
$statusFilter = isset($_GET['status']) ? (int)$_GET['status'] : -1;

// Apply status filter if specified
if ($statusFilter !== -1) {
    $filteredAdmins = [];
    foreach ($admins as $admin) {
        if ($admin['is_active'] == $statusFilter) {
            $filteredAdmins[] = $admin;
        }
    }
    $admins = $filteredAdmins;
}

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Administrators</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="<?= APP_URL ?>/public/admins/add.php" class="btn btn-sm btn-outline-primary">
                    <i data-feather="user-plus"></i> Add New Admin
                </a>
                <a href="<?= APP_URL ?>/public/reports/users.php?role=<?= ROLE_ADMIN ?>" class="btn btn-sm btn-outline-secondary">
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
        <div class="col-md-6">
            <form action="<?= $_SERVER['PHP_SELF'] ?>" method="get" class="d-flex">
                <select name="status" class="form-select me-2">
                    <option value="-1" <?= $statusFilter == -1 ? 'selected' : '' ?>>All Statuses</option>
                    <option value="1" <?= $statusFilter == 1 ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= $statusFilter == 0 ? 'selected' : '' ?>>Inactive</option>
                </select>
                <button type="submit" class="btn btn-primary me-2">Filter</button>
                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Reset</a>
            </form>
        </div>
    </div>
    
    <!-- Admins List -->
    <?php include_once BASE_PATH . '/templates/admins/list.php'; ?>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
