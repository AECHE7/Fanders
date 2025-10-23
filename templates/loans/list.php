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
                                    <!-- <button type="button" class="btn btn-outline-success btn-loan-action"
                                            data-action="approve" data-id="<?= $loan['id'] ?>" title="Approve Loan">
                                        <i data-feather="check"></i>
                                    </button> -->
                                    <!-- Cancel Button -->
                                    <!-- <button type="button" class="btn btn-outline-danger btn-loan-action"
                                            data-action="cancel" data-id="<?= $loan['id'] ?>" title="Cancel Application">
                                        <i data-feather="x-circle"></i>
                                    </button> -->
                                <?php endif; ?>

                                <?php if ($canManage && $status === 'approved'): ?>
                                    <!-- Disburse Button (Manager/Admin action) -->
                                    <button type="button" class="btn btn-outline-primary btn-loan-action"
                                            data-action="disburse" data-id="<?= $loan['id'] ?>" title="Disburse Funds (Activate Loan)">
                                        <i data-feather="send"></i> Disburse
                                    </button>
                                <?php endif; ?>

                                <?php if ($status === 'active'): ?>
                                    <!-- Enhanced Payment Options (Direct Payment vs Collection Sheet) -->
                                    <div class="btn-group" role="group">
                                        <a href="<?= APP_URL ?>/public/payments/approvals.php?loan_id=<?= $loan['id'] ?>" 
                                           class="btn btn-success btn-sm" title="Record Direct Payment">
                                            <i data-feather="credit-card"></i> Pay Now
                                        </a>
                                        <button type="button" class="btn btn-success btn-sm dropdown-toggle dropdown-toggle-split" 
                                                data-bs-toggle="dropdown" aria-expanded="false" title="More Payment Options">
                                            <span class="visually-hidden">Toggle Dropdown</span>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="<?= APP_URL ?>/public/payments/approvals.php?loan_id=<?= $loan['id'] ?>">
                                                    <i data-feather="credit-card" style="width: 14px; height: 14px;"></i> Direct Payment (Instant)
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <?php if (in_array($userRole, ['super-admin', 'admin', 'manager', 'account_officer'])): ?>
                                            <li>
                                                <a class="dropdown-item" href="<?= APP_URL ?>/public/collection-sheets/add.php?loan_id=<?= $loan['id'] ?>">
                                                    <i data-feather="file-plus" style="width: 14px; height: 14px;"></i> Add to Collection Sheet
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="<?= APP_URL ?>/public/collection-sheets/add.php?loan_id=<?= $loan['id'] ?>&auto_add=1">
                                                    <i data-feather="plus-circle" style="width: 14px; height: 14px;"></i> Add to Current Sheet (Auto)
                                                </a>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
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
                let message = `Are you sure you want to perform the action: ${action.toUpperCase()} on Loan ID ${loanId}?`;

                if (action === 'approve') {
                    message = `CONFIRM: Approve Loan ID ${loanId}? This action cannot be easily undone.`;
                } else if (action === 'disburse') {
                    message = `CONFIRM: Disburse funds for Loan ID ${loanId}? This will activate the payment schedule.`;
                } else if (action === 'cancel') {
                    message = `WARNING: Are you sure you want to CANCEL Loan ID ${loanId}? This will terminate the application.`;
                }

                if (confirm(message)) {
                    actionIdInput.value = loanId;
                    actionTypeInput.value = action;
                    actionForm.submit();
                }
            });
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