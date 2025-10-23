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
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border-radius: 8px;
        overflow: hidden;
    }
    
    .loans-approval-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
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
    
    .loans-approval-table tbody tr {
        transition: all 0.3s ease;
        border-bottom: 1px solid #e9ecef;
    }
    
    .loans-approval-table tbody tr:hover {
        background-color: #f8f9fe;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    }
    
    .loans-approval-table tbody td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
        border: none;
    }
    
    .loans-approval-table tbody tr:last-child {
        border-bottom: none;
    }
    
    .loan-id-badge {
        display: inline-block;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85rem;
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
        transition: color 0.3s ease;
    }
    
    .client-name-link:hover {
        color: #667eea;
    }
    
    .client-phone {
        color: #718096;
        font-size: 0.85rem;
    }
    
    .amount-cell {
        font-weight: 600;
        color: #2d3748;
        font-size: 1rem;
    }
    
    .status-badge-custom {
        padding: 0.4rem 0.9rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
    }
    
    .status-active { background: #48bb78; color: white; }
    .status-application { background: #ed8936; color: white; }
    .status-approved { background: #4299e1; color: white; }
    .status-completed { background: #9f7aea; color: white; }
    .status-defaulted { background: #f56565; color: white; }
    .status-secondary { background: #a0aec0; color: white; }
    
    .date-cell {
        color: #718096;
        font-size: 0.9rem;
    }
    
    .action-btn-group {
        display: flex;
        gap: 0.4rem;
        justify-content: center;
    }
    
    .action-btn-group .btn {
        border-radius: 6px;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    
    .action-btn-group .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
</style>

<!-- Loans List Table -->
<?php if (empty($loans)): ?>
    <div class="alert alert-info" role="alert">
        <i data-feather="info"></i> No loans found matching the current filters.
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
                                <span class="client-phone"><?= htmlspecialchars($loan['phone_number'] ?? 'N/A') ?></span>
                            </div>
                        </td>
                        <td class="amount-cell">₱<?= number_format($loan['principal'], 2) ?></td>
                        <td class="amount-cell">₱<?= number_format($loan['total_loan_amount'] / $loan['term_weeks'], 2) ?></td>
                        <td>
                            <span class="status-badge-custom status-<?= strtolower($loan['status']) ?>">
                                <?= htmlspecialchars(ucfirst($loan['status'])) ?>
                            </span>
                        </td>
                        <td class="date-cell"><?= htmlspecialchars(date('M d, Y', strtotime($loan['application_date']))) ?></td>
                        <td>
                            <div class="action-btn-group">
                                <!-- View Details -->
                                <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $loan['id'] ?>" class="btn btn-sm btn-outline-info" title="View Loan Details">
                                    <i data-feather="eye"></i>
                                </a>

                                <?php 
                                $status = strtolower($loan['status']);
                                // Check if user can approve/disburse (Admin/Manager role check)
                                $canManage = $auth->hasRole(['super-admin', 'admin', 'manager']);
                                ?>

                                <?php if ($canManage && $status === 'application'): ?>
                                    <!-- Approve Button (Manager/Admin action) -->
                                    <button type="button" class="btn btn-sm btn-outline-success btn-loan-action"
                                            data-action="approve" data-id="<?= $loan['id'] ?>" title="Approve Loan">
                                        <i data-feather="check"></i>
                                    </button>
                                    <!-- Cancel Button -->
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-loan-action"
                                            data-action="cancel" data-id="<?= $loan['id'] ?>" title="Cancel Application">
                                        <i data-feather="x-circle"></i>
                                    </button>
                                <?php endif; ?>

                                <?php if ($canManage && $status === 'approved'): ?>
                                    <!-- Disburse Button (Manager/Admin action) -->
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-loan-action"
                                            data-action="disburse" data-id="<?= $loan['id'] ?>" title="Disburse Funds (Activate Loan)">
                                        <i data-feather="send"></i> Disburse
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
<?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-muted">
            <?= $pagination->getInfo() ?>
        </div>
        <nav aria-label="Loans pagination">
            <?= $pagination->render() ?>
        </nav>
    </div>
<?php endif; ?>

<!-- Approval Confirmation Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="approvalModalLabel">
                    <i data-feather="check-circle"></i> Confirm Loan Approval
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <i data-feather="info"></i>
                    <strong>Note:</strong> After confirming this approval, a loan agreement will be automatically generated and saved to the system.
                </div>
                <p class="mb-2">You are about to approve:</p>
                <ul class="list-unstyled ms-3">
                    <li><strong>Loan ID:</strong> <span id="modalLoanId"></span></li>
                    <li><strong>Client:</strong> <span id="modalClientName"></span></li>
                    <li><strong>Principal Amount:</strong> <span id="modalPrincipal"></span></li>
                </ul>
                <p class="text-muted small mt-3">This action cannot be easily undone. Please verify all details before proceeding.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmApprovalBtn">
                    <i data-feather="check"></i> Confirm Approval
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Disburse Confirmation Modal -->
<div class="modal fade" id="disburseModal" tabindex="-1" aria-labelledby="disburseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="disburseModalLabel">
                    <i data-feather="send"></i> Confirm Loan Disbursement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">You are about to disburse funds for:</p>
                <ul class="list-unstyled ms-3">
                    <li><strong>Loan ID:</strong> <span id="modalDisburseLoanId"></span></li>
                    <li><strong>Client:</strong> <span id="modalDisburseClientName"></span></li>
                    <li><strong>Amount to Disburse:</strong> <span id="modalDisbursePrincipal"></span></li>
                </ul>
                <p class="text-warning small mt-3">This will activate the payment schedule. Please ensure funds are ready.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmDisburseBtn">
                    <i data-feather="send"></i> Confirm Disbursement
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
                    <i data-feather="x-circle"></i> Confirm Loan Cancellation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i data-feather="alert-triangle"></i>
                    <strong>Warning:</strong> This action will cancel the loan application.
                </div>
                <p class="mb-2">You are about to cancel:</p>
                <ul class="list-unstyled ms-3">
                    <li><strong>Loan ID:</strong> <span id="modalCancelLoanId"></span></li>
                    <li><strong>Client:</strong> <span id="modalCancelClientName"></span></li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="confirmCancelBtn">
                    <i data-feather="x-circle"></i> Confirm Cancellation
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form for POST Actions (Approve/Disburse/Cancel) -->
<form id="loanActionForm" method="POST" action="<?= APP_URL ?>/public/loans/index.php" style="display:none;">
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
                    const approvalModal = new bootstrap.Modal(document.getElementById('approvalModal'));
                    approvalModal.show();
                } else if (action === 'disburse') {
                    document.getElementById('modalDisburseLoanId').textContent = loanId;
                    document.getElementById('modalDisburseClientName').textContent = clientName;
                    document.getElementById('modalDisbursePrincipal').textContent = principal;
                    const disburseModal = new bootstrap.Modal(document.getElementById('disburseModal'));
                    disburseModal.show();
                } else if (action === 'cancel') {
                    document.getElementById('modalCancelLoanId').textContent = loanId;
                    document.getElementById('modalCancelClientName').textContent = clientName;
                    const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));
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