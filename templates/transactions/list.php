<?php
// Transactions List Template
?>

<!-- Transactions List -->
<div class="card">
    <div class="card-body">
        <?php if (empty($transactions)): ?>
            <div class="alert alert-info">
                No transactions found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Book Title</th>
                    <?php if (!in_array($userRole, ['student', 'staff', 'other'])): ?>
                    <th>Borrower</th>
                    <?php endif; ?>
                    <th>Loan Date</th>
                    <th>Due Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
<?php
$i = 1;
?>
<?php foreach ($transactions as $transaction): ?>
    <tr>
        <td><?= htmlspecialchars($i) ?></td>
        <td><?= htmlspecialchars($transaction['book_title'] ?? '') ?></td>
        <?php if (!in_array($userRole, ['student', 'staff', 'other'])): ?>
        <td><?= htmlspecialchars($transaction['name'] ?? '') ?></td>
        <?php endif; ?>
        <td><?= htmlspecialchars($transaction['borrow_date'] ?? '') ?></td>
        <td><?= htmlspecialchars($transaction['due_date'] ?? '') ?></td>
        <td>
            <?= !empty($transaction['return_date']) ? htmlspecialchars($transaction['return_date']) : 'Not returned' ?>
        </td>
        <td>
            <?php if (($transaction['status'] ?? '') == 'overdue'): ?>
                <span class="badge bg-danger">Overdue</span>
            <?php elseif (($transaction['status'] ?? '') == 'borrowed'): ?>
                <span class="badge bg-success">Borrowed</span>
            <?php elseif (($transaction['status'] ?? '') == 'returned'): ?>
                <span class="badge bg-secondary">Returned</span>
            <?php elseif (($transaction['status'] ?? '') == 'pending_approval'): ?>
                <span class="badge bg-warning text-dark">Pending Approval</span>
            <?php elseif (($transaction['status'] ?? '') == 'pending_return_approval'): ?>
                <span class="badge bg-warning text-dark">Pending Return Approval</span>
            <?php else: ?>
                <span class="badge bg-secondary"><?= htmlspecialchars($transaction['status'] ?? '') ?></span>
            <?php endif; ?>
        </td>
        <td>
            <div class="btn-group btn-group-sm">
                <a href="<?= APP_URL ?>/public/transactions/view.php?id=<?= htmlspecialchars($transaction['id'] ?? '') ?>" class="btn btn-outline-primary">
                    <i data-feather="eye"></i>
                </a>
                <?php if (($transaction['status'] ?? '') == 'borrowed' || ($transaction['status'] ?? '') == 'overdue'): ?>
<form action="<?= $_SERVER['PHP_SELF'] ?>" method="post" class="d-inline ms-1">
    <input type="hidden" name="transaction_id" value="<?= htmlspecialchars($transaction['id'] ?? '') ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->getToken() ?? '') ?>">
    <button type="submit" name="return_book" class="btn btn-outline-success" 
            onclick="return confirm('Are you sure you want to return this book?')">
        <i data-feather="rotate-ccw"></i>
    </button>
</form>
                <?php elseif (($transaction['status'] ?? '') == 'pending_approval' || ($transaction['status'] ?? '') == 'pending_return_approval'): ?>
<form action="<?= $_SERVER['PHP_SELF'] ?>" method="post" class="d-inline ms-1">
    <input type="hidden" name="transaction_id" value="<?= htmlspecialchars($transaction['id'] ?? '') ?>">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->getToken() ?? '') ?>">
    <button type="submit" name="approve" class="btn btn-outline-success" 
            onclick="return confirm('Are you sure you want to approve this request?')">
        <i data-feather="check"></i>
    </button>
    <button type="submit" name="reject" class="btn btn-outline-danger ms-1" 
            onclick="return confirm('Are you sure you want to reject this request?')">
        <i data-feather="x"></i>
    </button>
</form>
                <?php endif; ?>
            </div>
        </td>
    </tr>
<?php
$i++;
?>
<?php endforeach; ?>
            </tbody>
        </table>
            </div>
        <?php endif; ?>
    </div>
</div>
