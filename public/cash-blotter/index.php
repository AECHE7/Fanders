<?php
/**
 * Cash Blotter index page for the Fanders Microfinance System
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

// Check if user has permission to access cash blotter (Administrator, Manager, Collector)
if (!$auth->hasRole(['administrator', 'manager', 'collector'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Initialize cash blotter service
$cashBlotterService = new CashBlotterService();

// Get date filters
$startDateFilter = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDateFilter = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get blotters based on user role
if ($userRole == 'administrator') {
    // Administrators can see all blotters
    $blotters = $cashBlotterService->getBlotterSummary($startDateFilter, $endDateFilter);
} elseif ($userRole == 'manager') {
    // Managers can see blotters for their area (simplified - all for now)
    $blotters = $cashBlotterService->getBlotterSummary($startDateFilter, $endDateFilter);
} else {
    // Collectors can only see today's blotter
    $todayBlotter = $cashBlotterService->getTodayBlotter();
    $blotters = $todayBlotter ? ['blotters' => [$todayBlotter]] : ['blotters' => []];
}

// Get current cash position
$currentCashPosition = $cashBlotterService->getCurrentCashPosition();

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';

?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Cash Blotter</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <?php if ($userRole == 'administrator' || $userRole == 'manager'): ?>
                    <a href="<?= APP_URL ?>/public/cash-blotter/add.php" class="btn btn-sm btn-outline-success">
                        <i data-feather="plus"></i> Add Entry
                    </a>
                    <a href="<?= APP_URL ?>/public/reports/cash-blotter.php" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="file-text"></i> Generate Report
                    </a>
                <?php endif; ?>
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

    <!-- Current Cash Position -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">Current Cash Position</h5>
                            <h2 class="mb-0">₱<?= number_format($currentCashPosition, 2) ?></h2>
                        </div>
                        <div class="text-end">
                            <small>As of <?= date('M d, Y') ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Options -->
    <?php if ($userRole == 'administrator' || $userRole == 'manager'): ?>
        <div class="row mb-3">
            <div class="col-md-8">
                <form action="<?= $_SERVER['PHP_SELF'] ?>" method="get" class="d-flex gap-2">
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDateFilter) ?>" placeholder="Start Date">
                    <span class="align-self-center">-</span>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDateFilter) ?>" placeholder="End Date">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Reset</a>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Cash Blotter Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Daily Cash Records</h5>
        </div>
        <div class="card-body">
            <?php if (empty($blotters['blotters'])): ?>
                <div class="text-center py-5">
                    <i data-feather="dollar-sign" class="text-muted mb-3" style="width:48px;height:48px;"></i>
                    <h5 class="text-muted">No Cash Records Found</h5>
                    <p class="text-muted">No cash blotter records match your criteria.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Opening Balance</th>
                                <th>Collections</th>
                                <th>Loan Releases</th>
                                <th>Expenses</th>
                                <th>Closing Balance</th>
                                <th>Status</th>
                                <th>Recorded By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($blotters['blotters'] as $blotter): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($blotter['blotter_date'])) ?></td>
                                    <td>₱<?= number_format($blotter['opening_balance'], 2) ?></td>
                                    <td class="text-success">+₱<?= number_format($blotter['total_collections'], 2) ?></td>
                                    <td class="text-danger">-₱<?= number_format($blotter['total_loan_releases'], 2) ?></td>
                                    <td class="text-danger">-₱<?= number_format($blotter['total_expenses'], 2) ?></td>
                                    <td><strong>₱<?= number_format($blotter['closing_balance'], 2) ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?= $blotter['status'] == 'finalized' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($blotter['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($blotter['recorded_by_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?= APP_URL ?>/public/cash-blotter/view.php?id=<?= $blotter['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i data-feather="eye" style="width:14px;height:14px;"></i>
                                            </a>
                                            <?php if ($userRole == 'administrator' && $blotter['status'] == 'draft'): ?>
                                                <a href="<?= APP_URL ?>/public/cash-blotter/edit.php?id=<?= $blotter['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit Entry">
                                                    <i data-feather="edit" style="width:14px;height:14px;"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-success" title="Finalize" onclick="finalizeBlotter(<?= $blotter['id'] ?>)">
                                                    <i data-feather="check" style="width:14px;height:14px;"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary Statistics -->
                <?php if ($userRole == 'administrator' || $userRole == 'manager'): ?>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Period Summary</h6>
                                    <div class="row text-center">
                                        <div class="col-md-2">
                                            <strong>Total Opening:</strong><br>₱<?= number_format($blotters['total_opening_balance'] ?? 0, 2) ?>
                                        </div>
                                        <div class="col-md-2">
                                            <strong>Total Collections:</strong><br><span class="text-success">₱<?= number_format($blotters['total_collections'] ?? 0, 2) ?></span>
                                        </div>
                                        <div class="col-md-2">
                                            <strong>Total Releases:</strong><br><span class="text-danger">₱<?= number_format($blotters['total_loan_releases'] ?? 0, 2) ?></span>
                                        </div>
                                        <div class="col-md-2">
                                            <strong>Total Expenses:</strong><br><span class="text-danger">₱<?= number_format($blotters['total_expenses'] ?? 0, 2) ?></span>
                                        </div>
                                        <div class="col-md-2">
                                            <strong>Net Change:</strong><br><span class="text-<?= (($blotters['total_collections'] ?? 0) - ($blotters['total_loan_releases'] ?? 0) - ($blotters['total_expenses'] ?? 0)) >= 0 ? 'success' : 'danger' ?>">
                                                ₱<?= number_format((($blotters['total_collections'] ?? 0) - ($blotters['total_loan_releases'] ?? 0) - ($blotters['total_expenses'] ?? 0)), 2) ?>
                                            </span>
                                        </div>
                                        <div class="col-md-2">
                                            <strong>Final Balance:</strong><br><strong>₱<?= number_format($blotters['total_closing_balance'] ?? 0, 2) ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
// Initialize Feather icons
if (typeof feather !== 'undefined') {
    feather.replace();
}

// Finalize blotter function
function finalizeBlotter(blotterId) {
    if (confirm('Are you sure you want to finalize this cash blotter? This action cannot be undone.')) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= APP_URL ?>/public/cash-blotter/finalize.php';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'blotter_id';
        idInput.value = blotterId;

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?= $csrf->generateToken() ?>';

        form.appendChild(idInput);
        form.appendChild(csrfInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';


// End output buffering and flush output
ob_end_flush();
?>
