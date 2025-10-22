<?php
/**
 * View Staff User details page for the Fanders Microfinance Loan Management System
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
$viewUser = $userService->getUserWithRoleName($userId);

if (!$viewUser) {
    // User not found
    $session->setFlash('error', 'User not found.');
    header('Location: ' . APP_URL . '/public/users/index.php');
    exit;
}

// Get staff activity stats (loans processed, payments recorded, etc.)
$staffStats = [];
if (in_array($viewUser['role'], ['super-admin', 'admin', 'manager', 'cashier', 'account-officer'])) {
    // Get staff performance metrics
    $staffStats = $userService->getStaffActivityStats($userId);
}

// Check if user has permission to view this staff user
// Super Admin can view any staff user, Admin can only view operational staff or themselves
if ($userRole == 'admin' && !in_array($viewUser['role'], ['manager', 'cashier', 'account-officer']) && $userId !== $user['id']) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to view this staff user.');
    header('Location: ' . APP_URL . '/public/dashboard/index.php');
    exit;
}

// No additional data needed for staff users

// Handle activate/deactivate/delete actions
if (isset($_POST['action']) && $csrf->validateRequest()) {
    if ($_POST['action'] == 'activate' && $viewUser['status'] === UserModel::$STATUS_INACTIVE) {
        if ($userService->activateUser($userId)) {
            $session->setFlash('success', 'User activated successfully.');
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $userId);
            exit;
        } else {
            $session->setFlash('error', 'Failed to activate user.');
        }
    } elseif ($_POST['action'] == 'deactivate' && $viewUser['status'] === UserModel::$STATUS_ACTIVE) {
        if ($userService->deactivateUser($userId)) {
            $session->setFlash('success', 'User deactivated successfully.');
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $userId);
            exit;
        } else {
            $session->setFlash('error', $userService->getErrorMessage());
        }
    } elseif ($_POST['action'] == 'delete') {
        if ($userService->deleteUser($userId)) {
            $session->setFlash('success', 'User deleted successfully.');
            header('Location: ' . APP_URL . '/public/users/index.php');
            exit;
        } else {
            $session->setFlash('error', $userService->getErrorMessage());
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
        <h1 class="h2">Staff User Details</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                    <a href="<?= APP_URL ?>/public/users/edit.php?id=<?= $userId ?>" class="btn btn-sm btn-outline-primary">
                        <i data-feather="edit"></i> Edit Staff Profile
                    </a>
                    <?php if ($userRole === 'super-admin' || $userRole === 'admin'): ?>
                        <a href="<?= APP_URL ?>/public/users/index.php" class="btn btn-sm btn-outline-secondary">
                            <i data-feather="arrow-left"></i> Back to Staff
                        </a>
                    <?php endif; ?>
                    <a href="<?= APP_URL ?>/public/dashboard/index.php" class="btn btn-sm btn-outline-secondary">
                            <i data-feather="arrow-left"></i> Back to Dashboard
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
    
    <!-- User Details -->
    <?php include_once BASE_PATH . '/templates/users/view.php'; ?>
    
    <!-- Staff Activity Summary -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Staff Activity Summary</h5>
        </div>
        <div class="card-body">
            <?php if (empty($staffStats)): ?>
                <p class="text-muted">No activity data available.</p>
            <?php else: ?>
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-primary"><?= $staffStats['loans_processed'] ?? 0 ?></h4>
                            <small class="text-muted">Loans Processed</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-success"><?= $staffStats['payments_recorded'] ?? 0 ?></h4>
                            <small class="text-muted">Payments Recorded</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-info"><?= $staffStats['clients_served'] ?? 0 ?></h4>
                            <small class="text-muted">Clients Served</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="text-warning"><?= $staffStats['active_loans'] ?? 0 ?></h4>
                            <small class="text-muted">Active Loans</small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    
    <?php if (($userRole == 'super-admin') || 
             ($userRole == 'admin')): ?>
        <!-- Staff Management Actions -->
        <div class="card mt-4">
            <div class="card-header bg-<?= $viewUser['status'] ? 'warning' : 'success' ?> text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Staff Status Management</h5>
            </div>
            <div class="card-body">
            <?php if ($viewUser['status'] === UserModel::$STATUS_ACTIVE): ?>
                <p class="card-text">This staff member is currently active. You can deactivate this account.</p>
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#deactivateUserModal">
                    <i data-feather="user-x"></i> Deactivate Staff
                </button>
            <?php else: ?>
                <p class="card-text">This staff member is currently inactive. You can activate this account.</p>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#activateUserModal">
                    <i data-feather="user-check"></i> Activate Staff
                </button>
            <?php endif; ?>
            </div>
        </div>

        <!-- <div class="card mt-4">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Delete Zone</h5>
            </div>
            <div class="card-body">
            <?php if ($userRole === 'super-admin' || $userRole === 'admin'): ?>
                <p class="card-text">Deleting this user is irreversible. Please be certain..</p>
                    <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $userId ?>" onsubmit="return confirm('Are you sure you want to delete this user account? This action cannot be undone.');" style="margin:0;">
                        <?= $csrf->getTokenField() ?>
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i data-feather="trash-2"></i> Delete User
                        </button>
                    </form>
                <?php endif; ?>
            </div> -->
        </div>
    <?php endif; ?>
    </div>
</main>

<!-- Activate User Modal -->
<div class="modal fade" id="activateUserModal" tabindex="-1" aria-labelledby="activateUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="activateUserModalLabel">
                    <i data-feather="user-check"></i> Confirm Staff Activation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>You are about to activate staff account:</p>
                <ul class="list-unstyled ms-3">
                    <li><strong>Name:</strong> <?= htmlspecialchars($viewUser['full_name']) ?></li>
                    <li><strong>Username:</strong> <?= htmlspecialchars($viewUser['username']) ?></li>
                    <li><strong>Role:</strong> <span class="badge text-bg-primary"><?= ucfirst($viewUser['role']) ?></span></li>
                </ul>
                <div class="alert alert-info mt-3">
                    <i data-feather="info"></i> Activating this account will restore system access.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $userId ?>" method="post" style="display:inline;">
                    <?= $csrf->getTokenField() ?>
                    <input type="hidden" name="action" value="activate">
                    <button type="submit" class="btn btn-success">
                        <i data-feather="user-check"></i> Confirm Activation
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Deactivate User Modal -->
<div class="modal fade" id="deactivateUserModal" tabindex="-1" aria-labelledby="deactivateUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="deactivateUserModalLabel">
                    <i data-feather="user-x"></i> Confirm Staff Deactivation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i data-feather="alert-triangle"></i> <strong>Warning:</strong> Deactivating this staff account will revoke system access.
                </div>
                <p>Staff to deactivate:</p>
                <ul class="list-unstyled ms-3">
                    <li><strong>Name:</strong> <?= htmlspecialchars($viewUser['full_name']) ?></li>
                    <li><strong>Username:</strong> <?= htmlspecialchars($viewUser['username']) ?></li>
                    <li><strong>Role:</strong> <span class="badge text-bg-primary"><?= ucfirst($viewUser['role']) ?></span></li>
                </ul>
                <p class="text-muted small">This staff member will no longer be able to log in to the system.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $userId ?>" method="post" style="display:inline;">
                    <?= $csrf->getTokenField() ?>
                    <input type="hidden" name="action" value="deactivate">
                    <button type="submit" class="btn btn-warning">
                        <i data-feather="user-x"></i> Confirm Deactivation
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
