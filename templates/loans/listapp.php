<?php
/**
 * Loans List Template (templates/loans/list.php)
 * Displays a table of all loans, handling role-based actions.
 * Assumes variables are passed from public/loans/index.php:
 * @var array $loans Array of loan records with client data
 * @var AuthService $auth The authenticated user service
 * @var string $csrfToken The CSRF token for forms
 * @var callable $getLoanStatusBadgeClass Helper function for status styling
 */

// We assume $userRole is available from the controller environment
global $userRole; 

// Ensure the helper function is available (defined in public/loans/index.php)
if (!function_exists('getLoanStatusBadgeClass')) {
    function getLoanStatusBadgeClass($status) {
        switch(strtolower($status)) {
            case 'active': return 'success';
            case 'application': return 'warning';
            case 'approved': return 'info';
            case 'completed': return 'primary';
            case 'defaulted': return 'danger';
            default: return 'secondary';
        }
    }
}
?>
<!-- Custom Styling for Loans Table -->
<style>
    .loans-approval-table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
    }
    
    .loans-approval-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .loans-approval-table thead th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 1rem 0.75rem;
        border: none;
        vertical-align: middle;
    }
    
    .loans-approval-table thead th:first-child {
        border-top-left-radius: 0;
    }
    
    .loans-approval-table thead th:last-child {
        border-top-right-radius: 0;
    }
    
    .loans-approval-table tbody tr {
        transition: all 0.2s ease;
        border-bottom: 1px solid #e9ecef;
        background-color: white;
    }
    
    .loans-approval-table tbody tr:hover {
        background-color: #f8f9fe;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.08);
    }
    
    .loans-approval-table tbody td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
        border: none;
    }
    
    .loans-approval-table tbody tr:last-child td:first-child {
        border-bottom-left-radius: 0;
    }
    
    .loans-approval-table tbody tr:last-child td:last-child {
        border-bottom-right-radius: 0;
    }
    
    .loan-id-badge {
        display: inline-block;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
    }
    
    .client-info-cell {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .client-name-link {
        color: #2d3748;
        font-weight: 600;
        text-decoration: none;
        transition: color 0.2s ease;
        font-size: 0.95rem;
    }
    
    .client-name-link:hover {
        color: #667eea;
        text-decoration: underline;
    }
    
    .client-phone {
        color: #718096;
        font-size: 0.8rem;
    }
    
    .amount-cell {
        font-weight: 700;
        color: #2d3748;
        font-size: 0.95rem;
    }
    
    .status-badge-custom {
        padding: 0.4rem 0.9rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
        white-space: nowrap;
    }
    
    .status-active { 
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); 
        color: white; 
        box-shadow: 0 2px 4px rgba(72, 187, 120, 0.3);
    }
    .status-application { 
        background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%); 
        color: white; 
        box-shadow: 0 2px 4px rgba(66, 153, 225, 0.3);
    }
    .status-approved { 
        background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%); 
        color: white; 
        box-shadow: 0 2px 4px rgba(237, 137, 54, 0.3);
    }
    .status-completed { 
        background: linear-gradient(135deg, #9f7aea 0%, #805ad5 100%); 
        color: white; 
        box-shadow: 0 2px 4px rgba(159, 122, 234, 0.3);
    }
    .status-defaulted { 
        background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%); 
        color: white; 
        box-shadow: 0 2px 4px rgba(245, 101, 101, 0.3);
    }
    .status-secondary { 
        background: #a0aec0; 
        color: white; 
    }
    
    .date-cell {
        color: #718096;
        font-size: 0.85rem;
    }
    
    .action-btn-group {
        display: flex;
        gap: 0.4rem;
        justify-content: flex-start;
        flex-wrap: wrap;
    }
    
    .action-btn-group .btn {
        border-radius: 8px;
        transition: all 0.2s ease;
        border: 2px solid transparent;
        padding: 0.375rem 0.75rem;
    }
    
    .action-btn-group .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .action-btn-group .btn-outline-info:hover {
        background-color: #0dcaf0;
        border-color: #0dcaf0;
        color: white;
    }
    
    .action-btn-group .btn-outline-success:hover {
        background-color: #198754;
        border-color: #198754;
        color: white;
    }
    
    .action-btn-group .btn-outline-danger:hover {
        background-color: #dc3545;
        border-color: #dc3545;
        color: white;
    }
    
    .action-btn-group .btn-outline-primary:hover {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: white;
    }
    
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
</style>

