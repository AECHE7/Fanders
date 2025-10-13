<?php
/**
 * Template for displaying archived books
 */
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Archived Books</h5>
    </div>
<div class="card-body">
    <?php if (empty($archivedBooks)): ?>
        <p class="text-muted">No archived books found.</p>
    <?php else: ?>
        <!-- Bulk Actions -->
        <?php if ($userRole === 'super-admin' || $userRole === 'admin'): ?>
        <form action="<?= APP_URL ?>/public/books/bulk_restore.php" method="post" class="mb-3">
            <?= $csrf->getTokenField() ?>
            <div class="btn-group">
                <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to restore selected books?')">
                    <i data-feather="refresh-cw"></i> Restore Selected
                </button>
            </div>
            <?php endif; ?>

            <?php if ($userRole === 'super-admin' ): ?>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#bulkDeleteModal">
                    <i data-feather="trash-2"></i> Delete Selected
                </button>
        <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <?php if ($userRole === 'super-admin' || $userRole === 'admin'): ?>
                            <th>
                                <input type="checkbox" class="form-check-input" id="selectAll">
                            </th>
                            <?php endif; ?>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Date Archived</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($archivedBooks as $book): ?>
                            <tr>
                                <?php if ($userRole === 'super-admin' || $userRole === 'admin'): ?>
                                <td>
                                    <input type="checkbox" class="form-check-input book-select" name="book_ids[]" value="<?= $book['id'] ?>">
                                </td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($book['title']) ?></td>
                                <td><?= htmlspecialchars($book['author']) ?></td>
                                <td><?= !empty($book['deleted_at']) ? date('Y-m-d', strtotime($book['deleted_at'])) : '' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Bulk Delete Modal -->
<div class="modal fade" id="bulkDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Bulk Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to permanently delete the selected books? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="<?= APP_URL ?>/public/books/bulk_delete.php" method="post">
                    <?= $csrf->getTokenField() ?>
                    <input type="hidden" name="book_ids" id="bulkDeleteIds">
                    <button type="submit" class="btn btn-danger">Delete Selected</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all functionality
    const selectAll = document.getElementById('selectAll');
    const bookSelects = document.querySelectorAll('.book-select');
    
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            bookSelects.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    // Bulk delete functionality
    const bulkDeleteModal = document.getElementById('bulkDeleteModal');
    if (bulkDeleteModal) {
        bulkDeleteModal.addEventListener('show.bs.modal', function() {
            const selectedIds = Array.from(bookSelects)
                .filter(checkbox => checkbox.checked)
                .map(checkbox => checkbox.value);
            document.getElementById('bulkDeleteIds').value = JSON.stringify(selectedIds);
        });
    }
});
</script> 