<?php
/**
 * Transaction Audit Log template for Fanders Microfinance
 * Displays system activity logs with filtering and search
 */
?>

<!-- Filters and Search
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="searchTerm" class="form-label">Search</label>
                        <input type="text" class="form-control" id="searchTerm" placeholder="Search transactions...">
                    </div>
                    <div class="col-md-3">
                        <label for="transactionType" class="form-label">Type</label>
                        <select class="form-select" id="transactionType">
                            <option value="">All Types</option>
                            <option value="LOAN_CREATED">Loan Created</option>
                            <option value="LOAN_APPROVED">Loan Approved</option>
                            <option value="LOAN_DISBURSED">Loan Disbursed</option>
                            <option value="PAYMENT_RECORDED">Payment Recorded</option>
                            <option value="CLIENT_CREATED">Client Created</option>
                            <option value="USER_CREATED">User Created</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="dateRange" class="form-label">Date Range</label>
                        <select class="form-select" id="dateRange">
                            <option value="7">Last 7 days</option>
                            <option value="30">Last 30 days</option>
                            <option value="90">Last 90 days</option>
                            <option value="365">Last year</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-primary w-100" onclick="filterTransactions()">
                            <i data-feather="search" class="me-1" style="width: 14px; height: 14px;"></i> Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Transaction Statistics</h6>
                <div class="row text-center">
                    <div class="col-6">
                        <div class="p-2">
                            <h4 class="mb-0 text-primary"><?= $stats['total_transactions'] ?? 0 ?></h4>
                            <small class="text-muted">Total</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2">
                            <h4 class="mb-0 text-success"><?= $stats['recent_transactions'] ?? 0 ?></h4>
                            <small class="text-muted">This Week</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> -->

<!-- Transactions Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Recent Transactions</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Reference</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody id="transactionsTableBody">
                    <?php if (isset($transactions) && is_array($transactions)): ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <i data-feather="clock" style="width: 14px; height: 14px; color: #6c757d;"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= date('M j, Y', strtotime($transaction['created_at'])) ?></div>
                                            <small class="text-muted"><?= date('H:i:s', strtotime($transaction['created_at'])) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-2" style="width: 32px; height: 32px; background-color: #e9ecef; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <span class="fw-bold text-muted" style="font-size: 12px;"><?= strtoupper(substr($transaction['user_name'], 0, 1)) ?></span>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($transaction['user_name']) ?></div>
                                            <small class="text-muted"><?= ucfirst($transaction['user_role']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?= getActionBadgeClass($transaction['transaction_type']) ?>">
                                        <?= getActionLabel($transaction['transaction_type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($transaction['reference_id']): ?>
                                        <a href="#" class="text-decoration-none" onclick="viewReference(<?= $transaction['reference_id'] ?>, '<?= $transaction['transaction_type'] ?>')">
                                            #<?= $transaction['reference_id'] ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $details = json_decode($transaction['details'], true);
                                    if ($details && isset($details['message'])) {
                                        echo htmlspecialchars($details['message']);
                                    } elseif ($details && isset($details['amount'])) {
                                        echo '₱' . number_format($details['amount'], 2);
                                    } elseif ($details && isset($details['principal'])) {
                                        echo '₱' . number_format($details['principal'], 2);
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <div class="text-muted">
                                    <i data-feather="inbox" style="width: 48px; height: 48px;"></i>
                                    <p class="mt-2">No transactions found</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<?php if (isset($totalPages) && $totalPages > 1): ?>
<div class="d-flex justify-content-center mt-4">
    <nav aria-label="Transaction pagination">
        <ul class="pagination">
            <li class="page-item <?= ($currentPage ?? 1) <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="#" onclick="changePage(<?= ($currentPage ?? 1) - 1 ?>)">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($currentPage ?? 1) == $i ? 'active' : '' ?>">
                    <a class="page-link" href="#" onclick="changePage(<?= $i ?>)"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= ($currentPage ?? 1) >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="#" onclick="changePage(<?= ($currentPage ?? 1) + 1 ?>)">Next</a>
            </li>
        </ul>
    </nav>
</div>
<?php endif; ?>

<script>
// Transaction filtering and pagination
let currentPage = 1;
let currentFilters = {};

function filterTransactions() {
    currentFilters = {
        search: document.getElementById('searchTerm').value,
        type: document.getElementById('transactionType').value,
        dateRange: document.getElementById('dateRange').value
    };
    currentPage = 1;
    loadTransactions();
}

function changePage(page) {
    currentPage = page;
    loadTransactions();
}

function loadTransactions() {
    // AJAX call to load filtered transactions
    // Implementation depends on your AJAX setup
    console.log('Loading transactions with filters:', currentFilters, 'Page:', currentPage);
}

function viewReference(referenceId, transactionType) {
    // Navigate to the appropriate view page based on transaction type
    if (transactionType.includes('LOAN')) {
        window.location.href = '<?= APP_URL ?>/public/loans/view.php?id=' + referenceId;
    } else if (transactionType.includes('CLIENT')) {
        window.location.href = '<?= APP_URL ?>/public/clients/view.php?id=' + referenceId;
    } else if (transactionType.includes('PAYMENT')) {
        window.location.href = '<?= APP_URL ?>/public/payments/view.php?id=' + referenceId;
    }
}

function exportTransactions() {
    // Export functionality
    window.location.href = '<?= APP_URL ?>/public/transactions/export.php?' + new URLSearchParams(currentFilters);
}

// Helper functions for display
function getActionBadgeClass(type) {
    const classes = {
        'LOAN_CREATED': 'success',
        'LOAN_APPROVED': 'info',
        'LOAN_DISBURSED': 'primary',
        'LOAN_CANCELLED': 'warning',
        'LOAN_RESTORED': 'secondary',
        'PAYMENT_RECORDED': 'success',
        'CLIENT_CREATED': 'info',
        'CLIENT_UPDATED': 'secondary',
        'USER_CREATED': 'primary',
        'USER_UPDATED': 'secondary'
    };
    return classes[type] || 'secondary';
}

function getActionLabel(type) {
    const labels = {
        'LOAN_CREATED': 'Loan Created',
        'LOAN_APPROVED': 'Loan Approved',
        'LOAN_DISBURSED': 'Loan Disbursed',
        'LOAN_CANCELLED': 'Loan Cancelled',
        'LOAN_RESTORED': 'Loan Restored',
        'PAYMENT_RECORDED': 'Payment Recorded',
        'CLIENT_CREATED': 'Client Created',
        'CLIENT_UPDATED': 'Client Updated',
        'USER_CREATED': 'User Created',
        'USER_UPDATED': 'User Updated'
    };
    return labels[type] || type.replace(/_/g, ' ');
}
</script>

<style>
.avatar-circle {
    flex-shrink: 0;
}

.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
}

.badge {
    font-size: 0.75rem;
}

.page-icon {
    flex-shrink: 0;
}
</style>
