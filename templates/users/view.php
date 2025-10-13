<?php
// User View Template
?>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">User Information</h5>
        <dl class="row">
            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($viewUser['name']) ?></dd>
            
            <dt class="col-sm-3">Email</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($viewUser['email']) ?></dd>
            
            <dt class="col-sm-3">Phone Number</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($viewUser['phone_number']) ?></dd>

            <dt class="col-sm-3">Role</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($viewUser['role']) ?></dd>

            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9">
                <?php
                // Define mapping of user status to badge class and label
                $userStatusMap = [
                    'active' => ['class' => 'bg-success', 'label' => 'Active'],
                    'inactive' => ['class' => 'bg-danger', 'label' => 'Inactive'],
                    1 => ['class' => 'bg-success', 'label' => 'Active'],
                    0 => ['class' => 'bg-danger', 'label' => 'Inactive'],
                    true => ['class' => 'bg-success', 'label' => 'Active'],
                    false => ['class' => 'bg-danger', 'label' => 'Inactive'],
                ];

                $statusKey = strtolower((string)$viewUser['status']);
                if (isset($userStatusMap[$statusKey])) {
                    $badgeClass = $userStatusMap[$statusKey]['class'];
                    $badgeLabel = $userStatusMap[$statusKey]['label'];
                } else {
                    // Default fallback
                    $badgeClass = 'bg-secondary';
                    $badgeLabel = 'Unknown';
                }
                ?>
                <span class="badge <?= $badgeClass ?>">
                    <?= htmlspecialchars($badgeLabel) ?>
                </span>
            </dd>
            </dd>

            <dt class="col-sm-3">Registered On</dt>
            <dd class="col-sm-9"><?= date('Y-m-d', strtotime($viewUser['created_at'])) ?></dd>
        </dl>
    </div>
</div>

   <!-- Currently Borrowed Books -->
   <?php if (!empty($activeLoans)): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Currently Borrowed Books</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Book Title</th>
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
                                <td><?= htmlspecialchars($loan['book_title']) ?></td>
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
        </div>
    </div>
    <?php endif; ?>

    <!-- Borrowing History -->
    <?php if (!empty($loanHistory)): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Borrowing History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Book Title</th>
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
                                <td><?= htmlspecialchars($loan['book_title']) ?></td>
                                <td><?= date('M d, Y', strtotime($loan['borrow_date'])) ?></td>
                                <td><?= date('M d, Y', strtotime($loan['due_date'])) ?></td>
                                <td><?= $loan['return_date'] ? date('M d, Y', strtotime($loan['return_date'])) : '-' ?></td>
                                <td><span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
 
<style>
.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,.075);
}
.table-danger {
    background-color: rgba(220,53,69,.1);
}
</style>
