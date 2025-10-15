<?php
/**
 * View SLR Document - Fanders Microfinance System
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

// Get SLR ID
$slrId = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$slrId) {
    $session->setFlash('error', 'Invalid SLR document ID.');
    header('Location: ' . APP_URL . '/public/slr-documents/index.php');
    exit;
}

// Initialize SLR document service
$slrDocumentService = new SlrDocumentService();

// Get SLR document details
$slrDetails = $slrDocumentService->getSlrDetails($slrId);
if (!$slrDetails) {
    $session->setFlash('error', 'SLR document not found.');
    header('Location: ' . APP_URL . '/public/slr-documents/index.php');
    exit;
}

$slr = $slrDetails['slr'];
$loan = $slrDetails['loan'];
$client = $slrDetails['client'];

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';

?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2">SLR Document Details</h1>
            <p class="text-muted mb-0">
                SLR Number: <?= htmlspecialchars($slr['slr_number'] ?? 'N/A') ?> |
                Status: <span class="badge bg-<?= $slr['status'] == 'disbursed' ? 'success' : ($slr['status'] == 'approved' ? 'info' : 'secondary') ?>"><?= ucfirst($slr['status']) ?></span>
            </p>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <?php if ($slr['status'] == 'draft' && $userRole != 'account_officer'): ?>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="approveSlr(<?= $slrId ?>)">
                        <i data-feather="check"></i> Approve
                    </button>
                <?php endif; ?>
                <?php if ($slr['status'] == 'approved'): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="disburseLoan(<?= $slrId ?>)">
                        <i data-feather="dollar-sign"></i> Mark as Disbursed
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-outline-info" onclick="printSlr()">
                    <i data-feather="printer"></i> Print
                </button>
            </div>
            <a href="<?= APP_URL ?>/public/slr-documents/index.php" class="btn btn-sm btn-secondary">
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
        <!-- SLR Document Details -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Document Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>SLR Number:</strong><br>
                            <?= htmlspecialchars($slr['slr_number'] ?? 'N/A') ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong><br>
                            <span class="badge bg-<?= $slr['status'] == 'disbursed' ? 'success' : ($slr['status'] == 'approved' ? 'info' : 'secondary') ?>">
                                <?= ucfirst($slr['status']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Disbursement Date:</strong><br>
                            <?= $slr['disbursement_date'] ? date('F d, Y', strtotime($slr['disbursement_date'])) : 'Not set' ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Disbursement Amount:</strong><br>
                            ₱<?= number_format($slr['disbursement_amount'], 2) ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Client Present:</strong><br>
                            <span class="badge bg-<?= $slr['client_present'] ? 'success' : 'warning' ?>">
                                <?= $slr['client_present'] ? 'Yes' : 'No' ?>
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Witness Name:</strong><br>
                            <?= htmlspecialchars($slr['witness_name'] ?? 'N/A') ?>
                        </div>
                    </div>

                    <?php if ($slr['notes']): ?>
                        <div class="mb-3">
                            <strong>Notes:</strong><br>
                            <?= nl2br(htmlspecialchars($slr['notes'])) ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6">
                            <strong>Created:</strong><br>
                            <?= date('F d, Y H:i', strtotime($slr['created_at'])) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Last Updated:</strong><br>
                            <?= date('F d, Y H:i', strtotime($slr['updated_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Client Information -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Client Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Name:</strong><br>
                            <?= htmlspecialchars($client['name']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Phone:</strong><br>
                            <?= htmlspecialchars($client['phone_number'] ?? 'N/A') ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Email:</strong><br>
                            <?= htmlspecialchars($client['email'] ?? 'N/A') ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Address:</strong><br>
                            <?= htmlspecialchars($client['address'] ?? 'N/A') ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loan Information -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Loan Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Loan Amount:</strong><br>
                            ₱<?= number_format($loan['loan_amount'], 2) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Interest Rate:</strong><br>
                            <?= $loan['interest_rate'] ?>%
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Total Amount:</strong><br>
                            ₱<?= number_format($loan['total_amount'], 2) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Weekly Payment:</strong><br>
                            ₱<?= number_format($loan['weekly_payment'], 2) ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <strong>Loan Status:</strong><br>
                            <span class="badge bg-<?= $loan['status'] == 'active' ? 'success' : ($loan['status'] == 'approved' ? 'info' : 'secondary') ?>">
                                <?= ucfirst($loan['status']) ?>
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Application Date:</strong><br>
                            <?= date('F d, Y', strtotime($loan['application_date'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions and Status -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Actions</h6>
                </div>
                <div class="card-body">
                    <?php if ($slr['status'] == 'draft' && $userRole != 'account_officer'): ?>
                        <button type="button" class="btn btn-success btn-sm w-100 mb-2" onclick="approveSlr(<?= $slrId ?>)">
                            <i data-feather="check"></i> Approve SLR Document
                        </button>
                    <?php endif; ?>

                    <?php if ($slr['status'] == 'approved'): ?>
                        <button type="button" class="btn btn-primary btn-sm w-100 mb-2" onclick="disburseLoan(<?= $slrId ?>)">
                            <i data-feather="dollar-sign"></i> Mark Loan as Disbursed
                        </button>
                    <?php endif; ?>

                    <button type="button" class="btn btn-info btn-sm w-100 mb-2" onclick="printSlr()">
                        <i data-feather="printer"></i> Print SLR Document
                    </button>

                    <button type="button" class="btn btn-secondary btn-sm w-100" onclick="downloadPdf()">
                        <i data-feather="download"></i> Download PDF
                    </button>
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">Status Timeline</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-secondary"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Created</h6>
                                <p class="timeline-text small">
                                    SLR document created<br>
                                    <?= date('M d, Y H:i', strtotime($slr['created_at'])) ?>
                                </p>
                            </div>
                        </div>

                        <?php if ($slr['status'] == 'approved' || $slr['status'] == 'disbursed'): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Approved</h6>
                                    <p class="timeline-text small">
                                        Document approved for disbursement<br>
                                        <?= date('M d, Y H:i', strtotime($slr['updated_at'])) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($slr['status'] == 'disbursed'): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Disbursed</h6>
                                    <p class="timeline-text small">
                                        Loan funds disbursed to client<br>
                                        <?= date('M d, Y H:i', strtotime($slr['disbursement_date'])) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Related Actions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">Related Actions</h6>
                </div>
                <div class="card-body">
                    <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $loan['id'] ?>" class="btn btn-outline-primary btn-sm w-100 mb-2">
                        <i data-feather="eye"></i> View Loan Details
                    </a>
                    <a href="<?= APP_URL ?>/public/clients/view.php?id=<?= $client['id'] ?>" class="btn btn-outline-primary btn-sm w-100 mb-2">
                        <i data-feather="user"></i> View Client Profile
                    </a>
                    <a href="<?= APP_URL ?>/public/payments/add.php?loan_id=<?= $loan['id'] ?>" class="btn btn-outline-success btn-sm w-100">
                        <i data-feather="plus"></i> Record Payment
                    </a>
                </div>
            </div>
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
                <input type="hidden" name="slr_id" value="<?= $slrId ?>">
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
                <input type="hidden" name="slr_id" value="<?= $slrId ?>">
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
    const modal = new bootstrap.Modal(document.getElementById('approveSlrModal'));
    modal.show();
}

// Disburse loan
function disburseLoan(slrId) {
    const modal = new bootstrap.Modal(document.getElementById('disburseLoanModal'));
    modal.show();
}

// Print SLR document
function printSlr() {
    window.print();
}

// Download PDF (placeholder)
function downloadPdf() {
    alert('PDF download functionality will be implemented soon.');
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

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -38px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #dee2e6;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -32px;
    top: 17px;
    width: 2px;
    height: calc(100% + 3px);
    background: #dee2e6;
}

.timeline-title {
    font-size: 0.9rem;
    margin-bottom: 2px;
    color: #495057;
}

.timeline-text {
    margin: 0;
    color: #6c757d;
}
</style>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';


// End output buffering and flush output
ob_end_flush();
?>
