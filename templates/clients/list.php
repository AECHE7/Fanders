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
                                    <!-- Placeholder: Actual check done via LoanService in view.php -->
                                    <span class="badge text-bg-secondary">Check Loan</span> 
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

                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-outline-danger btn-delete-action"
                                                    data-id="<?= $client['id'] ?>" data-name="<?= htmlspecialchars($client['name'] ?? '') ?>" title="Delete Client">
                                                <i data-feather="trash-2"></i>
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

<!-- Hidden Form for POST Actions (Status Change, Delete) -->
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

        // Status/Action Buttons Handler
        document.querySelectorAll('.btn-status-action').forEach(button => {
            button.addEventListener('click', function() {
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

        // Delete Button Handler
        document.querySelectorAll('.btn-delete-action').forEach(button => {
            button.addEventListener('click', function() {
                const clientId = this.getAttribute('data-id');
                const clientName = this.getAttribute('data-name');
                const message = `Are you absolutely sure you want to permanently delete the record for Client: ${clientName} (ID: ${clientId})?\n\nWARNING: This action is irreversible and requires the client to have NO active or pending loans.`;

                if (confirm(message)) {
                    actionIdInput.value = clientId;
                    actionTypeInput.value = 'delete';
                    actionForm.submit();
                }
            });
        });
    });
</script>