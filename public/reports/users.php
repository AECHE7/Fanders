<?php
/**
 * Users report page for the Library Management System
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

// Check if user has permission to generate reports (Super Admin or Admin)
if (!$auth->hasRole(['super-admin', 'admin'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Initialize services
$userService = new UserService();
$reportService = new ReportService();

// Process filter form
$filters = [];
$generatePdf = false;

// Determine roles filter based on logged-in user's role
if ($userRole === 'admin') {
    // Admin can only see borrower roles, exclude admin and super-admin
    $filters['roles'] = [
        UserModel::$ROLE_STUDENT,
        UserModel::$ROLE_STAFF,
        UserModel::$ROLE_OTHER
    ];
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $csrf->validateRequest()) {
        $filters['status'] = isset($_POST['status']) && $_POST['status'] !== '' ? (int)$_POST['status'] : null;
        $generatePdf = isset($_POST['generate_pdf']) && $_POST['generate_pdf'] == 1;
    }
} elseif ($userRole === 'super-admin') {
    // Super-admin can filter by role from form input
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $csrf->validateRequest()) {
        $roleInput = isset($_POST['role_id']) && !empty($_POST['role_id']) ? $_POST['role_id'] : null;
        if ($roleInput) {
            $filters['role'] = $roleInput;
        }
        $filters['status'] = isset($_POST['status']) && $_POST['status'] !== '' ? (int)$_POST['status'] : null;
        $generatePdf = isset($_POST['generate_pdf']) && $_POST['generate_pdf'] == 1;
    } else {
        // For GET requests or no form submission, allow all roles
        $filters['role'] = null;
        $filters['status'] = null;
    }
} else {
    // Other roles have no access
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

if ($generatePdf) {
    // Generate PDF
    $reportService->generateUsersReport($filters, true);
    exit; // PDF is output directly
}

// Generate the report with applied filters
$reportData = $reportService->generateUsersReport($filters);

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Users Report</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/reports/index.php" class="btn btn-sm btn-outline-primary me-2">
                <i data-feather="list"></i> Reports Index
            </a>
            <a href="<?= APP_URL ?>/public/users/index.php" class="btn btn-sm btn-outline-secondary">
                <i data-feather="arrow-left"></i> Back to Users
            </a>
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
    
    <!-- Report Filter Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Report Filters</h5>
        </div>
        <div class="card-body">
            <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
                <?= $csrf->getTokenField() ?>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="role_id" class="form-label">Role</label>
                    <select name="role" id="role" class="form-select">
                        <option value="">All Roles</option>
                        <?php if ($userRole == 'super-admin'): ?>
                        <option value="<?= UserModel::$ROLE_SUPER_ADMIN ?>" <?= isset($filters['role']) && $filters['role'] == UserModel::$ROLE_SUPER_ADMIN ? 'selected' : '' ?>>Super Admin</option>
                        <option value="<?= UserModel::$ROLE_ADMIN ?>" <?= isset($filters['role']) && $filters['role'] == UserModel::$ROLE_ADMIN ? 'selected' : '' ?>>Admin</option>
                        <?php elseif ($userRole == 'admin'): ?>
                        <option value="<?= UserModel::$ROLE_STUDENT ?>" <?= isset($filters['role']) && $filters['role'] == UserModel::$ROLE_STUDENT ? 'selected' : '' ?>>Student</option>
                        <option value="<?= UserModel::$ROLE_STAFF ?>" <?= isset($filters['role']) && $filters['role'] == UserModel::$ROLE_STAFF ? 'selected' : '' ?>>Staff</option>
                        <option value="<?= UserModel::$ROLE_OTHER ?>" <?= isset($filters['role']) && $filters['role'] == UserModel::$ROLE_OTHER ? 'selected' : '' ?>>Other</option>
                        <?php endif; ?>
                    </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="1" <?= isset($filters['status']) && $filters['status'] == 1 ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= isset($filters['status']) && $filters['status'] == 0 ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" name="apply_filters" class="btn btn-primary me-2">Apply Filters</button>
                        <button type="submit" name="generate_pdf" value="1" class="btn btn-success">
                            <i data-feather="file-text"></i> Generate PDF
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Report Results -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Users Report</h5>
            <small class="text-muted">Generated on: <?= $reportData['generated_date'] ?></small>
        </div>
        <div class="card-body">
            <?php if (empty($reportData['users'])): ?>
                <div class="alert alert-info">
                    No users found matching the selected criteria.
                </div>
            <?php else: ?>
                <div class="mb-3">
                    <strong>Total Users:</strong> <?= $reportData['total_users'] ?><br>
                    <strong>Active Users:</strong> <?= $reportData['total_active'] ?><br>
                    <strong>Inactive Users:</strong> <?= $reportData['total_inactive'] ?>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData['users'] as $user): ?>
                                <tr>
                                    <!-- Name -->
                                    <td><?= htmlspecialchars($user['name'] ?? '',ENT_QUOTES,'UTF-8') ?></td>
                                    <!-- Email Address (unique) -->
                                    <td><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <!-- Phone Number (unique) -->
                                    <td><?= htmlspecialchars($user['phone_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <!-- Role (students, staff, admin, others) -->
                                    <td>
                                        <?php
                                    $role = strtolower($user['role'] ?? '');
                                    switch ($role) {
                                        case 'super-admin':
                                        case 'admin':
                                            echo 'Admin';
                                            break;
                                        case 'staff':
                                            echo 'Staff';
                                            break;
                                        case 'student':
                                            echo 'Student';
                                            break;
                                        case 'borrower':
                                            echo 'Borrower';
                                            break;
                                        case 'other':
                                            echo 'Other';
                                            break;
                                        default:
                                            echo !empty($role) ? ucfirst($role) : 'Other';
                                    }
                                        ?>
                                    </td>
                                    <!-- Account Status -->
                                    <td>
                                        <?php 
                                            $status = strtolower($user['status'] ?? '');
                                            switch ($status) {
                                                case 'active':
                                                    echo '<span class="badge bg-success">Active</span>';
                                                    break;
                                                case 'inactive':
                                                    echo '<span class="badge bg-danger">Inactive</span>';
                                                    break;
                                                case 'suspended':
                                                    echo '<span class="badge bg-warning text-dark">Suspended</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge bg-secondary">Unknown</span>';
                                                    break;
                                            }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
