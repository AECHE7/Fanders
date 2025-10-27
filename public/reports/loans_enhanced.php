<?php
/**
 * Loan Reports Controller
 * Generates comprehensive reports for loan management with professional design
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

// Additional loan-specific filters
$loanFilters = [
    'status' => $filters['status'] ?? '',
    'client_id' => $filters['client_id'] ?? '',
    'loan_officer' => $filters['loan_officer'] ?? '',
    'min_amount' => $filters['min_amount'] ?? '',
    'max_amount' => $filters['max_amount'] ?? ''
];

$filters = array_merge($filters, $loanFilters);

// --- 2. Generate Report Data ---
$reportData = $reportService->generateLoanReport($filters);

// Add array check to prevent fatal error
if (!is_array($reportData)) {
    $reportData = [];
}

// --- 3. Calculate Statistics ---
$loanStats = [
    'total_loans' => count($reportData),
    'total_principal' => array_sum(array_column($reportData, 'principal_amount')),
    'total_disbursed' => array_sum(array_column($reportData, 'total_amount')),
    'total_paid' => array_sum(array_column($reportData, 'total_paid')),
    'total_balance' => array_sum(array_column($reportData, 'remaining_balance')),
    'active_loans' => count(array_filter($reportData, fn($loan) => $loan['status'] === 'active')),
    'completed_loans' => count(array_filter($reportData, fn($loan) => $loan['status'] === 'completed')),
    'pending_loans' => count(array_filter($reportData, fn($loan) => $loan['status'] === 'pending'))
];

// Calculate collection rate
$loanStats['collection_rate'] = $loanStats['total_disbursed'] > 0 
    ? round(($loanStats['total_paid'] / $loanStats['total_disbursed']) * 100, 1) 
    : 0;

// --- 4. Handle CSV Export ---
$exportFormat = $_GET['format'] ?? '';

if ($exportFormat === 'csv') {
    try {
        if (empty($reportData)) {
            throw new Exception('No data available for export.');
        }
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="loan_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, ['Loan Number', 'Client Name', 'Principal Amount', 'Total Amount', 'Amount Paid', 'Balance', 'Status', 'Created Date']);
        
        // Data
        foreach ($reportData as $loan) {
            fputcsv($output, [
                $loan['loan_number'],
                $loan['client_name'],
                $loan['principal_amount'],
                $loan['total_amount'],
                $loan['total_paid'],
                $loan['remaining_balance'],
                ucfirst($loan['status']),
                $loan['created_at']
            ]);
        }
        
        fclose($output);
    } catch (Exception $e) {
        $session->setFlash('error', 'Error exporting CSV: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/reports/loans.php');
    }
    exit;
}

// --- 5. Handle PDF Export ---
if ($exportFormat === 'pdf') {
    try {
        $reportService = new ReportService();
        $reportService->exportLoanReportPDF($reportData, $filters);
        exit;
    } catch (Exception $e) {
        $session->setFlash('error', 'Error generating PDF: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/reports/loans.php');
        exit;
    }
}

// Prepare data for template
$pageTitle = "Loan Reports";
$reportMetrics = [
    'report_date' => date('F j, Y'),
    'report_period' => !empty($filters['date_from']) && !empty($filters['date_to']) 
        ? date('M j', strtotime($filters['date_from'])) . ' - ' . date('M j, Y', strtotime($filters['date_to']))
        : 'All Time',
    'total_clients' => count(array_unique(array_column($reportData, 'client_id')))
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
                        <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #e8f4fd;">
                            <i data-feather="credit-card" style="width: 24px; height: 24px; color: #0d6efd;"></i>
                        </div>
                    </div>
                    <h1 class="notion-page-title mb-0">Loan Reports</h1>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <div class="text-muted d-none d-md-block me-3">
                        <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i>
                        <?= date('l, F j, Y') ?>
                    </div>
                    <div class="btn-group">
                        <a href="<?= APP_URL ?>/public/reports/loans.php?format=csv&<?= http_build_query($filters) ?>" class="btn btn-sm btn-outline-success">
                            <i data-feather="download" class="me-1" style="width: 14px; height: 14px;"></i> CSV
                        </a>
                        <a href="<?= APP_URL ?>/public/reports/loans.php?format=pdf&<?= http_build_query($filters) ?>" class="btn btn-sm btn-outline-danger">
                            <i data-feather="file-text" class="me-1" style="width: 14px; height: 14px;"></i> PDF
                        </a>
                    </div>
                    <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="arrow-left" class="me-1" style="width: 14px; height: 14px;"></i> Back to Loans
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
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
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
                <div class="card text-white bg-primary shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Total Loans</h6>
                                <h3 class="mb-0"><?= number_format($loanStats['total_loans']) ?></h3>
                            </div>
                            <i data-feather="credit-card" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Total Disbursed</h6>
                                <h3 class="mb-0">₱<?= number_format($loanStats['total_disbursed'], 2) ?></h3>
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
                                <h3 class="mb-0"><?= $loanStats['collection_rate'] ?>%</h3>
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
                                <h6 class="card-title text-uppercase small">Outstanding</h6>
                                <h3 class="mb-0">₱<?= number_format($loanStats['total_balance'], 2) ?></h3>
                            </div>
                            <i data-feather="clock" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Breakdown -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-success shadow-sm">
                    <div class="card-body text-center">
                        <i data-feather="check-circle" class="text-success mb-2" style="width: 2rem; height: 2rem;"></i>
                        <h4 class="text-success"><?= number_format($loanStats['active_loans']) ?></h4>
                        <small class="text-muted">Active Loans</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-info shadow-sm">
                    <div class="card-body text-center">
                        <i data-feather="clock" class="text-info mb-2" style="width: 2rem; height: 2rem;"></i>
                        <h4 class="text-info"><?= number_format($loanStats['pending_loans']) ?></h4>
                        <small class="text-muted">Pending Loans</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning shadow-sm">
                    <div class="card-body text-center">
                        <i data-feather="check" class="text-warning mb-2" style="width: 2rem; height: 2rem;"></i>
                        <h4 class="text-warning"><?= number_format($loanStats['completed_loans']) ?></h4>
                        <small class="text-muted">Completed Loans</small>
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
                        <strong>Loan Report Data</strong>
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
                        <h5 class="text-muted">No loans data found</h5>
                        <p class="text-muted">Try adjusting your filters or date range to see results.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 120px;">Loan #</th>
                                    <th>Client Name</th>
                                    <th class="text-end">Principal</th>
                                    <th class="text-end">Total Amount</th>
                                    <th class="text-end">Amount Paid</th>
                                    <th class="text-end">Balance</th>
                                    <th style="width: 100px;">Status</th>
                                    <th style="width: 110px;">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $loan): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $loan['id'] ?>" class="text-decoration-none fw-bold">
                                                <?= htmlspecialchars($loan['loan_number'] ?? '') ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-primary text-white me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                    <?= strtoupper(substr($loan['client_name'] ?? 'U', 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($loan['client_name'] ?? '') ?></div>
                                                    <?php if (!empty($loan['client_phone'])): ?>
                                                        <small class="text-muted"><?= htmlspecialchars($loan['client_phone']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end fw-semibold">₱<?= number_format((float)($loan['principal_amount'] ?? 0), 2) ?></td>
                                        <td class="text-end">₱<?= number_format((float)($loan['total_amount'] ?? 0), 2) ?></td>
                                        <td class="text-end text-success">₱<?= number_format((float)($loan['total_paid'] ?? 0), 2) ?></td>
                                        <td class="text-end">
                                            <?php $balance = (float)($loan['remaining_balance'] ?? 0); ?>
                                            <span class="<?= $balance > 0 ? 'text-warning fw-semibold' : 'text-success' ?>">
                                                ₱<?= number_format($balance, 2) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            $status = strtolower($loan['status'] ?? '');
                                            $badgeClass = match($status) {
                                                'active' => 'bg-success',
                                                'completed' => 'bg-primary',
                                                'pending' => 'bg-warning text-dark',
                                                'cancelled' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $badgeClass ?> px-2 py-1">
                                                <?= htmlspecialchars(ucfirst($loan['status'] ?? '')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-nowrap">
                                                <?= !empty($loan['created_at']) ? date('M d, Y', strtotime($loan['created_at'])) : '' ?>
                                            </div>
                                            <?php if (!empty($loan['created_at'])): ?>
                                                <small class="text-muted"><?= date('g:i A', strtotime($loan['created_at'])) ?></small>
                                            <?php endif; ?>
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
                                    <div class="h5 mb-1"><?= number_format($loanStats['total_loans']) ?></div>
                                    <small class="text-muted">Total Loans</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border-end pe-3">
                                    <div class="h5 mb-1">₱<?= number_format($loanStats['total_disbursed'], 2) ?></div>
                                    <small class="text-muted">Total Disbursed</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border-end pe-3">
                                    <div class="h5 mb-1 text-success">₱<?= number_format($loanStats['total_paid'], 2) ?></div>
                                    <small class="text-muted">Total Collected</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="h5 mb-1 text-warning">₱<?= number_format($loanStats['total_balance'], 2) ?></div>
                                <small class="text-muted">Outstanding Balance</small>
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