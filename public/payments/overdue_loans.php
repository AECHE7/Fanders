<?php
/**
 * Overdue Loans Management Page
 * Shows all loans with overdue payments for follow-up
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

// Prepare filters
$filters = [];
if ($daysOverdue) {
    $filters['days_overdue'] = $daysOverdue;
}

// Get overdue loans
$overdueLoans = $reportService->generateOverdueReport($filters);

// Filter by search term if provided
if ($searchTerm && !empty($overdueLoans)) {
    $overdueLoans = array_filter($overdueLoans, function($loan) use ($searchTerm) {
        return stripos($loan['client_name'], $searchTerm) !== false || 
               stripos($loan['client_email'], $searchTerm) !== false ||
               stripos($loan['phone'], $searchTerm) !== false;
    });
}

// Calculate totals
$totalOverdue = count($overdueLoans);
$totalOverdueAmount = array_sum(array_column($overdueLoans, 'remaining_balance'));

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="overdue_loans_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Loan #', 'Client Name', 'Phone', 'Email', 'Principal', 'Total Amount', 'Total Paid', 'Balance', 'Days Overdue', 'Maturity Date']);
    
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
            $loan['maturity_date']
        ]);
    }
    
    fclose($output);
    exit;
}

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="notion-page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #fee2e2;">
                            <i data-feather="alert-triangle" style="width: 24px; height: 24px; color: #dc2626;"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="notion-page-title mb-0">Overdue Loans</h1>
                        <p class="text-muted mb-0">Monitor and manage overdue payments</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= APP_URL ?>/public/payments/overdue_loans.php?export=csv" class="btn btn-outline-secondary">
                        <i data-feather="download" class="me-1" style="width: 14px; height: 14px;"></i> Export CSV
                    </a>
                    <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-outline-secondary">
                        <i data-feather="arrow-left" class="me-1" style="width: 14px; height: 14px;"></i> Back to Loans
                    </a>
                </div>
            </div>
            <div class="notion-divider my-3"></div>
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

        <!-- Summary Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm" style="background-color: #fef3c7; border-left: 4px solid #f59e0b !important;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; background-color: #f59e0b;">
                                <i data-feather="alert-circle" style="width: 24px; height: 24px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">Total Overdue Loans</h6>
                                <h3 class="mb-0 fw-bold"><?= $totalOverdue ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm" style="background-color: #fee2e2; border-left: 4px solid #dc2626 !important;">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; background-color: #dc2626;">
                                <i data-feather="dollar-sign" style="width: 24px; height: 24px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 text-muted">Total Overdue Amount</h6>
                                <h3 class="mb-0 fw-bold">₱<?= number_format($totalOverdueAmount, 2) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search Client</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Client name, email, or phone" value="<?= htmlspecialchars($searchTerm) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="days_overdue" class="form-label">Minimum Days Overdue</label>
                        <select class="form-select" id="days_overdue" name="days_overdue">
                            <option value="">All</option>
                            <option value="1" <?= $daysOverdue == 1 ? 'selected' : '' ?>>1+ days</option>
                            <option value="7" <?= $daysOverdue == 7 ? 'selected' : '' ?>>1+ week</option>
                            <option value="14" <?= $daysOverdue == 14 ? 'selected' : '' ?>>2+ weeks</option>
                            <option value="30" <?= $daysOverdue == 30 ? 'selected' : '' ?>>1+ month</option>
                        </select>
                    </div>
                    <div class="col-md-5 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i data-feather="filter" class="me-1" style="width: 14px; height: 14px;"></i> Apply Filters
                        </button>
                        <a href="<?= APP_URL ?>/public/payments/overdue_loans.php" class="btn btn-outline-secondary">
                            <i data-feather="x" class="me-1" style="width: 14px; height: 14px;"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Overdue Loans Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3 border-bottom">
                <h5 class="mb-0">
                    <i data-feather="list" class="me-2" style="width: 18px; height: 18px;"></i>
                    Overdue Loans List (<?= $totalOverdue ?>)
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($overdueLoans)): ?>
                    <div class="text-center p-5">
                        <div class="mb-3">
                            <i data-feather="check-circle" style="width: 48px; height: 48px; color: #16a34a;"></i>
                        </div>
                        <h5 class="text-muted">No Overdue Loans</h5>
                        <p class="text-muted mb-0">All payments are up to date!</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Loan #</th>
                                    <th>Client</th>
                                    <th>Contact</th>
                                    <th class="text-end">Principal</th>
                                    <th class="text-end">Total Amount</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Balance</th>
                                    <th class="text-center">Days Overdue</th>
                                    <th>Maturity Date</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overdueLoans as $loan): ?>
                                <tr>
                                    <td class="ps-4">
                                        <strong>#<?= $loan['loan_number'] ?></strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($loan['client_name']) ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
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
                                        <?php 
                                        $daysClass = 'danger';
                                        if ($loan['days_overdue'] < 7) $daysClass = 'warning';
                                        ?>
                                        <span class="badge bg-<?= $daysClass ?>">
                                            <?= $loan['days_overdue'] ?> day<?= $loan['days_overdue'] > 1 ? 's' : '' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?= date('M d, Y', strtotime($loan['maturity_date'])) ?></small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $loan['id'] ?>" 
                                               class="btn btn-outline-primary" title="View Loan">
                                                <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                            </a>
                                            <a href="<?= APP_URL ?>/public/payments/add.php?loan_id=<?= $loan['id'] ?>" 
                                               class="btn btn-outline-success" title="Record Payment">
                                                <i data-feather="dollar-sign" style="width: 14px; height: 14px;"></i>
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

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';
?> 