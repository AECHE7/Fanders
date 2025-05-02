<?php
/**
 * Admin/Super Admin dashboard template for the Library Management System
 * Notion-inspired design
 */
?>

<!-- Dashboard Header with Reports Links -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="notion-page-title mb-0">Dashboard</h1>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/public/reports/books.php" class="btn btn-sm btn-outline-secondary px-3">Books Report</a>
        <a href="<?= APP_URL ?>/public/reports/users.php" class="btn btn-sm btn-outline-secondary px-3">Users Report</a>
        <a href="<?= APP_URL ?>/public/reports/transactions.php" class="btn btn-sm btn-outline-secondary px-3">Transactions Report</a>
    </div>
</div>

<!-- Stats Overview -->
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i data-feather="book" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h6 class="card-subtitle text-muted mb-0">Total Books</h6>
                </div>
                <p class="stat-value mb-0"><?= $stats['total_books'] ?? 0 ?></p>
                <p class="card-text text-muted small">Books in the library</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i data-feather="book-open" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h6 class="card-subtitle text-muted mb-0">Available Books</h6>
                </div>
                <p class="stat-value mb-0"><?= $stats['available_books'] ?? 0 ?></p>
                <p class="card-text text-muted small">Books available for borrowing</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i data-feather="users" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h6 class="card-subtitle text-muted mb-0">Active Borrowers</h6>
                </div>
                <p class="stat-value mb-0"><?= $stats['active_borrowers'] ?? 0 ?></p>
                <p class="card-text text-muted small">Registered borrowers</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i data-feather="repeat" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h6 class="card-subtitle text-muted mb-0">Active Loans</h6>
                </div>
                <p class="stat-value mb-0"><?= $stats['active_loans'] ?? 0 ?></p>
                <p class="card-text text-muted small">Currently borrowed books</p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Action Buttons -->
<div class="card border-0 shadow-sm mb-5">
    <div class="card-body p-4">
        <h5 class="card-title mb-3">Quick Actions</h5>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= APP_URL ?>/public/books/add.php" class="btn btn-primary d-inline-flex align-items-center">
                <i data-feather="plus-circle" class="me-2" style="width: 18px; height: 18px;"></i> Add New Book
            </a>
            <a href="<?= APP_URL ?>/public/users/add.php" class="btn btn-success d-inline-flex align-items-center">
                <i data-feather="user-plus" class="me-2" style="width: 18px; height: 18px;"></i> Add New User
            </a>
            <a href="<?= APP_URL ?>/public/transactions/borrow.php" class="btn btn-info d-inline-flex align-items-center text-white">
                <i data-feather="log-in" class="me-2" style="width: 18px; height: 18px;"></i> Issue Book
            </a>
            <a href="<?= APP_URL ?>/public/transactions/return.php" class="btn btn-warning d-inline-flex align-items-center">
                <i data-feather="log-out" class="me-2" style="width: 18px; height: 18px;"></i> Return Book
            </a>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <!-- Recently Added Books -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent py-3 border-bottom">
                <h5 class="card-title mb-0">Recently Added Books</h5>
            </div>
            <div class="card-body p-0">
                <?php if (isset($recentlyAddedBooks) && !empty($recentlyAddedBooks)): ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Title</th>
                                <th>Author</th>
                                <th>Added On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentlyAddedBooks as $book): ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="<?= APP_URL ?>/public/books/view.php?id=<?= $book['id'] ?>" class="text-decoration-none">
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
                <div class="text-center p-4 text-muted">
                    <i data-feather="book" style="width: 24px; height: 24px;" class="mb-2"></i>
                    <p>No books have been added recently.</p>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent border-top text-end py-3">
                <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-sm btn-outline-primary px-3">View All Books</a>
            </div>
        </div>
    </div>
    
    <!-- Books Due for Return Today -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent py-3 border-bottom">
                <h5 class="card-title mb-0">Books Due for Return Today</h5>
            </div>
            <div class="card-body p-0">
                <?php if (isset($dueTodayLoans) && !empty($dueTodayLoans)): ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Book Title</th>
                                <th>Borrower</th>
                                <th>Due Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dueTodayLoans as $loan): ?>
                            <tr>
                                <td class="ps-4"><?= htmlspecialchars($loan['book_title']) ?></td>
                                <td><?= htmlspecialchars($loan['borrower_name']) ?></td>
                                <td><?= date('M d, Y', strtotime($loan['due_date'])) ?></td>
                                <td>
                                    <a href="<?= APP_URL ?>/public/transactions/return.php?id=<?= $loan['id'] ?>" class="btn btn-sm btn-warning d-inline-flex align-items-center">
                                        <i data-feather="check-circle" class="me-1" style="width: 14px; height: 14px;"></i> Return
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center p-4 text-muted">
                    <i data-feather="check-circle" style="width: 24px; height: 24px;" class="mb-2"></i>
                    <p>No books are due for return today.</p>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent border-top text-end py-3">
                <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-sm btn-outline-primary px-3">View All Transactions</a>
            </div>
        </div>
    </div>
