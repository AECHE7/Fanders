<?php
/**
 * SLR Management Interface
 * Provides comprehensive SLR document management capabilities
 */

require_once '../init.php';
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier']);

$slrService = new SLRServiceAdapter();
$pageTitle = 'SLR Document Management';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$csrf->validateRequest(false)) {
        $session->setFlash('error', 'Invalid security token.');
        header('Location: ' . APP_URL . '/public/slr/manage.php');
        exit;
    }
    
    switch ($_POST['action']) {
        case 'archive':
            $slrId = (int)($_POST['slr_id'] ?? 0);
            $reason = trim($_POST['reason'] ?? '');
            
            if ($slrId && $slrService->archiveSLR($slrId, $user['id'], $reason)) {
                $session->setFlash('success', 'SLR document archived successfully.');
            } else {
                $session->setFlash('error', 'Failed to archive SLR: ' . $slrService->getErrorMessage());
            }
            break;
            
        case 'generate':
            $loanId = (int)($_POST['loan_id'] ?? 0);
            $trigger = $_POST['trigger'] ?? 'manual_request';
            
            if ($loanId) {
                $slrDocument = $slrService->generateSLR($loanId, $user['id'], $trigger);
                if ($slrDocument) {
                    $session->setFlash('success', 'SLR document generated successfully!');
                } else {
                    $session->setFlash('error', 'Failed to generate SLR: ' . $slrService->getErrorMessage());
                }
            }
            break;
    }
    
    header('Location: ' . APP_URL . '/public/slr/manage.php');
    exit;
}

// Get filters
$filters = [
    'loan_id' => isset($_GET['loan_id']) ? (int)$_GET['loan_id'] : null,
    'status' => $_GET['status'] ?? '',
    'client_id' => isset($_GET['client_id']) ? (int)$_GET['client_id'] : null,
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
];

// Remove empty filters
$filters = array_filter($filters, function($value) {
    return $value !== '' && $value !== null;
});

// Get SLR documents with pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$slrDocuments = $slrService->listSLRDocuments($filters, $limit, $offset);

// Get total count for pagination
require_once BASE_PATH . '/app/services/SLR/SLRRepository.php';
$database = Database::getInstance();
$slrRepository = new App\Services\SLR\SLRRepository($database->getConnection());
$totalDocuments = $slrRepository->countSLRDocuments($filters);
$totalPages = ceil($totalDocuments / $limit);

