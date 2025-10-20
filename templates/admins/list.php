<?php
// Staff Users List Template for Fanders Microfinance System
?>

<!-- Users List -->
<div class="card">
    <div class="card-body">
        <?php if (empty($users) || !is_array($users)): ?>
            <div class="alert alert-info">
                No staff users found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        ?>
                        <?php foreach ($users as $user): ?>
                            <?php if (is_array($user)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($i) ?></td>
                                    <td><?= htmlspecialchars($user['name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($user['email'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($user['phone_number'] ?? '') ?></td>
                                    <td>
                                        <span class="badge bg-<?= getRoleBadgeClass($user['role'] ?? '') ?>">
                                            <?= htmlspecialchars(ucfirst($user['role'] ?? '')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= getStatusBadgeClass($user['status'] ?? '') ?>">
                                            <?= htmlspecialchars(ucfirst($user['status'] ?? '')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?= APP_URL ?>/public/admins/view.php?id=<?= htmlspecialchars($user['id'] ?? '') ?>"
                                               class="btn btn-sm btn-outline-primary"
                                               title="View Staff User">
                                                <i data-feather="eye"></i>
                                            </a>
                                            <?php if ($userRole === 'super-admin' || ($userRole === 'admin' && in_array($user['role'], ['account_officer', 'cashier']))): ?>
                                                <a href="<?= APP_URL ?>/public/admins/edit.php?id=<?= htmlspecialchars($user['id'] ?? '') ?>"
                                                   class="btn btn-sm btn-outline-secondary"
                                                   title="Edit Staff User">
                                                    <i data-feather="edit-2"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                $i++;
                                ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-muted">
            <?= $pagination->getInfo() ?>
        </div>
        <nav aria-label="Staff users pagination">
            <?= $pagination->render() ?>
        </nav>
    </div>
<?php endif; ?>

<?php
/**
 * Get badge class for role
 * 
 * @param string $role
 * @return string
 */
function getRoleBadgeClass($role) {
    switch($role) {
        case 'super-admin':
            return 'danger';
        case 'admin':
            return 'warning';
        case 'manager':
            return 'info';
        case 'account_officer':
            return 'primary';
        case 'cashier':
            return 'success';
        default:
            return 'secondary';
    }
}

/**
 * Get badge class for status
 * 
 * @param string $status
 * @return string
 */
function getStatusBadgeClass($status) {
    switch($status) {
        case 'active':
            return 'success';
        case 'inactive':
            return 'secondary';
        case 'suspended':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>
