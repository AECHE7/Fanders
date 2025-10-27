<?php
/**
 * Payment Reports Controller
 * Generates comprehensive reports for payment tracking with professional design
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control for Fanders Microfinance Staff
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account-officer']);

// Initialize services
$reportService = new ReportService();

// --- 1. Process Filters ---
require_once BASE_PATH . '/app/utilities/FilterUtility.php';
$filters = FilterUtility::sanitizeFilters($_GET);
$filters = FilterUtility::validateDateRange($filters);

// Set default date range if not provided
if (empty($filters['date_from'])) {
    $filters['date_from'] = date('Y-m-01');
}
if (empty($filters['date_to'])) {
    $filters['date_to'] = date('Y-m-t');
}

// Additional payment-specific filters
$paymentFilters = [
    'client_id' => $filters['client_id'] ?? '',
    'payment_method' => $filters['payment_method'] ?? '',
    'min_amount' => $filters['min_amount'] ?? '',
    'max_amount' => $filters['max_amount'] ?? '',
    'loan_officer' => $filters['loan_officer'] ?? ''
];

$filters = array_merge($filters, $paymentFilters);

// --- 2. Generate Report Data ---
$reportData = $reportService->generatePaymentReport($filters);

// Add array check to prevent fatal error
if (!is_array($reportData)) {
    $reportData = [];
}

// --- 3. Calculate Statistics ---
$paymentStats = [
    'total_payments' => count($reportData),
    'total_amount' => array_sum(array_column($reportData, 'amount')),
    'cash_payments' => count(array_filter($reportData, fn($payment) => strtolower($payment['payment_method'] ?? '') === 'cash')),
    'bank_payments' => count(array_filter($reportData, fn($payment) => strtolower($payment['payment_method'] ?? '') === 'bank')),
    'online_payments' => count(array_filter($reportData, fn($payment) => in_array(strtolower($payment['payment_method'] ?? ''), ['gcash', 'paymaya', 'online']))),
    'unique_clients' => count(array_unique(array_column($reportData, 'client_id'))),
    'unique_loans' => count(array_unique(array_column($reportData, 'loan_id')))
];

// Calculate averages
$paymentStats['average_payment'] = $paymentStats['total_payments'] > 0 
    ? $paymentStats['total_amount'] / $paymentStats['total_payments'] 
    : 0;

// --- 4. Handle CSV Export ---
$exportFormat = $_GET['format'] ?? '';

if ($exportFormat === 'csv') {
    try {
        if (empty($reportData)) {
            throw new Exception('No data available for export.');
        }
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="payment_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, ['Payment Date', 'Client Name', 'Loan Number', 'Amount', 'Payment Method', 'Reference', 'Notes']);
        
        // Data
        foreach ($reportData as $payment) {
            fputcsv($output, [
                $payment['payment_date'],
                $payment['client_name'],
                $payment['loan_number'],
                $payment['amount'],
                $payment['payment_method'],
                $payment['reference_number'] ?? '',
                $payment['notes'] ?? ''
            ]);
        }
        
        fclose($output);
    } catch (Exception $e) {
        $session->setFlash('error', 'Error exporting CSV: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/reports/payments.php');
    }
    exit;
}

// --- 5. Handle PDF Export ---
if ($exportFormat === 'pdf') {
    try {
        $reportService = new ReportService();
        $reportService->exportPaymentReportPDF($reportData, $filters);
        exit;
    } catch (Exception $e) {
        $session->setFlash('error', 'Error generating PDF: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/reports/payments.php');
        exit;
    }
}

// Prepare data for template
$pageTitle = "Payment Reports";
$reportMetrics = [
    'report_date' => date('F j, Y'),
    'report_period' => date('M j', strtotime($filters['date_from'])) . ' - ' . date('M j, Y', strtotime($filters['date_to'])),
    'total_clients' => $paymentStats['unique_clients'],
    'total_loans' => $paymentStats['unique_loans']
];

include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <!-- Dashboard Header -->
        <div class="notion-page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #e8f5e8;">
                            <i data-feather="dollar-sign" style="width: 24px; height: 24px; color: #198754;"></i>
                        </div>
                    </div>
                    <h1 class="notion-page-title mb-0">Payment Reports</h1>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <div class="text-muted d-none d-md-block me-3">
                        <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i>
                        <?= date('l, F j, Y') ?>
                    </div>
                    <div class="btn-group">
                        <a href="<?= APP_URL ?>/public/reports/payments.php?format=csv&<?= http_build_query($filters) ?>" class="btn btn-sm btn-outline-success">
                            <i data-feather="download" class="me-1" style="width: 14px; height: 14px;"></i> CSV
                        </a>
                        <a href="<?= APP_URL ?>/public/reports/payments.php?format=pdf&<?= http_build_query($filters) ?>" class="btn btn-sm btn-outline-danger">
                            <i data-feather="file-text" class="me-1" style="width: 14px; height: 14px;"></i> PDF
                        </a>
                    </div>
                    <a href="<?= APP_URL ?>/public/payments/index.php" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="arrow-left" class="me-1" style="width: 14px; height: 14px;"></i> Back to Payments
                    </a>
                </div>
            </div>
            <div class="notion-divider my-3"></div>
        </div>

        <!-- Flash Messages -->
        <?php if ($session->hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i data-feather="check-circle" class="me-2" style="width: 16px; height: 16px;"></i>
                <?= $session->getFlash('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($session->hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i data-feather="alert-circle" class="me-2" style="width: 16px; height: 16px;"></i>
                <?= $session->getFlash('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Report Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <i data-feather="filter" class="me-2" style="width:18px;height:18px;"></i>
                    <strong>Report Filters</strong>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="date_from" value="<?= $filters['date_from'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="date_to" value="<?= $filters['date_to'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" name="payment_method">
                            <option value="">All Methods</option>
                            <option value="cash" <?= ($filters['payment_method'] ?? '') === 'cash' ? 'selected' : '' ?>>Cash</option>
                            <option value="bank" <?= ($filters['payment_method'] ?? '') === 'bank' ? 'selected' : '' ?>>Bank</option>
                            <option value="gcash" <?= ($filters['payment_method'] ?? '') === 'gcash' ? 'selected' : '' ?>>GCash</option>
                            <option value="paymaya" <?= ($filters['payment_method'] ?? '') === 'paymaya' ? 'selected' : '' ?>>PayMaya</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Min Amount</label>
                        <input type="number" class="form-control" name="min_amount" placeholder="0" value="<?= $filters['min_amount'] ?? '' ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="search" style="width: 16px; height: 16px;"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-success shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Total Payments</h6>
                                <h3 class="mb-0"><?= number_format($paymentStats['total_payments']) ?></h3>
                            </div>
                            <i data-feather="credit-card" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-primary shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Total Amount</h6>
                                <h3 class="mb-0">₱<?= number_format($paymentStats['total_amount'], 2) ?></h3>
                            </div>
                            <i data-feather="dollar-sign" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Average Payment</h6>
                                <h3 class="mb-0">₱<?= number_format($paymentStats['average_payment'], 2) ?></h3>
                            </div>
                            <i data-feather="trending-up" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-secondary shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Active Clients</h6>
                                <h3 class="mb-0"><?= number_format($paymentStats['unique_clients']) ?></h3>
                            </div>
                            <i data-feather="users" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Method Breakdown -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-success shadow-sm">
                    <div class="card-body text-center">
                        <i data-feather="dollar-sign" class="text-success mb-2" style="width: 2rem; height: 2rem;"></i>
                        <h4 class="text-success"><?= number_format($paymentStats['cash_payments']) ?></h4>
                        <small class="text-muted">Cash Payments</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-primary shadow-sm">
                    <div class="card-body text-center">
                        <i data-feather="credit-card" class="text-primary mb-2" style="width: 2rem; height: 2rem;"></i>
                        <h4 class="text-primary"><?= number_format($paymentStats['bank_payments']) ?></h4>
                        <small class="text-muted">Bank Transfers</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info shadow-sm">
                    <div class="card-body text-center">
                        <i data-feather="smartphone" class="text-info mb-2" style="width: 2rem; height: 2rem;"></i>
                        <h4 class="text-info"><?= number_format($paymentStats['online_payments']) ?></h4>
                        <small class="text-muted">Online Payments</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Data Table -->
        <div class="card shadow-sm">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i data-feather="list" class="me-2" style="width:18px;height:18px;"></i>
                        <strong>Payment Report Data</strong>
                        <span class="badge bg-primary ms-2"><?= count($reportData) ?> records</span>
                    </div>
                    <div class="text-muted small">
                        Period: <?= $reportMetrics['report_period'] ?>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($reportData)): ?>
                    <div class="text-center py-5">
                        <i data-feather="inbox" class="text-muted mb-3" style="width: 3rem; height: 3rem;"></i>
                        <h5 class="text-muted">No payment data found</h5>
                        <p class="text-muted">Try adjusting your filters or date range to see results.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 110px;">Date</th>
                                    <th>Client Name</th>
                                    <th style="width: 130px;">Loan #</th>
                                    <th class="text-end">Amount</th>
                                    <th style="width: 120px;">Method</th>
                                    <th style="width: 130px;">Reference</th>
                                    <th style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $payment): ?>
                                    <tr>
                                        <td>
                                            <div class="text-nowrap">
                                                <?= !empty($payment['payment_date']) ? date('M d, Y', strtotime($payment['payment_date'])) : '' ?>
                                            </div>
                                            <?php if (!empty($payment['payment_date'])): ?>
                                                <small class="text-muted"><?= date('g:i A', strtotime($payment['payment_date'])) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-success text-white me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                    <?= strtoupper(substr($payment['client_name'] ?? 'U', 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($payment['client_name'] ?? '') ?></div>
                                                    <?php if (!empty($payment['client_phone'])): ?>
                                                        <small class="text-muted"><?= htmlspecialchars($payment['client_phone']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $payment['loan_id'] ?>" class="text-decoration-none fw-bold">
                                                <?= htmlspecialchars($payment['loan_number'] ?? '') ?>
                                            </a>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold text-success">₱<?= number_format((float)($payment['amount'] ?? 0), 2) ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            $method = strtolower($payment['payment_method'] ?? '');
                                            switch($method) {
                                                case 'cash':
                                                    $methodClass = 'bg-success';
                                                    break;
                                                case 'bank':
                                                    $methodClass = 'bg-primary';
                                                    break;
                                                case 'gcash':
                                                    $methodClass = 'bg-info';
                                                    break;
                                                case 'paymaya':
                                                    $methodClass = 'bg-warning text-dark';
                                                    break;
                                                default:
                                                    $methodClass = 'bg-secondary';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?= $methodClass ?> px-2 py-1">
                                                <?= htmlspecialchars(ucfirst($payment['payment_method'] ?? '')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($payment['reference_number'])): ?>
                                                <small class="text-muted font-monospace"><?= htmlspecialchars($payment['reference_number']) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?= APP_URL ?>/public/payments/view.php?id=<?= $payment['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Payment">
                                                    <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                                </a>
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

        <!-- Executive Summary Footer -->
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-12">
                        <h6 class="text-muted mb-3">Executive Summary</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="border-end pe-3">
                                    <div class="h5 mb-1"><?= number_format($paymentStats['total_payments']) ?></div>
                                    <small class="text-muted">Total Payments</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border-end pe-3">
                                    <div class="h5 mb-1">₱<?= number_format($paymentStats['total_amount'], 2) ?></div>
                                    <small class="text-muted">Total Amount</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border-end pe-3">
                                    <div class="h5 mb-1">₱<?= number_format($paymentStats['average_payment'], 2) ?></div>
                                    <small class="text-muted">Average Payment</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="h5 mb-1"><?= number_format($paymentStats['unique_clients']) ?></div>
                                <small class="text-muted">Active Clients</small>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-top">
                            <small class="text-muted">
                                <i data-feather="clock" class="me-1" style="width: 14px; height: 14px;"></i>
                                Report generated on <?= date('F j, Y \a\t g:i A') ?> | Period: <?= $reportMetrics['report_period'] ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>