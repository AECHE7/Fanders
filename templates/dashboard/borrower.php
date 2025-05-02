<?php
/**
 * Borrower dashboard template for the Library Management System
 * Notion-inspired design
 */
?>

<!-- Dashboard Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="notion-page-title mb-0">Dashboard</h1>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-sm btn-outline-secondary px-3">Available Books</a>
        <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-sm btn-outline-secondary px-3">My Loans</a>
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
                    <h6 class="card-subtitle text-muted mb-0">Books Borrowed</h6>
                </div>
                <p class="stat-value mb-0"><?= $stats['total_borrowed'] ?? 0 ?></p>
                <p class="card-text text-muted small">Total borrowed books</p>
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
                    <h6 class="card-subtitle text-muted mb-0">Currently Borrowed</h6>
                </div>
                <p class="stat-value mb-0"><?= $stats['current_borrowed'] ?? 0 ?></p>
                <p class="card-text text-muted small">Books currently in possession</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i data-feather="alert-circle" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h6 class="card-subtitle text-muted mb-0">Overdue Books</h6>
                </div>
                <p class="stat-value mb-0"><?= $stats['overdue_count'] ?? 0 ?></p>
                <p class="card-text text-muted small">Books past due date</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        <i data-feather="dollar-sign" style="width: 20px; height: 20px;"></i>
                    </div>
                    <h6 class="card-subtitle text-muted mb-0">Penalties Due</h6>
                </div>
                <p class="stat-value mb-0">₱<?= number_format($stats['total_penalties'] ?? 0, 2) ?></p>
                <p class="card-text text-muted small">Unpaid penalty fees</p>
            </div>
        </div>
    </div>
</div>

<!-- Currently Borrowed Books -->
<div class="card border-0 shadow-sm mb-5">
    <div class="card-header bg-transparent py-3 border-bottom">
        <h5 class="card-title mb-0">Currently Borrowed Books</h5>
    </div>
    <div class="card-body p-0">
        <?php if (isset($activeLoans) && !empty($activeLoans)): ?>
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
        <span class="text-muted small">You can borrow up to <?= BORROWER_MAX_BOOKS ?> books at a time</span>
        <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-primary btn-sm px-3">
            <i data-feather="search" class="me-1" style="width: 14px; height: 14px;"></i> Browse Books
        </a>
    </div>
</div>

<!-- Unpaid Penalties -->
<?php if (isset($penalties) && !empty($penalties)): ?>
<div class="card border-0 shadow-sm mb-5">
    <div class="card-header bg-transparent py-3 border-bottom d-flex align-items-center">
        <i data-feather="alert-triangle" class="text-warning me-2" style="width: 18px; height: 18px;"></i>
        <h5 class="card-title mb-0">Unpaid Penalties</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Book Title</th>
                        <th>Return Date</th>
                        <th>Days Overdue</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($penalties as $penalty): ?>
                        <tr>
                            <td class="ps-4"><?= htmlspecialchars($penalty['book_title']) ?></td>
                            <td><?= $penalty['return_date'] ? date('M d, Y', strtotime($penalty['return_date'])) : 'Not returned' ?></td>
                            <td><?= $penalty['days_overdue'] ?></td>
                            <td>₱<?= number_format($penalty['amount'], 2) ?></td>
                            <td><span class="badge bg-danger">Unpaid</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-transparent p-4 border-top">
        <div class="notion-callout">
            <div class="notion-callout-icon">
                <i data-feather="info"></i>
            </div>
            <div>
                <strong>Penalty Information</strong>
                <p class="mb-2 mt-1">Penalties are calculated as follows:</p>
                <ul class="mb-2">
                    <li>Base penalty: ₱<?= PENALTY_BASE_AMOUNT ?> for each overdue book</li>
                    <li>Daily increment: ₱<?= PENALTY_DAILY_INCREMENT ?> for each day a book is overdue</li>
                </ul>
                <p class="mb-0">Please settle your penalties at the library counter.</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Borrowing History -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent py-3 border-bottom">
        <h5 class="card-title mb-0">Recent Borrowing History</h5>
    </div>
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
            <p>You haven't borrowed any books yet.</p>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-footer bg-transparent border-top text-end py-3">
        <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-sm btn-outline-primary px-3">
            <i data-feather="list" class="me-1" style="width: 14px; height: 14px;"></i> View All History
        </a>
    </div>
</div>

<!-- Reading Stats Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <h6 class="card-subtitle mb-3 text-muted">Reading Stats</h6>
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