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
    'status'    => $_GET['status'] ?? ''
];

// Generate client report data
$reportData = $reportService->generateClientReport($filters);

// Add array check to prevent fatal error
if (!is_array($reportData)) {
    $reportData = [];
}

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $reportService->exportClientReportPDF($reportData, $filters);
    exit;
}

// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $reportService->exportClientReportExcel($reportData, $filters);
    exit;
}

$pageTitle = 'Client Reports';
include '../../templates/layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Client Report Filters</h5>
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
                                <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="blacklisted" <?= $filters['status'] === 'blacklisted' ? 'selected' : '' ?>>Blacklisted</option>
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
                            <a href="?<?= http_build_query(array_merge($filters, ['export' => 'excel'])) ?>"
                               class="btn btn-outline-success">
                                <i data-feather="file" class="me-1"></i>Export Excel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Client Reports</h1>
                <div>
                    <small class="text-muted">
                        <?php if ($filters['date_from'] && $filters['date_to']): ?>
                            Period: <?= date('M d, Y', strtotime($filters['date_from'])) ?> -
                            <?= date('M d, Y', strtotime($filters['date_to'])) ?>
                        <?php else: ?>
                            <span class="text-muted">All time</span>
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
                            <small class="text-muted">Total Clients</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="text-success">₱<?= number_format(array_sum(array_column($reportData, 'total_principal')), 2) ?></h4>
                            <small class="text-muted">Total Principal</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="text-info">₱<?= number_format(array_sum(array_column($reportData, 'total_payments')), 2) ?></h4>
                            <small class="text-muted">Total Payments</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="text-warning">₱<?= number_format(array_sum(array_column($reportData, 'outstanding_balance')), 2) ?></h4>
                            <small class="text-muted">Outstanding</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Client Details</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Client Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Address</th>
                                    <th>Loans</th>
                                    <th>Total Principal</th>
                                    <th>Total Payments</th>
                                    <th>Outstanding</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reportData)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">No client data found for the selected criteria.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reportData as $client): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= APP_URL ?>/public/clients/view.php?id=<?= $client['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($client['client_name'] ?? '') ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="mailto:<?= htmlspecialchars($client['email'] ?? '') ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($client['email'] ?? '') ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="tel:<?= htmlspecialchars($client['phone'] ?? '') ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($client['phone'] ?? '') ?>
                                                </a>
                                            </td>
                                            <?php $addr = $client['address'] ?? ''; ?>
                                            <td><?= htmlspecialchars(substr($addr, 0, 30)) ?><?= strlen($addr) > 30 ? '...' : '' ?></td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?= $client['total_loans'] ?>
                                                </span>
                                            </td>
                                            <td>₱<?= number_format($client['total_principal'], 2) ?></td>
                                            <td>₱<?= number_format($client['total_payments'], 2) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $client['outstanding_balance'] > 0 ? 'warning' : 'success' ?>">
                                                    ₱<?= number_format($client['outstanding_balance'], 2) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $client['status'] === 'active' ? 'success' : ($client['status'] === 'inactive' ? 'secondary' : 'danger') ?>">
                                                    <?= ucfirst($client['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($client['created_at'])) ?></td>
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