<!-- Loans List Table -->
<?php if (empty($loans)): ?>
    <div class="alert alert-info m-4" role="alert">
        <div class="d-flex align-items-center">
            <i data-feather="info" class="me-2"></i>
            <div>
                <strong>No loans found</strong>
                <p class="mb-0 small">Try adjusting your filters or search criteria to find more results.</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table loans-approval-table align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 8%;">Loan ID</th>
                    <th style="width: 22%;">Client Details</th>
                    <th style="width: 13%;">Principal</th>
                    <th style="width: 13%;">Weekly Pay</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 12%;">Applied On</th>
                    <th style="width: 20%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loans as $loan): ?>
                    <tr>
                        <td>
                            <span class="loan-id-badge">#<?= htmlspecialchars($loan['id']) ?></span>
                        </td>
                        <td>
                            <div class="client-info-cell">
                                <a href="<?= APP_URL ?>/public/clients/view.php?id=<?= $loan['client_id'] ?>" class="client-name-link">
                                    <?= htmlspecialchars($loan['client_name'] ?? 'N/A') ?>
                                </a>
                                <span class="client-phone">
                                    <i data-feather="phone" style="width: 12px; height: 12px;"></i>
                                    <?= htmlspecialchars($loan['phone_number'] ?? 'N/A') ?>
                                </span>
                            </div>
                        </td>
                        <td class="amount-cell">₱<?= number_format($loan['principal'], 2) ?></td>
                        <?php 
                            $termWeeks = (int)($loan['term_weeks'] ?? 0);
                            $weeklyPay = $termWeeks > 0 ? ($loan['total_loan_amount'] / $termWeeks) : 0;
                        ?>
                        <td class="amount-cell">₱<?= number_format($weeklyPay, 2) ?></td>
                        <td>
                            <span class="status-badge-custom status-<?= strtolower($loan['status']) ?>">
                                <?= htmlspecialchars(ucfirst($loan['status'])) ?>
                            </span>
                        </td>
                        <td class="date-cell">
                            <i data-feather="calendar" style="width: 14px; height: 14px;"></i>
                            <?= htmlspecialchars(date('M d, Y', strtotime($loan['application_date']))) ?>
                        </td>
                        <td>
                            <div class="action-btn-group">
                                <!-- View Details -->
                                <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $loan['id'] ?>" 
                                   class="btn btn-sm btn-outline-info" 
                                   title="View Loan Details"
                                   data-bs-toggle="tooltip">
                                    <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                </a>

                                <?php 
                                $status = strtolower($loan['status']);
                                // Check if user can approve/disburse (Admin/Manager role check)
                                $canManage = $auth->hasRole(['super-admin', 'admin', 'manager']);
                                ?>

                                <?php if ($canManage && $status === 'application'): ?>
                                    <!-- Approve Button (Manager/Admin action) -->
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-success btn-loan-action"
                                            data-action="approve" 
                                            data-id="<?= $loan['id'] ?>" 
                                            title="Approve Loan"
                                            data-bs-toggle="tooltip">
                                        <i data-feather="check" style="width: 14px; height: 14px;"></i> Approve
                                    </button>
                                    <!-- Cancel Button -->
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger btn-loan-action"
                                            data-action="cancel" 
                                            data-id="<?= $loan['id'] ?>" 
                                            title="Cancel Application"
                                            data-bs-toggle="tooltip">
                                        <i data-feather="x-circle" style="width: 14px; height: 14px;"></i>
                                    </button>
                                <?php endif; ?>

                                <?php if ($canManage && $status === 'approved'): ?>
                                    <!-- Disburse Button (Manager/Admin action) -->
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-primary btn-loan-action"
                                            data-action="disburse" 
                                            data-id="<?= $loan['id'] ?>" 
                                            title="Disburse Funds (Activate Loan)"
                                            data-bs-toggle="tooltip">
                                        <i data-feather="send" style="width: 14px; height: 14px;"></i> Disburse
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

