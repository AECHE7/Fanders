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

<!-- Payments Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Payment Records</h5>
    </div>
    <div class="card-body">
        <?php if (empty($payments)): ?>
            <div class="text-center py-5">
                <i data-feather="credit-card" class="text-muted mb-3" style="width:48px;height:48px;"></i>
                <h5 class="text-muted">No Payments Found</h5>
                <p class="text-muted">No payment records match your criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Loan Amount</th>
                            <th>Payment Amount</th>
                            <th>Week</th>
                            <th>Payment Date</th>
                            <th>Method</th>
                            <th>Collected By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($payment['id']) ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle bg-primary text-white me-2" style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:bold;">
                                            <?= strtoupper(substr($payment['client_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($payment['client_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($payment['phone_number']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>₱<?= number_format($payment['loan_amount'], 2) ?></td>
                                <td>
                                    <span class="badge bg-success">₱<?= number_format($payment['payment_amount'], 2) ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-info">Week <?= htmlspecialchars($payment['week_number']) ?></span>
                                </td>
                                <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?= ucfirst(htmlspecialchars($payment['payment_method'])) ?></span>
                                </td>
                                <td>
                                    <?php if ($payment['collected_by_name']): ?>
                                        <?= htmlspecialchars($payment['collected_by_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?= APP_URL ?>/public/payments/view.php?id=<?= $payment['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i data-feather="eye" style="width:14px;height:14px;"></i>
                                        </a>
                                        <?php if ($userRole == 'administrator' || $userRole == 'manager'): ?>
                                            <a href="<?= APP_URL ?>/public/payments/edit.php?id=<?= $payment['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit Payment">
                                                <i data-feather="edit" style="width:14px;height:14px;"></i>
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
            <?php if (count($payments) >= 50): ?>
                <nav aria-label="Payment pagination" class="mt-3">
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
