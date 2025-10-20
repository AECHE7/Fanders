<?php
/**
 * Transaction List Template
 * Displays transaction audit logs in a table format
 */

// Pagination setup
$pagination = [
    'current_page' => $filters['page'],
    'total_pages' => $totalPages,
    'total_items' => $totalTransactions,
    'items_per_page' => $filters['limit'],
    'base_url' => APP_URL . '/public/transactions/index.php?' . http_build_query(array_diff_key($filters, ['page' => '']))
];
?>

<!-- Pagination Info -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="text-muted">
        Showing <?= (($pagination['current_page'] - 1) * $pagination['items_per_page']) + 1 ?> to
        <?= min($pagination['current_page'] * $pagination['items_per_page'], $pagination['total_items']) ?> of
        <?= $pagination['total_items'] ?> transactions
    </div>
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleView('table')" id="tableViewBtn">
            <i data-feather="list"></i> Table
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleView('cards')" id="cardsViewBtn">
            <i data-feather="grid"></i> Cards
        </button>
    </div>
</div>

<!-- Table View -->
<div id="tableView" class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th scope="col" width="5%">#</th>
                <th scope="col" width="15%">Date & Time</th>
                <th scope="col" width="15%">User</th>
                <th scope="col" width="15%">Action</th>
                <th scope="col" width="10%">Type</th>
                <th scope="col" width="10%">Reference</th>
                <th scope="col" width="30%">Details</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <div class="text-muted">
                            <i data-feather="inbox" class="mb-2" style="width: 3rem; height: 3rem;"></i>
                            <p class="mb-0">No transactions found</p>
                            <small>Try adjusting your filters or search terms</small>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php
                $counter = (($pagination['current_page'] - 1) * $pagination['items_per_page']) + 1;
                foreach ($transactions as $transaction):
                    $details = json_decode($transaction['details'], true);
                    $transactionTypes = TransactionModel::getTransactionTypes();
                    $actionLabel = $transactionTypes[$transaction['transaction_type']] ?? ucfirst(str_replace('_', ' ', $transaction['transaction_type']));
                ?>
                    <tr class="transaction-row" data-transaction-id="<?= $transaction['id'] ?>" style="cursor: pointer;">
                        <td>
                            <span class="badge bg-secondary"><?= $counter++ ?></span>
                        </td>
                        <td>
                            <div class="fw-bold"><?= date('M j, Y', strtotime($transaction['created_at'])) ?></div>
                            <small class="text-muted"><?= date('H:i:s', strtotime($transaction['created_at'])) ?></small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-2" style="width: 32px; height: 32px; background: #007bff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                    <?= strtoupper(substr($transaction['user_name'] ?? 'U', 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($transaction['user_name'] ?? 'Unknown') ?></div>
                                    <small class="text-muted">ID: <?= $transaction['user_id'] ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-<?= getActionBadgeClass($transaction['transaction_type']) ?>">
                                <?= $actionLabel ?>
                            </span>
                        </td>
                        <td>
                            <small class="text-muted">
                                <?= ucfirst(str_replace('_', ' ', $transaction['transaction_type'])) ?>
                            </small>
                        </td>
                        <td>
                            <?php if ($transaction['reference_id']): ?>
                                <span class="badge bg-light text-dark">
                                    #<?= $transaction['reference_id'] ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="transaction-details">
                                <?php if ($details): ?>
                                    <?php if (isset($details['amount'])): ?>
                                        <div class="fw-bold text-success">₱<?= number_format($details['amount'], 2) ?></div>
                                    <?php elseif (isset($details['principal'])): ?>
                                        <div class="fw-bold text-primary">₱<?= number_format($details['principal'], 2) ?></div>
                                    <?php elseif (isset($details['ip_address'])): ?>
                                        <small class="text-muted">IP: <?= $details['ip_address'] ?></small>
                                    <?php elseif (isset($details['message'])): ?>
                                        <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($details['message']) ?>">
                                            <?= htmlspecialchars(substr($details['message'], 0, 50)) ?>...
                                        </div>
                                    <?php else: ?>
                                        <small class="text-muted">Details available</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <small class="text-muted">No details</small>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Cards View (Hidden by default) -->
