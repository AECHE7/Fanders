<?php
/**
 * Client List Template (templates/clients/list.php)
 * Displays a sortable/filterable table of clients.
 * Variables available: $clients (array), $auth (AuthService), $csrfToken (string), $statusMap (array)
 */

// Helper function to get badge class
if (!function_exists('getClientStatusBadgeClass')) {
    function getClientStatusBadgeClass($status) {
        switch($status) {
            case 'active':
                return 'success';
            case 'inactive':
                return 'warning'; // Changed from secondary for better visibility
            case 'blacklisted':
                return 'danger';
            default:
                return 'secondary';
        }
    }
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <h5 class="mb-0">Client List (<?= count($clients) ?> total)</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($clients) || !is_array($clients)): ?>
            <div class="alert alert-info m-4" role="alert">
                No clients found matching the current criteria.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" style="width: 5%;">ID</th>
                            <th scope="col" style="width: 25%;">Name</th>
                            <th scope="col" style="width: 20%;">Contact (Phone/Email)</th>
                            <th scope="col" style="width: 10%;">Status</th>
                            <th scope="col" style="width: 10%;">Active Loan?</th>
                            <th scope="col" style="width: 30%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td><?= htmlspecialchars($client['id'] ?? '') ?></td>
                                <td><?= htmlspecialchars($client['name'] ?? 'N/A') ?></td>
                                <td>
                                    <?= htmlspecialchars($client['phone_number'] ?? 'N/A') ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($client['email'] ?? '') ?></small>
                                </td>
                                <td>
                                    <span class="badge text-bg-<?= getClientStatusBadgeClass($client['status'] ?? '') ?>">
                                        <?= htmlspecialchars(ucfirst($client['status'] ?? 'Unknown')) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    // Check if client has active loans
                                    $loanService = new LoanService();
                                    $activeLoans = $loanService->getLoansByClient($client['id']);
                                    $hasActiveLoan = false;
                                    foreach ($activeLoans as $loan) {
                                        if ($loan['status'] === 'Active') {
                                            $hasActiveLoan = true;
                                            break;
                                        }
                                    }
                                    ?>
                                    <span class="badge text-bg-<?= $hasActiveLoan ? 'success' : 'secondary' ?>">
                                        <?= $hasActiveLoan ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <!-- View Button -->
                                        <a href="<?= APP_URL ?>/public/clients/view.php?id=<?= htmlspecialchars($client['id'] ?? '') ?>"
                                           class="btn btn-outline-info" title="View Details">
                                            <i data-feather="eye"></i>
                                        </a>

                                        <?php if ($auth->hasRole(['super-admin', 'admin', 'manager'])): ?>
                                            <!-- Edit Button -->
                                            <a href="<?= APP_URL ?>/public/clients/edit.php?id=<?= htmlspecialchars($client['id'] ?? '') ?>"
                                               class="btn btn-outline-secondary" title="Edit Profile">
                                                <i data-feather="edit-2"></i>
                                            </a>

                                            <!-- Status Change Buttons -->
                                            <?php if ($client['status'] === 'inactive' || $client['status'] === 'blacklisted'): ?>
                                                <button type="button" class="btn btn-outline-success btn-status-action"
                                                        data-action="activate" data-id="<?= $client['id'] ?>" data-name="<?= htmlspecialchars($client['name']) ?>" title="Activate Client">
                                                    <i data-feather="user-check"></i>
                                                </button>
                                            <?php elseif ($client['status'] === 'active'): ?>
                                                <button type="button" class="btn btn-outline-warning btn-status-action"
                                                        data-action="deactivate" data-id="<?= $client['id'] ?>" data-name="<?= htmlspecialchars($client['name']) ?>" title="Deactivate Client">
                                                    <i data-feather="user-minus"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-status-action"
                                                        data-action="blacklist" data-id="<?= $client['id'] ?>" data-name="<?= htmlspecialchars($client['name']) ?>" title="Blacklist Client">
                                                    <i data-feather="slash"></i>
                                                </button>
                                            <?php endif; ?>

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
            <?= $pagination->getInfo() ?>
        </div>
        <nav aria-label="Clients pagination">
            <?php
            // Clean filters for pagination to preserve filter state
            require_once __DIR__ . '/../app/utilities/FilterUtility.php';
            $paginationFilters = FilterUtility::cleanFiltersForUrl($filters ?? []);
            unset($paginationFilters['page']); // Remove page from filters since pagination will add it
            ?>
            <?= $pagination->render($paginationFilters) ?>
        </nav>
    </div>
<?php endif; ?>

<!-- Client Action Confirmation Modal -->
<div class="modal fade" id="clientActionModal" tabindex="-1" aria-labelledby="clientActionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="clientActionModalLabel">
                    <i data-feather="alert-circle" class="me-2" style="width:20px;height:20px;"></i>
                    Confirm Client Status Change
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">You are about to change the client status to: <strong id="modalAction">ACTION</strong></p>
                <div class="card bg-light">
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Client ID:</dt>
                            <dd class="col-sm-8 fw-bold" id="modalClientId">#000</dd>
                            <dt class="col-sm-4">Client Name:</dt>
                            <dd class="col-sm-8" id="modalClientName">Client Name</dd>
                        </dl>
                    </div>
                </div>
                <div id="modalWarning" class="alert alert-info mt-3">
                    This action will change the client status.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i data-feather="x" class="me-1" style="width:16px;height:16px;"></i>
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmClientAction">
                    <i data-feather="check" class="me-1" style="width:16px;height:16px;"></i>
                    Confirm Change
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Form for POST Actions (Status Change) -->
<form id="actionForm" method="POST" action="<?= APP_URL ?>/public/clients/index.php" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" id="actionId">
    <input type="hidden" name="action" id="actionType">
