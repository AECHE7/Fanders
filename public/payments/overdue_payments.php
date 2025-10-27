<?php
/**
 * Enhanced Overdue Payments Management Interface
 * Advanced dashboard for tracking and managing overdue payments
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account-officer', 'cashier']);

// Initialize services
$reportService = new ReportService();
$loanService = new LoanService();
$paymentService = new PaymentService();

// Get filter parameters
$daysOverdue = isset($_GET['days_overdue']) ? (int)$_GET['days_overdue'] : null;
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$severityFilter = isset($_GET['severity']) ? $_GET['severity'] : '';

// Prepare filters
$filters = [];
if ($daysOverdue) {
    $filters['days_overdue'] = $daysOverdue;
}

// Get overdue loans
$overdueLoans = $reportService->generateOverdueReport($filters);

// Enhance overdue loans with additional data
foreach ($overdueLoans as &$loan) {
    // Calculate severity based on days overdue
    if ($loan['days_overdue'] <= 7) {
        $loan['severity'] = 'low';
        $loan['severity_label'] = 'Recently Overdue';
        $loan['severity_class'] = 'warning';
    } elseif ($loan['days_overdue'] <= 30) {
        $loan['severity'] = 'medium';
        $loan['severity_label'] = 'Moderately Overdue';
        $loan['severity_class'] = 'danger';
    } else {
        $loan['severity'] = 'high';
        $loan['severity_label'] = 'Critically Overdue';
        $loan['severity_class'] = 'dark';
    }
    
    // Calculate percentage paid
    $loan['percentage_paid'] = $loan['total_amount'] > 0 ? 
        round(($loan['total_paid'] / $loan['total_amount']) * 100, 1) : 0;
    
    // Expected weekly payment (assuming standard calculation)
    $loan['expected_weekly'] = $loan['total_amount'] / 17; // Default 17 weeks
    
    // Estimated weeks behind
    $loan['weeks_behind'] = round($loan['days_overdue'] / 7, 1);
}

// Apply additional filters
if ($searchTerm && !empty($overdueLoans)) {
    $overdueLoans = array_filter($overdueLoans, function($loan) use ($searchTerm) {
        return stripos($loan['client_name'], $searchTerm) !== false || 
               stripos($loan['client_email'], $searchTerm) !== false ||
               stripos($loan['phone'], $searchTerm) !== false ||
               stripos($loan['loan_number'], $searchTerm) !== false;
    });
}

if ($severityFilter && !empty($overdueLoans)) {
    $overdueLoans = array_filter($overdueLoans, function($loan) use ($severityFilter) {
        return $loan['severity'] === $severityFilter;
    });
}

// Calculate analytics
$totalOverdue = count($overdueLoans);
$totalOverdueAmount = array_sum(array_column($overdueLoans, 'remaining_balance'));
$averageDaysOverdue = $totalOverdue > 0 ? round(array_sum(array_column($overdueLoans, 'days_overdue')) / $totalOverdue, 1) : 0;

// Severity breakdown
$severityStats = [
    'low' => count(array_filter($overdueLoans, fn($loan) => $loan['severity'] === 'low')),
    'medium' => count(array_filter($overdueLoans, fn($loan) => $loan['severity'] === 'medium')),
    'high' => count(array_filter($overdueLoans, fn($loan) => $loan['severity'] === 'high'))
];

// Sort by severity and days overdue
usort($overdueLoans, function($a, $b) {
    $severityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
    $aSeverity = $severityOrder[$a['severity']];
    $bSeverity = $severityOrder[$b['severity']];
    
    if ($aSeverity === $bSeverity) {
        return $b['days_overdue'] - $a['days_overdue']; // Higher days first
    }
    return $bSeverity - $aSeverity; // Higher severity first
});

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
    header('Content-Disposition: attachment; filename="overdue_payments_detailed_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Loan #', 'Client Name', 'Phone', 'Email', 'Principal', 'Total Amount', 
        'Total Paid', 'Balance', 'Days Overdue', 'Weeks Behind', 'Severity', 
        'Percentage Paid', 'Expected Weekly', 'Maturity Date'
    ]);
    
    foreach ($overdueLoans as $loan) {
        fputcsv($output, [
            $loan['loan_number'],
            $loan['client_name'],
            $loan['phone'],
            $loan['client_email'],
            number_format($loan['principal_amount'], 2),
            number_format($loan['total_amount'], 2),
            number_format($loan['total_paid'], 2),
            number_format($loan['remaining_balance'], 2),
            $loan['days_overdue'],
            $loan['weeks_behind'],
            $loan['severity_label'],
            $loan['percentage_paid'] . '%',
            number_format($loan['expected_weekly'], 2),
            $loan['maturity_date']
        ]);
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
        <!-- Page Header -->
        <div class="notion-page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 48px; height: 48px; background: linear-gradient(135deg, #ff6b6b, #ee5a24);">
                            <i data-feather="alert-triangle" style="width: 24px; height: 24px; color: white;"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="mb-1">Overdue Payments Dashboard</h1>
                        <p class="text-muted mb-0">Track and manage overdue loan payments</p>
                    </div>
                </div>
                <div class="btn-group">
                    <a href="<?= APP_URL ?>/public/payments/overdue_payments.php?export=csv" class="btn btn-outline-success">
                        <i data-feather="download" style="width: 16px; height: 16px;"></i> Export CSV
                    </a>
                    <a href="<?= APP_URL ?>/public/payments/index.php" class="btn btn-outline-secondary">
                        <i data-feather="arrow-left" style="width: 16px; height: 16px;"></i> Back to Payments
                    </a>
                </div>
            </div>
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

        <!-- Analytics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" 
                                 style="width: 48px; height: 48px; background-color: #ff6b6b;">
                                <i data-feather="alert-triangle" style="width: 24px; height: 24px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">Total Overdue</h6>
                                <h3 class="mb-0 fw-bold text-danger"><?= $totalOverdue ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" 
                                 style="width: 48px; height: 48px; background-color: #ee5a24;">
                                <i data-feather="dollar-sign" style="width: 24px; height: 24px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">Overdue Amount</h6>
                                <h3 class="mb-0 fw-bold text-danger">₱<?= number_format($totalOverdueAmount, 2) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" 
                                 style="width: 48px; height: 48px; background-color: #f39c12;">
                                <i data-feather="clock" style="width: 24px; height: 24px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">Avg Days Overdue</h6>
                                <h3 class="mb-0 fw-bold text-warning"><?= $averageDaysOverdue ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" 
                                 style="width: 48px; height: 48px; background-color: #2d3748;">
                                <i data-feather="trending-down" style="width: 24px; height: 24px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">Critical Cases</h6>
                                <h3 class="mb-0 fw-bold text-dark"><?= $severityStats['high'] ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Severity Breakdown -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0">Overdue Severity Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center p-3 border rounded">
                                    <div class="badge bg-warning mb-2" style="font-size: 0.9rem; padding: 8px 12px;">Recently Overdue</div>
                                    <h4 class="mb-1"><?= $severityStats['low'] ?></h4>
                                    <small class="text-muted">1-7 days overdue</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3 border rounded">
                                    <div class="badge bg-danger mb-2" style="font-size: 0.9rem; padding: 8px 12px;">Moderately Overdue</div>
                                    <h4 class="mb-1"><?= $severityStats['medium'] ?></h4>
                                    <small class="text-muted">8-30 days overdue</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3 border rounded">
                                    <div class="badge bg-dark mb-2" style="font-size: 0.9rem; padding: 8px 12px;">Critically Overdue</div>
                                    <h4 class="mb-1"><?= $severityStats['high'] ?></h4>
                                    <small class="text-muted">30+ days overdue</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">Filters & Search</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="search" value="<?= htmlspecialchars($searchTerm) ?>" 
                               placeholder="Search client, phone, email...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="severity">
                            <option value="">All Severities</option>
                            <option value="low" <?= $severityFilter === 'low' ? 'selected' : '' ?>>Recently Overdue</option>
                            <option value="medium" <?= $severityFilter === 'medium' ? 'selected' : '' ?>>Moderately Overdue</option>
                            <option value="high" <?= $severityFilter === 'high' ? 'selected' : '' ?>>Critically Overdue</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="days_overdue">
                            <option value="">All Days</option>
                            <option value="7" <?= $daysOverdue === 7 ? 'selected' : '' ?>>7+ days</option>
                            <option value="14" <?= $daysOverdue === 14 ? 'selected' : '' ?>>14+ days</option>
                            <option value="30" <?= $daysOverdue === 30 ? 'selected' : '' ?>>30+ days</option>
                            <option value="60" <?= $daysOverdue === 60 ? 'selected' : '' ?>>60+ days</option>
                        </select>
                    </div>
                    <div class="col-md-3">
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
            </div>
        </div>

        <!-- Overdue Loans Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Overdue Loans (<?= count($overdueLoans) ?>)</h5>
                    <small class="text-muted">Sorted by severity and days overdue</small>
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
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th class="ps-4">Loan #</th>
                                    <th>Client & Contact</th>
                                    <th class="text-end">Principal</th>
                                    <th class="text-end">Total Amount</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Balance</th>
                                    <th class="text-center">Progress</th>
                                    <th class="text-center">Days Overdue</th>
                                    <th class="text-center">Weekly Payment</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overdueLoans as $loan): ?>
                                <tr class="border-start border-<?= $loan['severity_class'] ?> border-3">
                                    <td class="ps-4">
                                        <div>
                                            <strong>#<?= $loan['loan_number'] ?></strong>
                                            <div>
                                                <span class="badge bg-<?= $loan['severity_class'] ?> small">
                                                    <?= $loan['severity_label'] ?>
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($loan['client_name']) ?></strong>
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
                                        <small class="text-muted"><?= $loan['percentage_paid'] ?>% paid</small>
                                    </td>
                                    <td class="text-center">
                                        <div>
                                            <span class="badge bg-<?= $loan['severity_class'] ?> fs-6">
                                                <?= $loan['days_overdue'] ?> days
                                            </span>
                                        </div>
                                        <small class="text-muted"><?= $loan['weeks_behind'] ?> weeks behind</small>
                                    </td>
                                    <td class="text-center">
                                        <strong>₱<?= number_format($loan['expected_weekly'], 2) ?></strong>
                                        <div><small class="text-muted">expected weekly</small></div>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group-vertical btn-group-sm">
                                            <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $loan['id'] ?>" 
                                               class="btn btn-outline-primary btn-sm" title="View Loan Details">
                                                <i data-feather="eye" style="width: 12px; height: 12px;"></i>
                                            </a>
                                            <a href="<?= APP_URL ?>/public/payments/add.php?loan_id=<?= $loan['id'] ?>" 
                                               class="btn btn-outline-success btn-sm" title="Record Payment">
                                                <i data-feather="plus-circle" style="width: 12px; height: 12px;"></i>
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
    </div>
</main>

<style>
.border-start {
    border-left-width: 4px !important;
}

.progress {
    background-color: #f0f0f0;
}

.btn-group-vertical .btn {
    margin-bottom: 2px;
}

.card-header {
    border-bottom: 1px solid #dee2e6;
}

.notion-page-header h1 {
    font-size: 1.75rem;
    font-weight: 600;
}

.table-dark th {
    background-color: #2d3748;
    border-color: #4a5568;
    color: white;
}

.alert-dismissible .btn-close {
    padding: 0.75rem 1rem;
}
</style>

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>