<?php 
// Admin/Super Admin Dashboard Template
?>

<!-- Stats Cards -->
<div class="row">
    <?php if ($userRole == ROLE_SUPER_ADMIN): ?>
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total Users</h6>
                            <h2 class="mb-0"><?= $stats['total_users'] ?></h2>
                        </div>
                        <div class="text-primary">
                            <span data-feather="users" style="width: 40px; height: 40px;"></span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Active: <?= $stats['active_users'] ?></small>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="col-md-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted">Total Borrowers</h6>
                            <h2 class="mb-0"><?= $stats['total_borrowers'] ?></h2>
                        </div>
                        <div class="text-primary">
                            <span data-feather="users" style="width: 40px; height: 40px;"></span>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Active: <?= $stats['active_borrowers'] ?></small>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Books</h6>
                        <h2 class="mb-0"><?= $stats['total_books'] ?></h2>
                    </div>
                    <div class="text-success">
                        <span data-feather="book" style="width: 40px; height: 40px;"></span>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-muted">Available: <?= $stats['available_books'] ?></small>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Borrowed Books</h6>
                        <h2 class="mb-0"><?= $stats['borrowed_books'] ?></h2>
                    </div>
                    <div class="text-warning">
                        <span data-feather="repeat" style="width: 40px; height: 40px;"></span>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-muted">Overdue: <?= $stats['overdue_books'] ?></small>
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
                        <h2 class="mb-0">₱<?= number_format($stats['total_penalties'], 2) ?></h2>
                    </div>
                    <div class="text-danger">
                        <span data-feather="alert-triangle" style="width: 40px; height: 40px;"></span>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-muted">Unpaid: ₱<?= number_format($stats['unpaid_penalties'], 2) ?></small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Overdue Books -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Overdue Books</h5>
        <a href="<?= APP_URL ?>/public/transactions/index.php?status=overdue" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body">
        <?php if (isset($overdueLoans) && count($overdueLoans) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Book Title</th>
                            <th>Borrower</th>
                            <th>Due Date</th>
                            <th>Days Overdue</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overdueLoans as $loan): ?>
                            <tr>
                                <td><?= $loan['id'] ?></td>
                                <td><?= htmlspecialchars($loan['book_title']) ?></td>
                                <td><?= htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']) ?></td>
                                <td><?= date('Y-m-d', strtotime($loan['due_date'])) ?></td>
                                <td><span class="badge bg-danger"><?= $loan['days_overdue'] ?> days</span></td>
                                <td>
                                    <a href="<?= APP_URL ?>/public/transactions/return.php?id=<?= $loan['id'] ?>" class="btn btn-sm btn-primary">Return</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted mb-0">No overdue books at the moment.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <!-- Recently Added Books -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recently Added Books</h5>
                <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (isset($recentlyAddedBooks) && count($recentlyAddedBooks) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($recentlyAddedBooks as $book): ?>
                            <a href="<?= APP_URL ?>/public/books/view.php?id=<?= $book['id'] ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($book['title']) ?></h6>
                                    <small class="text-muted"><?= date('M d', strtotime($book['created_at'])) ?></small>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars($book['author']) ?></p>
                                <small class="text-muted">
                                    <?= htmlspecialchars($book['category_name']) ?> • 
                                    <?= $book['available_copies'] ?>/<?= $book['total_copies'] ?> copies available
                                </small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No books added recently.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Transactions</h5>
                <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (isset($activeLoans) && count($activeLoans) > 0): ?>
                    <div class="list-group">
                        <?php 
                        $shownLoans = 0;
                        foreach ($activeLoans as $loan): 
                            if ($shownLoans >= 5) break;
                        ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($loan['book_title']) ?></h6>
                                    <small>
                                        <?php if ($loan['status_label'] == 'Overdue'): ?>
                                            <span class="badge bg-danger">Overdue</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Borrowed</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <p class="mb-1">Borrower: <?= htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']) ?></p>
                                <small class="text-muted">
                                    Due: <?= date('M d, Y', strtotime($loan['due_date'])) ?> • 
                                    <?php if ($loan['days_remaining'] < 0): ?>
                                        <span class="text-danger"><?= abs($loan['days_remaining']) ?> days overdue</span>
                                    <?php else: ?>
                                        <?= $loan['days_remaining'] ?> days remaining
                                    <?php endif; ?>
                                </small>
                            </div>
                        <?php 
                            $shownLoans++;
                        endforeach; 
                        ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No active loans at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($userRole == ROLE_SUPER_ADMIN): ?>
    <!-- User Distribution -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">User Distribution by Role</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <?php if (isset($stats['users_by_role'])): ?>
                    <?php foreach ($stats['users_by_role'] as $role => $count): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title"><?= $role ?></h5>
                                    <h2 class="mb-0"><?= $count ?></h2>
                                    <small class="text-muted">
                                        <?= round(($count / $stats['total_users']) * 100, 1) ?>% of total users
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
