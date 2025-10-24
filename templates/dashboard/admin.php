<?php
/**
 * Admin/Super Admin dashboard template for the Fanders Microfinance Loan Management System
 * Notion-inspired design
 */
?>

<!-- Dashboard Header with Title, Date and Reports Links -->
<div class="notion-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <div class="me-3">
                <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0f4fd;">
                    <i data-feather="grid" style="width: 24px; height: 24px; color:rgb(0, 0, 0);"></i>
                </div>
            </div>
            <h1 class="notion-page-title mb-0">Dashboard</h1>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <div class="text-muted d-none d-md-block me-3">
                <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i> 
                <?= date('l, F j, Y') ?>
            </div>
            <a href="<?= APP_URL ?>/public/reports/loans.php" class="btn btn-sm btn-outline-secondary px-3">
                <i data-feather="file-text" class="me-1" style="width: 14px; height: 14px;"></i> Loans
            </a>
            <a href="<?= APP_URL ?>/public/reports/clients.php" class="btn btn-sm btn-outline-secondary px-3">
                <i data-feather="users" class="me-1" style="width: 14px; height: 14px;"></i> Clients
            </a>
            <a href="<?= APP_URL ?>/public/reports/payments.php" class="btn btn-sm btn-outline-secondary px-3">
                <i data-feather="dollar-sign" class="me-1" style="width: 14px; height: 14px;"></i> Payments
            </a>
            <a href="<?= APP_URL ?>/public/admin/backup.php" class="btn btn-sm btn-outline-success px-3">
                <i data-feather="database" class="me-1" style="width: 14px; height: 14px;"></i> Backup
            </a>
        </div>
    </div>
    <div class="notion-divider my-3"></div>
</div>

<!-- Stats Overview with Color-coded Icons like Notion -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <i data-feather="trending-up" class="me-2" style="width: 20px; height: 20px; color: #0d6efd;"></i>
        <h5 class="mb-0">Microfinance Statistics</h5>
        <div class="notion-divider flex-grow-1 ms-2"></div>
    </div>
    <div class="row g-4 dashboard-stats-container">
        <div class="col-md-3">
            <div class="card card-contrast shadow-sm metric-card metric-accent-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Total Loans</h6>
                            <h3 class="mb-0"><?= $stats['total_loans'] ?? 0 ?></h3>
                        </div>
                        <i data-feather="file-text" class="icon-lg" style="width:3rem;height:3rem;color:#0d6efd;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-contrast shadow-sm metric-card metric-accent-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Active Loans</h6>
                            <h3 class="mb-0"><?= $stats['active_loans'] ?? 0 ?></h3>
                        </div>
                        <i data-feather="activity" class="icon-lg" style="width:3rem;height:3rem;color:#0dcaf0;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-contrast shadow-sm metric-card metric-accent-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Overdue Payments</h6>
                            <h3 class="mb-0"><?= $stats['overdue_returns'] ?? 0 ?></h3>
                        </div>
                        <i data-feather="alert-circle" class="icon-lg" style="width:3rem;height:3rem;color:#ffc107;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-contrast shadow-sm metric-card metric-accent-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Total Portfolio</h6>
                            <h3 class="mb-0">₱<?= number_format($stats['total_disbursed'] ?? 0, 2) ?></h3>
                        </div>
                        <i data-feather="dollar-sign" class="icon-lg" style="width:3rem;height:3rem;color:#dc3545;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Client & Approvals Overview -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <i data-feather="users" class="me-2" style="width: 20px; height: 20px; color: #198754;"></i>
        <h5 class="mb-0">Client & Approvals Overview</h5>
        <div class="notion-divider flex-grow-1 ms-2"></div>
    </div>
    <div class="row g-4">
        <div class="col-md-3">
            <div class="card card-contrast shadow-sm metric-card metric-accent-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Total Clients</h6>
                            <h3 class="mb-0"><?= $stats['total_clients'] ?? 0 ?></h3>
                        </div>
                        <i data-feather="users" class="icon-lg" style="width:3rem;height:3rem;color:#198754;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-contrast shadow-sm metric-card metric-accent-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Total Staff</h6>
                            <h3 class="mb-0"><?= $stats['total_staff'] ?? 0 ?></h3>
                        </div>
                        <i data-feather="user-check" class="icon-lg" style="width:3rem;height:3rem;color:#0d6efd;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-contrast shadow-sm metric-card metric-accent-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Pending Approvals</h6>
                            <?php
                            // Get pending approvals count
                            $pendingCount = 0;
                            try {
                                $pendingLoans = $loanService->getAllLoansWithClients(['status' => 'application'], 1, 1000);
                                $pendingCount = is_array($pendingLoans) ? count($pendingLoans) : 0;
                            } catch (Exception $e) {
                                error_log('Pending approvals count error: ' . $e->getMessage());
                            }
                            ?>
                            <h3 class="mb-0"><?= $pendingCount ?></h3>
                        </div>
                        <i data-feather="clock" class="icon-lg" style="width:3rem;height:3rem;color:#ffc107;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-contrast shadow-sm metric-card metric-accent-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Ready to Disburse</h6>
                            <?php
                            // Get approved (ready to disburse) count
                            $approvedCount = 0;
                            try {
                                $approvedLoans = $loanService->getAllLoansWithClients(['status' => 'approved'], 1, 1000);
                                $approvedCount = is_array($approvedLoans) ? count($approvedLoans) : 0;
                            } catch (Exception $e) {
                                error_log('Approved loans count error: ' . $e->getMessage());
                            }
                            ?>
                            <h3 class="mb-0"><?= $approvedCount ?></h3>
                        </div>
                        <i data-feather="check-circle" class="icon-lg" style="width:3rem;height:3rem;color:#0dcaf0;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Overdue Loans Alert Section (New!) -->
