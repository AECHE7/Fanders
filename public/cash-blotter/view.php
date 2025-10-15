<?php
/**
 * View Cash Blotter Details - Fanders Microfinance System
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

// Check if user has permission to access cash blotter
if (!$auth->hasRole(['administrator', 'manager', 'collector'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Get blotter ID from URL
$blotterId = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$blotterId) {
    $session->setFlash('error', 'Invalid blotter ID.');
    header('Location: ' . APP_URL . '/public/cash-blotter/index.php');
    exit;
}

// Initialize cash blotter service
$cashBlotterService = new CashBlotterService();

// Get blotter details
$blotter = $cashBlotterService->getBlotterById($blotterId);
if (!$blotter) {
    $session->setFlash('error', 'Cash blotter entry not found.');
    header('Location: ' . APP_URL . '/public/cash-blotter/index.php');
    exit;
}

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';

?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Cash Blotter Details</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <?php if ($userRole == 'administrator' || $userRole == 'manager'): ?>
                    <?php if ($blotter['status'] == 'draft'): ?>
                        <a href="<?= APP_URL ?>/public/cash-blotter/edit.php?id=<?= $blotter['id'] ?>" class="btn btn-sm btn-outline-warning">
                            <i data-feather="edit"></i> Edit
                        </a>
                    <?php endif; ?>
                    <a href="<?= APP_URL ?>/public/reports/cash-blotter.php?id=<?= $blotter['id'] ?>" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="file-text"></i> Generate Report
                    </a>
                <?php endif; ?>
            </div>
            <a href="<?= APP_URL ?>/public/cash-blotter/index.php" class="btn btn-secondary">
                <i data-feather="arrow-left"></i> Back to List
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

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Blotter Information</h5>
                    <span class="badge bg-<?= $blotter['status'] == 'finalized' ? 'success' : 'warning' ?>">
                        <?= ucfirst($blotter['status']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Date:</strong><br>
                            <?= date('F d, Y', strtotime($blotter['blotter_date'])) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Recorded By:</strong><br>
                            <?= htmlspecialchars($blotter['recorded_by_name'] ?? 'N/A') ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Created:</strong><br>
                            <?= date('F d, Y H:i', strtotime($blotter['created_at'])) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Last Updated:</strong><br>
                            <?= date('F d, Y H:i', strtotime($blotter['updated_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Cash Flow Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="border rounded p-3 mb-3">
                                <h6 class="text-muted mb-2">Opening Balance</h6>
                                <h4 class="mb-0">₱<?= number_format($blotter['opening_balance'], 2) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 mb-3 bg-success bg-opacity-10">
                                <h6 class="text-success mb-2">Collections</h6>
                                <h4 class="text-success mb-0">+₱<?= number_format($blotter['total_collections'], 2) ?></h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="border rounded p-3 mb-3 bg-danger bg-opacity-10">
                                <h6 class="text-danger mb-2">Loan Releases</h6>
                                <h4 class="text-danger mb-0">-₱<?= number_format($blotter['total_loan_releases'], 2) ?></h4>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 mb-3 bg-danger bg-opacity-10">
                                <h6 class="text-danger mb-2">Expenses</h6>
                                <h4 class="text-danger mb-0">-₱<?= number_format($blotter['total_expenses'], 2) ?></h4>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="border rounded p-3 bg-primary bg-opacity-10">
                                <h6 class="text-primary mb-2">Closing Balance</h6>
                                <h3 class="text-primary mb-0">₱<?= number_format($blotter['closing_balance'], 2) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Balance Verification</h6>
                </div>
                <div class="card-body">
                    <?php
                    $calculatedBalance = $blotter['opening_balance'] + $blotter['total_collections'] - $blotter['total_loan_releases'] - $blotter['total_expenses'];
                    $isBalanced = abs($calculatedBalance - $blotter['closing_balance']) < 0.01;
                    ?>
                    <div class="mb-3">
                        <strong>Calculated Balance:</strong><br>
                        ₱<?= number_format($calculatedBalance, 2) ?>
                    </div>
                    <div class="mb-3">
                        <strong>Recorded Balance:</strong><br>
                        ₱<?= number_format($blotter['closing_balance'], 2) ?>
                    </div>
                    <div class="mb-3">
                        <strong>Difference:</strong><br>
                        <span class="<?= $isBalanced ? 'text-success' : 'text-danger' ?>">
                            ₱<?= number_format($blotter['closing_balance'] - $calculatedBalance, 2) ?>
                        </span>
                    </div>
                    <div class="alert alert-<?= $isBalanced ? 'success' : 'warning' ?> p-2">
                        <small>
                            <i data-feather="<?= $isBalanced ? 'check-circle' : 'alert-triangle' ?>"></i>
                            <?= $isBalanced ? 'Balances match' : 'Balance discrepancy detected' ?>
                        </small>
                    </div>
                </div>
            </div>

            <?php if ($userRole == 'administrator' && $blotter['status'] == 'draft'): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">Actions</h6>
                    </div>
                    <div class="card-body">
                        <button type="button" class="btn btn-success btn-sm w-100" onclick="finalizeBlotter(<?= $blotter['id'] ?>)">
                            <i data-feather="check"></i> Finalize Blotter
                        </button>
                        <div class="form-text">
                            <small>Once finalized, this entry cannot be modified.</small>
                        </div>
                    </div>
                </div>
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
