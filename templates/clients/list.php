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
                                                        data-action="activate" data-id="<?= $client['id'] ?>" title="Activate Client">
                                                    <i data-feather="user-check"></i>
                                                </button>
                                            <?php elseif ($client['status'] === 'active'): ?>
                                                <button type="button" class="btn btn-outline-warning btn-status-action"
                                                        data-action="deactivate" data-id="<?= $client['id'] ?>" title="Deactivate Client">
                                                    <i data-feather="user-minus"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-status-action"
                                                        data-action="blacklist" data-id="<?= $client['id'] ?>" title="Blacklist Client">
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
            <?= $pagination->render() ?>
        </nav>
    </div>
<?php endif; ?>

<!-- Hidden Form for POST Actions (Status Change) -->
<form id="actionForm" method="POST" action="<?= APP_URL ?>/public/clients/index.php" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="id" id="actionId">
    <input type="hidden" name="action" id="actionType">
</form>

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

        // Status/Action Buttons Handler
        document.querySelectorAll('.btn-status-action').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const clientId = this.getAttribute('data-id');
                const action = this.getAttribute('data-action');
                let message = `Are you sure you want to change the client status to ${action.toUpperCase()}?`;

                if (action === 'deactivate') {
                    message += "\n\nWARNING: This will prevent the client from getting new loans, but existing active loans must be cleared first.";
                }

                if (confirm(message)) {
                    actionIdInput.value = clientId;
                    actionTypeInput.value = action;
                    actionForm.submit();
                }
            });
        });
    });
</script>