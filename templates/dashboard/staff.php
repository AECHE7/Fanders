<?php
/**
 * Staff dashboard template for the Fanders Microfinance Loan Management System
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
            <a href="<?= APP_URL ?>/public/reports/payments.php" class="btn btn-sm btn-outline-secondary px-3">
                <i data-feather="dollar-sign" class="me-1" style="width: 14px; height: 14px;"></i> Payments
            </a>
            <a href="<?= APP_URL ?>/public/reports/clients.php" class="btn btn-sm btn-outline-secondary px-3">
                <i data-feather="users" class="me-1" style="width: 14px; height: 14px;"></i> Clients
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
                    <p class="card-text text-muted mb-0 small">Active loans</p>
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
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['overdue_payments'] ?? 0 ?></p>
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
                    <p class="stat-value display-5 fw-bold mb-0">₱<?= number_format($stats['total_portfolio'] ?? 0, 2) ?></p>
                    <p class="card-text text-muted mb-0 small">Outstanding balance</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($userRole === 'administrator'): ?>
<!-- Administrative Statistics -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">👥 Administrative Overview</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="row g-4">
        <!-- Total Clients -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #F0FDF4;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #16a34a; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="users" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Total Clients</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['total_clients'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Registered clients</p>
                </div>
            </div>
        </div>

        <!-- Monthly Disbursements -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #F0F7FF;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #2563eb; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="trending-up" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Monthly Disbursements</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0">₱<?= number_format($stats['monthly_disbursements'] ?? 0, 2) ?></p>
                    <p class="card-text text-muted mb-0 small">This month</p>
                </div>
            </div>
        </div>

        <!-- Pending Approvals -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #FDF4FF;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #9333ea; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="clock" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Pending Approvals</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['pending_approvals'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Awaiting approval</p>
                </div>
            </div>
        </div>

        <!-- Collection Rate -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #FEF3F2;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #ea580c; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="target" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Collection Rate</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= number_format($stats['collection_rate'] ?? 0, 1) ?>%</p>
                    <p class="card-text text-muted mb-0 small">This month</p>
                </div>
            </div>
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
                    <?php if (isset($recentPayments) && !empty($recentPayments)): ?>
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
                                    <td>₱<?= number_format($payment['payment_amount'], 2) ?></td>
                                    <td>Week <?= $payment['week_number'] ?></td>
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

        <!-- Active Loans -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent py-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <div class="rounded d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; background-color: #9d71ea;">
                            <i data-feather="file-text" style="width: 14px; height: 14px; color: white;"></i>
                        </div>
                        <h5 class="card-title mb-0">Active Loans</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (isset($activeLoans) && !empty($activeLoans)): ?>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Client</th>
                                    <th>Loan Amount</th>
                                    <th>Status</th>
                                    <th>Next Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($activeLoans, 0, 5) as $loan): ?>
                                <tr>
                                    <td class="ps-4"><?= htmlspecialchars($loan['client_name']) ?></td>
                                    <td>₱<?= number_format($loan['loan_amount'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-success">Active</span>
                                    </td>
                                    <td>Week <?= $loan['current_week'] ?? 1 ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center p-4 text-muted">
                        <i data-feather="file-text" style="width: 24px; height: 24px;" class="mb-2"></i>
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
            <a href="<?= APP_URL ?>/public/payments/add.php" class="text-decoration-none">
                <div class="card border-0 h-100" style="background-color: #edf2fc;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #0b76ef;">
                                <i data-feather="dollar-sign" style="width: 20px; height: 20px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Record Payment</h6>
                                <p class="card-text small mb-0 text-muted">Process payment</p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?= APP_URL ?>/public/collection-sheets/index.php" class="text-decoration-none">
                <div class="card border-0 h-100" style="background-color: #fff3e9;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #ec7211;">
                                <i data-feather="clipboard" style="width: 20px; height: 20px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Collection Sheets</h6>
                                <p class="card-text small mb-0 text-muted">Manage collections</p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- Monthly Performance Summary -->
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
                    <h6 class="mb-0">Performance Summary</h6>
                    <small class="text-muted">
                        Key metrics for <?= date('F Y') ?>
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
            $performanceMetrics = $analytics['monthly'] ?? [
                ['label' => 'New Loans Disbursed', 'value' => 0, 'bg' => '#edf2fc', 'dot' => '#0b76ef'],
                ['label' => 'Payments Collected', 'value' => '₱0.00', 'bg' => '#f1ebfc', 'dot' => '#9d71ea'],
                ['label' => 'New Clients', 'value' => 0, 'bg' => '#fff3e9', 'dot' => '#ec7211'],
                ['label' => 'Portfolio Growth', 'value' => '₱0.00', 'bg' => '#ebfef6', 'dot' => '#0ca789']
            ];
            foreach ($performanceMetrics as $metric):
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
