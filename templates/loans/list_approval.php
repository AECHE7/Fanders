<?php
/**
 * Loans Approval List Template (templates/loans/list_approval.php)
 * Displays a clean table of loans needing approval using the same design as the main loan index.
 * Assumes variables are passed from public/loans/approvals.php:
 * @var array $loans Array of loan records with client data
 * @var AuthService $auth The authenticated user service
 * @var string $csrfToken The CSRF token for forms
 * @var callable $getLoanStatusBadgeClass Helper function for status styling
 */

// We assume $userRole is available from the controller environment
global $userRole; 

// Ensure the helper function is available (defined in public/loans/approvals.php)
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
<!-- Loans Approval List Table -->
<?php if (empty($loans)): ?>
    <div class="alert alert-info" role="alert">
        <div class="d-flex align-items-center">
            <i data-feather="info" class="me-2"></i>
            <div>
                <strong>No loans pending approval</strong>
                <p class="mb-0 small">All applications have been processed or no new applications are available.</p>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
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
                            <span class="badge bg-primary">#<?= htmlspecialchars($loan['id']) ?></span>
                        </td>
                        <td>
                            <div>
                                <a href="<?= APP_URL ?>/public/clients/view.php?id=<?= $loan['client_id'] ?>" class="text-decoration-none fw-medium">
                                    <?= htmlspecialchars($loan['client_name'] ?? 'N/A') ?>
                                </a>
                                <small class="text-muted d-block">
                                    <i data-feather="phone" style="width: 12px; height: 12px;"></i>
                                    <?= htmlspecialchars($loan['phone_number'] ?? 'N/A') ?>
                                </small>
                            </div>
                        </td>
                        <td>₱<?= number_format($loan['principal'], 2) ?></td>
                        <?php 
                            // Calculate weekly payment
                            $termWeeks = (int)($loan['term_weeks'] ?? 0);
                            $weeklyPay = $termWeeks > 0 ? ($loan['total_loan_amount'] / $termWeeks) : 0;
                        ?>
                        <td>₱<?= number_format($weeklyPay, 2) ?></td>
                        <td>
                            <span class="badge text-bg-<?= getLoanStatusBadgeClass($loan['status']) ?>">
                                <?= htmlspecialchars(ucfirst($loan['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <i data-feather="calendar" style="width: 14px; height: 14px;"></i>
                            <?= htmlspecialchars(date('M d, Y', strtotime($loan['application_date']))) ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <!-- View Details -->
                                <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $loan['id'] ?>" 
                                   class="btn btn-outline-info" 
                                   title="View Loan Details"
                                   data-bs-toggle="tooltip">
                                    <i data-feather="eye"></i>
                                </a>

                                <?php 
                                $status = strtolower($loan['status']);
                                // Check if user can approve/disburse (Admin/Manager role check)
                                $canManage = $auth->hasRole(['super-admin', 'admin', 'manager']);
                                ?>

                                <?php if ($canManage && $status === 'application'): ?>
                                    <!-- Approve Button (Manager/Admin action) -->
                                    <button type="button" 
                                            class="btn btn-outline-success btn-loan-action"
                                            data-action="approve" 
                                            data-id="<?= $loan['id'] ?>" 
                                            data-client="<?= htmlspecialchars($loan['client_name'] ?? 'N/A') ?>"
                                            data-amount="<?= number_format($loan['principal'], 2) ?>"
                                            title="Approve Loan"
                                            data-bs-toggle="tooltip">
                                        <i data-feather="check"></i> Approve
                                    </button>
                                    <!-- Cancel Button -->
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-loan-action"
                                            data-action="cancel" 
                                            data-id="<?= $loan['id'] ?>" 
                                            data-client="<?= htmlspecialchars($loan['client_name'] ?? 'N/A') ?>"
                                            title="Cancel Application"
                                            data-bs-toggle="tooltip">
                                        <i data-feather="x-circle"></i> Cancel
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
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-muted">
            <?= isset($pagination) ? $pagination->getInfo() : "Page {$page} of {$totalPages}" ?>
        </div>
        <nav aria-label="Loans pagination">
            <?= isset($pagination) ? $pagination->render() : '' ?>
        </nav>
    </div>
<?php endif; ?>

<!-- Hidden Form for POST Actions (Approve/Cancel) -->
<form id="loanActionForm" method="POST" action="<?= APP_URL ?>/public/loans/approvals.php" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
    <input type="hidden" name="id" id="loanActionId">
    <input type="hidden" name="action" id="loanActionType">
</form>

<!-- Approval Confirmation Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="approvalModalLabel">
                    <i data-feather="check-circle" class="me-2"></i> Confirm Loan Approval
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i data-feather="info" class="me-2"></i>
                    <strong>Note:</strong> After approval, a loan agreement will be automatically generated and the loan will be ready for disbursement.
                </div>
                <p class="mb-3">You are about to approve:</p>
                <div class="bg-light p-3 rounded">
                    <div class="row g-2">
                        <div class="col-12">
                            <small class="text-muted">Loan ID:</small>
                            <p class="mb-1 fw-bold">#<span id="modalLoanId"></span></p>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Client:</small>
                            <p class="mb-1" id="modalClientName"></p>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Principal Amount:</small>
                            <p class="mb-1 fw-bold">₱<span id="modalAmount"></span></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i data-feather="x" class="me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-success" id="confirmApproval">
                    <i data-feather="check" class="me-1"></i> Confirm Approval
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Confirmation Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="cancelModalLabel">
                    <i data-feather="x-circle" class="me-2"></i> Cancel Loan Application
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i data-feather="alert-triangle" class="me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone. The loan application will be permanently cancelled.
                </div>
                <p class="mb-3">You are about to cancel:</p>
                <div class="bg-light p-3 rounded">
                    <div class="row g-2">
                        <div class="col-12">
                            <small class="text-muted">Loan ID:</small>
                            <p class="mb-1 fw-bold">#<span id="cancelModalLoanId"></span></p>
                        </div>
                        <div class="col-12">
                            <small class="text-muted">Client:</small>
                            <p class="mb-1" id="cancelModalClientName"></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i data-feather="arrow-left" class="me-1"></i> Keep Application
                </button>
                <button type="button" class="btn btn-danger" id="confirmCancel">
                    <i data-feather="x-circle" class="me-1"></i> Cancel Application
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Action Confirmation -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const actionForm = document.getElementById('loanActionForm');
        const actionIdInput = document.getElementById('loanActionId');
        const actionTypeInput = document.getElementById('loanActionType');
        
        const approvalModal = new bootstrap.Modal(document.getElementById('approvalModal'));
        const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));

        // Handle action buttons
        document.querySelectorAll('.btn-loan-action').forEach(button => {
            button.addEventListener('click', function() {
                const loanId = this.getAttribute('data-id');
                const action = this.getAttribute('data-action');
                const clientName = this.getAttribute('data-client');
                const amount = this.getAttribute('data-amount');

                if (action === 'approve') {
                    // Show approval modal
                    document.getElementById('modalLoanId').textContent = loanId;
                    document.getElementById('modalClientName').textContent = clientName;
                    document.getElementById('modalAmount').textContent = amount;
                    
                    actionIdInput.value = loanId;
                    actionTypeInput.value = action;
                    
                    approvalModal.show();
                } else if (action === 'cancel') {
                    // Show cancel modal
                    document.getElementById('cancelModalLoanId').textContent = loanId;
                    document.getElementById('cancelModalClientName').textContent = clientName;
                    
                    actionIdInput.value = loanId;
                    actionTypeInput.value = action;
                    
                    cancelModal.show();
                }
            });
        });

        // Handle confirmation buttons
        document.getElementById('confirmApproval').addEventListener('click', function() {
            actionForm.submit();
        });

        document.getElementById('confirmCancel').addEventListener('click', function() {
            actionForm.submit();
        });

        // Initialize tooltips
        if (typeof bootstrap !== 'undefined') {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

        // Initialize Feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>