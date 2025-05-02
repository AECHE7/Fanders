<?php
/**
 * Admin/Super Admin dashboard template for the Library Management System
 */
?>

<!-- Stats Overview -->
<div class="row mt-4">
    <div class="col-md-3">
        <div class="card dashboard-stats">
            <div class="card-body">
                <h5 class="card-title">Total Books</h5>
                <p class="stat-value"><?= $stats['total_books'] ?? 0 ?></p>
                <p class="card-text">Books in the library</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-stats">
            <div class="card-body">
                <h5 class="card-title">Available Books</h5>
                <p class="stat-value"><?= $stats['available_books'] ?? 0 ?></p>
                <p class="card-text">Books available for borrowing</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-stats">
            <div class="card-body">
                <h5 class="card-title">Active Borrowers</h5>
                <p class="stat-value"><?= $stats['active_borrowers'] ?? 0 ?></p>
                <p class="card-text">Registered borrowers</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card dashboard-stats">
            <div class="card-body">
                <h5 class="card-title">Active Loans</h5>
                <p class="stat-value"><?= $stats['active_loans'] ?? 0 ?></p>
                <p class="card-text">Currently borrowed books</p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Action Buttons -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Quick Actions</h5>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= APP_URL ?>/public/books/add.php" class="btn btn-primary">
                        <i data-feather="plus-circle"></i> Add New Book
                    </a>
                    <a href="<?= APP_URL ?>/public/users/add.php" class="btn btn-success">
                        <i data-feather="user-plus"></i> Add New User
                    </a>
                    <a href="<?= APP_URL ?>/public/transactions/borrow.php" class="btn btn-info">
                        <i data-feather="log-in"></i> Issue Book
                    </a>
                    <a href="<?= APP_URL ?>/public/transactions/return.php" class="btn btn-warning">
                        <i data-feather="log-out"></i> Return Book
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Recently Added Books</h5>
            </div>
            <div class="card-body">
                <?php if (isset($recentlyAddedBooks) && !empty($recentlyAddedBooks)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Added On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentlyAddedBooks as $book): ?>
                            <tr>
                                <td>
                                    <a href="<?= APP_URL ?>/public/books/view.php?id=<?= $book['id'] ?>">
                                        <?= htmlspecialchars($book['title']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($book['author']) ?></td>
                                <td><?= date('M d, Y', strtotime($book['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No books have been added recently.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-sm btn-outline-primary">View All Books</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Books Due for Return Today</h5>
            </div>
            <div class="card-body">
                <?php if (isset($dueTodayLoans) && !empty($dueTodayLoans)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Borrower</th>
                                <th>Due Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dueTodayLoans as $loan): ?>
                            <tr>
                                <td><?= htmlspecialchars($loan['book_title']) ?></td>
                                <td><?= htmlspecialchars($loan['borrower_name']) ?></td>
                                <td><?= date('M d, Y', strtotime($loan['due_date'])) ?></td>
                                <td>
                                    <a href="<?= APP_URL ?>/public/transactions/return.php?id=<?= $loan['id'] ?>" class="btn btn-sm btn-warning">
                                        <i data-feather="check-circle"></i> Return
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No books are due for return today.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-sm btn-outline-primary">View All Transactions</a>
            </div>
        </div>
    </div>
</div>

<!-- Additional Stats for Super Admin -->
<?php if ($userRole == ROLE_SUPER_ADMIN): ?>
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">System Users</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Count</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Super Admin</td>
                                <td><?= $stats['super_admin_count'] ?? 0 ?></td>
                                <td>
                                    <a href="<?= APP_URL ?>/public/users/index.php?role=1" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                            <tr>
                                <td>Admin</td>
                                <td><?= $stats['admin_count'] ?? 0 ?></td>
                                <td>
                                    <a href="<?= APP_URL ?>/public/users/index.php?role=2" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                            <tr>
                                <td>Borrower</td>
                                <td><?= $stats['borrower_count'] ?? 0 ?></td>
                                <td>
                                    <a href="<?= APP_URL ?>/public/users/index.php?role=3" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?= APP_URL ?>/public/users/add.php" class="btn btn-sm btn-success">Add New User</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Most Popular Books</h5>
            </div>
            <div class="card-body">
                <?php if (isset($mostBorrowedBooks) && !empty($mostBorrowedBooks)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Times Borrowed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mostBorrowedBooks as $book): ?>
                            <tr>
                                <td>
                                    <a href="<?= APP_URL ?>/public/books/view.php?id=<?= $book['id'] ?>">
                                        <?= htmlspecialchars($book['title']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($book['author']) ?></td>
                                <td><?= $book['borrow_count'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No borrowing data available yet.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="<?= APP_URL ?>/public/reports/books.php" class="btn btn-sm btn-outline-primary">View Book Reports</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>