<?php if (($stats['overdue_returns'] ?? 0) > 0): ?>
<div class="mb-5">
    <div class="alert alert-danger border-0 shadow-sm" style="background-color: #fee2e2; border-left: 4px solid #dc2626 !important;">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; background-color: #dc2626;">
                    <i data-feather="alert-triangle" style="width: 24px; height: 24px; color: white;"></i>
                </div>
                <div>
                    <h5 class="mb-1 text-danger fw-bold">
                        <i data-feather="alert-triangle" style="width: 18px; height: 18px;" class="me-1"></i>
                        <?= $stats['overdue_returns'] ?? 0 ?> Overdue Payment<?= ($stats['overdue_returns'] ?? 0) > 1 ? 's' : '' ?> Require Attention
                    </h5>
                    <p class="mb-0 text-dark small">These loans have passed their due date and need immediate follow-up.</p>
                </div>
            </div>
            <a href="<?= APP_URL ?>/public/payments/overdue_loans.php" class="btn btn-danger">
                <i data-feather="eye" style="width: 14px; height: 14px;" class="me-1"></i> View Details
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent Activity -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <i data-feather="activity" class="me-2" style="width: 20px; height: 20px; color: #6c757d;"></i>
        <h5 class="mb-0">Recent Activity</h5>
        <div class="notion-divider flex-grow-1 ms-2"></div>
    </div>
    <div class="row g-4">
        <!-- Recent Payments -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent py-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <div class="rounded d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; background-color: #0b76ef;">
                            <i data-feather="dollar-sign" style="width: 14px; height: 14px; color: white;"></i>
                        </div>
                        <h5 class="card-title mb-0">Recent Payments</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php 
                    $recentPayments = $stats['recent_transactions'] ?? [];
                    if (!empty($recentPayments)): 
                    ?>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Client</th>
                                    <th>Amount</th>
                                    <th>Week</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentPayments as $payment): ?>
                                <tr>
                                    <td class="ps-4"><?= htmlspecialchars($payment['client_name']) ?></td>
                                    <td>₱<?= number_format($payment['amount'], 2) ?></td>
                                    <td>
                                        <?php if (isset($payment['week_number'])): ?>
                                            Week <?= $payment['week_number'] ?>/17
                                        <?php else: ?>
                                            Payment #<?= $payment['id'] ?? 'N/A' ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center p-4 text-muted">
                        <i data-feather="dollar-sign" style="width: 24px; height: 24px;" class="mb-2"></i>
                        <p>No recent payments.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Recently Added Loans -->
        <!-- Active Loans -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent py-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <div class="rounded d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; background-color: #9d71ea;">
                            <i data-feather="activity" style="width: 14px; height: 14px; color: white;"></i>
                        </div>
                        <h5 class="card-title mb-0">Active Loans</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php 
                    $activeLoans = $stats['active_loans_list'] ?? [];
                    if (!empty($activeLoans)): 
                    ?>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Client</th>
                                    <th>Loan Amount</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($activeLoans, 0, 5) as $loan): ?>
                                <tr>
                                    <td class="ps-4"><?= htmlspecialchars($loan['client_name']) ?></td>
                                    <td>₱<?= number_format($loan['total_loan_amount'] ?? 0, 2) ?></td>
                                    <td>
                                        <span class="badge bg-success">Active</span>
                                    </td>
                                    <td>
                                        <?php
                                        $paymentSummary = $paymentService->getPaymentSummaryByLoan($loan['id']);
                                        $paymentsMade = $paymentSummary['payment_count'] ?? 0;
                                        $totalWeeks = 17; // Assuming 17 weeks term
                                        $progress = min(100, round(($paymentsMade / $totalWeeks) * 100));
                                        ?>
                                        <div class="d-flex align-items-center">
                                            <div class="progress me-2" style="width: 60px; height: 6px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $progress ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?= $paymentsMade ?>/<?= $totalWeeks ?> weeks</small>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center p-4 text-muted">
                        <i data-feather="activity" style="width: 24px; height: 24px;" class="mb-2"></i>
                        <p>No active loans.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Action Section with Notion-style colored blocks -->
