<?php
// Users List Template
?>

<!-- Users List -->
<div class="card">
    <div class="card-body">
        <?php if (empty($users)): ?>
            <div class="alert alert-info">
                No users found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $userItem): ?>
                            <tr>
                                <td><?= htmlspecialchars($userItem['id']) ?></td>
                                <td><?= htmlspecialchars($userItem['username']) ?></td>
                                <td><?= htmlspecialchars($userItem['email']) ?></td>
                                <td><?= htmlspecialchars($userItem['role_name']) ?></td>
                                <td>
                                    <?php if ($userItem['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/public/users/view.php?id=<?= $userItem['id'] ?>" class="btn btn-outline-primary">
                                            <i data-feather="eye"></i>
                                        </a>
                                        <?php if ($userRole == ROLE_SUPER_ADMIN): ?>
                                            <a href="/public/users/edit.php?id=<?= $userItem['id'] ?>" class="btn btn-outline-secondary">
                                                <i data-feather="edit"></i>
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