include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="notion-page-header mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <div class="me-3">
                        <div class="page-icon rounded d-flex align-items-center justify-content-center" 
                             style="width: 50px; height: 50px; background-color: #e8f5e8;">
                            <i data-feather="file-text" style="width:24px;height:24px;color:#16a34a;"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="notion-page-title mb-0">SLR Document Management</h1>
                        <div class="text-muted small">
                            Statement of Loan Receipt Documents
                        </div>
                    </div>
                </div>
                <div>
                    <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="arrow-left" style="width: 14px; height: 14px;" class="me-1"></i> Back to Loans
                    </a>
                </div>
            </div>
            <div class="notion-divider my-3"></div>
        </div>

        <!-- Flash Messages -->
        <?php if ($session->hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $session->getFlash('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($session->hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $session->getFlash('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <i data-feather="filter" style="width: 18px; height: 18px;" class="me-2"></i>
                    <strong>Filter SLR Documents</strong>
                </div>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Loan ID</label>
                        <input type="number" class="form-control" name="loan_id" 
                               value="<?= htmlspecialchars($_GET['loan_id'] ?? '') ?>" placeholder="Enter loan ID">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Statuses</option>
                            <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="archived" <?= ($_GET['status'] ?? '') === 'archived' ? 'selected' : '' ?>>Archived</option>
                            <option value="replaced" <?= ($_GET['status'] ?? '') === 'replaced' ? 'selected' : '' ?>>Replaced</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date From</label>
                        <input type="date" class="form-control" name="date_from" 
                               value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date To</label>
                        <input type="date" class="form-control" name="date_to" 
                               value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="search" style="width: 14px; height: 14px;" class="me-1"></i> Filter
                        </button>
                        <a href="<?= APP_URL ?>/public/slr/manage.php" class="btn btn-outline-secondary">
                            <i data-feather="x" style="width: 14px; height: 14px;" class="me-1"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- SLR Documents List -->
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>SLR Documents</strong>
                <span class="badge bg-primary"><?= $totalDocuments ?> total</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($slrDocuments)): ?>
                    <div class="text-center py-5 text-muted">
                        <i data-feather="file" style="width: 48px; height: 48px;" class="mb-3"></i>
                        <p>No SLR documents found matching the current filters.</p>
                        <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-primary">
                            <i data-feather="plus" style="width: 14px; height: 14px;" class="me-1"></i> Generate from Loans
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Document #</th>
                                    <th>Loan ID</th>
                                    <th>Client</th>
                                    <th>Amount</th>
                                    <th>Generated</th>
                                    <th>Downloads</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($slrDocuments as $slr): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($slr['document_number']) ?></strong>
                                        </td>
                                        <td>
                                            <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $slr['loan_id'] ?>" 
                                               class="text-decoration-none">
                                                #<?= $slr['loan_id'] ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($slr['client_name']) ?>
                                            <small class="text-muted d-block">ID: <?= $slr['client_id'] ?></small>
                                        </td>
                                        <td>
                                            ₱<?= number_format($slr['principal'], 2) ?>
                                            <small class="text-muted d-block">Total: ₱<?= number_format($slr['total_loan_amount'], 2) ?></small>
                                        </td>
                                        <td>
                                            <?= date('M d, Y', strtotime($slr['generated_at'])) ?>
                                            <small class="text-muted d-block">by <?= htmlspecialchars($slr['generated_by_name']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= $slr['download_count'] ?></span>
                                            <?php if ($slr['last_downloaded_at']): ?>
                                                <small class="text-muted d-block">
                                                    Last: <?= date('M d, Y', strtotime($slr['last_downloaded_at'])) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $slr['status'] === 'active' ? 'success' : ($slr['status'] === 'archived' ? 'secondary' : 'warning') ?>">
                                                <?= ucfirst($slr['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <?php if ($slr['status'] === 'active'): ?>
                                                    <a href="<?= APP_URL ?>/public/slr/generate.php?action=download&loan_id=<?= $slr['loan_id'] ?>" 
                                                       class="btn btn-sm btn-success" title="Download SLR">
                                                        <i data-feather="download" style="width: 12px; height: 12px;"></i>
                                                    </a>
                                                    <?php if (in_array($userRole, ['super-admin', 'admin'])): ?>
                                                        <button type="button" class="btn btn-sm btn-warning" 
                                                                onclick="archiveSLR(<?= $slr['id'] ?>, '<?= htmlspecialchars($slr['document_number']) ?>')" 
                                                                title="Archive SLR">
                                                            <i data-feather="archive" style="width: 12px; height: 12px;"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted small">Archived</span>
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

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Showing <?= count($slrDocuments) ?> of <?= $totalDocuments ?> documents
                </div>
                <nav aria-label="SLR pagination">
                    <ul class="pagination mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Archive Modal -->
<div class="modal fade" id="archiveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Archive SLR Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="archive">
                    <input type="hidden" name="slr_id" id="archiveSlrId">
                    
                    <p>Are you sure you want to archive this SLR document?</p>
                    <p><strong>Document:</strong> <span id="archiveDocumentNumber"></span></p>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for archival:</label>
                        <textarea class="form-control" name="reason" rows="3" 
                                  placeholder="Enter reason for archiving this document..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Archive Document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function archiveSLR(slrId, documentNumber) {
    document.getElementById('archiveSlrId').value = slrId;
    document.getElementById('archiveDocumentNumber').textContent = documentNumber;
    
    new bootstrap.Modal(document.getElementById('archiveModal')).show();
}
</script>

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>