<?php
/**
 * Overdue Books Template
 */
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Overdue Books</h4>
                    <a href="<?= APP_URL ?>/transactions/export.php?type=overdue" class="btn btn-light btn-sm">
                        <i class="fas fa-file-pdf me-2"></i>Export to PDF
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($overdueLoans)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>No overdue books found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Book Title</th>
                                        <th>Borrower</th>
                                        <th>Borrow Date</th>
                                        <th>Due Date</th>
                                        <th>Days Overdue</th>
                                        <th>Penalty Amount</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($overdueLoans as $loan): ?>
                                        <tr>
                                            <td><?= $loan['id'] ?></td>
                                            <td><?= htmlspecialchars($loan['book_title']) ?></td>
                                            <td>
                                                <?= htmlspecialchars($loan['name']) ?>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars($loan['email']) ?></small>
                                            </td>
                                            <td><?= date('Y-m-d', strtotime($loan['borrow_date'])) ?></td>
                                            <td><?= date('Y-m-d', strtotime($loan['due_date'])) ?></td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    <?= $loan['days_overdue'] ?> days
                                                </span>
                                            </td>
                                            <td>
                                                ₱<?= number_format($loan['days_overdue'] * 50, 2) ?>
                                            </td>
                                            <td>
                                                <form action="<?= APP_URL ?>/transactions/return.php" method="post" class="d-inline">
                                                    <?= $csrf->getTokenField() ?>
                                                    <input type="hidden" name="transaction_id" value="<?= $loan['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-success" 
                                                            onclick="return confirm('Are you sure you want to return this book? A penalty will be applied.')">
                                                        <i class="fas fa-undo-alt me-1"></i>Return
                                                    </button>
                                                </form>
                                                <a href="<?= APP_URL ?>/transactions/send-reminder.php?id=<?= $loan['id'] ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="fas fa-bell me-1"></i>Send Reminder
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Note:</strong> Penalty is calculated at ₱50 per day for each overdue book.
                        </div>
                    <?php endif; ?>

                    <div class="mt-3">
                        <a href="<?= APP_URL ?>/transactions/index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Transactions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 