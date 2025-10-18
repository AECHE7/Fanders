<?php
/**
 * Payments List Template
 */
?>

<!-- Display Error if any -->
<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Active Loans Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Active Loans & Payment Progress</h5>
    </div>
    <div class="card-body">
        <?php if (empty($loansWithProgress)): ?>
            <div class="text-center py-5">
                <i data-feather="credit-card" class="text-muted mb-3" style="width:48px;height:48px;"></i>
                <h5 class="text-muted">No Active Loans Found</h5>
                <p class="text-muted">No active loans match your criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Loan ID</th>
                            <th>Client</th>
                            <th>Loan Amount</th>
                            <th>Progress</th>
                            <th>Weeks Paid</th>
                            <th>Total Paid</th>
                            <th>Remaining Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loansWithProgress as $loan): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($loan['id']) ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-primary text-white me-2" style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:bold;">
                                            <?= strtoupper(substr($loan['client_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($loan['client_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($loan['phone_number']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>₱<?= number_format($loan['total_loan_amount'], 2) ?></td>
                                <td>
                                    <?php
                                    $progressPercent = ($loan['weeks_paid'] / 17) * 100;
                                    $progressClass = $progressPercent >= 80 ? 'bg-success' : ($progressPercent >= 50 ? 'bg-warning' : 'bg-danger');
                                    ?>
                                    <div class="progress" style="width: 100px;">
                                        <div class="progress-bar <?= $progressClass ?>" role="progressbar"
                                             style="width: <?= $progressPercent ?>%"
                                             aria-valuenow="<?= $progressPercent ?>" aria-valuemin="0" aria-valuemax="100">
                                            <?= round($progressPercent) ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= htmlspecialchars($loan['weeks_paid']) ?>/17</span>
                                </td>
                                <td>
                                    <span class="badge bg-success">₱<?= number_format($loan['total_paid'], 2) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-danger">₱<?= number_format($loan['remaining_balance'], 2) ?></span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $loan['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Loan Details">
                                            <i data-feather="eye"></i>
                                        </a>
                                        <a href="<?= APP_URL ?>/public/payments/approvals.php?loan_id=<?= $loan['id'] ?>" class="btn btn-sm btn-outline-success" title="Record Payment">
                                            <i data-feather="credit-card"></i>
                                        </a>
                                        <?php if ($userRole == 'administrator' || $userRole == 'manager'): ?>
                                            <a href="<?= APP_URL ?>/public/loans/edit.php?id=<?= $loan['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit Loan">
                                                <i data-feather="edit"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination (if needed) -->
            <?php if (count($loansWithProgress) >= 50): ?>
                <nav aria-label="Loan pagination" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <li class="page-item disabled">
                            <span class="page-link">Previous</span>
                        </li>
                        <li class="page-item active">
                            <span class="page-link">1</span>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="#">2</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="#">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Payment Statistics Card (for staff) -->
<?php if ($userRole == 'administrator' || $userRole == 'manager' || $userRole == 'collector'): ?>
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Payment Statistics</h6>
                </div>
                <div class="card-body">
                    <?php
                    $stats = $paymentService->getPaymentStats();
                    $monthlyStats = $paymentService->getPaymentSummary(date('Y-m-01'), date('Y-m-t'));
                    ?>
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="border-end">
                                <h4 class="text-primary mb-1"><?= number_format($stats['total_payments']) ?></h4>
                                <small class="text-muted">Total Payments</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-end">
                                <h4 class="text-success mb-1">₱<?= number_format($stats['total_collected'], 2) ?></h4>
                                <small class="text-muted">Total Collected</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-end">
                                <h4 class="text-info mb-1">₱<?= number_format($stats['collected_this_month'], 2) ?></h4>
                                <small class="text-muted">This Month</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-warning mb-1">₱<?= number_format($stats['collected_today'], 2) ?></h4>
                            <small class="text-muted">Today</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
// Initialize Feather icons
if (typeof feather !== 'undefined') {
    feather.replace();
}
</script>
