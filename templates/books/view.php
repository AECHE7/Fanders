<?php
/**
 * Book Details View
 * Expects: $book (array), $transactions (array)
 */
?>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Book Details</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                <h6 class="card-subtitle mb-3 text-muted">
                    Author: <?= htmlspecialchars($book['author']) ?>
                </h6>
                <dl class="row">
                    <dt class="col-sm-4">Book ID</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($book['id']) ?></dd>

                    <dt class="col-sm-4">Category</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($book['category_name'] ?? '-') ?></dd>

                    <dt class="col-sm-4">Publication Year</dt>
                    <dd class="col-sm-8">
                        <?php
                            $year = $book['publication_year'] ?? $book['published_year'] ?? '';
                            echo htmlspecialchars($year !== '' && $year !== null ? (string)$year : '-');
                        ?>
                    </dd>

                    <dt class="col-sm-4">Total Copies</dt>
                    <dd class="col-sm-8"><?= isset($book['total_copies']) ? (int)$book['total_copies'] : '-' ?></dd>

                    <dt class="col-sm-4">Available Copies</dt>
                    <dd class="col-sm-8">
                        <?php if (isset($book['available_copies'])): ?>
                            <span class="badge <?= $book['available_copies'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                <?= (int)$book['available_copies'] ?>
                            </span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </dd>
                </dl>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Stats</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <h6>Total Borrows</h6>
                                <h3><?= is_array($transactions) ? count($transactions) : 0 ?></h3>
                            </div>
                            <div class="col-6 mb-3">
                                <h6>Current Borrows</h6>
                                <h3><?= is_array($transactions) ? count(array_filter($transactions, function($t) { return empty($t['return_date']); })) : 0 ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($transactions)): ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Borrowing History</h5>
            <?php if ($userRole === 'super-admin' || $userRole === 'admin'): ?>
            <form action="<?= APP_URL ?>/public/reports/book_borrowing_history.php" method="get" target="_blank" class="mb-0">
                <input type="hidden" name="book_id" value="<?= htmlspecialchars($book['id']) ?>">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i data-feather="file-text"></i> Generate Borrowing History Report
                </button>
            </form>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Borrower Name</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($transactions as $trx): ?>
                        <tr>
                            <td><?= htmlspecialchars($trx['name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($trx['borrow_date'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($trx['due_date'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($trx['return_date'] ?? '-') ?></td>
                            <td>
                                <?php
                                    $status = 'Borrowed';
                                    $badgeClass = 'bg-warning text-dark';
                                    
                                    if (!empty($trx['return_date'])) {
                                        $status = 'Returned';
                                        $badgeClass = 'bg-success';
                                    } elseif (!empty($trx['due_date']) && strtotime($trx['due_date']) < time()) {
                                        $status = 'Overdue';
                                        $badgeClass = 'bg-danger';
                                    }
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $status ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">
        No borrowing history available for this book.
    </div>
<?php endif; ?>
