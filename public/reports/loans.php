<?php
require_once '../init.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

// // Check permissions
// $allowedRoles = ['super-admin', 'admin', 'manager', 'staff'];
// if (!in_array($_SESSION['role'], $allowedRoles)) {
//     header('Location: ' . APP_URL . '/public/dashboard.php');
//     exit;
// }

$reportService = new ReportService();

// Get filter parameters
$filters = [
    'date_from' => $_GET['date_from'] ?? '',
    'date_to'   => $_GET['date_to'] ?? '',
    'status'    => $_GET['status'] ?? '',
    'client_id' => $_GET['client_id'] ?? ''
];

// Generate loan report data
$reportData = $reportService->generateLoanReport($filters);

// Add array check to prevent fatal error
if (!is_array($reportData)) {
    $reportData = [];
}

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $reportService->exportLoanReportPDF($reportData, $filters);
    exit;
}

$pageTitle = 'Loan Reports';
include '../../templates/layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Loan Report Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="date_from" name="date_from"
                                   value="<?= htmlspecialchars($filters['date_from'] ?: '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="date_to" name="date_to"
                                   value="<?= htmlspecialchars($filters['date_to'] ?: '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="search" class="me-1"></i>Generate Report
                            </button>
                            <a href="?<?= http_build_query(array_merge($filters, ['export' => 'pdf'])) ?>"
                               class="btn btn-success">
                                <i data-feather="download" class="me-1"></i>Export PDF
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Loan Reports</h1>
                <div>
                    <small class="text-muted">
                        <?php if (!empty($filters['date_from']) && !empty($filters['date_to'])): ?>
                            Period: <?= date('M d, Y', strtotime($filters['date_from'])) ?> -
                            <?= date('M d, Y', strtotime($filters['date_to'])) ?>
                        <?php else: ?>
                            Period: All time
                        <?php endif; ?>
                    </small>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="text-primary"><?= count($reportData) ?></h4>
                            <small class="text-muted">Total Loans</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="text-success">₱<?= number_format(array_sum(array_column($reportData, 'principal_amount')), 2) ?></h4>
                            <small class="text-muted">Total Principal</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="text-info">₱<?= number_format(array_sum(array_column($reportData, 'total_paid')), 2) ?></h4>
                            <small class="text-muted">Total Paid</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="text-warning">₱<?= number_format(array_sum(array_column($reportData, 'remaining_balance')), 2) ?></h4>
                            <small class="text-muted">Outstanding</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Loan Details</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Loan #</th>
                                    <th>Client</th>
                                    <th>Principal</th>
                                    <th>Total Amount</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reportData)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No loan data found for the selected criteria.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reportData as $loan): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $loan['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($loan['loan_number'] ?? '') ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($loan['client_name'] ?? '') ?></td>
                                            <td>₱<?= number_format((float)($loan['principal_amount'] ?? 0), 2) ?></td>
                                            <td>₱<?= number_format((float)($loan['total_amount'] ?? 0), 2) ?></td>
                                            <td>₱<?= number_format((float)($loan['total_paid'] ?? 0), 2) ?></td>
                                            <td>₱<?= number_format((float)($loan['remaining_balance'] ?? 0), 2) ?></td>
                                            <?php $status = strtolower($loan['status'] ?? ''); ?>
                                            <td>
                                                <span class="badge bg-<?= $status === 'active' ? 'success' : ($status === 'completed' ? 'primary' : 'secondary') ?>">
                                                    <?= htmlspecialchars(ucfirst($loan['status'] ?? '')) ?>
                                                </span>
                                            </td>
                                            <td><?= !empty($loan['created_at']) ? date('M d, Y', strtotime($loan['created_at'])) : '' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/layout/footer.php'; ?>
