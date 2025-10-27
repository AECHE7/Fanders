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
<!-- Loans List Table -->
<?php if (empty($loans)): ?>
    <div class="alert alert-info" role="alert">
        No loans found matching the current filters.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 5%;">ID</th>
                    <th style="width: 20%;">Client</th>
                    <th style="width: 15%;">Principal</th>
                    <th style="width: 15%;">Weekly Pay</th>
                    <th style="width: 15%;">Status</th>
                    <th style="width: 10%;">Applied On</th>
                    <th style="width: 20%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loans as $loan): 
                    // Check if loan is overdue
                    $isOverdue = false;
                    $daysOverdue = 0;
                    if (strtolower($loan['status']) === 'active' && isset($loan['completion_date'])) {
                        $completionDate = strtotime($loan['completion_date']);
                        $today = strtotime('today');
                        if ($completionDate < $today) {
                            $isOverdue = true;
                            $daysOverdue = floor(($today - $completionDate) / 86400);
                        }
                    }
                ?>
                    <tr class="<?= $isOverdue ? 'table-danger' : '' ?>">
                        <td><?= htmlspecialchars($loan['id']) ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if ($isOverdue): ?>
                                    <span class="badge bg-danger me-2" title="Overdue by <?= $daysOverdue ?> days">
                                        <i data-feather="alert-triangle" style="width: 12px; height: 12px;"></i>
                                    </span>
                                <?php endif; ?>
                                <div>
                                    <a href="<?= APP_URL ?>/public/clients/view.php?id=<?= $loan['client_id'] ?>" class="text-decoration-none fw-medium">
                                        <?= htmlspecialchars($loan['client_name'] ?? 'N/A') ?>
                                    </a>
                                    <small class="text-muted d-block"><?= htmlspecialchars($loan['phone_number'] ?? '') ?></small>
                                </div>
                            </div>
                        </td>
                        <td>₱<?= number_format($loan['principal'], 2) ?></td>
                        <td>₱<?= number_format($loan['total_loan_amount'] / $loan['term_weeks'], 2) ?></td>
                        <td>
                            <div>
                                <span class="badge text-bg-<?= getLoanStatusBadgeClass($loan['status']) ?>">
                                    <?= htmlspecialchars(ucfirst($loan['status'])) ?>
                                </span>
                                <?php if ($isOverdue): ?>
                                    <br>
                                    <small class="text-danger fw-bold">
                                        <i data-feather="clock" style="width: 12px; height: 12px;"></i>
                                        Overdue <?= $daysOverdue ?> day<?= $daysOverdue > 1 ? 's' : '' ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars(date('M d, Y', strtotime($loan['application_date']))) ?></td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <!-- View Details -->
                                <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $loan['id'] ?>" class="btn btn-outline-info" title="View Loan Details">
                                    <i data-feather="eye"></i>
                                </a>

                                <?php 
                                $status = strtolower($loan['status']);
                                // Check if user can approve/disburse (Admin/Manager role check)
                                $canManage = $auth->hasRole(['super-admin', 'admin', 'manager']);
                                ?>

                                <?php if ($canManage && $status === 'application'): ?>
                                    <!-- Approve Button (Manager/Admin action) -->
                                    <button type="button" class="btn btn-outline-success btn-loan-action"
                                            data-action="approve" data-id="<?= $loan['id'] ?>"
                                            data-client="<?= htmlspecialchars($loan['client_name']) ?>"
                                            data-amount="₱<?= number_format($loan['principal'], 2) ?>"
                                            data-status="<?= htmlspecialchars($loan['status']) ?>"
                                            title="Approve Loan">
                                        <i data-feather="check"></i>
                                    </button>
                                    <!-- Cancel Button -->
                                    <button type="button" class="btn btn-outline-danger btn-loan-action"
                                            data-action="cancel" data-id="<?= $loan['id'] ?>"
                                            data-client="<?= htmlspecialchars($loan['client_name']) ?>"
                                            data-amount="₱<?= number_format($loan['principal'], 2) ?>"
                                            data-status="<?= htmlspecialchars($loan['status']) ?>"
                                            title="Cancel Application">
                                        <i data-feather="x-circle"></i>
                                    </button>
                                <?php endif; ?>

                                <?php if ($canManage && $status === 'approved'): ?>
                                    <!-- Disburse Button (Manager/Admin action) -->
                                    <button type="button" class="btn btn-outline-primary btn-loan-action"
                                            data-action="disburse" data-id="<?= $loan['id'] ?>" 
                                            data-client="<?= htmlspecialchars($loan['client_name']) ?>"
                                            data-amount="₱<?= number_format($loan['principal'], 2) ?>"
                                            data-status="<?= htmlspecialchars($loan['status']) ?>"
                                            title="Disburse Funds (Activate Loan)">
                                        <i data-feather="send"></i> Disburse
                                    </button>
                                <?php endif; ?>

                                <?php if (strtolower($status) === 'active'): ?>
                                    <!-- Collection Sheet Payment Options (All payments through collection sheets) -->
                                    <?php if (in_array($userRole, ['super-admin', 'admin', 'manager', 'account_officer'])): ?>
                                    <div class="btn-group" role="group">
                                        <a href="<?= APP_URL ?>/public/collection-sheets/add.php?loan_id=<?= $loan['id'] ?>&auto_add=1&auto_process=1"
                                           class="btn btn-success btn-sm" title="Process Payment via Collection Sheet">
                                            <i data-feather="credit-card"></i> Pay Now
                                        </a>
                                        <button type="button" class="btn btn-success btn-sm dropdown-toggle dropdown-toggle-split"
                                                data-bs-toggle="dropdown" aria-expanded="false" title="Collection Sheet Options">
                                            <span class="visually-hidden">Toggle Dropdown</span>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li class="dropdown-header">
                                                <i data-feather="file-text" style="width: 12px; height: 12px;"></i> <strong>Collection Sheet Processing</strong>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-success fw-bold" href="<?= APP_URL ?>/public/collection-sheets/add.php?loan_id=<?= $loan['id'] ?>&auto_add=1&auto_process=1">
                                                    <i data-feather="zap" style="width: 14px; height: 14px;"></i> Auto-Process Payment Now
                                                </a>
                                                <small class="text-muted px-3">Instantly records payment via collection sheet</small>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="<?= APP_URL ?>/public/collection-sheets/add.php?loan_id=<?= $loan['id'] ?>">
                                                    <i data-feather="file-plus" style="width: 14px; height: 14px;"></i> Add to Collection Sheet (Manual)
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="<?= APP_URL ?>/public/collection-sheets/add.php?loan_id=<?= $loan['id'] ?>&auto_add=1">
                                                    <i data-feather="plus-circle" style="width: 14px; height: 14px;"></i> Add to Current Sheet (Auto)
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if (in_array($status, ['approved', 'active', 'completed']) && in_array($userRole, ['super-admin', 'admin', 'manager', 'cashier'])): ?>
                                    <!-- SLR Generation for eligible loans -->
                                    <a href="<?= APP_URL ?>/public/slr/generate.php?loan_id=<?= $loan['id'] ?>" 
                                       class="btn btn-outline-secondary btn-sm" title="Generate SLR Document">
                                        <i data-feather="file-text"></i> SLR
                                    </a>
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

