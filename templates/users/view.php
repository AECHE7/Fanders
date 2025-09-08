<?php
// User View Template
?>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">User Information</h5>
        <dl class="row">
            <dt class="col-sm-3">Name</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($viewUser['username']) ?></dd>

            <dt class="col-sm-3">First Name</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($viewUser['first_name']) ?></dd>

            <dt class="col-sm-3">Last Name</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($viewUser['last_name']) ?></dd>

            <dt class="col-sm-3">Email</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($viewUser['email']) ?></dd>

            <dt class="col-sm-3">Role</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($viewUser['role_name']) ?></dd>

            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9">
                <?php if ($viewUser['is_active']): ?>
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
