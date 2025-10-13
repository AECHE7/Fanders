<?php
/**
 * Transaction Details View
 */
?>


<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Transaction Details</h4>
        <div class="btn-group">
            <?php if (isset($transaction['id']) && ($userRole === 'super-admin' || $userRole === 'admin')): ?>
                <a href="<?= APP_URL ?>/public/transactions/export.php?id=<?= $transaction['id'] ?>" class="btn btn-primary">
                    <i data-feather="file-text"></i> Export to PDF
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="row mb-3">
            <div class="col-md-4 fw-bold">Book Title:</div>
            <div class="col-md-8"><?php echo htmlspecialchars($transaction['book_title']); ?></div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4 fw-bold">Author:</div>
            <div class="col-md-8"><?php echo htmlspecialchars($transaction['author']); ?></div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4 fw-bold">Borrower:</div>
            <div class="col-md-8"><?php echo htmlspecialchars($transaction['name']); ?></div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4 fw-bold">Borrow Date:</div>
            <div class="col-md-8"><?php echo date('F j, Y', strtotime($transaction['borrow_date'])); ?></div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4 fw-bold">Due Date:</div>
            <div class="col-md-8"><?php echo date('F j, Y', strtotime($transaction['due_date'])); ?></div>
        </div>

        <?php if ($transaction['return_date']): ?>
            <div class="row mb-3">
                <div class="col-md-4 fw-bold">Return Date:</div>
                <div class="col-md-8"><?php echo date('F j, Y', strtotime($transaction['return_date'])); ?></div>
            </div>
        <?php endif; ?>

        <div class="row mb-3">
            <div class="col-md-4 fw-bold">Status:</div>
            <div class="col-md-8">
                <?php
                $statusClass = '';
                $statusMessage = '';
                switch ($transaction['status']) {
                    case 'pending_approval':
                        $statusClass = 'text-warning';
                        $statusMessage = 'Pending admin approval for borrowing';
                        break;
                    case 'borrowing':
                        $statusClass = 'text-warning';
                        $statusMessage = 'Borrow request is being processed';
                        break;
                    case 'borrowed':
                        $statusClass = 'text-primary';
                        $statusMessage = 'Book currently borrowed';
                        break;
                    case 'returning':
                        $statusClass = 'text-warning';
                        $statusMessage = 'Return request pending admin approval';
                        break;
                    case 'returned':
                        $statusClass = 'text-success';
                        $statusMessage = 'Book returned';
                        break;
                    case 'overdue':
                        $statusClass = 'text-danger';
                        $statusMessage = 'Book overdue';
                        break;
                    default:
                        $statusClass = '';
                        $statusMessage = ucfirst($transaction['status']);
                        break;
                }
                ?>
                <span class="<?php echo $statusClass; ?>">
                    <?php echo $statusMessage; ?>
                </span>
            </div>
        </div>

        <?php if ($transaction['status'] === 'borrowed'): ?>
            <div class="row mb-3">
                <div class="col-md-4 fw-bold">Days Remaining:</div>
                <div class="col-md-8">
                    <?php
                    $daysRemaining = $transaction['days_remaining'];
                    if ($daysRemaining < 0) {
                        echo '<span class="text-danger">' . abs($daysRemaining) . ' days overdue</span>';
                    } else {
                        echo '<span class="text-primary">' . $daysRemaining . ' days</span>';
                    }
                    ?>
                </div>
            </div>
            <?php if ($userRole === 'borrower' && $transaction['user_id'] === $user['id']): ?>
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Actions:</div>
                    <div class="col-md-8">
                        <a href="<?= APP_URL ?>/public/transactions/return.php?transaction_id=<?= $transaction['id'] ?>" class="btn btn-warning">
                            Request Return
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