<div class="mb-5 animate-on-scroll">
    <div class="d-flex align-items-center mb-3">
        <i data-feather="zap" class="me-2" style="width: 20px; height: 20px; color: #ffc107;"></i>
        <h5 class="mb-0">Quick Actions</h5>
        <div class="notion-divider flex-grow-1 ms-2"></div>
    </div>
    <div class="row g-3 stagger-fade-in">
        <div class="col-md-3">
            <a href="<?= APP_URL ?>/public/loans/add.php" class="text-decoration-none">
                <div class="card border-0 h-100" style="background-color: #f7ecff;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #9d71ea;">
                                <i data-feather="plus" style="width: 20px; height: 20px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">New Loan Application</h6>
                                <p class="card-text small mb-0 text-muted">Create loan request</p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?= APP_URL ?>/public/clients/add.php" class="text-decoration-none">
                <div class="card border-0 h-100" style="background-color: #eaf8f6;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #0ca789;">
                                <i data-feather="user-plus" style="width: 20px; height: 20px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Add New Client</h6>
                                <p class="card-text small mb-0 text-muted">Register borrower</p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?= APP_URL ?>/public/loans/index.php" class="text-decoration-none">
                <div class="card border-0 h-100" style="background-color: #edf2fc;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #0b76ef;">
                                <i data-feather="file-text" style="width: 20px; height: 20px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Manage Loans</h6>
                                <p class="card-text small mb-0 text-muted">View all loans</p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?= APP_URL ?>/public/payments/add.php" class="text-decoration-none">
                <div class="card border-0 h-100" style="background-color: #fff3e9;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #ec7211;">
                                <i data-feather="dollar-sign" style="width: 20px; height: 20px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Record Payment</h6>
                                <p class="card-text small mb-0 text-muted">Process payments</p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <!-- Phase 2 Features -->
        <div class="col-md-3">
            <a href="<?= APP_URL ?>/public/transactions/index.php" class="text-decoration-none">
                <div class="card border-0 h-100" style="background-color: #fef3c7;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #f59e0b;">
                                <i data-feather="activity" style="width: 20px; height: 20px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Audit Log</h6>
                                <p class="card-text small mb-0 text-muted">System activity</p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?= APP_URL ?>/public/cash-blotter/index.php" class="text-decoration-none">
                <div class="card border-0 h-100" style="background-color: #fee2e2;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #dc2626;">
                                <i data-feather="dollar-sign" style="width: 20px; height: 20px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Cash Blotter</h6>
                                <p class="card-text small mb-0 text-muted">Daily cash flow</p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- Recent Clients Section -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <i data-feather="user-plus" class="me-2" style="width: 20px; height: 20px; color: #198754;"></i>
        <h5 class="mb-0">Recent Clients</h5>
        <div class="notion-divider flex-grow-1 ms-2"></div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="rounded d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; background-color: #198754;">
                        <i data-feather="user-plus" style="width: 14px; height: 14px; color: white;"></i>
                    </div>
                    <h5 class="card-title mb-0">Recently Registered</h5>
                </div>
                <a href="<?= APP_URL ?>/public/clients/index.php" class="btn btn-sm btn-outline-primary">
                    <i data-feather="arrow-right" style="width: 14px; height: 14px;"></i> View All
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <?php 
            $recentClients = $stats['recent_clients'] ?? [];
            if (!empty($recentClients) && is_array($recentClients)): 
            ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($recentClients, 0, 5) as $client): ?>
                        <tr>
                            <td class="ps-4"><?= htmlspecialchars($client['name']) ?></td>
                            <td><?= htmlspecialchars($client['phone_number'] ?? 'N/A') ?></td>
                            <td>
                                <span class="badge bg-<?= ($client['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($client['status'] ?? 'active') ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($client['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center p-4 text-muted">
                <i data-feather="user-plus" style="width: 24px; height: 24px;" class="mb-2"></i>
                <p>No recent clients.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Cash Flow Summary -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">💰 Cash Flow Summary</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card card-contrast shadow-sm metric-card metric-accent-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Total Disbursed</h6>
                            <h3 class="mb-0">₱<?= number_format($stats['total_disbursed'] ?? 0, 2) ?></h3>
                        </div>
                        <i data-feather="trending-up" class="icon-lg" style="width:3rem;height:3rem;color:#198754;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-contrast shadow-sm metric-card metric-accent-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Collections Today</h6>
                            <?php
                            // Get today's payments total
                            $todayAmount = 0;
                            try {
                                $today = date('Y-m-d');
                                $todayPayments = $paymentService->getAllPayments([
                                    'date_from' => $today,
                                    'date_to' => $today
                                ]);
                                if (is_array($todayPayments)) {
                                    foreach ($todayPayments as $p) {
                                        $todayAmount += (float)($p['amount'] ?? 0);
                                    }
                                }
                            } catch (Exception $e) {
                                error_log('Today payments error: ' . $e->getMessage());
                            }
                            ?>
                            <h3 class="mb-0">₱<?= number_format($todayAmount, 2) ?></h3>
                        </div>
                        <i data-feather="dollar-sign" class="icon-lg" style="width:3rem;height:3rem;color:#0d6efd;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-contrast shadow-sm metric-card metric-accent-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">This Month Collections</h6>
                            <?php
                            // Get this month's payments total
                            $monthAmount = 0;
                            try {
                                $monthStart = date('Y-m-01');
                                $monthEnd = date('Y-m-t');
                                $monthPayments = $paymentService->getAllPayments([
                                    'date_from' => $monthStart,
                                    'date_to' => $monthEnd
                                ]);
                                if (is_array($monthPayments)) {
                                    foreach ($monthPayments as $p) {
                                        $monthAmount += (float)($p['amount'] ?? 0);
                                    }
                                }
                            } catch (Exception $e) {
                                error_log('Month payments error: ' . $e->getMessage());
                            }
                            ?>
                            <h3 class="mb-0">₱<?= number_format($monthAmount, 2) ?></h3>
                        </div>
                        <i data-feather="calendar" class="icon-lg" style="width:3rem;height:3rem;color:#0dcaf0;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