<!-- Pagination -->
<?php if (isset($totalPages) && $totalPages > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-4 px-3 pb-3">
        <div class="text-muted small">
            <?= isset($pagination) ? $pagination->getInfo() : "Page {$page} of {$totalPages}" ?>
        </div>
        <nav aria-label="Loans pagination">
            <?php if (isset($pagination)): ?>
                <?php
                // Clean filters for pagination to preserve filter state
                require_once __DIR__ . '/../app/utilities/FilterUtility.php';
                $paginationFilters = FilterUtility::cleanFiltersForUrl($filters ?? []);
                unset($paginationFilters['page']); // Remove page from filters since pagination will add it
                ?>
                <?= $pagination->render($paginationFilters) ?>
            <?php endif; ?>
        </nav>
    </div>
<?php endif; ?>

<!-- Approval Confirmation Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);">
                <h5 class="modal-title" id="approvalModalLabel">
                    <i data-feather="check-circle" class="me-2"></i> Confirm Loan Approval
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 shadow-sm mb-3">
                    <div class="d-flex align-items-start">
                        <i data-feather="info" class="me-2 mt-1"></i>
                        <div>
                            <strong>Note:</strong> After confirming this approval, a loan agreement will be automatically generated and saved to the system.
                        </div>
                    </div>
                </div>
                <p class="mb-3 fw-semibold">You are about to approve:</p>
                <div class="bg-light p-3 rounded">
                    <div class="row g-2">
                        <div class="col-12">
                            <small class="text-muted">Loan ID:</small>
                            <p class="mb-1 fw-bold">#<span id="modalLoanId"></span></p>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Client:</small>
                            <p class="mb-1 fw-bold"><span id="modalClientName"></span></p>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Principal Amount:</small>
                            <p class="mb-0 fw-bold"><span id="modalPrincipal"></span></p>
                        </div>
                    </div>
                </div>
                <p class="text-muted small mt-3 mb-0">
                    <i data-feather="alert-circle" style="width: 14px; height: 14px;"></i>
                    This action cannot be easily undone. Please verify all details before proceeding.
                </p>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmApprovalBtn">
                    <i data-feather="check" class="me-1"></i> Confirm Approval
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Disburse Confirmation Modal -->
<div class="modal fade" id="disburseModal" tabindex="-1" aria-labelledby="disburseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);">
                <h5 class="modal-title" id="disburseModalLabel">
                    <i data-feather="send" class="me-2"></i> Confirm Loan Disbursement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-3 fw-semibold">You are about to disburse funds for:</p>
                <div class="bg-light p-3 rounded">
                    <div class="row g-2">
                        <div class="col-12">
                            <small class="text-muted">Loan ID:</small>
                            <p class="mb-1 fw-bold">#<span id="modalDisburseLoanId"></span></p>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Client:</small>
                            <p class="mb-1 fw-bold"><span id="modalDisburseClientName"></span></p>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Amount to Disburse:</small>
                            <p class="mb-0 fw-bold"><span id="modalDisbursePrincipal"></span></p>
                        </div>
                    </div>
                </div>
                <div class="alert alert-warning border-0 shadow-sm mt-3 mb-0">
                    <div class="d-flex align-items-start">
                        <i data-feather="alert-triangle" class="me-2 mt-1"></i>
                        <div>
                            <small>This will activate the payment schedule. Please ensure funds are ready for disbursement.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmDisburseBtn">
                    <i data-feather="send" class="me-1"></i> Confirm Disbursement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Confirmation Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);">
                <h5 class="modal-title" id="cancelModalLabel">
                    <i data-feather="x-circle" class="me-2"></i> Confirm Loan Cancellation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning border-0 shadow-sm mb-3">
                    <div class="d-flex align-items-start">
                        <i data-feather="alert-triangle" class="me-2 mt-1"></i>
                        <div>
                            <strong>Warning:</strong> This action will cancel the loan application permanently.
                        </div>
                    </div>
                </div>
                <p class="mb-3 fw-semibold">You are about to cancel:</p>
                <div class="bg-light p-3 rounded">
                    <div class="row g-2">
                        <div class="col-12">
                            <small class="text-muted">Loan ID:</small>
                            <p class="mb-1 fw-bold">#<span id="modalCancelLoanId"></span></p>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Client:</small>
                            <p class="mb-0 fw-bold"><span id="modalCancelClientName"></span></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="confirmCancelBtn">
                    <i data-feather="x-circle" class="me-1"></i> Confirm Cancellation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form for POST Actions (Approve/Disburse/Cancel) -->
