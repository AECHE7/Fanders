<?php
/**
 * Borrower dashboard template for the Library Management System
 * Notion-inspired design
 */
?>

<!-- Dashboard Header with Title, Date and Quick Actions -->
<div class="notion-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <div class="me-3">
                <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0f4fd;">
                    <i data-feather="book-open" style="width: 24px; height: 24px; color: #0b76ef;"></i>
                </div>
            </div>
            <h1 class="notion-page-title mb-0">Dashboard</h1>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <div class="text-muted d-none d-md-block me-3">
                <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i> 
                <?= date('l, F j, Y') ?>
            </div>
            <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-sm btn-outline-secondary px-3">
                <i data-feather="book" class="me-1" style="width: 14px; height: 14px;"></i> Browse Books
            </a>
            <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-sm btn-outline-secondary px-3">
                <i data-feather="list" class="me-1" style="width: 14px; height: 14px;"></i> My Loans
            </a>
        </div>
    </div>
    <div class="notion-divider my-3"></div>
</div>

<!-- Stats Overview with Color-coded Icons like Notion -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">📊 My Library Statistics</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="row g-4 dashboard-stats-container">
        <!-- Total Books Borrowed -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #F5F4FF;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #9d71ea; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="book" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Books Borrowed</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['total_borrowed'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Total borrowed</p>
                </div>
            </div>
        </div>
        
        <!-- Currently Borrowed -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #E0F2FE;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #0b76ef; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="book-open" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Currently Borrowed</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['current_borrowed'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">In possession</p>
                </div>
            </div>
        </div>
        
        <!-- Overdue Books -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #FEF3E4;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #ec7211; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="alert-circle" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Overdue Books</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0"><?= $stats['overdue_count'] ?? 0 ?></p>
                    <p class="card-text text-muted mb-0 small">Past due date</p>
                </div>
            </div>
        </div>
        
        <!-- Penalties Due -->
        <div class="col-md-3">
            <div class="p-4 rounded" style="background-color: #FEE2E2;">
                <div class="d-flex mb-3 align-items-center">
                    <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #dc2626; display: flex; align-items: center; justify-content: center;">
                        <i data-feather="dollar-sign" style="width: 20px; height: 20px; color: white;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">Penalties Due</h6>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <p class="stat-value display-5 fw-bold mb-0">₱<?= number_format($stats['total_penalties'] ?? 0) ?></p>
                    <p class="card-text text-muted mb-0 small">Unpaid fees</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Currently Borrowed Books -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">📚 Currently Borrowed Books</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (isset($stats['current_borrowed']) && !empty($stats['current_borrowed'])): ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Book Title</th>
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
                                $daysLeft = $interval->format("%r%a");
                                
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
                                <td class="ps-4"><?= htmlspecialchars($loan['book_title']) ?></td>
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
            <div class="text-center p-4 text-muted">
                <i data-feather="book-open" style="width: 24px; height: 24px;" class="mb-2"></i>
                <p>You don't have any books borrowed at the moment.</p>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center py-3">
            <span class="text-muted small">Borrow Books Now</span>
            <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-primary btn-sm px-3">
                <i data-feather="search" class="me-1" style="width: 14px; height: 14px;"></i> Browse Books
            </a>
        </div>
    </div>
</div>

<!-- Available Books -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">📖 Available Books</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php 
                // Debug output for available books count
                echo '<div class="alert alert-info">Available books count: ' . count($availableBooks) . '</div>';
                $books = $availableBooks;
                $userRole = isset($user['role']) ? $user['role'] : null;
                include BASE_PATH . '/templates/books/list.php'; 
            ?>
        </div>
        <div class="card-footer bg-transparent border-top text-end py-3">
            <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-primary btn-sm px-3">View All Books</a>
        </div>
    </div>
</div>

<!-- Borrowing History -->
<div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">📜 Borrowing History</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (isset($loanHistory) && !empty($loanHistory)): ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Book Title</th>
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
                                <td class="ps-4"><?= htmlspecialchars($loan['book_title']) ?></td>
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
            <div class="text-center p-4 text-muted">
                <i data-feather="clock" style="width: 24px; height: 24px;" class="mb-2"></i>
                <p>No borrowing history available.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Reading Stats Card -->
<!-- <div class="mb-5">
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 me-2">📈 Reading Statistics</h5>
        <div class="notion-divider flex-grow-1"></div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 36px; height: 36px;">
                            <i data-feather="book" style="width: 18px; height: 18px;"></i>
                        </div>
                        <div>
                            <div class="small text-muted">Books Read</div>
                            <div class="fw-bold"><?= $stats['total_borrowed'] ?? 0 ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 36px; height: 36px;">
                            <i data-feather="calendar" style="width: 18px; height: 18px;"></i>
                        </div>
                        <div>
                            <div class="small text-muted">Avg. Borrow Time</div>
                            <div class="fw-bold">14 days</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 36px; height: 36px;">
                            <i data-feather="trending-up" style="width: 18px; height: 18px;"></i>
                        </div>
                        <div>
                            <div class="small text-muted">Reading Streak</div>
                            <div class="fw-bold">2 months</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> -->