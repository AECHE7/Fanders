<?php
/**
 * View SLR Document Controller (view.php)
 * Role: Displays a comprehensive audit view of a single SLR document, loan details, and client information.
 * Integrates: SlrDocumentService
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control (Staff roles for operational and audit access)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier', 'account-officer']);

// Initialize services
$slrDocumentService = new SlrDocumentService();

// --- 1. Get SLR ID and Initial Data ---
$slrId = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$slrId) {
    $session->setFlash('error', 'Invalid SLR document ID.');
    header('Location: ' . APP_URL . '/public/slr-documents/index.php');
    exit;
}

// Get SLR document details (Model joins client, loan, disbursed_by users)
$slrDetails = $slrDocumentService->getSlrDetails($slrId);

if (!$slrDetails) {
    $session->setFlash('error', 'SLR document not found.');
    header('Location: ' . APP_URL . '/public/slr-documents/index.php');
    exit;
}

// Extract data for template use
$slr = $slrDetails['slr'];
$loan = $slrDetails['loan'];
$client = $slrDetails['client'];
$csrfToken = $csrf->generateToken();

// --- 2. Display View ---
$pageTitle = "SLR #{$slr['slr_number']} Details";
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';

// Helper function to get status badge class
function getSlrStatusBadgeClass($status) {
    switch(strtolower($status)) {
        case 'draft': return 'warning';
        case 'approved': return 'info';
        case 'disbursed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2">SLR Document Details</h1>
            <p class="text-muted mb-0">
                SLR Number: **<?= htmlspecialchars($slr['slr_number'] ?? 'N/A') ?>** |
                Client: <a href="<?= APP_URL ?>/public/clients/view.php?id=<?= $client['id'] ?>" class="text-primary"><?= htmlspecialchars($client['name']) ?></a>
            </p>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <?php if ($slr['status'] == 'draft' && $auth->hasRole(['super-admin', 'admin', 'manager'])): ?>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="approveSlr(<?= $slrId ?>)" data-bs-toggle="modal" data-bs-target="#approveSlrModal">
                        <i data-feather="check"></i> Approve
                    </button>
                <?php endif; ?>
                <?php if ($slr['status'] == 'approved'): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="disburseLoan(<?= $slrId ?>)" data-bs-toggle="modal" data-bs-target="#disburseLoanModal">
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
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Document Information & Status</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Loan Principal Amount:</strong><br>
                            <span class="fs-5 text-primary">₱<?= number_format($loan['principal'], 2) ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Disbursement Amount (Cash Given):</strong><br>
                            <span class="fs-5 text-success">₱<?= number_format($slr['disbursement_amount'], 2) ?></span>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                         <div class="col-md-6 mb-3">
                            <strong>SLR Status:</strong><br>
                            <span class="badge bg-<?= getSlrStatusBadgeClass($slr['status']) ?> fs-6">
                                <?= ucfirst($slr['status']) ?>
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Disbursement Date:</strong><br>
                            <?= $slr['disbursement_date'] ? date('F d, Y H:i', strtotime($slr['disbursement_date'])) : 'Not set' ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Approved By:</strong><br>
                            <?= htmlspecialchars($slr['approved_by_name'] ?? 'N/A') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Disbursed By:</strong><br>
                            <?= htmlspecialchars($slr['disbursed_by_name'] ?? 'N/A') ?>
                        </div>
                        <div class="col-md-12">
                            <strong>Notes / Remarks:</strong><br>
                            <?= nl2br(htmlspecialchars($slr['notes'] ?? 'None')) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline / Status Actions (Right Column) -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">Document Timeline</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <!-- Created -->
                        <div class="timeline-item">
                            <div class="timeline-marker bg-secondary"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">SLR Created (Draft)</h6>
                                <p class="timeline-text small">
                                    Document drafted for Loan #<?= $loan['id'] ?><br>
                                    <?= date('M d, Y H:i', strtotime($slr['created_at'])) ?>
                                </p>
                            </div>
                        </div>

                        <!-- Approved -->
                        <?php if ($slr['status'] == 'approved' || $slr['status'] == 'disbursed'): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Approved for Release</h6>
                                    <p class="timeline-text small">
                                        Approved by <?= htmlspecialchars($slr['approved_by_name'] ?? 'Manager') ?><br>
                                        <?= date('M d, Y H:i', strtotime($slr['updated_at'])) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Disbursed -->
                        <?php if ($slr['status'] == 'disbursed'): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Funds Disbursed (Loan Active)</h6>
                                    <p class="timeline-text small">
                                        Funds released to client. Loan schedule is now active.<br>
                                        <?= date('M d, Y H:i', strtotime($slr['disbursement_date'])) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
             <!-- Related Actions -->
            <div class="card mt-4 shadow-sm">
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
                    <a href="<?= APP_URL ?>/public/payments/record.php?loan_id=<?= $loan['id'] ?>" class="btn btn-outline-success btn-sm w-100">
                        <i data-feather="plus"></i> Record Payment
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modals for Approve and Disburse (Integrated from SLR Index) -->
<!-- Approve SLR Modal -->
<div class="modal fade" id="approveSlrModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve SLR Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="approveSlrForm">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" id="approve_slr_id" name="slr_id" value="<?= $slrId ?>">
                <div class="modal-body">
                    <p>Are you sure you want to approve SLR **<?= htmlspecialchars($slr['slr_number']) ?>**?</p>
                    <div class="alert alert-info">
                        <small>Approving this document will allow the disbursement process to begin. This step is for Manager/Admin sign-off.</small>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="disburseLoanForm">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" id="disburse_slr_id" name="slr_id" value="<?= $slrId ?>">
                <div class="modal-body">
                    <p>Confirm final disbursement for SLR **<?= htmlspecialchars($slr['slr_number']) ?>**. This will formally activate the loan schedule.</p>
                    <div class="alert alert-warning">
                        <small>CRITICAL: This action updates the associated loan status to **Active** and starts the repayment schedule.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Disbursement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// --- JS Event Handlers ---

// Re-map modal calls
function approveSlr(slrId) {
    const modal = new bootstrap.Modal(document.getElementById('approveSlrModal'));
    modal.show();
}

function disburseLoan(slrId) {
    const modal = new bootstrap.Modal(document.getElementById('disburseLoanModal'));
    modal.show();
}

function printSlr() {
    window.print();
}

// Handle AJAX submission for modals
document.getElementById('approveSlrForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('<?= APP_URL ?>/public/slr-documents/approve.php', { method: 'POST', body: formData })
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
        alert('An error occurred. Check console.');
    });
});

document.getElementById('disburseLoanForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('<?= APP_URL ?>/public/slr-documents/disburse.php', { method: 'POST', body: formData })
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
        alert('An error occurred. Check console.');
    });
});

// Initialize Feather icons and CSS for timeline
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>

<style>
/* --- Timeline Styles --- */
.timeline { position: relative; padding-left: 30px; }
.timeline-item { position: relative; margin-bottom: 20px; }
.timeline-marker {
    position: absolute; left: -38px; top: 5px; width: 12px; height: 12px;
    border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 2px #dee2e6;
}
.timeline-item:not(:last-child)::before {
    content: ''; position: absolute; left: -32px; top: 17px; width: 2px;
    height: calc(100% + 3px); background: #dee2e6;
}
.timeline-title { font-size: 0.9rem; margin-bottom: 2px; color: #495057; }
.timeline-text { margin: 0; color: #6c757d; }
</style>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>
