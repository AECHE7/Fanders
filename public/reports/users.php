<?php
require_once '../../public/init.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . '/public/auth/login.php');
    exit;
}

// Check permissions - only super-admin and admin can view user reports
$allowedRoles = ['super-admin', 'admin'];
if (!in_array($_SESSION['role'], $allowedRoles)) {
    header('Location: ' . APP_URL . '/public/dashboard/index.php');
    exit;
}

$reportService = new ReportService();

// Get filter parameters
$filters = [
    'role' => $_GET['role'] ?? '',
    'is_active' => isset($_GET['is_active']) ? $_GET['is_active'] : '',
    'date_from' => $_GET['date_from'] ?? date('Y-m-01'),
    'date_to' => $_GET['date_to'] ?? date('Y-m-t')
];

// Convert is_active to boolean if set
if ($filters['is_active'] !== '') {
    $filters['is_active'] = $filters['is_active'] === '1';
}

// Generate user report data
$reportData = $reportService->generateUserReport($filters);

// Handle PDF export
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    try {
        // Validate report data
        if (empty($reportData) || !is_array($reportData)) {
            throw new Exception('No user data available for export. Please generate a report first.');
        }
        
        $reportService->exportUserReportPDF($reportData, $filters);
    } catch (Exception $e) {
        error_log("User PDF export error: " . $e->getMessage());
        $session->setFlash('error', 'Error exporting PDF: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/reports/users.php?' . http_build_query($filters));
        exit;
    }
    exit;
}

// Handle Excel export
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    // Clear output buffers to prevent contamination
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    try {
        // Validate report data
        if (empty($reportData) || !is_array($reportData)) {
            throw new Exception('No user data available for export. Please generate a report first.');
        }
        
        $reportService->exportUserReportExcel($reportData, $filters);
    } catch (Exception $e) {
        // Restart output buffering for error display
        ob_start();
        error_log("User Excel export error: " . $e->getMessage());
        $session->setFlash('error', 'Error exporting Excel: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/reports/users.php?' . http_build_query($filters));
        exit;
    }
    exit;
}

$pageTitle = 'User Reports';
include '../../templates/layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">User Report Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="">All Roles</option>
                                <option value="super-admin" <?= $filters['role'] === 'super-admin' ? 'selected' : '' ?>>Super Admin</option>
                                <option value="admin" <?= $filters['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="manager" <?= $filters['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
                                <option value="staff" <?= $filters['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="borrower" <?= $filters['role'] === 'borrower' ? 'selected' : '' ?>>Borrower</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="is_active" class="form-label">Status</label>
                            <select class="form-select" id="is_active" name="is_active">
                                <option value="">All Status</option>
                                <option value="1" <?= $filters['is_active'] === true ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= $filters['is_active'] === false ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="date_from" name="date_from"
                                   value="<?= $filters['date_from'] ?>">
                        </div>

                        <div class="mb-3">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="date_to" name="date_to"
                                   value="<?= $filters['date_to'] ?>">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="search" class="me-1"></i>Generate Report
                            </button>
                            <a href="?<?= http_build_query(array_merge($filters, ['export' => 'pdf'])) ?>"
                               class="btn btn-success">
                                <i data-feather="download" class="me-1"></i>Export PDF
                            </a>
                            <a href="?<?= http_build_query(array_merge($filters, ['export' => 'excel'])) ?>"
                               class="btn btn-outline-success">
                                <i data-feather="file" class="me-1"></i>Export Excel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">User Reports</h1>
                <div>
                    <small class="text-muted">
                        Period: <?= date('M d, Y', strtotime($filters['date_from'])) ?> -
                        <?= date('M d, Y', strtotime($filters['date_to'])) ?>
                    </small>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <?php
                $totalUsers = count($reportData);
                $activeUsers = count(array_filter($reportData, function($user) { return $user['is_active']; }));
                $inactiveUsers = $totalUsers - $activeUsers;
                $roleCounts = array_count_values(array_column($reportData, 'role'));
                ?>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="text-primary"><?= $totalUsers ?></h4>
                            <small class="text-muted">Total Users</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="text-success"><?= $activeUsers ?></h4>
                            <small class="text-muted">Active Users</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="text-warning"><?= $inactiveUsers ?></h4>
                            <small class="text-muted">Inactive Users</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="text-info"><?= count($roleCounts) ?></h4>
                            <small class="text-muted">Roles</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role Distribution -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Role Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($roleCounts as $role => $count): ?>
                            <div class="col-md-3 mb-3">
                                <div class="text-center">
                                    <h5 class="text-primary"><?= $count ?></h5>
                                    <small class="text-muted"><?= ucfirst($role) ?>s</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">User Details</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Last Login</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reportData)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No user data found for the selected criteria.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reportData as $user): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= APP_URL ?>/public/users/view.php?id=<?= $user['id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($user['username']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                                            <td>
                                                <a href="mailto:<?= htmlspecialchars($user['email']) ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($user['email']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?= ucfirst($user['role']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $user['is_active'] ? 'success' : 'danger' ?>">
                                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($user['last_login']): ?>
                                                    <?= date('M d, Y H:i', strtotime($user['last_login'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Never</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <a href="<?= APP_URL ?>/public/users/edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i data-feather="edit-2" class="me-1" style="width: 14px; height: 14px;"></i>Edit
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/layout/footer.php'; ?>
