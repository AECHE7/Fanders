<?php
/**
 * Enhanced Overdue Payments Management Interface
 * Professional dashboard for tracking and managing overdue payments with advanced analytics
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account-officer', 'cashier']);

// Initialize services
$reportService = new ReportService();
$loanService = new LoanService();
$paymentService = new PaymentService();
$overdueService = new OverduePaymentService();

// Enhanced filter handling using FilterUtility
require_once '../../app/utilities/FilterUtility.php';

$filterOptions = [
    'allowed_severities' => ['low', 'medium', 'high', 'critical']
];
$filters = FilterUtility::sanitizeFilters($_GET, $filterOptions);

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

// Get enhanced overdue analysis
$allOverdueLoans = $overdueService->getOverdueAnalysis($filters);
$totalOverdueLoans = count($allOverdueLoans);

// Apply search filter if provided
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($searchTerm && !empty($allOverdueLoans)) {
    $allOverdueLoans = array_filter($allOverdueLoans, function($loan) use ($searchTerm) {
        return stripos($loan['client_name'], $searchTerm) !== false || 
               stripos($loan['client_email'], $searchTerm) !== false ||
               stripos($loan['phone'], $searchTerm) !== false ||
               stripos($loan['loan_number'], $searchTerm) !== false;
    });
}

// Pagination
$totalFilteredLoans = count($allOverdueLoans);
$overdueLoans = array_slice($allOverdueLoans, ($page - 1) * $limit, $limit);
$totalPages = ceil($totalFilteredLoans / $limit);

// Initialize pagination utility
require_once '../../app/utilities/PaginationUtility.php';
$pagination = new PaginationUtility($totalFilteredLoans, $page, $limit, 'page');

// Get comprehensive statistics
$overdueStats = $overdueService->getOverdueStatistics($allOverdueLoans);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$csrf->validateRequest()) {
        $session->setFlash('error', 'Invalid security token.');
    } else {
        switch ($_POST['action']) {
            case 'add_note':
                // Future: Add collection note functionality
                $session->setFlash('info', 'Collection note functionality coming soon.');
                break;
            case 'send_reminder':
                // Future: Send payment reminder functionality
                $session->setFlash('info', 'Payment reminder functionality coming soon.');
                break;
        }
    }
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="overdue_payments_enhanced_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    $csvData = $overdueService->exportOverdueLoansCSV($allOverdueLoans);
    
    foreach ($csvData as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Include header and navbar
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
                        <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #fff2f2;">
                            <i data-feather="alert-triangle" style="width: 24px; height: 24px; color: #dc3545;"></i>
                        </div>
                    </div>
                    <h1 class="notion-page-title mb-0">Overdue Payments Management</h1>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <div class="text-muted d-none d-md-block me-3">
                        <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i>
                        <?= date('l, F j, Y') ?>
                    </div>
                    <a href="<?= APP_URL ?>/public/reports/overdue.php" class="btn btn-sm btn-outline-secondary px-3">
                        <i data-feather="file-text" class="me-1" style="width: 14px; height: 14px;"></i> Overdue Report
                    </a>
                    <a href="<?= APP_URL ?>/public/payments/overdue_payments.php?export=csv" class="btn btn-sm btn-outline-success">
                        <i data-feather="download" class="me-1" style="width: 14px; height: 14px;"></i> Export CSV
                    </a>
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

        <!-- Quick Actions -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-3">
                <a href="<?= APP_URL ?>/public/payments/overdue_payments.php?severity=critical" class="btn btn-danger w-100">
                    <i data-feather="alert-circle" class="me-2"></i>Critical Cases
                </a>
            </div>
            <div class="col-12 col-md-3">
                <a href="<?= APP_URL ?>/public/payments/overdue_payments.php?severity=high" class="btn btn-warning w-100">
                    <i data-feather="alert-triangle" class="me-2"></i>High Priority
                </a>
            </div>
            <div class="col-12 col-md-3">
                <a href="<?= APP_URL ?>/public/payments/overdue_payments.php?days_overdue=30" class="btn btn-outline-secondary w-100">
                    <i data-feather="clock" class="me-2"></i>30+ Days
                </a>
            </div>
            <div class="col-12 col-md-3">
                <a href="<?= APP_URL ?>/public/collection-sheets/index.php" class="btn btn-outline-primary w-100">
                    <i data-feather="clipboard" class="me-2"></i>Collection Sheets
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
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
                                <h6 class="card-title text-uppercase small">Critical Cases</h6>
                                <h3 class="mb-0"><?= $overdueStats['severity_stats']['critical'] ?></h3>
                            </div>
                            <i data-feather="alert-circle" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Severity Breakdown -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <i data-feather="pie-chart" class="me-2" style="width:18px;height:18px;"></i>
                            <strong>Overdue Severity Analysis</strong>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded bg-light">
                                    <div class="badge bg-secondary mb-2" style="font-size: 0.9rem; padding: 8px 12px;">Monitor</div>
                                    <h4 class="mb-1"><?= $overdueStats['severity_stats']['low'] ?></h4>
                                    <small class="text-muted">Recently overdue</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded bg-light">
                                    <div class="badge bg-info mb-2" style="font-size: 0.9rem; padding: 8px 12px;">Follow Up</div>
                                    <h4 class="mb-1"><?= $overdueStats['severity_stats']['medium'] ?></h4>
                                    <small class="text-muted">Moderate priority</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded bg-light">
                                    <div class="badge bg-warning mb-2" style="font-size: 0.9rem; padding: 8px 12px;">Contact Client</div>
                                    <h4 class="mb-1"><?= $overdueStats['severity_stats']['high'] ?></h4>
                                    <small class="text-muted">High priority</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded bg-light">
                                    <div class="badge bg-danger mb-2" style="font-size: 0.9rem; padding: 8px 12px;">Immediate Action</div>
                                    <h4 class="mb-1"><?= $overdueStats['severity_stats']['critical'] ?></h4>
                                    <small class="text-muted">Critical cases</small>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i data-feather="info" class="me-1" style="width: 14px; height: 14px;"></i>
                                Average Days Overdue: <?= $overdueStats['average_days_overdue'] ?> days | 
                                Total Outstanding: ₱<?= number_format($overdueStats['total_remaining_balance'], 2) ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <i data-feather="filter" class="me-2" style="width:18px;height:18px;"></i>
                    <strong>Filters & Search</strong>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i data-feather="search" style="width: 16px; height: 16px;"></i></span>
                            <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($searchTerm) ?>" 
                                   placeholder="Search client, phone, email, loan #...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="severity">
                            <option value="">All Severities</option>
                            <option value="low" <?= ($filters['severity'] ?? '') === 'low' ? 'selected' : '' ?>>Monitor</option>
                            <option value="medium" <?= ($filters['severity'] ?? '') === 'medium' ? 'selected' : '' ?>>Follow Up</option>
                            <option value="high" <?= ($filters['severity'] ?? '') === 'high' ? 'selected' : '' ?>>Contact Client</option>
                            <option value="critical" <?= ($filters['severity'] ?? '') === 'critical' ? 'selected' : '' ?>>Immediate Action</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="days_overdue">
                            <option value="">All Days</option>
                            <option value="7" <?= ($filters['days_overdue'] ?? '') == 7 ? 'selected' : '' ?>>7+ days</option>
                            <option value="14" <?= ($filters['days_overdue'] ?? '') == 14 ? 'selected' : '' ?>>14+ days</option>
                            <option value="30" <?= ($filters['days_overdue'] ?? '') == 30 ? 'selected' : '' ?>>30+ days</option>
                            <option value="60" <?= ($filters['days_overdue'] ?? '') == 60 ? 'selected' : '' ?>>60+ days</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="limit">
                            <option value="20" <?= $limit == 20 ? 'selected' : '' ?>>20 per page</option>
                            <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50 per page</option>
                            <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100 per page</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="search" style="width: 16px; height: 16px;"></i> Filter
                            </button>
                            <a href="<?= APP_URL ?>/public/payments/overdue_payments.php" class="btn btn-outline-secondary">
                                <i data-feather="x" style="width: 16px; height: 16px;"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
                <?php 
                $filterSummary = FilterUtility::getFilterSummary($filters);
                if (!empty($filterSummary)): ?>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i data-feather="info" class="me-1" style="width: 14px; height: 14px;"></i>
                            Active filters: <?= implode(', ', $filterSummary) ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Overdue Loans List -->
        <div class="card shadow-sm">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i data-feather="list" class="me-2" style="width:18px;height:18px;"></i>
                        Overdue Loans List
                    </h5>
                    <div class="d-flex align-items-center gap-3">
                        <small class="text-muted">
                            Showing <?= count($overdueLoans) ?> of <?= number_format($totalFilteredLoans) ?> 
                            (<?= number_format($totalOverdueLoans) ?> total)
                        </small>
                        <small class="text-muted">Sorted by severity and days overdue</small>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($overdueLoans)): ?>
                    <div class="text-center p-5">
                        <div class="mb-3">
                            <i data-feather="check-circle" style="width: 48px; height: 48px; color: #16a34a;"></i>
                        </div>
                        <h5 class="text-muted">No Overdue Payments</h5>
                        <p class="text-muted mb-0">All payments are up to date!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%;">Loan #</th>
                                    <th style="width: 20%;">Client & Contact</th>
                                    <th style="width: 12%;" class="text-end">Principal</th>
                                    <th style="width: 12%;" class="text-end">Total Amount</th>
                                    <th style="width: 12%;" class="text-end">Paid</th>
                                    <th style="width: 12%;" class="text-end">Balance</th>
                                    <th style="width: 8%;" class="text-center">Progress</th>
                                    <th style="width: 8%;" class="text-center">Status</th>
                                    <th style="width: 8%;" class="text-center">Expected Weekly</th>
                                    <th style="width: 15%;" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overdueLoans as $loan): ?>
                                <?php
                                    // Determine row styling based on severity
                                    $rowClass = '';
                                    $borderClass = 'border-' . $loan['severity_class'];
                                    if ($loan['severity'] === 'critical') {
                                        $rowClass = 'table-danger';
                                    } elseif ($loan['severity'] === 'high') {
                                        $rowClass = 'table-warning';
                                    }
                                ?>
                                <tr class="<?= $rowClass ?> border-start <?= $borderClass ?> border-3">
                                    <td>
                                        <div>
                                            <strong>#<?= $loan['loan_number'] ?></strong>
                                            <div>
                                                <span class="badge bg-<?= $loan['severity_class'] ?> badge-sm">
                                                    <?= ucfirst($loan['severity']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <a href="<?= APP_URL ?>/public/clients/view.php?id=<?= $loan['client_id'] ?>" 
                                               class="text-decoration-none fw-medium">
                                                <?= htmlspecialchars($loan['client_name']) ?>
                                            </a>
                                        </div>
                                        <div class="small text-muted">
                                            <?php if ($loan['phone']): ?>
                                                <div><i data-feather="phone" style="width: 12px; height: 12px;"></i> <?= htmlspecialchars($loan['phone']) ?></div>
                                            <?php endif; ?>
                                            <?php if ($loan['client_email']): ?>
                                                <div><i data-feather="mail" style="width: 12px; height: 12px;"></i> <?= htmlspecialchars($loan['client_email']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-end">₱<?= number_format($loan['principal_amount'], 2) ?></td>
                                    <td class="text-end">₱<?= number_format($loan['total_amount'], 2) ?></td>
                                    <td class="text-end">₱<?= number_format($loan['total_paid'], 2) ?></td>
                                    <td class="text-end">
                                        <strong class="text-danger">₱<?= number_format($loan['remaining_balance'], 2) ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <div class="progress mb-1" style="height: 8px;">
                                            <div class="progress-bar bg-success" style="width: <?= $loan['percentage_paid'] ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= $loan['percentage_paid'] ?>%</small>
                                    </td>
                                    <td class="text-center">
                                        <div>
                                            <span class="badge bg-<?= $loan['severity_class'] ?> mb-1">
                                                <?= $loan['days_overdue'] ?> days
                                            </span>
                                        </div>
                                        <small class="text-muted"><?= $loan['weeks_behind'] ?> weeks behind</small>
                                        <?php if ($loan['days_since_last_payment']): ?>
                                            <div><small class="text-muted">Last payment: <?= $loan['days_since_last_payment'] ?> days ago</small></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <strong>₱<?= number_format($loan['expected_weekly_payment'], 2) ?></strong>
                                        <div><small class="text-muted">weekly</small></div>
                                        <?php if ($loan['payment_shortfall'] > 0): ?>
                                            <div><small class="text-danger">₱<?= number_format($loan['payment_shortfall'], 2) ?> behind</small></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $loan['id'] ?>" 
                                               class="btn btn-outline-primary" title="View Loan Details">
                                                <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                            </a>
                                            <a href="<?= APP_URL ?>/public/payments/add.php?loan_id=<?= $loan['id'] ?>" 
                                               class="btn btn-outline-success" title="Record Payment">
                                                <i data-feather="plus-circle" style="width: 14px; height: 14px;"></i>
                                            </a>
                                            <a href="<?= APP_URL ?>/public/clients/view.php?id=<?= $loan['client_id'] ?>" 
                                               class="btn btn-outline-info" title="Client Details">
                                                <i data-feather="user" style="width: 14px; height: 14px;"></i>
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
            <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">
                                Showing <?= (($page - 1) * $limit) + 1 ?> to <?= min($page * $limit, $totalFilteredLoans) ?> of <?= number_format($totalFilteredLoans) ?> entries
                            </small>
                        </div>
                        <div>
                            <?php 
                            // Build pagination query string preserving filters
                            $queryParams = $_GET;
                            unset($queryParams['page']);
                            $queryString = http_build_query($queryParams);
                            $queryString = $queryString ? '&' . $queryString : '';
                            ?>
                            <nav aria-label="Overdue loans pagination">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page - 1 ?><?= $queryString ?>">
                                                <i data-feather="chevron-left" style="width: 14px; height: 14px;"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?><?= $queryString ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $page + 1 ?><?= $queryString ?>">
                                                <i data-feather="chevron-right" style="width: 14px; height: 14px;"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
/* Enhanced styling for overdue payments module */
.border-start {
    border-left-width: 4px !important;
}

.progress {
    background-color: #f0f0f0;
}

.badge-sm {
    font-size: 0.65rem;
    padding: 0.25rem 0.5rem;
}

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

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.075) !important;
}

.btn-group .btn {
    border-radius: 0.375rem;
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.progress-bar {
    transition: width 0.6s ease;
}

.alert-dismissible .btn-close {
    padding: 0.75rem 1rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-bottom: 2px;
        margin-right: 0;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}

/* Animation for severity badges */
.badge {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.8); }
    to { opacity: 1; transform: scale(1); }
}

/* Enhanced card shadows */
.card.shadow-sm {
    box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.1) !important;
    transition: box-shadow 0.15s ease-in-out;
}

.card.shadow-sm:hover {
    box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.15) !important;
}
</style>

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>