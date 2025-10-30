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

                                            <!-- Direct Status Change Buttons -->
                                            <?php if ($client['status'] === 'inactive' || $client['status'] === 'blacklisted'): ?>
                                                <form method="POST" action="<?= APP_URL ?>/public/clients/index.php" style="display: inline;" 
                                                      onsubmit="return confirm('Activate client <?= htmlspecialchars($client['name']) ?>?')">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                    <input type="hidden" name="id" value="<?= $client['id'] ?>">
                                                    <input type="hidden" name="action" value="activate">
                                                    <button type="submit" class="btn btn-outline-success" title="Activate Client">
                                                        <i data-feather="user-check"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($client['status'] === 'active'): ?>
                                                <form method="POST" action="<?= APP_URL ?>/public/clients/index.php" style="display: inline;" 
                                                      onsubmit="return confirm('Deactivate client <?= htmlspecialchars($client['name']) ?>? This will prevent new loans.')">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                    <input type="hidden" name="id" value="<?= $client['id'] ?>">
                                                    <input type="hidden" name="action" value="deactivate">
                                                    <button type="submit" class="btn btn-outline-warning" title="Deactivate Client">
                                                        <i data-feather="user-minus"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" action="<?= APP_URL ?>/public/clients/index.php" style="display: inline;" 
                                                      onsubmit="return confirm('BLACKLIST client <?= htmlspecialchars($client['name']) ?>? This is a serious action!')">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                    <input type="hidden" name="id" value="<?= $client['id'] ?>">
                                                    <input type="hidden" name="action" value="blacklist">
                                                    <button type="submit" class="btn btn-outline-danger" title="Blacklist Client">
                                                        <i data-feather="slash"></i>
                                                    </button>
                                                </form>
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

<!-- Simple JavaScript for Feather Icons -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>