<!-- Loan Action Confirmation Modal -->
<div class="modal fade" id="loanActionModal" tabindex="-1" aria-labelledby="loanActionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="loanActionModalLabel">
                    <i data-feather="alert-circle" class="me-2" style="width:20px;height:20px;"></i>
                    Confirm Loan Action
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">You are about to perform the action: <strong id="modalLoanAction">ACTION</strong></p>
                <div class="card bg-light">
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Loan ID:</dt>
                            <dd class="col-sm-8 fw-bold" id="modalLoanId">#000</dd>
                            <dt class="col-sm-4">Client:</dt>
                            <dd class="col-sm-8" id="modalLoanClient">Client Name</dd>
                            <dt class="col-sm-4">Amount:</dt>
                            <dd class="col-sm-8 text-success fw-bold" id="modalLoanAmount">₱0.00</dd>
                            <dt class="col-sm-4">Status:</dt>
                            <dd class="col-sm-8" id="modalLoanStatus">
                                <span class="badge text-bg-secondary">Status</span>
                            </dd>
                        </dl>
                    </div>
                </div>
                <div id="modalLoanWarning" class="alert alert-info mt-3">
                    This action will change the loan status.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i data-feather="x" class="me-1" style="width:16px;height:16px;"></i>
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmLoanAction">
                    <i data-feather="check" class="me-1" style="width:16px;height:16px;"></i>
                    Confirm Action
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

        document.querySelectorAll('.btn-loan-action').forEach(button => {
            button.addEventListener('click', function() {
                const loanId = this.getAttribute('data-id');
                const action = this.getAttribute('data-action');
                const clientName = this.getAttribute('data-client') || 'Unknown Client';
                const amount = this.getAttribute('data-amount') || '₱0.00';
                const status = this.getAttribute('data-status') || 'Unknown';
                
                // Set modal content
                document.getElementById('modalLoanId').textContent = '#' + loanId;
                document.getElementById('modalLoanClient').textContent = clientName;
                document.getElementById('modalLoanAmount').textContent = amount;
                document.getElementById('modalLoanAction').textContent = action.toUpperCase();
                
                // Set status badge
                const statusElement = document.getElementById('modalLoanStatus');
                let statusClass = 'secondary';
                if (status === 'application') statusClass = 'warning';
                else if (status === 'approved') statusClass = 'info';
                else if (status === 'active') statusClass = 'success';
                else if (status === 'completed') statusClass = 'success';
                else if (status === 'cancelled') statusClass = 'danger';
                statusElement.innerHTML = `<span class="badge text-bg-${statusClass}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
                
                // Set warning message based on action
                const warningElement = document.getElementById('modalLoanWarning');
                if (action === 'approve') {
                    warningElement.innerHTML = '<i data-feather="info" class="me-1" style="width:16px;height:16px;"></i><strong>Action:</strong> This will approve the loan application. The loan will be ready for disbursement.';
                    warningElement.className = 'alert alert-info mt-3';
                } else if (action === 'disburse') {
                    warningElement.innerHTML = '<i data-feather="alert-triangle" class="me-1" style="width:16px;height:16px;"></i><strong>Important:</strong> This will disburse the funds and activate the payment schedule. This action cannot be easily undone.';
                    warningElement.className = 'alert alert-warning mt-3';
                } else if (action === 'cancel') {
                    warningElement.innerHTML = '<i data-feather="x-circle" class="me-1" style="width:16px;height:16px;"></i><strong>Warning:</strong> This will cancel the loan application permanently. This action cannot be undone.';
                    warningElement.className = 'alert alert-danger mt-3';
                } else {
                    warningElement.innerHTML = '<i data-feather="info" class="me-1" style="width:16px;height:16px;"></i>This action will change the loan status.';
                    warningElement.className = 'alert alert-info mt-3';
                }
                
                // Set form values
                actionIdInput.value = loanId;
                actionTypeInput.value = action;
                
                // Update modal button color
                const confirmBtn = document.getElementById('confirmLoanAction');
                if (action === 'cancel') {
                    confirmBtn.className = 'btn btn-danger';
                } else if (action === 'disburse') {
                    confirmBtn.className = 'btn btn-warning';
                } else {
                    confirmBtn.className = 'btn btn-success';
                }
                
                // Show modal
                new bootstrap.Modal(document.getElementById('loanActionModal')).show();
            });
        });

        // Confirm loan action button handler
        document.getElementById('confirmLoanAction').addEventListener('click', function() {
            actionForm.submit();
        });

        // Feather icons initialization
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });

    // Add to Active Collection Sheet functionality
    function addToActiveSheet(loanId, clientName, weeklyAmount) {
        // Check if user has permission (Account Officer or Super Admin)
        <?php if (!in_array($userRole, ['super-admin', 'account-officer'])): ?>
            alert('Only Account Officers and Super Admins can add loans to collection sheets.');
            return false;
        <?php endif; ?>

        if (confirm(`Add ${clientName}'s loan (#${loanId}) with weekly payment ₱${weeklyAmount.toFixed(2)} to your current collection sheet?`)) {
            // Redirect to collection sheet add page with this loan pre-populated and auto-added
            window.location.href = `<?= APP_URL ?>/public/collection-sheets/add.php?loan_id=${loanId}&auto_add=1`;
        }
    }
</script>