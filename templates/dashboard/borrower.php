<?php
/**
 * Borrower dashboard template for the Library Management System
 */
?>

<!-- Stats Overview -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card dashboard-stats">
            <div class="card-body">
                <h5 class="card-title">Books Borrowed</h5>
                <p class="stat-value"><?= $stats['total_borrowed'] ?? 0 ?></p>
                <p class="card-text">Total borrowed books</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-stats">
            <div class="card-body">
                <h5 class="card-title">Currently Borrowed</h5>
                <p class="stat-value"><?= $stats['current_borrowed'] ?? 0 ?></p>
                <p class="card-text">Books currently in possession</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-stats">
            <div class="card-body">
                <h5 class="card-title">Overdue Books</h5>
                <p class="stat-value"><?= $stats['overdue_count'] ?? 0 ?></p>
                <p class="card-text">Books past due date</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-stats">
            <div class="card-body">
                <h5 class="card-title">Penalties Due</h5>
                <p class="stat-value">₱<?= number_format($stats['total_penalties'] ?? 0, 2) ?></p>
                <p class="card-text">Unpaid penalty fees</p>
            </div>
        </div>
    </div>
</div>

<!-- Currently Borrowed Books -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Currently Borrowed Books</h5>
            </div>
            <div class="card-body">
                <?php if (isset($activeLoans) && !empty($activeLoans)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Author</th>
                                <th>Borrowed On</th>
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
                                    $daysLeft = $interval->format("%r%a"); // Includes the sign
                                    
                                    $statusClass = 'success';
                                    $statusText = 'On Time';
                                    
                                    if ($daysLeft < 0) {
                                        $statusClass = 'danger';
                                        $statusText = 'Overdue by ' . abs($daysLeft) . ' days';
                                    } elseif ($daysLeft <= 2) {
                                        $statusClass = 'warning';
                                        $statusText = 'Due Soon';
                                    }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($loan['book_title']) ?></td>
                                    <td><?= htmlspecialchars($loan['book_author']) ?></td>
                                    <td><?= date('M d, Y', strtotime($loan['borrow_date'])) ?></td>
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
                <p class="text-muted">You don't have any books borrowed at the moment.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-primary">Browse Books</a>
            </div>
        </div>
    </div>
</div>

<!-- Unpaid Penalties -->
<?php if (isset($penalties) && !empty($penalties)): ?>
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="card-title">Unpaid Penalties</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Book Title</th>
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
                                    <td><?= $penalty['return_date'] ? date('M d, Y', strtotime($penalty['return_date'])) : 'Not returned' ?></td>
                                    <td><?= $penalty['days_overdue'] ?></td>
                                    <td>₱<?= number_format($penalty['amount'], 2) ?></td>
                                    <td><span class="badge bg-danger">Unpaid</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info mt-3">
                    <h5>Penalty Information</h5>
                    <p>Penalties are calculated as follows:</p>
                    <ul>
                        <li>Base penalty: ₱<?= PENALTY_BASE_AMOUNT ?> for each overdue book</li>
                        <li>Daily increment: ₱<?= PENALTY_DAILY_INCREMENT ?> for each day a book is overdue</li>
                    </ul>
                    <p>Please settle your penalties at the library counter.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Borrowing History -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Recent Borrowing History</h5>
            </div>
            <div class="card-body">
                <?php if (isset($loanHistory) && !empty($loanHistory)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Borrowed On</th>
                                <th>Due Date</th>
                                <th>Returned On</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($loanHistory as $loan): ?>
                                <?php 
                                    $statusClass = 'success';
                                    $statusText = 'Returned';
                                    
                                    if ($loan['status'] === 'borrowed') {
                                        $dueDate = new DateTime($loan['due_date']);
                                        $today = new DateTime();
                                        
                                        if ($today > $dueDate) {
                                            $statusClass = 'danger';
                                            $statusText = 'Overdue';
                                        } else {
                                            $statusClass = 'info';
                                            $statusText = 'Borrowed';
                                        }
                                    } elseif ($loan['status'] === 'overdue') {
                                        $statusClass = 'warning';
                                        $statusText = 'Returned Late';
                                    }
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($loan['book_title']) ?></td>
                                    <td><?= date('M d, Y', strtotime($loan['borrow_date'])) ?></td>
                                    <td><?= date('M d, Y', strtotime($loan['due_date'])) ?></td>
                                    <td><?= $loan['return_date'] ? date('M d, Y', strtotime($loan['return_date'])) : '-' ?></td>
                                    <td><span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">You haven't borrowed any books yet.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-sm btn-outline-primary">View All History</a>
            </div>
        </div>
    </div>
</div>