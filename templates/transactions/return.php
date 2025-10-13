<?php
/**
 * Return Book Form Template
 */
$i=0;
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Return Book</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <?php if (empty($activeLoans)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No active loans found.
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
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activeLoans as $loan): ?>
                                        <tr>
                                            <td><?= $loan['id'] ?></td>
                                            <td><?= htmlspecialchars($loan['book_title']) ?></td>
                                            <td><?= htmlspecialchars($loan['name']) ?></td>
                                            <td><?= date('Y-m-d', strtotime($loan['borrow_date'])) ?></td>
                                            <td><?= date('Y-m-d', strtotime($loan['due_date'])) ?></td>
                                            <td>
                                                <?php if ($loan['status_label'] === 'overdue'): ?>
                                                    <span class="badge bg-danger">Overdue</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">Borrowed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form action="<?= APP_URL ?>/transactions/return.php" method="post" class="d-inline">
                                                    <?= $csrf->getTokenField() ?>
                                                    <input type="hidden" name="transaction_id" value="<?= $loan['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-success" 
                                                            onclick="return confirm('Are you sure you want to return this book?')">
                                                        <i class="fas fa-undo-alt me-1"></i>Return
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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