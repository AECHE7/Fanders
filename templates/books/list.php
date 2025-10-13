<?php
// Books List Template
?>
<!-- Books List -->
<div class="card mb-3">
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
                            <th>Book ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Published Year</th>
                            <th>Copies</th>
                            <th>Stats</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        ?>
                        <?php foreach ($books as $book): ?>
                            <tr>
                            <td><?= htmlspecialchars($i) ?></td>
                            <td>
                                <a href="<?= APP_URL ?>/public/books/view.php?id=<?= $book['id'] ?>">
                                    <?= htmlspecialchars($book['title'] ?? '') ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($book['author'] ?? '') ?></td>
                            <td><?= htmlspecialchars($book['category_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($book['published_year'] ?? '-') ?></td>
                            <td>
                                <?= $book['available_copies'] ?>/<?= $book['total_copies'] ?>
                            </td>
                                <td>
                                    <?php 
                                        $status = strtolower($book['status'] ?? '');
                                        $displayStatus = ucfirst($status);
                                        if ($status === 'archived'): ?>
                                            <span class="badge" style="background-color: #ffc107; color: black;"><?= htmlspecialchars($displayStatus) ?></span>
                                        <?php elseif ($status === 'restored'): ?>
                                            <span class="badge" style="background-color: #0d6efd; color: white;"><?= htmlspecialchars($displayStatus) ?></span>
                                        <?php else: ?>
                                            <?= htmlspecialchars(ucfirst($book['status'] ?? '')) ?>
                                        <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($book['available_copies'] > 0): ?>
                                        <span class="badge bg-success">Available</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Not Available</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= APP_URL ?>/public/books/view.php?id=<?= $book['id'] ?>" class="btn btn-outline-primary me-2" title="View Details">
                                            <i data-feather="eye"></i>
                                        </a>
                                        
                                        <?php if ($userRole === 'super-admin' || $userRole === 'admin'): ?>
                                            <a href="<?= APP_URL ?>/public/books/edit.php?id=<?= $book['id'] ?>" class="btn btn-outline-secondary me-1" title="Edit Book">
                                                <i data-feather="edit"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($userRole === 'super-admin' || $userRole === 'admin'): ?>
                                            <form method="POST" action="<?= APP_URL ?>/public/books/archive.php" onsubmit="return confirm('Are you sure you want to archive this book?');" style="display:inline; margin-left: 4px;">
                                                <input type="hidden" name="id" value="<?= $book['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                                <input type="hidden" name="reason" value="out of stocks">
                                                <button type="submit" class="btn btn-outline-warning" title="Archive Book">
                                                    <i data-feather="archive"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                     
                                        
<?php if (in_array($userRole, ['student', 'staff', 'other']) && $book['available_copies'] > 0): ?>
    <a href="<?= APP_URL ?>/public/books/borrow.php?book_id=<?= $book['id'] ?>" class="btn btn-outline-success" title="Borrow Book">
        <i data-feather="book"></i>
    </a>
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
