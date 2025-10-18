<?php
/**
 * Cash Blotter template for Fanders Microfinance
 * Displays daily cash flow tracking and balance management
 */
?>

<!-- Current Balance Summary -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="p-3 text-center" style="background-color: #f8f9fa; border-radius: 8px;">
                            <h3 class="mb-1 text-success">₱<?= number_format($currentBalance ?? 0, 2) ?></h3>
                            <p class="mb-0 text-muted small">Current Balance</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 text-center" style="background-color: #e8f5e8; border-radius: 8px;">
                            <h4 class="mb-1 text-success">₱<?= number_format($summary['total_inflow'] ?? 0, 2) ?></h4>
                            <p class="mb-0 text-muted small">Total Inflow (Selected Period)</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 text-center" style="background-color: #ffe8e8; border-radius: 8px;">
                            <h4 class="mb-1 text-danger">₱<?= number_format($summary['total_outflow'] ?? 0, 2) ?></h4>
                            <p class="mb-0 text-muted small">Total Outflow (Selected Period)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Cash Alerts</h6>
                <?php if (!empty($alerts)): ?>
                    <?php foreach ($alerts as $alert): ?>
                        <div class="alert alert-<?= $alert['severity'] === 'critical' ? 'danger' : 'warning' ?> py-2 px-3 mb-2">
                            <small class="mb-0"><?= htmlspecialchars($alert['message']) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i data-feather="check-circle" class="text-success" style="width: 24px; height: 24px;"></i>
                        <p class="text-muted small mt-2 mb-0">All cash positions normal</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Date Range Filter -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="startDate" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="startDate" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
            </div>
            <div class="col-md-3">
                <label for="endDate" class="form-label">End Date</label>
                <input type="date" class="form-control" id="endDate" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-primary w-100" onclick="filterBlotter()">
                    <i data-feather="search" class="me-1" style="width: 14px; height: 14px;"></i> Filter
                </button>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-secondary w-100" onclick="resetFilter()">
                    <i data-feather="x" class="me-1" style="width: 14px; height: 14px;"></i> Reset
                </button>
            </div>
            <div class="col-md-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="autoUpdate" checked>
                    <label class="form-check-label small" for="autoUpdate">
                        Auto-update daily
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cash Blotter Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Daily Cash Flow</h5>
        <div class="text-muted small">
            Showing <?= count($blotterData ?? []) ?> days
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th class="text-end">Opening Balance</th>
                        <th class="text-end">Inflow</th>
                        <th class="text-end">Outflow</th>
                        <th class="text-end">Closing Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="blotterTableBody">
                    <?php if (isset($blotterData) && is_array($blotterData)): ?>
                        <?php
                        $runningBalance = 0;
                        foreach (array_reverse($blotterData) as $entry):
                            $openingBalance = $runningBalance;
                            $closingBalance = $entry['calculated_balance'] ?? 0;
                            $runningBalance = $closingBalance;
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <i data-feather="calendar" style="width: 14px; height: 14px; color: #6c757d;"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= date('M j, Y', strtotime($entry['blotter_date'] ?? 'today')) ?></div>
                                            <small class="text-muted"><?= date('l', strtotime($entry['blotter_date'] ?? 'today')) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold">₱<?= number_format($openingBalance, 2) ?></span>
                                </td>
                                <td class="text-end">
                                    <span class="text-success fw-bold">+₱<?= number_format($entry['total_inflow'] ?? 0, 2) ?></span>
                                </td>
                                <td class="text-end">
                                    <span class="text-danger fw-bold">-₱<?= number_format($entry['total_outflow'] ?? 0, 2) ?></span>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold <?= $closingBalance >= 0 ? 'text-success' : 'text-danger' ?>">
                                        ₱<?= number_format($closingBalance, 2) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($closingBalance < 0): ?>
                                        <span class="badge bg-danger">Negative</span>
                                    <?php elseif ($closingBalance < 1000): ?>
                                        <span class="badge bg-warning">Low</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Normal</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="text-muted">
                                    <i data-feather="inbox" style="width: 48px; height: 48px;"></i>
                                    <p class="mt-2">No cash blotter data found</p>
                                    <small>Try adjusting the date range or run the daily update</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Cash Flow Chart Placeholder -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">Cash Flow Trend</h5>
    </div>
    <div class="card-body">
        <div id="cashFlowChart" style="height: 300px;">
            <div class="text-center py-5 text-muted">
                <i data-feather="bar-chart-2" style="width: 48px; height: 48px;"></i>
                <p class="mt-2">Chart visualization will be implemented in Phase 3</p>
                <small>Current data shows <?= count($blotterData ?? []) ?> days of cash flow history</small>
            </div>
        </div>
    </div>
</div>

<script>
// Cash blotter functionality
function filterBlotter() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    if (!startDate || !endDate) {
        alert('Please select both start and end dates');
        return;
    }

    if (new Date(startDate) > new Date(endDate)) {
        alert('Start date cannot be after end date');
        return;
    }

    // Reload page with new date range
    window.location.href = `?start_date=${startDate}&end_date=${endDate}`;
}

function resetFilter() {
    document.getElementById('startDate').value = '<?= date('Y-m-d', strtotime('-30 days')) ?>';
    document.getElementById('endDate').value = '<?= date('Y-m-d') ?>';
    window.location.href = window.location.pathname;
}

function recalculateBlotter() {
    if (confirm('This will recalculate all cash blotter entries. Continue?')) {
        // AJAX call to recalculate blotter
        fetch('<?= APP_URL ?>/public/cash_blotter/recalculate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cash blotter recalculated successfully');
                location.reload();
            } else {
                alert('Error recalculating cash blotter: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error recalculating cash blotter');
        });
    }
}

function exportBlotter() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    window.location.href = `<?= APP_URL ?>/public/cash_blotter/export.php?start_date=${startDate}&end_date=${endDate}`;
}

// Auto-update functionality
document.getElementById('autoUpdate').addEventListener('change', function() {
    const isEnabled = this.checked;
    localStorage.setItem('cashBlotterAutoUpdate', isEnabled);

    if (isEnabled) {
        console.log('Auto-update enabled');
        // Could implement periodic updates here
    }
});

// Load auto-update preference
document.addEventListener('DOMContentLoaded', function() {
    const autoUpdate = localStorage.getItem('cashBlotterAutoUpdate') !== 'false';
    document.getElementById('autoUpdate').checked = autoUpdate;
});
</script>

<style>
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

.alert {
    border: none;
    border-radius: 6px;
}
</style>
