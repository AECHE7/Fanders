<?php
/**
 * Penalties report page for the Library Management System
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

// Check if user has permission to access penalties (Super Admin or Admin)
if (!$auth->hasRole(['super-admin', 'admin'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Initialize PenaltyService
$penaltyService = new PenaltyService();

// Process filter form
$filters = [];
$generatePdf = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $csrf->validateRequest()) {
    // Get filters
    $filters['start_date'] = isset($_POST['start_date']) && !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $filters['end_date'] = isset($_POST['end_date']) && !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $filters['is_paid'] = isset($_POST['is_paid']) && $_POST['is_paid'] !== '' ? $_POST['is_paid'] : null;
    
    // Check if PDF generation is requested
    $generatePdf = isset($_POST['generate_pdf']) && $_POST['generate_pdf'] == 1;
    
    if ($generatePdf) {
        // TODO: Implement PDF generation for penalties report
        // $penaltyService->generatePenaltiesReport($filters, true);
        exit; // PDF is output directly
    }
}

$penalties = $penaltyService->getPenaltiesForReports(
    $filters['start_date'] ?? null,
    $filters['end_date'] ?? null,
    $filters['status'] ?? null
);

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Penalties Report</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/dashboard.php" class="btn btn-sm btn-outline-secondary">
                <i data-feather="arrow-left"></i> Back to Dashboard
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
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?= isset($filters['start_date']) ? $filters['start_date'] : '' ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?= isset($filters['end_date']) ? $filters['end_date'] : '' ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All</option>
                            <option value="pending" <?= isset($filters['status']) && $filters['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="paid" <?= isset($filters['status']) && $filters['status'] === 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="waived" <?= isset($filters['status']) && $filters['status'] === 'waived' ? 'selected' : '' ?>>Waived</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end">
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
            <h5 class="mb-0">Penalties Report</h5>
            <small class="text-muted">Generated on: <?= date('Y-m-d H:i:s') ?></small>
            <small class="text-muted d-block">Period: <?= (isset($filters['start_date']) ? $filters['start_date'] : 'N/A') . ' to ' . (isset($filters['end_date']) ? $filters['end_date'] : 'N/A') ?></small>
        </div>
        <div class="card-body">
            <?php if (empty($penalties)): ?>
                <div class="alert alert-info">
                    No penalties found matching the selected criteria.
                </div>
            <?php else: ?>
                <?php include BASE_PATH . '/templates/penalties/list.php'; ?>
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
