<?php
/**
 * Collection Sheets index page for the Fanders Microfinance System
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

// Check if user has permission to access collection sheets (Administrator, Manager, Account Officer)
if (!$auth->hasRole(['administrator', 'manager', 'account_officer'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Initialize collection sheet service
$collectionSheetService = new CollectionSheetService();

// Get date filters
$startDateFilter = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDateFilter = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get collection sheets based on user role
if ($userRole == 'administrator') {
    // Administrators can see all collection sheets
    $sheets = $collectionSheetService->getAllSheets(['start_date' => $startDateFilter, 'end_date' => $endDateFilter]);
} elseif ($userRole == 'manager') {
    // Managers can see all collection sheets for approval/review
    $sheets = $collectionSheetService->getAllSheets(['start_date' => $startDateFilter, 'end_date' => $endDateFilter]);
} else {
    // Account officers can only see their own sheets
    $sheets = $collectionSheetService->getOfficerSheets($user['id'], ['start_date' => $startDateFilter, 'end_date' => $endDateFilter]);
}

// Get pending approvals count for managers/administrators
$pendingApprovalsCount = 0;
if ($userRole == 'administrator' || $userRole == 'manager') {
    $pendingSheets = $collectionSheetService->getPendingApprovals();
    $pendingApprovalsCount = count($pendingSheets);
}

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';

?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Collection Sheets</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <?php if ($userRole == 'administrator' || $userRole == 'manager'): ?>
                    <a href="<?= APP_URL ?>/public/collection-sheets/add.php" class="btn btn-sm btn-outline-success">
                        <i data-feather="plus"></i> Create Sheet
                    </a>
                    <?php if ($pendingApprovalsCount > 0): ?>
                        <a href="<?= APP_URL ?>/public/collection-sheets/approvals.php" class="btn btn-sm btn-outline-warning">
                            <i data-feather="check-circle"></i> Pending Approvals (<?= $pendingApprovalsCount ?>)
                        </a>
                    <?php endif; ?>
                <?php elseif ($userRole == 'account_officer'): ?>
                    <a href="<?= APP_URL ?>/public/collection-sheets/add.php" class="btn btn-sm btn-outline-success">
                        <i data-feather="plus"></i> Create Sheet
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

    <!-- Collection Sheets Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Collection Sheets</h5>
        </div>
        <div class="card-body">
            <?php if (empty($sheets)): ?>
                <div class="text-center py-5">
                    <i data-feather="file-text" class="text-muted mb-3" style="width:48px;height:48px;"></i>
                    <h5 class="text-muted">No Collection Sheets Found</h5>
                    <p class="text-muted">No collection sheets match your criteria.</p>
                    <?php if ($userRole == 'account_officer'): ?>
                        <a href="<?= APP_URL ?>/public/collection-sheets/add.php" class="btn btn-primary">Create Your First Sheet</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Collection Date</th>
                                <th>Account Officer</th>
                                <th>Expected Amount</th>
                                <th>Collected Amount</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sheets as $sheet): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($sheet['collection_date'])) ?></td>
                                    <td><?= htmlspecialchars($sheet['officer_name'] ?? 'N/A') ?></td>
                                    <td>₱<?= number_format($sheet['total_expected'], 2) ?></td>
                                    <td class="text-success">₱<?= number_format($sheet['total_collected'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $sheet['status'] == 'approved' ? 'success' : ($sheet['status'] == 'submitted' ? 'warning' : 'secondary') ?>">
                                            <?= ucfirst($sheet['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($sheet['created_at'])) ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?= APP_URL ?>/public/collection-sheets/view.php?id=<?= $sheet['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                                <i data-feather="eye" style="width:14px;height:14px;"></i>
                                            </a>
                                            <?php if ($userRole == 'account_officer' && $sheet['status'] == 'draft'): ?>
                                                <a href="<?= APP_URL ?>/public/collection-sheets/edit.php?id=<?= $sheet['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit Sheet">
                                                    <i data-feather="edit" style="width:14px;height:14px;"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-info" title="Submit for Approval" onclick="submitSheet(<?= $sheet['id'] ?>)">
                                                    <i data-feather="send" style="width:14px;height:14px;"></i>
                                                </button>
                                            <?php elseif (($userRole == 'administrator' || $userRole == 'manager') && $sheet['status'] == 'submitted'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" title="Approve Sheet" onclick="approveSheet(<?= $sheet['id'] ?>)">
                                                    <i data-feather="check" style="width:14px;height:14px;"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" title="Reject Sheet" onclick="rejectSheet(<?= $sheet['id'] ?>)">
                                                    <i data-feather="x" style="width:14px;height:14px;"></i>
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
                                        <div class="col-md-3">
                                            <strong>Total Sheets:</strong><br><?= count($sheets) ?>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Total Expected:</strong><br>₱<?= number_format(array_sum(array_column($sheets, 'total_expected')), 2) ?>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Total Collected:</strong><br><span class="text-success">₱<?= number_format(array_sum(array_column($sheets, 'total_collected')), 2) ?></span>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Collection Rate:</strong><br>
                                            <?php
                                            $totalExpected = array_sum(array_column($sheets, 'total_expected'));
                                            $totalCollected = array_sum(array_column($sheets, 'total_collected'));
                                            $rate = $totalExpected > 0 ? ($totalCollected / $totalExpected) * 100 : 0;
                                            ?>
                                            <span class="text-<?= $rate >= 80 ? 'success' : ($rate >= 50 ? 'warning' : 'danger') ?>">
                                                <?= number_format($rate, 1) ?>%
                                            </span>
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

// Submit sheet for approval
function submitSheet(sheetId) {
    if (confirm('Are you sure you want to submit this collection sheet for approval? You will not be able to edit it after submission.')) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= APP_URL ?>/public/collection-sheets/submit.php';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'sheet_id';
        idInput.value = sheetId;

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

// Approve collection sheet
function approveSheet(sheetId) {
    if (confirm('Are you sure you want to approve this collection sheet?')) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= APP_URL ?>/public/collection-sheets/approve.php';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'sheet_id';
        idInput.value = sheetId;

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

// Reject collection sheet
function rejectSheet(sheetId) {
    const reason = prompt('Please provide a reason for rejection:');
    if (reason !== null && reason.trim() !== '') {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= APP_URL ?>/public/collection-sheets/reject.php';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'sheet_id';
        idInput.value = sheetId;

        const reasonInput = document.createElement('input');
        reasonInput.type = 'hidden';
        reasonInput.name = 'rejection_reason';
        reasonInput.value = reason;

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?= $csrf->generateToken() ?>';

        form.appendChild(idInput);
        form.appendChild(reasonInput);
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
