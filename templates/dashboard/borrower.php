<?php 
// Borrower Dashboard Template
?>

<!-- Stats Cards -->
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Books Borrowed</h6>
                        <h2 class="mb-0"><?= $stats['total_borrowed'] ?></h2>
                    </div>
                    <div class="text-primary">
                        <span data-feather="book" style="width: 40px; height: 40px;"></span>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-muted">Currently borrowed: <?= $stats['currently_borrowed'] ?></small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Overdue Books</h6>
                        <h2 class="mb-0"><?= $stats['overdue_books'] ?></h2>
                    </div>
                    <div class="text-danger">
                        <span data-feather="alert-triangle" style="width: 40px; height: 40px;"></span>
                    </div>
                </div>
                <div class="mt-3">
                    <?php if ($stats['overdue_books'] > 0): ?>
                        <small class="text-danger">You have overdue books!</small>
                    <?php else: ?>
                        <small class="text-success">No overdue books</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Penalties</h6>
                        <h2 class="mb-0">₱<?= number_format($stats['total_penalties'] ?? 0, 2) ?></h2>
                    </div>
                    <div class="text-warning">
                        <span data-feather="dollar-sign" style="width: 40px; height: 40px;"></span>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-muted">Unpaid: ₱<?= number_format($stats['unpaid_penalties'] ?? 0, 2) ?></small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Account Status</h6>
                        <h2 class="mb-0"><?= $user['is_active'] ? 'Active' : 'Inactive' ?></h2>
                    </div>
                    <div class="<?= $user['is_active'] ? 'text-success' : 'text-danger' ?>">
                        <span data-feather="<?= $user['is_active'] ? 'user-check' : 'user-x' ?>" style="width: 40px; height: 40px;"></span>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-muted">Member since: <?= date('M d, Y', strtotime($user['created_at'])) ?></small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Currently Borrowed Books -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Currently Borrowed Books</h5>
        <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body">
        <?php if (isset($activeLoans) && count($activeLoans) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeLoans as $loan): ?>
                            <tr>
                                <td>
                                    <a href="<?= APP_URL ?>/public/books/view.php?id=<?= $loan['book_id'] ?>">
                                        <?= htmlspecialchars($loan['book_title']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($loan['author']) ?></td>
                                <td><?= date('Y-m-d', strtotime($loan['borrow_date'])) ?></td>
                                <td><?= date('Y-m-d', strtotime($loan['due_date'])) ?></td>
                                <td>
                                    <?php if ($loan['days_remaining'] < 0): ?>
                                        <span class="badge bg-danger">Overdue by <?= abs($loan['days_remaining']) ?> days</span>
                                    <?php elseif ($loan['days_remaining'] <= 3): ?>
                                        <span class="badge bg-warning">Due in <?= $loan['days_remaining'] ?> days</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary"><?= $loan['days_remaining'] ?> days remaining</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">You have no currently borrowed books.</p>
            <div class="mt-3">
                <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-primary">Browse Books</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Overdue Books Alert -->
<?php if (isset($overdueBooks) && count($overdueBooks) > 0): ?>
    <div class="alert alert-danger">
        <h5 class="alert-heading">Overdue Books Notice</h5>
        <p>You have <?= count($overdueBooks) ?> overdue book(s). Please return them as soon as possible to avoid additional penalties.</p>
        <hr>
        <p class="mb-0">Accumulated penalties: ₱<?= number_format($stats['unpaid_penalties'] ?? 0, 2) ?></p>
    </div>
<?php endif; ?>

<!-- Penalties -->
<?php if (isset($penalties) && count($penalties) > 0): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Penalty History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th>Days Overdue</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($penalties as $penalty): ?>
                            <tr>
                                <td><?= htmlspecialchars($penalty['book_title']) ?></td>
                                <td><?= date('Y-m-d', strtotime($penalty['due_date'])) ?></td>
                                <td><?= date('Y-m-d', strtotime($penalty['return_date'])) ?></td>
                                <td><?= $penalty['days_overdue'] ?></td>
                                <td>₱<?= number_format($penalty['amount'], 2) ?></td>
                                <td>
                                    <?php if ($penalty['is_paid']): ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Unpaid</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Recent Borrowing History -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Recent Borrowing History</h5>
    </div>
    <div class="card-body">
        <?php if (isset($loanHistory) && count($loanHistory) > 0): ?>
            <div class="list-group">
                <?php 
                $shownHistory = 0;
                foreach ($loanHistory as $history): 
                    if ($shownHistory >= 5) break;
                ?>
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><?= htmlspecialchars($history['book_title']) ?></h6>
                            <small>
                                <span class="badge bg-success">Returned</span>
                            </small>
                        </div>
                        <p class="mb-1">By <?= htmlspecialchars($history['author']) ?></p>
                        <small class="text-muted">
                            Borrowed: <?= date('M d, Y', strtotime($history['borrow_date'])) ?> • 
                            Returned: <?= date('M d, Y', strtotime($history['return_date'])) ?> • 
                            Duration: <?= $history['days_borrowed'] ?> days
                        </small>
                    </div>
                <?php 
                    $shownHistory++;
                endforeach; 
                ?>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">You have no borrowing history yet.</p>
        <?php endif; ?>
    </div>
</div>
