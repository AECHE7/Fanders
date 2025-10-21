<?php
/**
 * Client dashboard template for the Fanders Microfinance Loan Management System
 * Notion-inspired design
 */
?>

<!-- Dashboard Header with Title, Date and Quick Actions -->
<div class="notion-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <div class="me-3">
                <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0f4fd;">
                    <i data-feather="user" style="width: 24px; height: 24px; color:#0b76ef;"></i>
                </div>
            </div>
            <h1 class="notion-page-title mb-0">Dashboard</h1>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <div class="text-muted d-none d-md-block me-3">
                <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i>
                <?= date('l, F j, Y') ?>
            </div>
            <a href="<?= APP_URL ?>/public/loans/add.php" class="btn btn-sm btn-outline-secondary px-3">
                <i data-feather="plus" class="me-1" style="width: 14px; height: 14px;"></i> Apply for Loan
            </a>
            <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-sm btn-outline-secondary px-3">
                <i data-feather="file-text" class="me-1" style="width: 14px; height: 14px;"></i> My Loans
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
                        <i data-feather="file-text" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Total Loans</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['total_borrowed'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Loans taken</p>
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
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['current_borrowed'] ?? 0 ?></p>
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
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['overdue_count'] ?? 0 ?></p>
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
        <h5 class="mb-0 me-2">📋 Active Loans</h5>
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
                            <th>Loan Amount</th>
                            <th>Weekly Payment</th>
                            <th>Status</th>
                            <th>Next Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeLoans as $loan): ?>
                            <?php
                                $statusClass = 'success';
                                $statusText = 'Active';

                                // Check if payment is overdue
                                $nextWeek = isset($loan['current_week']) ? $loan['current_week'] + 1 : 1;
                                $currentWeek = date('W');
                                if ($nextWeek < $currentWeek) {
                                    $statusClass = 'warning';
                                    $statusText = 'Payment Due';
                                }
                            ?>
                            <tr>
                                <td class="ps-4">#<?= htmlspecialchars($loan['id']) ?></td>
                                <td>₱<?= number_format((float)($loan['total_loan_amount'] ?? 0), 2) ?></td>
                                <?php $weeks = (int)($loan['term_weeks'] ?? 17); $weeks = $weeks > 0 ? $weeks : 17; $weekly = ($loan['total_loan_amount'] ?? 0) / $weeks; ?>
                                <td>₱<?= number_format((float)$weekly, 2) ?></td>
                                <td><span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($statusText) ?></span></td>
                                <td>Week <?= (int)$nextWeek ?></td>
                                <td>
                                    <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $loan['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center p-4 text-muted">
                <i data-feather="file-text" style="width: 24px; height: 24px;" class="mb-2"></i>
                <p>You don't have any active loans at the moment.</p>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center py-3">
            <span class="text-muted small">Apply for a new loan</span>
            <a href="<?= APP_URL ?>/public/loans/add.php" class="btn btn-primary btn-sm px-3">
                <i data-feather="plus" class="me-1" style="width: 14px; height: 14px;"></i> Apply Now
            </a>
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
                            <th>Loan Amount</th>
                            <th>Application Date</th>
                            <th>Status</th>
                            <th>Completion Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loanHistory as $loan): ?>
                            <?php
                                $statusClass = 'secondary';
                                $statusRaw = strtolower($loan['status'] ?? '');
                                $statusText = ucfirst($statusRaw);

                                switch ($statusRaw) {
                                    case 'active':
                                        $statusClass = 'success';
                                        break;
                                    case 'completed':
                                        $statusClass = 'info';
                                        break;
                                    case 'application':
                                        $statusClass = 'warning';
                                        break;
                                    case 'approved':
                                        $statusClass = 'primary';
                                        break;
                                    case 'defaulted':
                                        $statusClass = 'danger';
                                        break;
                                }
                            ?>
                            <tr>
                                <td class="ps-4">#<?= htmlspecialchars($loan['id']) ?></td>
                                <td>₱<?= number_format((float)($loan['total_loan_amount'] ?? 0), 2) ?></td>
                                <td><?= !empty($loan['application_date']) ? date('M d, Y', strtotime($loan['application_date'])) : '-' ?></td>
                                <td><span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($statusText) ?></span></td>
                                <td><?= !empty($loan['completion_date']) ? date('M d, Y', strtotime($loan['completion_date'])) : '-' ?></td>
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

<!-- Quick Action Section with Notion-style colored blocks -->
<div class="mb-5 animate-on-scroll">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">📌 Quick Actions</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="row g-3 stagger-fade-in">
        <div class="col-md-4">
            <a href="<?= APP_URL ?>/public/loans/add.php" class="text-decoration-none">
                <div class="card border-0 h-100" style="background-color: #f7ecff;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #9d71ea;">
                                <i data-feather="plus" style="width: 20px; height: 20px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Apply for Loan</h6>
                                <p class="card-text small mb-0 text-muted">Submit loan application</p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="<?= APP_URL ?>/public/loans/index.php" class="text-decoration-none">
                <div class="card border-0 h-100" style="background-color: #eaf8f6;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #0ca789;">
                                <i data-feather="file-text" style="width: 20px; height: 20px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">My Loans</h6>
                                <p class="card-text small mb-0 text-muted">View all loans</p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="<?= APP_URL ?>/public/payments/index.php" class="text-decoration-none">
                <div class="card border-0 h-100" style="background-color: #edf2fc;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #0b76ef;">
                                <i data-feather="dollar-sign" style="width: 20px; height: 20px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Payment History</h6>
                                <p class="card-text small mb-0 text-muted">View payments made</p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
