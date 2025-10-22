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
        <h5 class="mb-0 me-2">📈 Microfinance Statistics</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="row g-4 dashboard-stats-container">
        <!-- Total Loans -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #F5F4FF;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #9d71ea; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="file-text" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Total Loans</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['total_loans'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">All loans</p>
                </div>
            </div>
        </div>

        <!-- Active Loans -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #E0F2FE;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #0b76ef; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="activity" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Active Loans</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['active_loans'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Currently active</p>
                </div>
            </div>
        </div>

        <!-- Overdue Payments -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #FEF3E4;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #ec7211; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="alert-circle" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Overdue Payments</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['overdue_returns'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Past due date</p>
                </div>
            </div>
        </div>

        <!-- Total Portfolio -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #FEE2E2;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #dc2626; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="dollar-sign" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Total Portfolio</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0">₱<?= number_format($stats['total_disbursed'] ?? 0, 2) ?></p>
                    <p class="card-text text-muted mb-0 small">Outstanding balance</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($userRole === UserModel::$ROLE_SUPER_ADMIN): ?>
<!-- User Statistics (Super Admin Only) -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">👥 User Statistics</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="row g-4">
        <!-- Total Students -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #F0FDF4;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #16a34a; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="users" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Total Students</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['total_students'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Student users</p>
                </div>
            </div>
        </div>
        
        <!-- Total Staff -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #F0F7FF;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #2563eb; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="user-check" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Total Staff</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['total_staff'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Staff users</p>
                </div>
            </div>
        </div>
        
        <!-- Total Admins -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #FDF4FF;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #9333ea; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="shield" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Total Admins</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['total_admins'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Admin users</p>
                </div>
            </div>
        </div>
        
        <!-- Total Others -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #FEF3F2;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #ea580c; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="user" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Total Others</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['total_others'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Other users</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<!-- Total Borrowers (Admin Only) -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">👥 Borrower Statistics</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="row g-4">
        <div class="col-md-12">
            <div class="p-4 rounded" style="background-color: #F0FDF4;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #16a34a; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="users" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Total Borrowers</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['total_borrowers'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Active borrowers</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

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
                    <h5 class="mb-1 text-danger fw-bold">⚠️ <?= $stats['overdue_returns'] ?? 0 ?> Overdue Payment<?= ($stats['overdue_returns'] ?? 0) > 1 ? 's' : '' ?> Require Attention</h5>
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
        <h5 class="mb-0 me-2">🔍 Recent Activity</h5>
        <div class="notion-divider flex-grow-1"></div>
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
        <h5 class="mb-0 me-2">📌 Quick Actions</h5>
        <div class="notion-divider flex-grow-1"></div>
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
            <a href="<?= APP_URL ?>/public/cash_blotter/index.php" class="text-decoration-none">
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

<!-- Analytics Summary Section -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">📊 Monthly Performance</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    
    <div class="p-4 rounded" style="background-color: #f3f4f6;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <div class="rounded me-3" style="width: 32px; height: 32px; background-color: #0b76ef; display: flex; align-items: center; justify-content: center;">
                    <i data-feather="trending-up" style="width: 16px; height: 16px; color: white;"></i>
                </div>
                <div>
                    <h6 class="mb-0">Activity Summary</h6>
                    <small class="text-muted">
                        <?= $analytics['borrower_growth_text'] ?? 'Active borrowers compared to last month' ?>
                    </small>
                </div>
            </div>
            <div class="custom-filter-tabs">
                <button type="button" class="btn btn-sm px-3 me-1 active">This Month</button>
                <button type="button" class="btn btn-sm px-3">Last Month</button>
            </div>
        </div>
        
        <div class="row g-4 mt-2">
            <?php
            $analyticMetrics = $analytics['monthly'] ?? [
                ['label' => 'New Loans Disbursed', 'value' => $stats['loans_this_month'] ?? 0, 'bg' => '#edf2fc', 'dot' => '#0b76ef'],
                ['label' => 'Payments Collected', 'value' => '₱' . number_format($stats['total_disbursed'] ?? 0, 2), 'bg' => '#f1ebfc', 'dot' => '#9d71ea'],
                ['label' => 'New Clients', 'value' => $stats['total_clients'] ?? 0, 'bg' => '#fff3e9', 'dot' => '#ec7211'],
                ['label' => 'Portfolio Growth', 'value' => '₱' . number_format($stats['total_disbursed'] ?? 0, 2), 'bg' => '#ebfef6', 'dot' => '#0ca789']
            ];
            foreach ($analyticMetrics as $metric):
            ?>
                <div class="col-md-3">
                    <div class="p-3 rounded" style="background-color: <?= htmlspecialchars($metric['bg']) ?>;">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle me-2"
                                 style="width: 8px; height: 8px; background-color: <?= htmlspecialchars($metric['dot']) ?>;">
                            </div>
                            <p class="mb-0 small"><?= htmlspecialchars($metric['label']) ?></p>
                        </div>
                        <h3 class="mb-0"><?= htmlspecialchars($metric['value']) ?></h3>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

