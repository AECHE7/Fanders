<?php
/**
 * Admin/Super Admin dashboard template for the Library Management System
 * Notion-inspired design
 */
?>

<!-- Dashboard Header with Title, Date and Reports Links -->
<div class="notion-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <div class="me-3">
                <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0f4fd;">
                    <i data-feather="grid" style="width: 24px; height: 24px; color: #0b76ef;"></i>
                </div>
            </div>
            <h1 class="notion-page-title mb-0">Dashboard</h1>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <div class="text-muted d-none d-md-block me-3">
                <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i> 
                <?= date('l, F j, Y') ?>
            </div>
            <a href="<?= APP_URL ?>/public/reports/books.php" class="btn btn-sm btn-outline-secondary px-3">
                <i data-feather="book" class="me-1" style="width: 14px; height: 14px;"></i> Books
            </a>
            <a href="<?= APP_URL ?>/public/reports/users.php" class="btn btn-sm btn-outline-secondary px-3">
                <i data-feather="users" class="me-1" style="width: 14px; height: 14px;"></i> Users
            </a>
            <a href="<?= APP_URL ?>/public/reports/transactions.php" class="btn btn-sm btn-outline-secondary px-3">
                <i data-feather="repeat" class="me-1" style="width: 14px; height: 14px;"></i> Transactions
            </a>
        </div>
    </div>
    <div class="notion-divider my-3"></div>
</div>

<!-- Stats Overview with Color-coded Icons like Notion -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">📈 Library Statistics</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="row g-4 dashboard-stats-container">
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #F5F4FF;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #9d71ea; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="book" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Total Books</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['total_books'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Books in library</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #E0F2FE;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #0b76ef; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="book-open" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Available Books</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['available_books'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Ready to borrow</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #EBFEF6;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #0ca789; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="users" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Active Borrowers</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['active_borrowers'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Registered users</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #FEF3E4;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #ec7211; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="repeat" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Active Loans</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['active_loans'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Currently on loan</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Action Section with Notion-style colored blocks -->
<div class="mb-5 animate-on-scroll">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">📌 Quick Actions</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="row g-3 stagger-fade-in">
        <div class="col-md-3">
            <a href="<?= APP_URL ?>/public/books/add.php" class="text-decoration-none">
                <div class="card border-0 h-100" style="background-color: #f7ecff;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #9d71ea;">
                                <i data-feather="book" style="width: 20px; height: 20px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Add New Book</h6>
                                <p class="card-text small mb-0 text-muted">Create book record</p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?= APP_URL ?>/public/users/add.php" class="text-decoration-none">
                <div class="card border-0 h-100" style="background-color: #eaf8f6;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #0ca789;">
                                <i data-feather="user-plus" style="width: 20px; height: 20px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Add New User</h6>
                                <p class="card-text small mb-0 text-muted">Register borrower</p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?= APP_URL ?>/public/transactions/borrow.php" class="text-decoration-none">
                <div class="card border-0 h-100" style="background-color: #edf2fc;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #0b76ef;">
                                <i data-feather="log-in" style="width: 20px; height: 20px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Issue Book</h6>
                                <p class="card-text small mb-0 text-muted">Lend to borrower</p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="<?= APP_URL ?>/public/transactions/return.php" class="text-decoration-none">
                <div class="card border-0 h-100" style="background-color: #fff3e9;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; background-color: #ec7211;">
                                <i data-feather="check-square" style="width: 20px; height: 20px; color: white;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Return Book</h6>
                                <p class="card-text small mb-0 text-muted">Process returns</p>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<div class="mb-5 animate-on-scroll">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">🔍 Recent Activity</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="row g-4 stagger-fade-in">
    <!-- Recently Added Books -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-transparent py-3 border-bottom">
                <div class="d-flex align-items-center">
                    <div class="rounded d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; background-color: #9d71ea;">
                        <i data-feather="book" style="width: 14px; height: 14px; color: white;"></i>
                    </div>
                    <h5 class="card-title mb-0">Recently Added Books</h5>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (isset($recentlyAddedBooks) && !empty($recentlyAddedBooks)): ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" data-sort="title">Title</th>
                                <th data-sort="author">Author</th>
                                <th data-sort="date">Added On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentlyAddedBooks as $book): ?>
                            <tr>
                                <td class="ps-4" data-key="title">
                                    <a href="<?= APP_URL ?>/public/books/view.php?id=<?= $book['id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($book['title']) ?>
                                    </a>
                                </td>
                                <td data-key="author"><?= htmlspecialchars($book['author']) ?></td>
                                <td data-key="date"><?= date('M d, Y', strtotime($book['created_at'])) ?></td>
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
                <div class="d-flex align-items-center">
                    <div class="rounded d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; background-color: #ec7211;">
                        <i data-feather="calendar" style="width: 14px; height: 14px; color: white;"></i>
                    </div>
                    <h5 class="card-title mb-0">Books Due for Return Today</h5>
                </div>
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

<!-- Analytics Summary Section -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">📉 Monthly Activity</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    
    <div class="p-4 rounded" style="background-color: #f3f4f6;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <div class="rounded me-3" style="width: 32px; height: 32px; background-color: #0b76ef; display: flex; align-items: center; justify-content: center;">
                    <i data-feather="trending-up" style="width: 16px; height: 16px; color: white;"></i>
                </div>
                <div>
                    <h6 class="mb-0">Activity Summary</h6>
                    <small class="text-muted">Active borrowers have increased by 12% compared to last month</small>
                </div>
            </div>
            <div class="custom-filter-tabs">
                <button type="button" class="btn btn-sm px-3 me-1 active">This Month</button>
                <button type="button" class="btn btn-sm px-3">Last Month</button>
            </div>
        </div>
        
        <div class="row g-4 mt-2">
            <div class="col-md-3">
                <div class="p-3 rounded" style="background-color: #edf2fc;">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #0b76ef;"></div>
                        <p class="mb-0 small">New Books Added</p>
                    </div>
                    <h3 class="mb-0">15</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 rounded" style="background-color: #f1ebfc;">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #9d71ea;"></div>
                        <p class="mb-0 small">Books Borrowed</p>
                    </div>
                    <h3 class="mb-0">27</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 rounded" style="background-color: #fff3e9;">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #ec7211;"></div>
                        <p class="mb-0 small">Books Returned</p>
                    </div>
                    <h3 class="mb-0">23</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 rounded" style="background-color: #ebfef6;">
                    <div class="d-flex align-items-center mb-2">
                        <div class="rounded-circle me-2" style="width: 8px; height: 8px; background-color: #0ca789;"></div>
                        <p class="mb-0 small">New Borrowers</p>
                    </div>
                    <h3 class="mb-0">9</h3>
                </div>
            </div>
        </div>
    </div>
</div>