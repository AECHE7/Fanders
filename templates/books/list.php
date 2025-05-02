<?php
// Books List Template
?>

<!-- Books List -->
<div class="card">
    <div class="card-body">
        <?php if (empty($books)): ?>
            <div class="alert alert-info">
                No books found. <?php echo !empty($searchTerm) ? 'Try a different search term.' : ''; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Author</th>
                            <th>ISBN</th>
                            <th>Category</th>
                            <th>Copies</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                            <tr>
                                <td>
                                    <a href="<?= APP_URL ?>/public/books/view.php?id=<?= $book['id'] ?>">
                                        <?= htmlspecialchars($book['title']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($book['author']) ?></td>
                                <td><?= htmlspecialchars($book['isbn']) ?></td>
                                <td><?= htmlspecialchars($book['category_name']) ?></td>
                                <td>
                                    <?= $book['available_copies'] ?>/<?= $book['total_copies'] ?>
                                </td>
                                <td>
                                    <?php if ($book['is_available'] && $book['available_copies'] > 0): ?>
                                        <span class="badge bg-success">Available</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Not Available</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= APP_URL ?>/public/books/view.php?id=<?= $book['id'] ?>" class="btn btn-outline-primary">
                                            <i data-feather="eye"></i>
                                        </a>
                                        
                                        <?php if ($userRole == ROLE_SUPER_ADMIN || $userRole == ROLE_ADMIN): ?>
                                            <a href="<?= APP_URL ?>/public/books/edit.php?id=<?= $book['id'] ?>" class="btn btn-outline-secondary">
                                                <i data-feather="edit"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($userRole == ROLE_BORROWER && $book['is_available'] && $book['available_copies'] > 0): ?>
                                            <a href="<?= APP_URL ?>/public/transactions/borrow.php?book_id=<?= $book['id'] ?>" class="btn btn-outline-success">
                                                <i data-feather="book"></i> Borrow
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
