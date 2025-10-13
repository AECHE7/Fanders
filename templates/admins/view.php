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
                <?php if ($viewUser['status']): ?>
                    <span class="badge bg-success">Active</span>
                <?php else: ?>
                    <span class="badge bg-danger">Inactive</span>
                <?php endif; ?>
            </dd>

            <dt class="col-sm-3">Registered On</dt>
            <dd class="col-sm-9"><?= date('Y-m-d', strtotime($viewUser['created_at'])) ?></dd>
        </dl>
    </div>
</div>

<!-- Borrowed Books Section -->
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="card-title mb-0">Borrowed Books</h5>
        </div>
        
        <?php if (!empty($borrowedBooks)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Published Year</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($borrowedBooks as $book): ?>
                            <tr class="<?= $book['status'] === 'overdue' ? 'table-danger' : '' ?>">
                                <td><?= htmlspecialchars($book['title']) ?></td>
                                <td><?= htmlspecialchars($book['author']) ?></td>
                                <td>
                                    <a href="<?= APP_URL ?>/public/categories/view.php?id=<?= $book['category_id'] ?>" 
                                       class="text-decoration-none">
                                        <?= htmlspecialchars($book['category_name']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($book['published_year']) ?></td>
                                <td><?= date('Y-m-d', strtotime($book['borrow_date'])) ?></td>
                                <td><?= date('Y-m-d', strtotime($book['due_date'])) ?></td>
                                <td>
                                    <?= $book['return_date'] ? date('Y-m-d', strtotime($book['return_date'])) : '-' ?>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = 'bg-secondary';
                                    switch($book['status']) {
                                        case 'borrowed':
                                            $statusClass = 'bg-primary';
                                            break;
                                        case 'returned':
                                            $statusClass = 'bg-success';
                                            break;
                                        case 'overdue':
                                            $statusClass = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= ucfirst(htmlspecialchars($book['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-0">
                No books have been borrowed by this user.
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,.075);
}
.table-danger {
    background-color: rgba(220,53,69,.1);
}
</style>