</form>

<!-- Anti-Jitter CSS for Client Action Modal -->
<style>
/* Client Action Modal Anti-Jitter Enhancements */
#clientActionModal {
    --modal-transition-duration: 0.2s;
}

#clientActionModal .modal-dialog {
    transform: translateZ(0);
    backface-visibility: hidden;
    contain: layout style;
    transition: transform var(--modal-transition-duration) ease-out, opacity 0.15s ease-out;
}

#clientActionModal .modal-content {
    transform: translateZ(0);
    overflow: hidden;
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.2);
}

/* Smooth button interactions */
#clientActionModal .btn {
    transition: transform 0.15s ease-out, box-shadow 0.15s ease-out;
    transform: translateZ(0);
}

#clientActionModal .btn:hover:not(:disabled) {
    transform: translateY(-1px) translateZ(0);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

#clientActionModal .btn:active {
    transform: translateY(0) translateZ(0);
    transition-duration: 0.05s;
}

#clientActionModal .btn:disabled {
    transform: none !important;
}

/* Prevent animation conflicts */
#clientActionModal .feather {
    transition: none;
}

/* Loading state styling */
#clientActionModal .btn .spinner-border-sm {
    width: 0.875rem;
    height: 0.875rem;
}
</style>

<!-- JavaScript for Action Confirmation -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const actionForm = document.getElementById('actionForm');
        const actionIdInput = document.getElementById('actionId');
        const actionTypeInput = document.getElementById('actionType');

        // Initialize Feather icons for dynamically rendered elements
        if (typeof feather !== 'undefined') {
            feather.replace();
        }

        // Enhanced Status/Action Buttons Handler with Anti-Jitter
        const activeOperations = new Set();
        
        document.querySelectorAll('.btn-status-action').forEach(button => {
            button.addEventListener('click', async function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const clientId = this.getAttribute('data-id');
                const action = this.getAttribute('data-action');
                const clientName = this.getAttribute('data-name') || 'Unknown Client';
                
                // Prevent rapid clicks
                const operationId = `client_${clientId}_${action}`;
                if (activeOperations.has(operationId)) {
                    return;
                }
                activeOperations.add(operationId);
                
                try {
                    // Set modal content
                    document.getElementById('modalClientId').textContent = '#' + clientId;
                    document.getElementById('modalClientName').textContent = clientName;
                    document.getElementById('modalAction').textContent = action.toUpperCase();
                    
                    // Set warning message based on action
                    const warningElement = document.getElementById('modalWarning');
                    if (action === 'deactivate') {
                        warningElement.innerHTML = '<i data-feather="alert-triangle" class="me-1" style="width:16px;height:16px;"></i><strong>Warning:</strong> This will prevent the client from getting new loans. Existing active loans must be cleared first.';
                        warningElement.className = 'alert alert-warning mt-3';
                    } else if (action === 'blacklist') {
                        warningElement.innerHTML = '<i data-feather="alert-triangle" class="me-1" style="width:16px;height:16px;"></i><strong>Danger:</strong> Blacklisted clients cannot apply for new loans and may require special approval to reactivate.';
                        warningElement.className = 'alert alert-danger mt-3';
                    } else {
                        warningElement.innerHTML = '<i data-feather="info" class="me-1" style="width:16px;height:16px;"></i>This will change the client status and may affect their ability to apply for loans.';
                        warningElement.className = 'alert alert-info mt-3';
                    }
                    
                    // Set form values
                    actionIdInput.value = clientId;
                    actionTypeInput.value = action;
                    
                    // Update modal button color
                    const confirmBtn = document.getElementById('confirmClientAction');
                    confirmBtn.className = action === 'blacklist' ? 'btn btn-danger' : (action === 'deactivate' ? 'btn btn-warning' : 'btn btn-success');
                    
                    // Show modal with enhanced system if available
                    if (window.ModalUtils && typeof ModalUtils.showModal === 'function') {
                        await ModalUtils.showModal('clientActionModal');
                    } else {
                        // Fallback with anti-jitter timing
                        requestAnimationFrame(() => {
                            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('clientActionModal'));
                            modal.show();
                            
                            // Refresh feather icons after modal is shown
                            if (typeof feather !== 'undefined') {
                                setTimeout(() => feather.replace(), 100);
                            }
                        });
                    }
                } finally {
                    // Clear operation lock after delay
                    setTimeout(() => {
                        activeOperations.delete(operationId);
                    }, 500);
                }
            });
        });
        
        // Enhanced confirm action button handler with anti-jitter
        document.getElementById('confirmClientAction').addEventListener('click', async function() {
            // Prevent multiple clicks
            if (this.disabled) return;
            
            this.disabled = true;
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
            
            try {
                // Hide modal smoothly then submit
                if (window.ModalUtils && typeof ModalUtils.hideModal === 'function') {
                    await ModalUtils.hideModal('clientActionModal');
                    setTimeout(() => actionForm.submit(), 50);
                } else {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('clientActionModal'));
                    if (modal) {
                        modal.hide();
                    }
                    setTimeout(() => actionForm.submit(), 150);
                }
            } catch (error) {
                console.warn('Action confirmation failed:', error);
                // Restore button on error
                this.disabled = false;
                this.innerHTML = originalText;
            }
        });
    });
</script>