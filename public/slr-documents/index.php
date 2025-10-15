<?php
/**
 * SLR Documents Index - Fanders Microfinance System
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

// Check if user has permission to view SLR documents
if (!$auth->hasRole(['administrator', 'manager', 'account_officer'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Initialize SLR document service
$slrDocumentService = new SlrDocumentService();

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$search = $_GET['search'] ?? '';

// Build filters
$filters = [];
if ($status !== 'all') {
    $filters['status'] = $status;
}
if (!empty($startDate)) {
    $filters['start_date'] = $startDate;
}
if (!empty($endDate)) {
    $filters['end_date'] = $endDate;
}
if (!empty($search)) {
    $filters['search'] = $search;
}

// Get SLR documents
$slrDocuments = $slrDocumentService->searchSlrDocuments($filters);

// Get summary statistics
$stats = [
    'total' => count($slrDocuments),
    'draft' => count(array_filter($slrDocuments, function($slr) { return $slr['status'] == 'draft'; })),
    'approved' => count(array_filter($slrDocuments, function($slr) { return $slr['status'] == 'approved'; })),
    'disbursed' => count(array_filter($slrDocuments, function($slr) { return $slr['status'] == 'disbursed'; })),
    'total_amount' => array_sum(array_column($slrDocuments, 'disbursement_amount'))
];

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';

?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">SLR Documents</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/slr-documents/add.php" class="btn btn-primary">
                <i data-feather="plus"></i> Create SLR Document
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

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?= $stats['total'] ?></h5>
                    <p class="card-text">Total SLR Documents</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-warning"><?= $stats['draft'] ?></h5>
                    <p class="card-text">Draft</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-info"><?= $stats['approved'] ?></h5>
                    <p class="card-text">Approved</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success">₱<?= number_format($stats['total_amount'], 2) ?></h5>
                    <p class="card-text">Total Disbursed</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" <?= $status == 'all' ? 'selected' : '' ?>>All Status</option>
                        <option value="draft" <?= $status == 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="approved" <?= $status == 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="disbursed" <?= $status == 'disbursed' ? 'selected' : '' ?>>Disbursed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
                </div>
                <div class="col-md-2">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="SLR number, client name, phone...">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i data-feather="search"></i> Filter
                    </button>
                    <a href="<?= APP_URL ?>/public/slr-documents/index.php" class="btn btn-secondary">
                        <i data-feather="x"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- SLR Documents Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">SLR Documents</h5>
        </div>
        <div class="card-body">
            <?php if (empty($slrDocuments)): ?>
                <div class="text-center py-5">
                    <i data-feather="file-text" class="text-muted mb-3" style="width:48px;height:48px;"></i>
                    <h5 class="text-muted">No SLR Documents Found</h5>
                    <p class="text-muted">No SLR documents match your current filters.</p>
                    <a href="<?= APP_URL ?>/public/slr-documents/add.php" class="btn btn-primary">
                        <i data-feather="plus"></i> Create First SLR Document
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>SLR Number</th>
                                <th>Client Name</th>
                                <th>Loan Amount</th>
                                <th>Disbursement Amount</th>
                                <th>Status</th>
                                <th>Disbursement Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($slrDocuments as $slr): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($slr['slr_number'] ?? 'N/A') ?></strong>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($slr['client_name'] ?? 'N/A') ?></strong><br>
                                        <small class="text-muted">Phone: <?= htmlspecialchars($slr['client_phone'] ?? 'N/A') ?></small>
                                    </td>
                                    <td>₱<?= number_format($slr['loan_amount'] ?? 0, 2) ?></td>
                                    <td>₱<?= number_format($slr['disbursement_amount'] ?? 0, 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $slr['status'] == 'disbursed' ? 'success' : ($slr['status'] == 'approved' ? 'info' : 'secondary') ?>">
                                            <?= ucfirst($slr['status'] ?? 'unknown') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= isset($slr['disbursement_date']) ? date('M d, Y', strtotime($slr['disbursement_date'])) : '-' ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?= APP_URL ?>/public/slr-documents/view.php?id=<?= $slr['id'] ?>" class="btn btn-sm btn-outline-primary" title="View">
                                                <i data-feather="eye" style="width:14px;height:14px;"></i>
                                            </a>
                                            <?php if ($slr['status'] == 'draft' && $userRole != 'account_officer'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" title="Approve" onclick="approveSlr(<?= $slr['id'] ?>)">
                                                    <i data-feather="check" style="width:14px;height:14px;"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($slr['status'] == 'approved'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-info" title="Mark as Disbursed" onclick="disburseLoan(<?= $slr['id'] ?>)">
                                                    <i data-feather="dollar-sign" style="width:14px;height:14px;"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
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

<!-- Approve SLR Modal -->
<div class="modal fade" id="approveSlrModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve SLR Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveSlrForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf->generateToken() ?>">
                <input type="hidden" id="approve_slr_id" name="slr_id" value="">
                <div class="modal-body">
                    <p>Are you sure you want to approve this SLR document?</p>
                    <div class="alert alert-info">
                        <small>Approving this document will allow the loan to be marked as disbursed.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Disburse Loan Modal -->
<div class="modal fade" id="disburseLoanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Loan as Disbursed</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="disburseLoanForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf->generateToken() ?>">
                <input type="hidden" id="disburse_slr_id" name="slr_id" value="">
                <div class="modal-body">
                    <p>Are you sure you want to mark this loan as disbursed?</p>
                    <div class="alert alert-warning">
                        <small>This action will update the loan status to "Active" and start the repayment schedule.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Mark as Disbursed</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Initialize Feather icons
if (typeof feather !== 'undefined') {
    feather.replace();
}

// Approve SLR document
function approveSlr(slrId) {
    document.getElementById('approve_slr_id').value = slrId;
    const modal = new bootstrap.Modal(document.getElementById('approveSlrModal'));
    modal.show();
}

// Disburse loan
function disburseLoan(slrId) {
    document.getElementById('disburse_slr_id').value = slrId;
    const modal = new bootstrap.Modal(document.getElementById('disburseLoanModal'));
    modal.show();
}

// Handle approve SLR form submission
document.getElementById('approveSlrForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('<?= APP_URL ?>/public/slr-documents/approve.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while approving the SLR document.');
    });
});

// Handle disburse loan form submission
document.getElementById('disburseLoanForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('<?= APP_URL ?>/public/slr-documents/disburse.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while marking the loan as disbursed.');
    });
});
</script>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';


// End output buffering and flush output
ob_end_flush();
?>
