<?php
/**
 * SLR Documents Index Controller (index.php)
 * Role: Displays a list of all SLR documents for management and disbursement actions.
 * Integrates: SlrDocumentService
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control (Staff roles for operational and audit access)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account-officer']);

// Initialize services
$slrDocumentService = new SlrDocumentService();

// --- 1. Get Filter Parameters ---
$filters = [
    'status' => $_GET['status'] ?? 'all',
    'start_date' => $_GET['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? '',
    'search' => $_GET['search'] ?? '',
];

// --- 2. Fetch Data ---

if (!empty($filters['search'])) {
    $slrDocuments = $slrDocumentService->searchSlrDocuments($filters['search']);
} else if ($filters['status'] !== 'all') {
    $slrDocuments = $slrDocumentService->getSlrDocumentsByStatus($filters['status']);
} else if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
     $slrDocuments = $slrDocumentService->getSlrsByDateRange($filters['start_date'], $filters['end_date']);
} else {
    // Default: Get all documents
    $slrDocuments = $slrDocumentService->slrDocumentModel->getAllSlrsWithClientDetails(); 
    /* NOTE: Since SlrDocumentService::searchSlrDocuments(array $filters) 
       is meant to handle all filtering, we will use a dedicated model method 
       to fetch all detailed documents efficiently if no filter is applied. 
       Assuming getAllSlrsWithClientDetails exists in SlrDocumentModel. */
    // If the model method doesn't exist, use the search method with an empty term:
    $slrDocuments = $slrDocumentService->searchSlrDocuments('');
}


// --- 3. Calculate Summary Statistics ---
// These statistics are calculated dynamically based on the full dataset or can be pulled from a ReportService later.
$stats = [
    'total' => count($slrDocuments),
    'draft' => count(array_filter($slrDocuments, fn($slr) => $slr['status'] == 'draft')),
    'approved' => count(array_filter($slrDocuments, fn($slr) => $slr['status'] == 'approved')),
    'disbursed' => count(array_filter($slrDocuments, fn($slr) => $slr['status'] == 'disbursed')),
    // Total disbursement amount only includes 'disbursed' loans
    'total_amount' => array_sum(array_column(array_filter($slrDocuments, fn($slr) => $slr['status'] == 'disbursed'), 'disbursement_amount'))
];

// --- 4. Display View ---
$pageTitle = "SLR Documents Management";
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';

// Helper function to get status badge class (used in the HTML table below)
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
        <h1 class="h2">SLR Documents</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <!-- Only Admins/Managers can create new SLR documents -->
            <?php if ($auth->hasRole(['super-admin', 'admin', 'manager'])): ?>
                <a href="<?= APP_URL ?>/public/slr-documents/add.php" class="btn btn-primary">
                    <i data-feather="plus"></i> Generate New SLR
                </a>
            <?php endif; ?>
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
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?= $stats['total'] ?></h5>
                    <p class="card-text">Total SLR Documents</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-warning"><?= $stats['draft'] ?></h5>
                    <p class="card-text">Pending DRAFT</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-info"><?= $stats['approved'] ?></h5>
                    <p class="card-text">Awaiting Disbursement</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-success">₱<?= number_format($stats['total_amount'], 2) ?></h5>
                    <p class="card-text">Total Disbursed Amount</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="<?= APP_URL ?>/public/slr-documents/index.php" class="row g-3">
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" <?= $filters['status'] == 'all' ? 'selected' : '' ?>>All Status</option>
                        <option value="draft" <?= $filters['status'] == 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="approved" <?= $filters['status'] == 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="disbursed" <?= $filters['status'] == 'disbursed' ? 'selected' : '' ?>>Disbursed</option>
                        <option value="cancelled" <?= $filters['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= htmlspecialchars($filters['start_date']) ?>">
                </div>
                <div class="col-md-2">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= htmlspecialchars($filters['end_date']) ?>">
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($filters['search']) ?>" placeholder="SLR number, client name, phone...">
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
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">SLR Documents List (<?= $stats['total'] ?> Found)</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($slrDocuments)): ?>
                <div class="text-center py-5">
                    <i data-feather="file-text" class="text-muted mb-3" style="width:48px;height:48px;"></i>
                    <h5 class="text-muted">No SLR Documents Found</h5>
                    <p class="text-muted">No SLR documents match your current filters.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 15%;">SLR Number</th>
                                <th style="width: 20%;">Client Name</th>
                                <th style="width: 15%;">Principal</th>
                                <th style="width: 15%;">Disbursement Amount</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 15%;">Disbursement Date</th>
                                <th style="width: 10%;">Actions</th>
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
                                        <small class="text-muted">Loan ID: <?= $slr['loan_id'] ?></small>
                                    </td>
                                    <td>₱<?= number_format($slr['principal'] ?? 0, 2) ?></td>
                                    <td>₱<?= number_format($slr['disbursement_amount'] ?? 0, 2) ?></td>
                                    <td>
                                        <span class="badge text-bg-<?= getSlrStatusBadgeClass($slr['status']) ?>">
                                            <?= ucfirst($slr['status'] ?? 'unknown') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= isset($slr['disbursement_date']) && $slr['disbursement_date'] !== NULL 
                                            ? date('M d, Y', strtotime($slr['disbursement_date'])) : '-' ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= APP_URL ?>/public/slr-documents/view.php?id=<?= $slr['id'] ?>" class="btn btn-outline-primary" title="View/Audit Document">
                                                <i data-feather="eye" style="width:14px;height:14px;"></i>
                                            </a>
                                            <?php if ($slr['status'] == 'draft' && $auth->hasRole(['super-admin', 'admin', 'manager'])): ?>
                                                <button type="button" class="btn btn-outline-success btn-slr-action" data-action="approve" data-id="<?= $slr['id'] ?>" title="Approve SLR Document">
                                                    <i data-feather="check" style="width:14px;height:14px;"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($slr['status'] == 'approved' && $auth->hasRole(['super-admin', 'admin', 'manager'])): ?>
                                                <button type="button" class="btn btn-outline-info btn-slr-action" data-action="disburse" data-id="<?= $slr['id'] ?>" title="Mark as Disbursed (Activates Loan)">
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

<!-- Hidden Form for Approve/Disburse Actions -->
<form id="slrActionForm" method="POST" action="<?= APP_URL ?>/public/slr-documents/index.php" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="slr_id" id="slrActionId">
    <input type="hidden" name="action" id="slrActionType">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const actionForm = document.getElementById('slrActionForm');
    const actionIdInput = document.getElementById('slrActionId');
    const actionTypeInput = document.getElementById('slrActionType');

    document.querySelectorAll('.btn-slr-action').forEach(button => {
        button.addEventListener('click', function() {
            const slrId = this.getAttribute('data-id');
            const action = this.getAttribute('data-action');
            let message = '';
            let confirmAction = false;

            if (action === 'approve') {
                message = `Confirm approval of SLR Document ID ${slrId}? This makes the loan ready for physical disbursement.`;
                confirmAction = true;
            } else if (action === 'disburse') {
                message = `CRITICAL ACTION: Confirm DISBURSEMENT for SLR ID ${slrId}? This will transactionally activate the associated loan's repayment schedule.`;
                confirmAction = true;
            }

            if (confirmAction && confirm(message)) {
                actionIdInput.value = slrId;
                actionTypeInput.value = action;
                actionForm.submit();
            }
        });
    });
    
    // Feather icons re-initialization 
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>