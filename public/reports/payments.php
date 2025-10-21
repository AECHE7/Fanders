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
    'date_from' => $_GET['date_from'] ?? date('Y-m-01'),
    'date_to'   => $_GET['date_to'] ?? date('Y-m-t'),
    'client_id' => $_GET['client_id'] ?? ''
];

// Generate payment report data
$reportData = $reportService->generatePaymentReport($filters);

// Add array check to prevent fatal error
if (!is_array($reportData)) {
    $reportData = [];
}

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $reportService->exportPaymentReportPDF($reportData, $filters);
    exit;
}

$pageTitle = 'Payment Reports';
include '../../templates/layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Report Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="date_from" name="date_from"
                                   value="<?= $filters['date_from'] ?>">
                        </div>

                        <div class="mb-3">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="date_to" name="date_to"
                                   value="<?= $filters['date_to'] ?>">
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
                <h1 class="h3 mb-0">Payment Reports</h1>
                <div>
                    <small class="text-muted">
                        Period: <?= date('M d, Y', strtotime($filters['date_from'])) ?> -
                        <?= date('M d, Y', strtotime($filters['date_to'])) ?>
                    </small>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="text-primary"><?= count($reportData) ?></h4>
                            <small class="text-muted">Total Payments</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="text-success">₱<?= number_format(array_sum(array_column($reportData, 'amount')), 2) ?></h4>
                            <small class="text-muted">Total Amount</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="text-info">
                                ₱<?= count($reportData) > 0 ? number_format(array_sum(array_column($reportData, 'amount')) / count($reportData), 2) : '0.00' ?>
                            </h4>
                            <small class="text-muted">Average Payment</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Details</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Payment #</th>
                                    <th>Client</th>
                                    <th>Loan #</th>
                                    <th>Amount</th>
                                    
                                    <th>Date</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reportData)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">No payment data found for the selected criteria.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reportData as $payment): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= APP_URL ?>/public/payments/view.php?id=<?= $payment['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($payment['payment_number']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($payment['client_name']) ?></td>
                                            <td>
                                                <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $payment['loan_id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($payment['loan_number']) ?>
                                                </a>
                                            </td>
                                            <td>₱<?= number_format($payment['amount'], 2) ?></td>
                                        
                                            <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                        
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
