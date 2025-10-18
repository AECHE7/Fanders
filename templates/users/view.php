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

    <!-- Staff Activity Summary -->
    <?php if (!empty($staffStats)): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Staff Activity Summary</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <h4 class="text-primary"><?= $staffStats['loans_processed'] ?? 0 ?></h4>
                        <small class="text-muted">Loans Processed</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h4 class="text-success"><?= $staffStats['payments_recorded'] ?? 0 ?></h4>
                        <small class="text-muted">Payments Recorded</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h4 class="text-info"><?= $staffStats['clients_served'] ?? 0 ?></h4>
                        <small class="text-muted">Clients Served</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h4 class="text-warning"><?= $staffStats['active_loans'] ?? 0 ?></h4>
                        <small class="text-muted">Active Loans</small>
                    </div>
                </div>
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
