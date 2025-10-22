<?php
/**
 * Staff Users Management Index page for the Fanders Microfinance Loan Management System
 * Main entry point for staff user management with overview and quick actions
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

// Check if user has permission to view staff users (Super Admin or Admin)
if (!$auth->hasRole(['super-admin', 'admin'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard/index.php');
    exit;
}

// Initialize user service
$userService = new UserService();

// Get staff statistics
$staffStats = $userService->getStaffStats();

// Get recent staff users
$recentStaff = $userService->getRecentStaffUsers(5);

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
    <!-- Dashboard Header with Title, Date and Reports Links -->
    <div class="notion-page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0f4fd;">
                        <i data-feather="users" style="width: 24px; height: 24px; color:rgb(0, 0, 0);"></i>
                    </div>
                </div>
                <h1 class="notion-page-title mb-0">Staff Management</h1>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <div class="text-muted d-none d-md-block me-3">
                    <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i>
                    <?= date('l, F j, Y') ?>
                </div>
                <a href="<?= APP_URL ?>/public/reports/users.php" class="btn btn-sm btn-outline-secondary px-3">
                    <i data-feather="file-text" class="me-1" style="width: 14px; height: 14px;"></i> Staff Report
                </a>
                <?php if ($userRole == 'super-admin' || $userRole == 'admin'): ?>
                    <a href="<?= APP_URL ?>/public/users/add.php" class="btn btn-sm btn-outline-primary">
                        <i data-feather="user-plus" class="me-1" style="width: 14px; height: 14px;"></i> Add Staff
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="notion-divider my-3"></div>
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

    <!-- Staff Statistics Overview -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="card-title">
                        <h2 class="text-primary mb-0"><?= $staffStats['total_staff'] ?? 0 ?></h2>
                        <small class="text-muted">Total Staff</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="card-title">
                        <h2 class="text-success mb-0"><?= $staffStats['active_staff'] ?? 0 ?></h2>
                        <small class="text-muted">Active Staff</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="card-title">
                        <h2 class="text-warning mb-0"><?= $staffStats['inactive_staff'] ?? 0 ?></h2>
                        <small class="text-muted">Inactive Staff</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="card-title">
                        <h2 class="text-info mb-0"><?= $staffStats['recent_staff'] ?? 0 ?></h2>
                        <small class="text-muted">Added This Month</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff by Role -->
    <div class="row g-4 mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Staff Distribution by Role</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php
                        $roles = [
                            'super-admin' => ['label' => 'Super Admin', 'color' => 'danger'],
                            'admin' => ['label' => 'Admin', 'color' => 'warning'],
                            'manager' => ['label' => 'Manager', 'color' => 'primary'],
                            'cashier' => ['label' => 'Cashier', 'color' => 'success'],
                            'account-officer' => ['label' => 'Account Officer', 'color' => 'info']
                        ];

                        foreach ($roles as $roleKey => $roleInfo):
                            $count = $staffStats['staff_by_role'][$roleKey] ?? 0;
                        ?>
                            <div class="col-md-2">
                                <div class="text-center">
                                    <h4 class="text-<?= $roleInfo['color'] ?> mb-0"><?= $count ?></h4>
                                    <small class="text-muted"><?= $roleInfo['label'] ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Staff Users -->
    <div class="row g-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Staff Users</h5>
                    <a href="<?= APP_URL ?>/public/users/list.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentStaff)): ?>
                        <p class="text-muted">No recent staff users found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentStaff as $staff): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($staff['name']) ?></td>
                                            <td><?= htmlspecialchars($staff['email']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= getRoleBadgeClass($staff['role']) ?>">
                                                    <?= htmlspecialchars(ucfirst($staff['role'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $staff['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                    <?= htmlspecialchars(ucfirst($staff['status'])) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($staff['created_at'])) ?></td>
                                            <td>
                                                <a href="<?= APP_URL ?>/public/users/view.php?id=<?= $staff['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i data-feather="eye"></i>
                                                </a>
                                                <?php if ($userRole === 'super-admin' || ($userRole === 'admin' && in_array($staff['role'], ['manager', 'cashier', 'account-officer']))): ?>
                                                    <a href="<?= APP_URL ?>/public/users/edit.php?id=<?= $staff['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i data-feather="edit-2"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-4 mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="<?= APP_URL ?>/public/users/add.php" class="btn btn-primary w-100 mb-2">
                                <i data-feather="user-plus" class="me-2"></i>Add New Staff
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?= APP_URL ?>/public/users/list.php?status=inactive" class="btn btn-warning w-100 mb-2">
                                <i data-feather="user-x" class="me-2"></i>Manage Inactive Staff
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?= APP_URL ?>/public/reports/users.php" class="btn btn-info w-100 mb-2">
                                <i data-feather="file-text" class="me-2"></i>Staff Reports
                            </a>
                        </div>
                        <div class="col-md-3">
                                                        <a href="<?= APP_URL ?>/public/dashboard/index.php" class="btn btn-secondary w-100 mb-2">
                                <i data-feather="arrow-left" class="me-1" style="width: 14px; height: 14px;"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</main>

<?php
/**
 * Get badge class for role
 */
function getRoleBadgeClass($role) {
    switch($role) {
        case 'super-admin':
            return 'danger';
        case 'admin':
            return 'warning';
        case 'manager':
            return 'primary';
        case 'cashier':
            return 'success';
        case 'account-officer':
            return 'info';
        default:
            return 'secondary';
    }
}

// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
