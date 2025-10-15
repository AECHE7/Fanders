 <?php
// Loans List Template
?>
<!-- Loans List -->
<div class="card mb-3">
    <div class="card-body">
        <?php if (empty($loans)): ?>
            <div class="alert alert-info">
                No loans found. <?php echo !empty($searchTerm) ? 'Try a different search term.' : ''; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Loan ID</th>
                            <th>Client Name</th>
                            <th>Loan Amount</th>
                            <th>Weekly Payment</th>
                            <th>Status</th>
                            <th>Application Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        ?>
                        <?php foreach ($loans as $loan): ?>
                            <tr>
                            <td><?= htmlspecialchars($i) ?></td>
                            <td>
                                <a href="<?= APP_URL ?>/public/clients/view.php?id=<?= $loan['client_id'] ?>">
                                    <?= htmlspecialchars($loan['client_name'] ?? '') ?>
                                </a>
                            </td>
                            <td>₱<?= number_format($loan['loan_amount'], 2) ?></td>
                            <td>₱<?= number_format($loan['weekly_payment'], 2) ?></td>
                            <td>
                                <?php
                                $status = strtolower($loan['status'] ?? '');
                                $statusClasses = [
                                    'application' => 'badge bg-warning text-dark',
                                    'approved' => 'badge bg-info',
                                    'active' => 'badge bg-success',
                                    'completed' => 'badge bg-primary',
                                    'defaulted' => 'badge bg-danger'
                                ];
                                $statusClass = $statusClasses[$status] ?? 'badge bg-secondary';
                                ?>
                                <span class="<?= $statusClass ?>"><?= ucfirst($status) ?></span>
                            </td>
                            <td><?= htmlspecialchars(date('M d, Y', strtotime($loan['application_date']))) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $loan['id'] ?>" class="btn btn-outline-primary me-2" title="View Details">
                                        <i data-feather="eye"></i>
                                    </a>

                                    <?php if (in_array($userRole, ['administrator', 'manager']) && $loan['status'] === 'application'): ?>
                                        <form method="POST" action="<?= APP_URL ?>/public/loans/approve.php" style="display:inline; margin-left: 4px;">
                                            <input type="hidden" name="id" value="<?= $loan['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                            <button type="submit" class="btn btn-outline-success" title="Approve Loan">
                                                <i data-feather="check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (in_array($userRole, ['administrator', 'manager']) && $loan['status'] === 'approved'): ?>
                                        <form method="POST" action="<?= APP_URL ?>/public/loans/disburse.php" style="display:inline; margin-left: 4px;">
                                            <input type="hidden" name="id" value="<?= $loan['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                            <button type="submit" class="btn btn-outline-info" title="Disburse Loan">
                                                <i data-feather="dollar-sign"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($loan['status'] === 'active'): ?>
                                        <a href="<?= APP_URL ?>/public/payments/add.php?loan_id=<?= $loan['id'] ?>" class="btn btn-outline-secondary" title="Record Payment">
                                            <i data-feather="credit-card"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                        $i++;
                        ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
