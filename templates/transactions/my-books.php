<?php
/**
 * User's Borrowed Books View
 */
require_once '../templates/layout/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">My Borrowed Books</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <?php if (empty($transactions)): ?>
                        <div class="alert alert-info">
                            You haven't borrowed any books yet.
                            <a href="/books" class="alert-link">Browse our collection</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Book Title</th>
                                        <th>Author</th>
                                        <th>Borrow Date</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Days Remaining</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($transaction['book_title']); ?></td>
                                            <td><?php echo htmlspecialchars($transaction['author']); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($transaction['borrow_date'])); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($transaction['due_date'])); ?></td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                switch ($transaction['status']) {
                                                    case 'borrowed':
                                                        $statusClass = 'text-primary';
                                                        break;
                                                    case 'returned':
                                                        $statusClass = 'text-success';
                                                        break;
                                                    case 'overdue':
                                                        $statusClass = 'text-danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="<?php echo $statusClass; ?>">
                                                    <?php echo ucfirst($transaction['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $daysRemaining = $transaction['days_remaining'];
                                                if ($daysRemaining < 0) {
                                                    echo '<span class="text-danger">' . abs($daysRemaining) . ' days overdue</span>';
                                                } else {
                                                    echo '<span class="text-primary">' . $daysRemaining . ' days</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <a href="/transactions/view/<?php echo $transaction['id']; ?>" class="btn btn-sm btn-info">View</a>
                                                <?php if ($transaction['status'] === 'borrowed'): ?>
                                                    <a href="/transactions/return/<?php echo $transaction['id']; ?>" class="btn btn-sm btn-success">Return</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../templates/layout/footer.php'; ?> 