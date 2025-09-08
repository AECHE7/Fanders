<?php
// Transactions List Template
?>

<!-- Transactions List -->
<div class="card">
    <div class="card-body">
        <?php if (empty($transactions)): ?>
            <div class="alert alert-info">
                No transactions found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Book Title</th>
                            <th>Borrower</th>
                            <th>Loan Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?= htmlspecialchars($transaction['id']) ?></td>
                                <td><?= htmlspecialchars($transaction['book_title']) ?></td>
                                <td><?= htmlspecialchars($transaction['borrower_name']) ?></td>
                                <td><?= htmlspecialchars($transaction['loan_date']) ?></td>
                                <td><?= htmlspecialchars($transaction['due_date']) ?></td>
                                <td>
                                    <?php if ($transaction['status'] == 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php elseif ($transaction['status'] == 'overdue'): ?>
                                        <span class="badge bg-danger">Overdue</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($transaction['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= APP_URL ?>/public/transactions/view.php?id=<?= $transaction['id'] ?>" class="btn btn-outline-primary">
                                            <i data-feather="eye"></i>
                                        </a>
                                        <?php if ($userRole == ROLE_SUPER_ADMIN || $userRole == ROLE_ADMIN): ?>
                                            <a href="<?= APP_URL ?>/public/transactions/edit.php?id=<?= $transaction['id'] ?>" class="btn btn-outline-secondary">
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
        <?php endif; ?>
    </div>
</div>
