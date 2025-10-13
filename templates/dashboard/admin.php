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
                    <i data-feather="grid" style="width: 24px; height: 24px; color:rgb(0, 0, 0);"></i>
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
        <!-- Total Books -->
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
        
        <!-- Borrowed Books -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #E0F2FE;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #0b76ef; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="book-open" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Borrowed Books</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['borrowed_books'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Currently on loan</p>
                </div>
            </div>
        </div>
        
        <!-- Overdue Returns -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #FEF3E4;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #ec7211; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="alert-circle" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Overdue Returns</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['overdue_returns'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Past due date</p>
                </div>
            </div>
        </div>
        
        <!-- Total Penalties -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #FEE2E2;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #dc2626; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="dollar-sign" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Total Penalties</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0">₱<?= number_format($stats['total_penalties'] ?? 0) ?></p>
                    <p class="card-text text-muted mb-0 small">Unpaid penalties</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($userRole === UserModel::$ROLE_SUPER_ADMIN): ?>
<!-- User Statistics (Super Admin Only) -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">👥 User Statistics</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="row g-4">
        <!-- Total Students -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #F0FDF4;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #16a34a; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="users" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Total Students</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['total_students'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Student users</p>
                </div>
            </div>
        </div>
        
        <!-- Total Staff -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #F0F7FF;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #2563eb; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="user-check" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Total Staff</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['total_staff'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Staff users</p>
                </div>
            </div>
        </div>
        
        <!-- Total Admins -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #FDF4FF;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #9333ea; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="shield" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Total Admins</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['total_admins'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Admin users</p>
                </div>
            </div>
        </div>
        
        <!-- Total Others -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #FEF3F2;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #ea580c; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="user" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Total Others</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['total_others'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Other users</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<!-- Total Borrowers (Admin Only) -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">👥 Borrower Statistics</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="row g-4">
        <div class="col-md-12">
            <div class="p-4 rounded" style="background-color: #F0FDF4;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #16a34a; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="users" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Total Borrowers</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['total_borrowers'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Active borrowers</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent Activity -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">🔍 Recent Activity</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="row g-4">
        <!-- Recent Borrowing/Return Activity -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent py-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <div class="rounded d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; background-color: #0b76ef;">
                            <i data-feather="repeat" style="width: 14px; height: 14px; color: white;"></i>
                        </div>
                        <h5 class="card-title mb-0">Recent Transactions</h5>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (isset($recentTransactions) && !empty($recentTransactions)): ?>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Book</th>
                                    <th>Borrower</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTransactions as $transaction): ?>
                                <tr>
                                    <td class="ps-4"><?= htmlspecialchars($transaction['book_title']) ?></td>
                                    <td><?= htmlspecialchars($transaction['borrower_name']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $transaction['type'] === 'borrow' ? 'primary' : 'success' ?>">
                                            <?= ucfirst($transaction['type']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($transaction['date'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center p-4 text-muted">
                        <i data-feather="repeat" style="width: 24px; height: 24px;" class="mb-2"></i>
                        <p>No recent transactions.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
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
                                    <th class="ps-4">Title</th>
                                    <th>Author</th>
                                    <th>Added On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentlyAddedBooks as $book): ?>
                                <tr>
                                    <td class="ps-4"><?= htmlspecialchars($book['title']) ?></td>
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
                    <small class="text-muted">
                        <?= $analytics['borrower_growth_text'] ?? 'Active borrowers compared to last month' ?>
                    </small>
                </div>
            </div>
            <div class="custom-filter-tabs">
                <button type="button" class="btn btn-sm px-3 me-1 active">This Month</button>
                <button type="button" class="btn btn-sm px-3">Last Month</button>
            </div>
        </div>
        
        <div class="row g-4 mt-2">
            <?php
            $analyticMetrics = $analytics['monthly'] ?? [
                ['label' => 'New Books Added', 'value' => 0, 'bg' => '#edf2fc', 'dot' => '#0b76ef'],
                ['label' => 'Books Borrowed', 'value' => 0, 'bg' => '#f1ebfc', 'dot' => '#9d71ea'],
                ['label' => 'Books Returned', 'value' => 0, 'bg' => '#fff3e9', 'dot' => '#ec7211'],
                ['label' => 'New Borrowers', 'value' => 0, 'bg' => '#ebfef6', 'dot' => '#0ca789']
            ];
            foreach ($analyticMetrics as $metric):
            ?>
                <div class="col-md-3">
                    <div class="p-3 rounded" style="background-color: <?= htmlspecialchars($metric['bg'] ?? '#f8f9fa') ?>;">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle me-2"
                                 style="width: 8px; height: 8px; background-color: <?= htmlspecialchars($metric['dot'] ?? '#ccc') ?>;">
                            </div>
                            <p class="mb-0 small"><?= htmlspecialchars($metric['label'] ?? '-') ?></p>
                        </div>
                        <h3 class="mb-0"><?= htmlspecialchars($metric['value'] ?? 0) ?></h3>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

