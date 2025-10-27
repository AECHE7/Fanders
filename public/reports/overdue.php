<?php
/**
 * Overdue Loans Report Controller
 * Generates comprehensive reports for overdue loan payments
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control (Management and higher)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager']);

// Initialize services
$overdueService = new OverduePaymentService();
$reportService = new ReportService();

// Enhanced filter handling using FilterUtility
require_once '../../app/utilities/FilterUtility.php';

$filterOptions = [
    'allowed_severities' => ['low', 'medium', 'high', 'critical'],
    'allowed_formats' => ['html', 'pdf', 'excel', 'csv']
];
$filters = FilterUtility::sanitizeFilters($_GET, $filterOptions);

// Set default date range if not specified
if (empty($filters['date_from'])) {
    $filters['date_from'] = date('Y-m-01'); // First day of current month
}
if (empty($filters['date_to'])) {
    $filters['date_to'] = date('Y-m-d'); // Today
}

$filters = FilterUtility::validateDateRange($filters);

// Get comprehensive overdue data
$overdueLoans = $overdueService->getOverdueAnalysis($filters);
$overdueStats = $overdueService->getOverdueStatistics($overdueLoans);

// Handle different export formats
$exportFormat = $_GET['format'] ?? 'html';

if ($exportFormat === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="overdue_loans_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    $csvData = $overdueService->exportOverdueLoansCSV($overdueLoans);
    
    foreach ($csvData as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

if ($exportFormat === 'pdf') {
    try {
        $reportService = new ReportService();
        $reportService->exportOverdueReportPDF($overdueLoans, $filters);
        exit;
    } catch (Exception $e) {
        $session->setFlash('error', 'Error generating PDF: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/reports/overdue.php');
        exit;
    }
}

// Prepare data for template
$pageTitle = "Overdue Loans Report";

// Group loans by severity for better presentation
$loansBySeverity = [
    'critical' => array_filter($overdueLoans, fn($loan) => $loan['severity'] === 'critical'),
    'high' => array_filter($overdueLoans, fn($loan) => $loan['severity'] === 'high'),
    'medium' => array_filter($overdueLoans, fn($loan) => $loan['severity'] === 'medium'),
    'low' => array_filter($overdueLoans, fn($loan) => $loan['severity'] === 'low')
];

// Calculate additional report metrics
$reportMetrics = [
    'report_date' => date('F j, Y'),
    'report_period' => date('M j', strtotime($filters['date_from'])) . ' - ' . date('M j, Y', strtotime($filters['date_to'])),
    'total_loans_analyzed' => count($overdueLoans),
    'highest_overdue_amount' => !empty($overdueLoans) ? max(array_column($overdueLoans, 'payment_shortfall')) : 0,
    'average_loan_amount' => !empty($overdueLoans) ? array_sum(array_column($overdueLoans, 'total_amount')) / count($overdueLoans) : 0,
    'clients_affected' => count(array_unique(array_column($overdueLoans, 'client_id')))
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
                        <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #fff2f2;">
                            <i data-feather="file-text" style="width: 24px; height: 24px; color: #dc3545;"></i>
                        </div>
                    </div>
                    <h1 class="notion-page-title mb-0">Overdue Loans Report</h1>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <div class="text-muted d-none d-md-block me-3">
                        <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i>
                        <?= date('l, F j, Y') ?>
                    </div>
                    <div class="btn-group">
                        <a href="<?= APP_URL ?>/public/reports/overdue.php?format=csv&<?= http_build_query($filters) ?>" class="btn btn-sm btn-outline-success">
                            <i data-feather="download" class="me-1" style="width: 14px; height: 14px;"></i> CSV
                        </a>
                        <a href="<?= APP_URL ?>/public/reports/overdue.php?format=pdf&<?= http_build_query($filters) ?>" class="btn btn-sm btn-outline-danger">
                            <i data-feather="file-text" class="me-1" style="width: 14px; height: 14px;"></i> PDF
                        </a>
                    </div>
                    <a href="<?= APP_URL ?>/public/payments/overdue_payments.php" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="arrow-left" class="me-1" style="width: 14px; height: 14px;"></i> Back to Overdue
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
                        <label class="form-label">Severity</label>
                        <select class="form-select" name="severity">
                            <option value="">All Severities</option>
                            <option value="critical" <?= ($filters['severity'] ?? '') === 'critical' ? 'selected' : '' ?>>Critical</option>
                            <option value="high" <?= ($filters['severity'] ?? '') === 'high' ? 'selected' : '' ?>>High</option>
                            <option value="medium" <?= ($filters['severity'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="low" <?= ($filters['severity'] ?? '') === 'low' ? 'selected' : '' ?>>Low</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Min Days Overdue</label>
                        <select class="form-select" name="days_overdue">
                            <option value="">All</option>
                            <option value="7" <?= ($filters['days_overdue'] ?? '') == 7 ? 'selected' : '' ?>>7+ days</option>
                            <option value="14" <?= ($filters['days_overdue'] ?? '') == 14 ? 'selected' : '' ?>>14+ days</option>
                            <option value="30" <?= ($filters['days_overdue'] ?? '') == 30 ? 'selected' : '' ?>>30+ days</option>
                            <option value="60" <?= ($filters['days_overdue'] ?? '') == 60 ? 'selected' : '' ?>>60+ days</option>
                        </select>
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
                <div class="card text-white bg-danger shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Total Overdue</h6>
                                <h3 class="mb-0"><?= number_format($overdueStats['total_overdue']) ?></h3>
                            </div>
                            <i data-feather="alert-triangle" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Overdue Amount</h6>
                                <h3 class="mb-0">₱<?= number_format($overdueStats['total_overdue_amount'], 2) ?></h3>
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
                                <h6 class="card-title text-uppercase small">Collection Rate</h6>
                                <h3 class="mb-0"><?= $overdueStats['collection_rate'] ?>%</h3>
                            </div>
                            <i data-feather="trending-up" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-dark shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Clients Affected</h6>
                                <h3 class="mb-0"><?= $reportMetrics['clients_affected'] ?></h3>
                            </div>
                            <i data-feather="users" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Details by Severity -->
        <?php foreach (['critical', 'high', 'medium', 'low'] as $severity): ?>
            <?php if (!empty($loansBySeverity[$severity])): ?>
                <?php
                $severityConfig = [
                    'critical' => ['label' => 'Critical - Immediate Action Required', 'class' => 'danger', 'icon' => 'alert-octagon'],
                    'high' => ['label' => 'High Priority - Contact Client', 'class' => 'warning', 'icon' => 'alert-triangle'],
                    'medium' => ['label' => 'Moderate - Follow Up Soon', 'class' => 'info', 'icon' => 'clock'],
                    'low' => ['label' => 'Recently Overdue - Monitor', 'class' => 'secondary', 'icon' => 'eye']
                ];
                $config = $severityConfig[$severity];
                ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-<?= $config['class'] ?> text-white">
                        <div class="d-flex align-items-center">
                            <i data-feather="<?= $config['icon'] ?>" class="me-2" style="width:18px;height:18px;"></i>
                            <strong><?= $config['label'] ?> (<?= count($loansBySeverity[$severity]) ?>)</strong>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Loan #</th>
                                        <th>Client</th>
                                        <th>Contact</th>
                                        <th class="text-end">Principal</th>
                                        <th class="text-end">Total Amount</th>
                                        <th class="text-end">Amount Paid</th>
                                        <th class="text-end">Balance</th>
                                        <th class="text-end">Shortfall</th>
                                        <th class="text-center">Days Overdue</th>
                                        <th class="text-center">Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($loansBySeverity[$severity] as $loan): ?>
                                    <tr>
                                        <td><strong>#<?= $loan['loan_number'] ?></strong></td>
                                        <td><?= htmlspecialchars($loan['client_name']) ?></td>
                                        <td>
                                            <small>
                                                <?php if ($loan['phone']): ?>
                                                    <div><i data-feather="phone" style="width: 12px; height: 12px;"></i> <?= htmlspecialchars($loan['phone']) ?></div>
                                                <?php endif; ?>
                                                <?php if ($loan['client_email']): ?>
                                                    <div><i data-feather="mail" style="width: 12px; height: 12px;"></i> <?= htmlspecialchars($loan['client_email']) ?></div>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td class="text-end">₱<?= number_format($loan['principal_amount'], 2) ?></td>
                                        <td class="text-end">₱<?= number_format($loan['total_amount'], 2) ?></td>
                                        <td class="text-end">₱<?= number_format($loan['total_paid'], 2) ?></td>
                                        <td class="text-end"><strong>₱<?= number_format($loan['remaining_balance'], 2) ?></strong></td>
                                        <td class="text-end text-danger"><strong>₱<?= number_format($loan['payment_shortfall'], 2) ?></strong></td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= $config['class'] ?>"><?= $loan['days_overdue'] ?> days</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="progress" style="height: 8px; width: 60px;">
                                                <div class="progress-bar bg-success" style="width: <?= $loan['percentage_paid'] ?>%"></div>
                                            </div>
                                            <small><?= $loan['percentage_paid'] ?>%</small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <!-- Report Summary Statistics -->
        <div class="card shadow-sm">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <i data-feather="bar-chart-2" class="me-2" style="width:18px;height:18px;"></i>
                    <strong>Report Summary</strong>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Collection Performance</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>Total Expected Payments:</td>
                                <td class="text-end">₱<?= number_format($overdueStats['total_expected_payments'], 2) ?></td>
                            </tr>
                            <tr>
                                <td>Total Actual Payments:</td>
                                <td class="text-end">₱<?= number_format($overdueStats['total_actual_payments'], 2) ?></td>
                            </tr>
                            <tr>
                                <td>Payment Shortfall:</td>
                                <td class="text-end text-danger">₱<?= number_format($overdueStats['total_overdue_amount'], 2) ?></td>
                            </tr>
                            <tr class="table-info">
                                <td><strong>Collection Rate:</strong></td>
                                <td class="text-end"><strong><?= $overdueStats['collection_rate'] ?>%</strong></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Overdue Analysis</h6>
                        <table class="table table-sm">
                            <tr>
                                <td>Critical Cases:</td>
                                <td class="text-end"><?= $overdueStats['severity_stats']['critical'] ?></td>
                            </tr>
                            <tr>
                                <td>High Priority:</td>
                                <td class="text-end"><?= $overdueStats['severity_stats']['high'] ?></td>
                            </tr>
                            <tr>
                                <td>Medium Priority:</td>
                                <td class="text-end"><?= $overdueStats['severity_stats']['medium'] ?></td>
                            </tr>
                            <tr>
                                <td>Low Priority:</td>
                                <td class="text-end"><?= $overdueStats['severity_stats']['low'] ?></td>
                            </tr>
                            <tr class="table-info">
                                <td><strong>Average Days Overdue:</strong></td>
                                <td class="text-end"><strong><?= $overdueStats['average_days_overdue'] ?> days</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <small class="text-muted">
                            <i data-feather="info" class="me-1" style="width: 14px; height: 14px;"></i>
                            Report generated on <?= $reportMetrics['report_date'] ?> for period <?= $reportMetrics['report_period'] ?>. 
                            Analysis includes <?= $reportMetrics['total_loans_analyzed'] ?> overdue loans affecting <?= $reportMetrics['clients_affected'] ?> clients.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
/* Report-specific styling */
.icon-lg {
    opacity: 0.7;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.card-header strong {
    color: #495057;
    font-weight: 600;
}

.notion-page-header h1 {
    font-size: 1.75rem;
    font-weight: 600;
}

.notion-page-title {
    color: #2d3748;
}

.notion-divider {
    height: 1px;
    background: linear-gradient(90deg, #e2e8f0 0%, #cbd5e0 50%, #e2e8f0 100%);
    border: none;
    margin: 1rem 0;
}

.page-icon {
    transition: all 0.3s ease;
}

.page-icon:hover {
    transform: scale(1.05);
}

.progress {
    background-color: #f0f0f0;
}

.progress-bar {
    transition: width 0.6s ease;
}

@media print {
    .btn, .card-header .btn-group {
        display: none !important;
    }
    
    .card {
        break-inside: avoid;
        margin-bottom: 1rem;
    }
}
</style>

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>