</div>

<!-- Additional Stats for Super Admin -->
<?php if ($userRole == ROLE_SUPER_ADMIN): ?>
<div class="row g-4 mb-5">
    <!-- System Users -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent py-3 border-bottom">
                <h5 class="card-title mb-0">System Users</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Role</th>
                                <th>Count</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-danger me-2">SA</span>
                                        Super Admin
                                    </div>
                                </td>
                                <td><?= $stats['super_admin_count'] ?? 0 ?></td>
                                <td>
                                    <a href="<?= APP_URL ?>/public/users/index.php?role=1" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-primary me-2">A</span>
                                        Admin
                                    </div>
                                </td>
                                <td><?= $stats['admin_count'] ?? 0 ?></td>
                                <td>
                                    <a href="<?= APP_URL ?>/public/users/index.php?role=2" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-success me-2">B</span>
                                        Borrower
                                    </div>
                                </td>
                                <td><?= $stats['borrower_count'] ?? 0 ?></td>
                                <td>
                                    <a href="<?= APP_URL ?>/public/users/index.php?role=3" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center py-3">
                <span class="text-muted">Total Users: <?= ($stats['super_admin_count'] ?? 0) + ($stats['admin_count'] ?? 0) + ($stats['borrower_count'] ?? 0) ?></span>
                <a href="<?= APP_URL ?>/public/users/add.php" class="btn btn-sm btn-success d-inline-flex align-items-center">
                    <i data-feather="user-plus" class="me-1" style="width: 14px; height: 14px;"></i> Add New User
                </a>
            </div>
        </div>
    </div>
    
    <!-- Most Popular Books -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent py-3 border-bottom">
                <h5 class="card-title mb-0">Most Popular Books</h5>
            </div>
            <div class="card-body p-0">
                <?php if (isset($mostBorrowedBooks) && !empty($mostBorrowedBooks)): ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Title</th>
                                <th>Author</th>
                                <th>Times Borrowed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mostBorrowedBooks as $book): ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="<?= APP_URL ?>/public/books/view.php?id=<?= $book['id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($book['title']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($book['author']) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark"><?= $book['borrow_count'] ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center p-4 text-muted">
                    <i data-feather="bar-chart-2" style="width: 24px; height: 24px;" class="mb-2"></i>
                    <p>No borrowing data available yet.</p>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent border-top text-end py-3">
                <a href="<?= APP_URL ?>/public/reports/books.php" class="btn btn-sm btn-outline-primary px-3">View Book Reports</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Analytics Summary Card -->
<div class="card border-0 shadow-sm mb-5">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="card-title mb-0">Library Activity</h5>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-outline-secondary active">This Month</button>
                <button type="button" class="btn btn-sm btn-outline-secondary">Last Month</button>
            </div>
        </div>
        
        <!-- Simple activity placeholder -->
        <div class="p-3 bg-light rounded">
            <div class="d-flex align-items-center mb-3">
                <div class="me-3 rounded-circle bg-primary bg-opacity-10 p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i data-feather="trending-up" style="width: 18px; height: 18px;" class="text-primary"></i>
                </div>
                <div>
                    <h6 class="mb-0">Activity Summary</h6>
                    <small class="text-muted">Active borrowers have increased by 12% compared to last month</small>
                </div>
            </div>
            
            <div class="row g-3 text-center text-md-start">
                <div class="col-md-3">
                    <div class="border-start ps-3">
                        <p class="mb-0 text-muted small">New Books Added</p>
                        <h4 class="mb-0">15</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border-start ps-3">
                        <p class="mb-0 text-muted small">Books Borrowed</p>
                        <h4 class="mb-0">27</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border-start ps-3">
                        <p class="mb-0 text-muted small">Books Returned</p>
                        <h4 class="mb-0">23</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="border-start ps-3">
                        <p class="mb-0 text-muted small">New Borrowers</p>
                        <h4 class="mb-0">9</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>