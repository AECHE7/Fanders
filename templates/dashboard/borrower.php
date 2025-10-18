<?php
/**
 * Borrower dashboard template for the Fanders Microfinance System
 * Notion-inspired design
 */
?>

<!-- Dashboard Header with Title, Date and Quick Actions -->
<div class="notion-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <div class="me-3">
                <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0f4fd;">
                    <i data-feather="dollar-sign" style="width: 24px; height: 24px; color: #0b76ef;"></i>
                </div>
            </div>
            <h1 class="notion-page-title mb-0">Dashboard</h1>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <div class="text-muted d-none d-md-block me-3">
                <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i>
                <?= date('l, F j, Y') ?>
            </div>
            <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-sm btn-outline-secondary px-3">
                <i data-feather="credit-card" class="me-1" style="width: 14px; height: 14px;"></i> View Loans
            </a>
            <a href="<?= APP_URL ?>/public/payments/index.php" class="btn btn-sm btn-outline-secondary px-3">
                <i data-feather="dollar-sign" class="me-1" style="width: 14px; height: 14px;"></i> Make Payment
            </a>
        </div>
    </div>
    <div class="notion-divider my-3"></div>
</div>

<!-- Stats Overview with Color-coded Icons like Notion -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">📊 My Loan Statistics</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="row g-4 dashboard-stats-container">
        <!-- Total Loans -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #F5F4FF;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #9d71ea; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="credit-card" style="width: 20px; height: 20px; color: white;"></i>
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

        <!-- Overdue Loans -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #FEF3E4;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #ec7211; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="alert-circle" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Overdue Loans</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['overdue_loans'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Past due date</p>
                </div>
            </div>
        </div>

        <!-- Penalties Due -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #FEE2E2;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #dc2626; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="dollar-sign" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Penalties Due</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0">₱<?= number_format($stats['total_penalties'] ?? 0) ?></p>
                    <p class="card-text text-muted mb-0 small">Unpaid fees</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Active Loans -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">💳 Active Loans</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (isset($activeLoans) && !empty($activeLoans)): ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Loan ID</th>
                            <th>Principal Amount</th>
                            <th>Total Amount</th>
                            <th>Disbursed On</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Days Left</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeLoans as $loan): ?>
                            <?php
                                $dueDate = new DateTime($loan['due_date']);
                                $today = new DateTime();
                                $interval = $today->diff($dueDate);
                                $daysLeft = $interval->format("%r%a");

                                $statusClass = 'success';
                                $statusText = 'On Time';

                                if ($daysLeft < 0) {
                                    $statusClass = 'danger';
                                    $statusText = 'Overdue by ' . abs($daysLeft) . ' days';
                                } elseif ($daysLeft <= 7) {
                                    $statusClass = 'warning';
                                    $statusText = 'Due Soon';
                                }
                            ?>
                            <tr>
                                <td class="ps-4">#<?= htmlspecialchars($loan['id']) ?></td>
                                <td>₱<?= number_format($loan['principal'], 2) ?></td>
                                <td>₱<?= number_format($loan['total_loan_amount'], 2) ?></td>
                                <td><?= date('M d, Y', strtotime($loan['created_at'])) ?></td>
                                <td><?= date('M d, Y', strtotime($loan['due_date'])) ?></td>
                                <td><span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span></td>
                                <td>
                                    <?php if ($daysLeft < 0): ?>
                                        <span class="text-danger">Overdue</span>
                                    <?php else: ?>
                                        <?= $daysLeft ?> days
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center p-4 text-muted">
                <i data-feather="credit-card" style="width: 24px; height: 24px;" class="mb-2"></i>
                <p>You don't have any active loans at the moment.</p>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center py-3">
            <span class="text-muted small">Apply for a Loan</span>
            <a href="<?= APP_URL ?>/public/loans/add.php" class="btn btn-primary btn-sm px-3">
                <i data-feather="plus" class="me-1" style="width: 14px; height: 14px;"></i> Apply Now
            </a>
        </div>
    </div>
</div>

<!-- Recent Payments -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">💰 Recent Payments</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (isset($recentPayments) && !empty($recentPayments)): ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Payment ID</th>
                            <th>Loan ID</th>
                            <th>Amount</th>
                            <th>Payment Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPayments as $payment): ?>
                            <tr>
                                <td class="ps-4">#<?= htmlspecialchars($payment['id']) ?></td>
                                <td>#<?= htmlspecialchars($payment['loan_id']) ?></td>
                                <td>₱<?= number_format($payment['amount'], 2) ?></td>
                                <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                <td><span class="badge bg-success">Paid</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center p-4 text-muted">
                <i data-feather="dollar-sign" style="width: 24px; height: 24px;" class="mb-2"></i>
                <p>No recent payments found.</p>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-transparent border-top text-end py-3">
            <a href="<?= APP_URL ?>/public/payments/index.php" class="btn btn-primary btn-sm px-3">View All Payments</a>
        </div>
    </div>
</div>

<!-- Loan History -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">📜 Loan History</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (isset($loanHistory) && !empty($loanHistory)): ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Loan ID</th>
                            <th>Principal Amount</th>
                            <th>Total Amount</th>
                            <th>Applied On</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loanHistory as $loan): ?>
                            <?php
                                $statusClass = 'success';
                                $statusText = 'Completed';

                                if ($loan['status'] === 'active') {
                                    $dueDate = new DateTime($loan['due_date']);
                                    $today = new DateTime();

                                    if ($today > $dueDate) {
                                        $statusClass = 'danger';
                                        $statusText = 'Overdue';
                                    } else {
                                        $statusClass = 'info';
                                        $statusText = 'Active';
                                    }
                                } elseif ($loan['status'] === 'defaulted') {
                                    $statusClass = 'danger';
                                    $statusText = 'Defaulted';
                                } elseif ($loan['status'] === 'completed') {
                                    $statusClass = 'success';
                                    $statusText = 'Completed';
                                }
                            ?>
                            <tr>
                                <td class="ps-4">#<?= htmlspecialchars($loan['id']) ?></td>
                                <td>₱<?= number_format($loan['principal'], 2) ?></td>
                                <td>₱<?= number_format($loan['total_loan_amount'], 2) ?></td>
                                <td><?= date('M d, Y', strtotime($loan['created_at'])) ?></td>
                                <td><?= date('M d, Y', strtotime($loan['due_date'])) ?></td>
                                <td><span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center p-4 text-muted">
                <i data-feather="clock" style="width: 24px; height: 24px;" class="mb-2"></i>
                <p>No loan history available.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>