<form id="loanActionForm" method="POST" action="<?= APP_URL ?>/public/loans/approvals.php" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
    <input type="hidden" name="id" id="loanActionId">
    <input type="hidden" name="action" id="loanActionType">
</form>

<!-- JavaScript for Action Confirmation -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const actionForm = document.getElementById('loanActionForm');
        const actionIdInput = document.getElementById('loanActionId');
        const actionTypeInput = document.getElementById('loanActionType');

        // Store current loan data for modals
        let currentLoanData = {};

        document.querySelectorAll('.btn-loan-action').forEach(button => {
            button.addEventListener('click', function() {
                const loanId = this.getAttribute('data-id');
                const action = this.getAttribute('data-action');
                
                // Get loan data from the row
                const row = this.closest('tr');
                const clientName = row.querySelector('td:nth-child(2) a').textContent.trim();
                const principal = row.querySelector('td:nth-child(3)').textContent.trim();
                
                currentLoanData = {
                    id: loanId,
                    clientName: clientName,
                    principal: principal,
                    action: action
                };

                // Show appropriate modal based on action
                if (action === 'approve') {
                    document.getElementById('modalLoanId').textContent = loanId;
                    document.getElementById('modalClientName').textContent = clientName;
                    document.getElementById('modalPrincipal').textContent = principal;
                    const approvalModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('approvalModal'));
                    approvalModal.show();
                } else if (action === 'disburse') {
                    document.getElementById('modalDisburseLoanId').textContent = loanId;
                    document.getElementById('modalDisburseClientName').textContent = clientName;
                    document.getElementById('modalDisbursePrincipal').textContent = principal;
                    const disburseModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('disburseModal'));
                    disburseModal.show();
                } else if (action === 'cancel') {
                    document.getElementById('modalCancelLoanId').textContent = loanId;
                    document.getElementById('modalCancelClientName').textContent = clientName;
                    const cancelModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('cancelModal'));
                    cancelModal.show();
                }
            });
        });

        // Confirmation buttons
        document.getElementById('confirmApprovalBtn').addEventListener('click', function() {
            actionIdInput.value = currentLoanData.id;
            actionTypeInput.value = 'approve';
            actionForm.submit();
        });

        document.getElementById('confirmDisburseBtn').addEventListener('click', function() {
            actionIdInput.value = currentLoanData.id;
            actionTypeInput.value = 'disburse';
            actionForm.submit();
        });

        document.getElementById('confirmCancelBtn').addEventListener('click', function() {
            actionIdInput.value = currentLoanData.id;
            actionTypeInput.value = 'cancel';
            actionForm.submit();
        });
    });
</script>