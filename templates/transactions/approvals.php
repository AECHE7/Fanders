<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Pending Borrow and Return Requests</h1>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error ?? '') ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success ?? '') ?></div>
    <?php endif; ?>

    <?php if (empty($pendingTransactions)): ?>
        <p>No pending requests at this time.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Book Title</th>
                        <th>Borrower</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingTransactions as $transaction): ?>
                        <tr>
                            <td><?= htmlspecialchars($transaction['id'] ?? '') ?></td>
                            <td><?= htmlspecialchars($transaction['book_title'] ?? '') ?></td>
                            <td><?= htmlspecialchars($transaction['borrower_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($transaction['borrow_date'] ?? '') ?></td>
                            <td><?= htmlspecialchars($transaction['due_date'] ?? '') ?></td>
                            <td>
                                <?php if (($transaction['status'] ?? '') == 'borrowing'): ?>
                                    <span class="badge bg-warning text-dark">Borrowing</span>
                                <?php elseif (($transaction['status'] ?? '') == 'returning'): ?>
                                    <span class="badge bg-warning text-dark">Returning</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($transaction['status'] ?? '') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post" style="display:inline;">
                                    <input type="hidden" name="transaction_id" value="<?= htmlspecialchars($transaction['id'] ?? '') ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->getToken() ?? '') ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to record this transaction?')">Record</button>
                                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to reject this transaction?')">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