<div id="cardsView" class="row" style="display: none;">
    <?php if (!empty($transactions)): ?>
        <?php foreach ($transactions as $transaction): ?>
            <?php
            $details = json_decode($transaction['details'], true);
            $transactionTypes = TransactionModel::getTransactionTypes();
            $actionLabel = $transactionTypes[$transaction['transaction_type']] ?? ucfirst(str_replace('_', ' ', $transaction['transaction_type']));
            ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100 shadow-sm transaction-card" data-transaction-id="<?= $transaction['id'] ?>" style="cursor: pointer;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-<?= getActionBadgeClass($transaction['transaction_type']) ?>">
                                <?= $actionLabel ?>
                            </span>
                            <small class="text-muted">
                                <?= date('M j, H:i', strtotime($transaction['created_at'])) ?>
                            </small>
                        </div>

                        <div class="d-flex align-items-center mb-2">
                            <div class="avatar-circle me-2" style="width: 24px; height: 24px; background: #007bff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">
                                <?= strtoupper(substr($transaction['user_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <small class="text-muted">
                                <?= htmlspecialchars($transaction['user_name'] ?? 'Unknown') ?>
                            </small>
                        </div>

                        <?php if ($transaction['reference_id']): ?>
                            <div class="mb-2">
                                <small class="text-muted">Reference: #<?= $transaction['reference_id'] ?></small>
                            </div>
                        <?php endif; ?>

                        <div class="transaction-details">
                            <?php if ($details): ?>
                                <?php if (isset($details['amount'])): ?>
                                    <div class="fw-bold text-success">₱<?= number_format($details['amount'], 2) ?></div>
                                <?php elseif (isset($details['principal'])): ?>
                                    <div class="fw-bold text-primary">₱<?= number_format($details['principal'], 2) ?></div>
                                <?php elseif (isset($details['ip_address'])): ?>
                                    <small class="text-muted">IP: <?= $details['ip_address'] ?></small>
                                <?php elseif (isset($details['message'])): ?>
                                    <div class="text-truncate" title="<?= htmlspecialchars($details['message']) ?>">
                                        <?= htmlspecialchars(substr($details['message'], 0, 50)) ?>...
                                    </div>
                                <?php else: ?>
                                    <small class="text-muted">Details available</small>
                                <?php endif; ?>
                            <?php else: ?>
                                <small class="text-muted">No details</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="text-center py-5">
                <div class="text-muted">
                    <i data-feather="inbox" class="mb-2" style="width: 3rem; height: 3rem;"></i>
                    <p class="mb-0">No transactions found</p>
                    <small>Try adjusting your filters or search terms</small>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <nav aria-label="Transaction pagination" class="mt-4">
        <ul class="pagination justify-content-center">
            <!-- Previous Button -->
            <li class="page-item <?= $pagination['current_page'] <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $pagination['base_url'] ?>&page=<?= $pagination['current_page'] - 1 ?>">
                    <i data-feather="chevron-left" class="me-1"></i> Previous
                </a>
            </li>

            <!-- Page Numbers -->
            <?php
            $startPage = max(1, $pagination['current_page'] - 2);
            $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);

            if ($startPage > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $pagination['base_url'] ?>&page=1">1</a>
                </li>
                <?php if ($startPage > 2): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $pagination['base_url'] ?>&page=<?= $i ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>

            <?php if ($endPage < $pagination['total_pages']): ?>
                <?php if ($endPage < $pagination['total_pages'] - 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $pagination['base_url'] ?>&page=<?= $pagination['total_pages'] ?>">
                        <?= $pagination['total_pages'] ?>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Next Button -->
            <li class="page-item <?= $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $pagination['base_url'] ?>&page=<?= $pagination['current_page'] + 1 ?>">
                    Next <i data-feather="chevron-right" class="ms-1"></i>
                </a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<!-- Transaction Detail Modal -->
<div class="modal fade" id="transactionDetailModal" tabindex="-1" aria-labelledby="transactionDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transactionDetailModalLabel">Transaction Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="transactionDetailContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
// Toggle between table and cards view
function toggleView(viewType) {
    const tableView = document.getElementById('tableView');
    const cardsView = document.getElementById('cardsView');
    const tableBtn = document.getElementById('tableViewBtn');
    const cardsBtn = document.getElementById('cardsViewBtn');

    if (viewType === 'table') {
        tableView.style.display = 'block';
        cardsView.style.display = 'none';
        tableBtn.classList.add('active');
        cardsBtn.classList.remove('active');
    } else {
        tableView.style.display = 'none';
        cardsView.style.display = 'block';
        tableBtn.classList.remove('active');
        cardsBtn.classList.add('active');
    }
}

// Export transactions function
function exportTransactions() {
    const filters = new URLSearchParams(window.location.search);
    const exportUrl = '<?= APP_URL ?>/public/reports/transactions.php?' + filters.toString() + '&export=pdf';
    window.open(exportUrl, '_blank');
}

// Transaction row click handler
document.addEventListener('DOMContentLoaded', function() {
    const transactionRows = document.querySelectorAll('.transaction-row, .transaction-card');

    transactionRows.forEach(row => {
        row.addEventListener('click', function() {
            const transactionId = this.getAttribute('data-transaction-id');
            showTransactionDetails(transactionId);
        });
    });
});

// Show transaction details in modal
function showTransactionDetails(transactionId) {
    // This would typically make an AJAX call to get detailed transaction info
    // For now, we'll show a placeholder
    const modal = new bootstrap.Modal(document.getElementById('transactionDetailModal'));
    const content = document.getElementById('transactionDetailContent');

    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">Loading transaction details...</p>
        </div>
    `;

    modal.show();

    // Simulate loading (replace with actual AJAX call)
    setTimeout(() => {
        content.innerHTML = `
            <div class="alert alert-info">
                <h6>Transaction Details</h6>
                <p><strong>Transaction ID:</strong> ${transactionId}</p>
                <p>This feature is under development. Detailed transaction information will be available soon.</p>
            </div>
        `;
    }, 1000);
}
</script>

<?php
/**
 * Helper function to get badge class based on transaction type
 */
function getActionBadgeClass($transactionType) {
    $badgeClasses = [
        // User events
        'login' => 'success',
        'logout' => 'secondary',
        'session_extended' => 'info',

        // User CRUD
        'user_created' => 'primary',
        'user_updated' => 'warning',
        'user_deleted' => 'danger',
        'user_viewed' => 'light',

        // Client CRUD
        'client_created' => 'primary',
        'client_updated' => 'warning',
        'client_deleted' => 'danger',
        'client_viewed' => 'light',

        // Loan CRUD
        'loan_created' => 'primary',
        'loan_updated' => 'warning',
        'loan_approved' => 'success',
        'loan_disbursed' => 'info',
        'loan_completed' => 'success',
        'loan_cancelled' => 'danger',
        'loan_deleted' => 'danger',
        'loan_viewed' => 'light',

        // Payment CRUD
        'payment_created' => 'primary',
        'payment_recorded' => 'success',
        'payment_approved' => 'success',
        'payment_cancelled' => 'danger',
        'payment_overdue' => 'warning',
        'payment_viewed' => 'light',

        // System events
        'system_backup' => 'info',
        'system_config_changed' => 'warning',
        'database_maintenance' => 'secondary'
    ];

    return $badgeClasses[$transactionType] ?? 'secondary';
}
?>
