<?php
/**
 * Reports Index Controller
 * Displays unified reports interface with filtering and export capabilities.
 * Integrates: ReportService
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control for Fanders Microfinance Staff
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account-officer']);

// Initialize services
$reportService = new ReportService();
require_once BASE_PATH . '/app/utilities/FormatUtility.php';

// --- 1. Process Filters ---
require_once '../../app/utilities/FilterUtility.php';
$filters = FilterUtility::sanitizeFilters($_GET);
$filters = FilterUtility::validateDateRange($filters);

// Set default report type if not specified
if (empty($filters['type'])) {
    $filters['type'] = 'loans';
}

// --- 2. Generate Report Data ---
$reportData = [];
$reportTitle = '';
$reportStats = [];

try {
    switch ($filters['type']) {
        case 'loans':
            $reportData = $reportService->generateLoanReport($filters);
            $reportTitle = 'Loan Report';
            if (is_array($reportData)) {
                $reportStats = [
                    'total_records' => count($reportData),
                    'total_principal' => array_sum(array_column($reportData, 'principal_amount')),
                    'total_paid' => array_sum(array_column($reportData, 'total_paid')),
                    'total_balance' => array_sum(array_column($reportData, 'remaining_balance'))
                ];
            }
            break;
        case 'payments':
            $reportData = $reportService->generatePaymentReport($filters);
            $reportTitle = 'Payment Report';
            if (is_array($reportData)) {
                $reportStats = [
                    'total_records' => count($reportData),
                    'total_amount' => array_sum(array_column($reportData, 'amount')),
                    'total_principal' => array_sum(array_column($reportData, 'principal_amount')),
                    'total_interest' => array_sum(array_column($reportData, 'interest_amount'))
                ];
            }
            break;
        case 'clients':
            $reportData = $reportService->generateClientReport($filters);
            $reportTitle = 'Client Report';
            if (is_array($reportData)) {
                $reportStats = [
                    'total_records' => count($reportData),
                    'total_loans' => array_sum(array_column($reportData, 'total_loans')),
                    'total_principal' => array_sum(array_column($reportData, 'total_principal')),
                    'total_outstanding' => array_sum(array_column($reportData, 'outstanding_balance'))
                ];
            }
            break;
        case 'users':
            $reportData = $reportService->generateUserReport($filters);
            $reportTitle = 'User Report';
            if (is_array($reportData)) {
                $reportStats = [
                    'total_records' => count($reportData),
                    'active_users' => count(array_filter($reportData, fn($user) => $user['is_active'])),
                    'inactive_users' => count(array_filter($reportData, fn($user) => !$user['is_active']))
                ];
            }
            break;
        case 'financial':
            $reportData = $reportService->generateFinancialSummary($filters);
            $reportTitle = 'Financial Summary';
            break;
        case 'overdue':
            $reportData = $reportService->generateOverdueReport($filters);
            $reportTitle = 'Overdue Loans Report';
            if (is_array($reportData)) {
                $reportStats = [
                    'total_records' => count($reportData),
                    'total_overdue' => array_sum(array_column($reportData, 'remaining_balance')),
                    'avg_days_overdue' => count($reportData) > 0 ? array_sum(array_column($reportData, 'days_overdue')) / count($reportData) : 0
                ];
            }
            break;
        default:
            $reportData = $reportService->generateLoanReport($filters);
            $reportTitle = 'Loan Report';
            if (is_array($reportData)) {
                $reportStats = [
                    'total_records' => count($reportData),
                    'total_principal' => array_sum(array_column($reportData, 'principal_amount')),
                    'total_paid' => array_sum(array_column($reportData, 'total_paid')),
                    'total_balance' => array_sum(array_column($reportData, 'remaining_balance'))
                ];
            }
    }
} catch (Exception $e) {
    $session->setFlash('error', 'Error generating report: ' . $e->getMessage());
    $reportData = [];
}

// --- 3. Handle PDF Export ---
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    try {
        switch ($filters['type']) {
            case 'loans':
                $reportService->exportLoanReportPDF($reportData, $filters);
                break;
            case 'payments':
                $reportService->exportPaymentReportPDF($reportData, $filters);
                break;
            case 'clients':
                $reportService->exportClientReportPDF($reportData, $filters);
                break;
            case 'users':
                $reportService->exportUserReportPDF($reportData, $filters);
                break;
            case 'financial':
                $reportService->exportFinancialSummaryPDF($reportData);
                break;
            case 'overdue':
                $reportService->exportOverdueReportPDF($reportData, $filters);
                break;
        }
    } catch (Exception $e) {
        $session->setFlash('error', 'Error exporting PDF: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/reports/index.php?' . http_build_query($filters));
        exit;
    }
    exit;
}

// --- 3b. Handle Excel Export ---
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    try {
        switch ($filters['type']) {
            case 'loans':
                $reportService->exportLoanReportExcel($reportData, $filters);
                break;
            case 'payments':
                $reportService->exportPaymentReportExcel($reportData, $filters);
                break;
            case 'clients':
                $reportService->exportClientReportExcel($reportData, $filters);
                break;
            case 'users':
                $reportService->exportUserReportExcel($reportData, $filters);
                break;
            case 'financial':
                $reportService->exportFinancialSummaryExcel($reportData);
                break;
            case 'overdue':
                $reportService->exportOverdueReportExcel($reportData, $filters);
                break;
        }
    } catch (Exception $e) {
        $session->setFlash('error', 'Error exporting Excel: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/reports/index.php?' . http_build_query($filters));
        exit;
    }
    exit;
}

// --- 4. Display View ---
$pageTitle = "Reports - " . $reportTitle;

include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
    <!-- Dashboard Header with Title, Date and Reports Links -->
    <div class="notion-page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0f4fd;">
                        <i data-feather="bar-chart-2" style="width: 24px; height: 24px; color:rgb(0, 0, 0);"></i>
                    </div>
                </div>
                <h1 class="notion-page-title mb-0">Reports Management</h1>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <div class="text-muted d-none d-md-block me-3">
                    <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i>
                    <?= date('l, F j, Y') ?>
                </div>
                <a href="<?= APP_URL ?>/public/reports/index.php?type=financial" class="btn btn-sm btn-success">
                    <i data-feather="trending-up" class="me-1" style="width: 14px; height: 14px;"></i> Financial Summary
                </a>
                <a href="<?= APP_URL ?>/public/reports/index.php?type=overdue" class="btn btn-sm btn-warning">
                    <i data-feather="alert-triangle" class="me-1" style="width: 14px; height: 14px;"></i> Overdue Report
                </a>
            </div>
        </div>
        <div class="notion-divider my-3"></div>
    </div>

    <!-- Report Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Report Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" id="report-filter-form">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="type" class="form-label">Report Type</label>
                        <select class="form-select" id="type" name="type">
                            <option value="loans" <?= $filters['type'] === 'loans' ? 'selected' : '' ?>>Loans</option>
                            <option value="payments" <?= $filters['type'] === 'payments' ? 'selected' : '' ?>>Payments</option>
                            <option value="clients" <?= $filters['type'] === 'clients' ? 'selected' : '' ?>>Clients</option>
                            <option value="users" <?= $filters['type'] === 'users' ? 'selected' : '' ?>>Users</option>
                            <option value="financial" <?= $filters['type'] === 'financial' ? 'selected' : '' ?>>Financial Summary</option>
                            <option value="overdue" <?= $filters['type'] === 'overdue' ? 'selected' : '' ?>>Overdue Loans</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3" id="date-filters">
                        <label for="date_from" class="form-label">Date From</label>
                        <input type="date" class="form-control" id="date_from" name="date_from"
                               value="<?= $filters['date_from'] ?>">
                    </div>

                    <div class="col-md-3 mb-3" id="date-filters-to">
                        <label for="date_to" class="form-label">Date To</label>
                        <input type="date" class="form-control" id="date_to" name="date_to"
                               value="<?= $filters['date_to'] ?>">
                    </div>

                    <div class="col-md-3 mb-3" id="status-filter">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="search" class="me-1"></i>Generate Report
                    </button>
                    <a href="?<?= http_build_query(array_merge($filters, ['export' => 'pdf'])) ?>"
                       class="btn btn-success">
                        <i data-feather="download" class="me-1"></i>Export PDF
                    </a>
                    <a href="?<?= http_build_query(array_merge($filters, ['export' => 'excel'])) ?>"
                       class="btn btn-outline-success">
                        <i data-feather=\"file\" class=\"me-1\"></i>Export Excel
                    </a>
                    <!-- Date Presets -->
                    <div class="ms-auto d-flex align-items-center gap-1 flex-wrap">
                        <span class="text-muted me-1">Presets:</span>
                        <button type="button" class="btn btn-outline-secondary btn-sm preset-btn" data-preset="today">Today</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm preset-btn" data-preset="this-month">This Month</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm preset-btn" data-preset="ytd">YTD</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm preset-btn" data-preset="last-month">Last Month</button>
                    </div>
                </div>
            </form>

            <!-- Quick Links -->
            <hr class="my-3">
            <h6 class="text-muted mb-2">Quick Access</h6>
            <div class="d-flex gap-1 flex-wrap">
                <a href="loans.php" class="btn btn-outline-primary btn-sm">
                    <i data-feather="file-text" class="me-1" style="width: 14px; height: 14px;"></i>Loans
                </a>
                <a href="payments.php" class="btn btn-outline-success btn-sm">
                    <i data-feather="dollar-sign" class="me-1" style="width: 14px; height: 14px;"></i>Payments
                </a>
                <a href="clients.php" class="btn btn-outline-info btn-sm">
                    <i data-feather="users" class="me-1" style="width: 14px; height: 14px;"></i>Clients
                </a>
                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['super-admin', 'admin'])): ?>
                <a href="users.php" class="btn btn-outline-warning btn-sm">
                    <i data-feather="user-check" class="me-1" style="width: 14px; height: 14px;"></i>Users
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Reports Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="<?= APP_URL ?>/public/reports/index.php?type=financial" class="btn btn-sm btn-success me-2">
                        <i data-feather="trending-up"></i> Financial Summary
                    </a>
                    <a href="<?= APP_URL ?>/public/reports/index.php?type=overdue" class="btn btn-sm btn-warning">
                        <i data-feather="alert-triangle"></i> Overdue Report
                    </a>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php if ($session->hasFlash('success')): ?>
                <div class="alert alert-success">
                    <?= $session->getFlash('success') ?>
                </div>
            <?php endif; ?>

            <?php if ($session->hasFlash('error')): ?>
                <div class="alert alert-danger">
                    <?= $session->getFlash('error') ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <?php if (!empty($reportStats)): ?>
                <div class="row mb-4">
                    <?php if ($filters['type'] === 'loans'): ?>
                        <div class="col-md-3">
                            <div class="card text-white bg-primary shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-uppercase small">Total Loans</h6>
                                            <h3 class="mb-0"><?= number_format($reportStats['total_records']) ?></h3>
                                        </div>
                                        <i data-feather="file-text" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-success shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-uppercase small">Total Principal</h6>
                                            <h3 class="mb-0">₱<?= number_format($reportStats['total_principal'], 2) ?></h3>
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
                                            <h6 class="card-title text-uppercase small">Total Paid</h6>
                                            <h3 class="mb-0">₱<?= number_format($reportStats['total_paid'], 2) ?></h3>
                                        </div>
                                        <i data-feather="check-circle" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-warning shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-uppercase small">Outstanding</h6>
                                            <h3 class="mb-0">₱<?= number_format($reportStats['total_balance'], 2) ?></h3>
                                        </div>
                                        <i data-feather="alert-triangle" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($filters['type'] === 'payments'): ?>
                        <div class="col-md-4">
                            <div class="card text-white bg-primary shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-uppercase small">Total Payments</h6>
                                            <h3 class="mb-0"><?= number_format($reportStats['total_records']) ?></h3>
                                        </div>
                                        <i data-feather="credit-card" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-white bg-success shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-uppercase small">Total Amount</h6>
                                            <h3 class="mb-0">₱<?= number_format($reportStats['total_amount'], 2) ?></h3>
                                        </div>
                                        <i data-feather="dollar-sign" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-white bg-info shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-uppercase small">Principal Paid</h6>
                                            <h3 class="mb-0">₱<?= number_format($reportStats['total_principal'], 2) ?></h3>
                                        </div>
                                        <i data-feather="trending-up" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($filters['type'] === 'clients'): ?>
                        <div class="col-md-3">
                            <div class="card text-white bg-primary shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-uppercase small">Total Clients</h6>
                                            <h3 class="mb-0"><?= number_format($reportStats['total_records']) ?></h3>
                                        </div>
                                        <i data-feather="users" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-success shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-uppercase small">Total Loans</h6>
                                            <h3 class="mb-0"><?= number_format($reportStats['total_loans']) ?></h3>
                                        </div>
                                <i data-feather="file-text" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-info shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-uppercase small">Total Principal</h6>
                                            <h3 class="mb-0">₱<?= number_format($reportStats['total_principal'], 2) ?></h3>
                                        </div>
                                        <i data-feather="dollar-sign" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-warning shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-uppercase small">Outstanding</h6>
                                            <h3 class="mb-0">₱<?= number_format($reportStats['total_outstanding'], 2) ?></h3>
                                        </div>
                                        <i data-feather="alert-triangle" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($filters['type'] === 'users'): ?>
                        <div class="col-md-4">
                            <div class="card text-white bg-primary shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-uppercase small">Total Users</h6>
                                            <h3 class="mb-0"><?= number_format($reportStats['total_records']) ?></h3>
                                        </div>
                                        <i data-feather="user-check" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-white bg-success shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-uppercase small">Active Users</h6>
                                            <h3 class="mb-0"><?= number_format($reportStats['active_users']) ?></h3>
                                        </div>
                                        <i data-feather="check-circle" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-white bg-warning shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-uppercase small">Inactive Users</h6>
                                            <h3 class="mb-0"><?= number_format($reportStats['inactive_users']) ?></h3>
                                        </div>
                                        <i data-feather="x-circle" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($filters['type'] === 'overdue'): ?>
                        <div class="col-md-4">
                            <div class="card text-white bg-danger shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-uppercase small">Overdue Loans</h6>
                                            <h3 class="mb-0"><?= number_format($reportStats['total_records']) ?></h3>
                                        </div>
                                        <i data-feather="alert-triangle" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-white bg-warning shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-uppercase small">Total Overdue</h6>
                                            <h3 class="mb-0">₱<?= number_format($reportStats['total_overdue'], 2) ?></h3>
                                        </div>
                                        <i data-feather="dollar-sign" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card text-white bg-info shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="card-title text-uppercase small">Avg Days Overdue</h6>
                                            <h3 class="mb-0"><?= number_format($reportStats['avg_days_overdue'], 1) ?> days</h3>
                                        </div>
                                        <i data-feather="clock" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Report Data Table -->
            <?php if ($filters['type'] !== 'financial'): ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if (empty($reportData)): ?>
                            <div class="text-center py-5">
                                <i data-feather="file-x" class="text-muted" style="width: 4rem; height: 4rem;"></i>
                                <h5 class="text-muted mt-3">No data found</h5>
                                <p class="text-muted">Try adjusting your filters or check back later.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <?php if ($filters['type'] === 'loans'): ?>
                                                <th>Loan #</th>
                                                <th>Client</th>
                                                <th>Principal</th>
                                                <th>Total Amount</th>
                                                <th>Paid</th>
                                                <th>Balance</th>
                                                <th>Status</th>
                                            <?php elseif ($filters['type'] === 'payments'): ?>
                                                <th>Payment #</th>
                                                <th>Client</th>
                                                <th>Loan #</th>
                                                <th>Amount</th>
                                                <th>Date</th>
                                            <?php elseif ($filters['type'] === 'clients'): ?>
                                                <th>Client Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Loans</th>
                                                <th>Outstanding</th>
                                                <th>Status</th>
                                            <?php elseif ($filters['type'] === 'users'): ?>
                                                <th>Username</th>
                                                <th>Full Name</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Status</th>
                                            <?php elseif ($filters['type'] === 'overdue'): ?>
                                                <th>Loan #</th>
                                                <th>Client</th>
                                                <th>Phone</th>
                                                <th>Principal</th>
                                                <th>Balance</th>
                                                <th>Days Overdue</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reportData as $row): ?>
                                            <tr>
                                                <?php if ($filters['type'] === 'loans'): ?>
                                                    <td>
                                                        <span class="badge bg-primary">#<?= htmlspecialchars($row['loan_number']) ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div>
                                                                <div class="fw-bold"><?= htmlspecialchars($row['client_name']) ?></div>
                                                                <small class="text-muted"><?= htmlspecialchars($row['client_email'] ?? '') ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="fw-bold">₱<?= number_format($row['principal_amount'], 2) ?></td>
                                                    <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                                                    <td class="text-success">₱<?= number_format($row['total_paid'], 2) ?></td>
                                                    <td class="text-warning">₱<?= number_format($row['remaining_balance'], 2) ?></td>
                                                    <?php $status = strtolower($row['status']); ?>
                                                    <td><span class="badge bg-<?= $status === 'active' ? 'success' : ($status === 'completed' ? 'primary' : 'secondary') ?>"><?= ucfirst($row['status']) ?></span></td>
                                                <?php elseif ($filters['type'] === 'payments'): ?>
                                                    <td>
                                                        <span class="badge bg-info">#<?= htmlspecialchars($row['payment_number']) ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div>
                                                                <div class="fw-bold"><?= htmlspecialchars($row['client_name']) ?></div>
                                                                <small class="text-muted"><?= htmlspecialchars($row['client_email'] ?? '') ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary">#<?= htmlspecialchars($row['loan_number']) ?></span>
                                                    </td>
                                                    <td class="fw-bold text-success">₱<?= number_format($row['amount'], 2) ?></td>
                                                    <td><?= date('M d, Y', strtotime($row['payment_date'])) ?></td>
                                                <?php elseif ($filters['type'] === 'clients'): ?>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div>
                                                                <div class="fw-bold"><?= htmlspecialchars($row['client_name']) ?></div>
                                                                <small class="text-muted">ID: <?= htmlspecialchars($row['client_id'] ?? ($row['id'] ?? '')) ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($row['email'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($row['phone'] ?? '') ?></td>
                                                    <td class="text-center">
                                                        <span class="badge bg-primary"><?= $row['total_loans'] ?></span>
                                                    </td>
                                                    <td class="text-warning">₱<?= number_format($row['outstanding_balance'], 2) ?></td>
                                                    <td><span class="badge bg-<?= $row['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($row['status']) ?></span></td>
                                                <?php elseif ($filters['type'] === 'users'): ?>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div>
                                                                <div class="fw-bold"><?= htmlspecialchars($row['username']) ?></div>
                                                                <small class="text-muted">ID: <?= htmlspecialchars($row['user_id'] ?? ($row['id'] ?? '')) ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($row['full_name'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($row['email'] ?? '') ?></td>
                                                    <td><span class="badge bg-info"><?= ucfirst($row['role']) ?></span></td>
                                                    <td><span class="badge bg-<?= $row['is_active'] ? 'success' : 'danger' ?>"><?= $row['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                                                <?php elseif ($filters['type'] === 'overdue'): ?>
                                                    <td>
                                                        <span class="badge bg-danger">#<?= htmlspecialchars($row['loan_number']) ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div>
                                                                <div class="fw-bold"><?= htmlspecialchars($row['client_name']) ?></div>
                                                                <small class="text-muted"><?= htmlspecialchars($row['client_email'] ?? '') ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?= htmlspecialchars($row['phone'] ?? '') ?></td>
                                                    <td>₱<?= number_format($row['principal_amount'], 2) ?></td>
                                                    <td class="text-danger fw-bold">₱<?= number_format($row['remaining_balance'], 2) ?></td>
                                                    <td><span class="badge bg-danger"><?= $row['days_overdue'] ?> days</span></td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Financial Summary Rendering -->
                <?php if (!empty($reportData) && isset($reportData['loans'], $reportData['payments'], $reportData['outstanding'])): ?>
                <div class="row mb-4">
                    <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                        <div class="card text-white bg-primary shadow-sm h-100">
                            <div class="card-body py-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i data-feather="file-text" class="me-2" style="width: 28px; height: 28px; opacity: .75;"></i>
                                    <h5 class="card-title mb-0 text-uppercase">Loans Disbursed</h5>
                                </div>
                                <div class="mt-2">
                                    <div class="d-flex justify-content-between align-items-center py-1">
                                        <span class="fw-medium">Total Loans</span>
                                        <span class="display-6 mb-0 lh-1"><?= number_format($reportData['loans']['total_loans']) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center py-1">
                                        <span class="fw-medium">Total Principal</span>
                                        <span class="h3 mb-0"><?= FormatUtility::peso($reportData['loans']['total_principal']) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center py-1">
                                        <span class="fw-medium">Total (with interest)</span>
                                        <span class="h3 mb-0"><?= FormatUtility::peso($reportData['loans']['total_amount_with_interest']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                        <div class="card text-white bg-success shadow-sm h-100">
                            <div class="card-body py-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i data-feather="credit-card" class="me-2" style="width: 28px; height: 28px; opacity: .75;"></i>
                                    <h5 class="card-title mb-0 text-uppercase">Payments Received</h5>
                                </div>
                                <div class="mt-2">
                                    <div class="d-flex justify-content-between align-items-center py-1">
                                        <span class="fw-medium">Total Payments</span>
                                        <span class="display-6 mb-0 lh-1"><?= number_format($reportData['payments']['total_payments']) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center py-1">
                                        <span class="fw-medium">Total Amount</span>
                                        <span class="h3 mb-0"><?= FormatUtility::peso($reportData['payments']['total_payments_received']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="card text-white bg-warning shadow-sm h-100">
                            <div class="card-body py-4">
                                <div class="d-flex align-items-center mb-3">
                                    <i data-feather="alert-triangle" class="me-2" style="width: 28px; height: 28px; opacity: .75;"></i>
                                    <h5 class="card-title mb-0 text-uppercase">Outstanding</h5>
                                </div>
                                <div class="mt-2">
                                    <div class="d-flex justify-content-between align-items-center py-1">
                                        <span class="fw-medium">Total Outstanding Balance</span>
                                        <span class="display-6 mb-0 lh-1"><?= FormatUtility::peso($reportData['outstanding']['total_outstanding']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1">Financial Summary</h4>
                                <small class="text-muted">Period: <?= htmlspecialchars($reportData['period']['from']) ?> to <?= htmlspecialchars($reportData['period']['to']) ?></small>
                            </div>
                            <a href="?<?= http_build_query(array_merge($filters, ['export' => 'pdf'])) ?>" class="btn btn-success btn-lg">
                                <i data-feather="download" class="me-1"></i>Export PDF
                            </a>
                        </div>
                        <hr/>
                        <p class="text-muted mb-0">Generated at: <?= htmlspecialchars($reportData['generated_at']) ?></p>
                    </div>
                </div>
                <?php else: ?>
                    <div class="card shadow-sm"><div class="card-body"><p class="mb-0 text-muted">No summary data available for the selected period.</p></div></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Toggle filters based on report type
document.getElementById('type').addEventListener('change', function() {
    const type = this.value;
    const dateFilters = document.getElementById('date-filters');
    const dateFiltersTo = document.getElementById('date-filters-to');
    const statusFilter = document.getElementById('status-filter');

    if (type === 'financial') {
        dateFilters.style.display = 'block';
        dateFiltersTo.style.display = 'block';
        statusFilter.style.display = 'none';
    } else if (type === 'users') {
        dateFilters.style.display = 'block';
        dateFiltersTo.style.display = 'block';
        statusFilter.style.display = 'none';
    } else {
        dateFilters.style.display = 'block';
        dateFiltersTo.style.display = 'block';
        statusFilter.style.display = 'block';
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('type').dispatchEvent(new Event('change'));
    // Date preset logic
    const form = document.getElementById('report-filter-form');
    const from = document.getElementById('date_from');
    const to = document.getElementById('date_to');
    function pad(n){return n<10? '0'+n : n}
    function fmt(d){return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())}
    function firstDayOfMonth(d){return new Date(d.getFullYear(), d.getMonth(), 1)}
    function lastDayOfMonth(d){return new Date(d.getFullYear(), d.getMonth()+1, 0)}
    function firstDayOfYear(d){return new Date(d.getFullYear(), 0, 1)}
    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const now = new Date();
            const preset = btn.getAttribute('data-preset');
            if (preset === 'today') {
                const t = fmt(now);
                from.value = t; to.value = t;
            } else if (preset === 'this-month') {
                from.value = fmt(firstDayOfMonth(now));
                to.value = fmt(lastDayOfMonth(now));
            } else if (preset === 'ytd') {
                from.value = fmt(firstDayOfYear(now));
                to.value = fmt(now);
            } else if (preset === 'last-month') {
                const prev = new Date(now.getFullYear(), now.getMonth()-1, 1);
                from.value = fmt(firstDayOfMonth(prev));
                to.value = fmt(lastDayOfMonth(prev));
            }
            form.requestSubmit();
        });
    });
});
</script>

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>
