<?php
/**
 * SLR Access Log & Audit Trail
 * 
 * Shows detailed access history for all SLR documents including:
 * - Who accessed documents
 * - When they were accessed
 * - What action was performed (generation, download, archive)
 * - IP addresses and user agents
 */

require_once '../init.php';
$auth->checkRoleAccess(['super-admin', 'admin', 'manager']);

// Get filters
$filters = [
    'document_id' => isset($_GET['document_id']) ? (int)$_GET['document_id'] : null,
    'access_type' => $_GET['access_type'] ?? '',
    'user_id' => isset($_GET['user_id']) ? (int)$_GET['user_id'] : null,
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
];

// Remove empty filters
$filters = array_filter($filters, function($value) {
    return $value !== '' && $value !== null;
});

// Get pagination
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build query
$database = Database::getInstance();
$db = $database->getConnection();

$sql = "SELECT 
            sal.id,
            sal.slr_document_id,
            sal.access_type,
            sal.accessed_at,
            sal.access_reason,
            sal.ip_address,
            sal.user_agent,
            sal.success,
            sal.error_message,
            u.name as user_name,
            u.role as user_role,
            s.document_number,
            l.id as loan_id,
            c.name as client_name
        FROM slr_access_log sal
        LEFT JOIN users u ON sal.accessed_by = u.id
        LEFT JOIN slr_documents s ON sal.slr_document_id = s.id
        LEFT JOIN loans l ON s.loan_id = l.id
        LEFT JOIN clients c ON l.client_id = c.id";

$conditions = [];
$params = [];

if (!empty($filters['document_id'])) {
    $conditions[] = 'sal.slr_document_id = ?';
    $params[] = $filters['document_id'];
}

if (!empty($filters['access_type'])) {
    $conditions[] = 'sal.access_type = ?';
    $params[] = $filters['access_type'];
}

if (!empty($filters['user_id'])) {
    $conditions[] = 'sal.accessed_by = ?';
    $params[] = $filters['user_id'];
}

if (!empty($filters['date_from'])) {
    $conditions[] = 'DATE(sal.accessed_at) >= ?';
    $params[] = $filters['date_from'];
}

if (!empty($filters['date_to'])) {
    $conditions[] = 'DATE(sal.accessed_at) <= ?';
    $params[] = $filters['date_to'];
}

if (!empty($conditions)) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY sal.accessed_at DESC LIMIT ? OFFSET ?';
$params[] = $limit;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$accessLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total
             FROM slr_access_log sal
             LEFT JOIN slr_documents s ON sal.slr_document_id = s.id
             LEFT JOIN loans l ON s.loan_id = l.id";

if (!empty($conditions)) {
    $countSql .= ' WHERE ' . implode(' AND ', $conditions);
}

$countParams = array_slice($params, 0, -2); // Remove limit and offset
$countStmt = $db->prepare($countSql);
$countStmt->execute($countParams);
$totalLogs = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $limit);

// Get statistics (using same conditions and params)
$statsSql = "SELECT 
                COUNT(*) as total_accesses,
                COUNT(DISTINCT slr_document_id) as unique_documents,
                COUNT(DISTINCT accessed_by) as unique_users,
                SUM(CASE WHEN access_type = 'generation' THEN 1 ELSE 0 END) as total_generations,
                SUM(CASE WHEN access_type = 'download' THEN 1 ELSE 0 END) as total_downloads,
                SUM(CASE WHEN access_type = 'archive' THEN 1 ELSE 0 END) as total_archives
             FROM slr_access_log sal
             LEFT JOIN slr_documents s ON sal.slr_document_id = s.id
             LEFT JOIN loans l ON s.loan_id = l.id";

if (!empty($conditions)) {
    $statsSql .= ' WHERE ' . implode(' AND ', $conditions);
}

$statsStmt = $db->prepare($statsSql);
$statsStmt->execute($countParams);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get all users for filter dropdown
$usersStmt = $db->query("SELECT id, name, role FROM users WHERE role IN ('super-admin', 'admin', 'manager', 'cashier') ORDER BY name");
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'SLR Access Log & Audit Trail';
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="notion-page-header mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <div class="me-3">
                        <div class="page-icon rounded d-flex align-items-center justify-content-center" 
                             style="width: 50px; height: 50px; background-color: #FEF3C7;">
                            <i data-feather="activity" style="width:24px;height:24px;color:#f59e0b;"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="notion-page-title mb-0">SLR Access Log</h1>
                        <div class="text-muted small">
                            Complete audit trail of all SLR document access
                        </div>
                    </div>
                </div>
                <div>
                    <a href="<?= APP_URL ?>/public/slr/index.php" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="arrow-left" style="width: 14px; height: 14px;" class="me-1"></i> SLR Dashboard
                    </a>
                </div>
            </div>
            <div class="notion-divider my-3"></div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-2">
                <div class="p-3 rounded shadow-sm" style="background-color: #E0F2FE;">
                    <div class="text-muted small mb-1">Total Access</div>
                    <div class="h4 mb-0"><?= number_format($stats['total_accesses']) ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="p-3 rounded shadow-sm" style="background-color: #D1FAE5;">
                    <div class="text-muted small mb-1">Generations</div>
                    <div class="h4 mb-0"><?= number_format($stats['total_generations']) ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="p-3 rounded shadow-sm" style="background-color: #E9D5FF;">
                    <div class="text-muted small mb-1">Downloads</div>
                    <div class="h4 mb-0"><?= number_format($stats['total_downloads']) ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="p-3 rounded shadow-sm" style="background-color: #FEF3C7;">
                    <div class="text-muted small mb-1">Archives</div>
                    <div class="h4 mb-0"><?= number_format($stats['total_archives']) ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="p-3 rounded shadow-sm" style="background-color: #DBEAFE;">
                    <div class="text-muted small mb-1">Documents</div>
                    <div class="h4 mb-0"><?= number_format($stats['unique_documents']) ?></div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="p-3 rounded shadow-sm" style="background-color: #FCE7F3;">
                    <div class="text-muted small mb-1">Users</div>
                    <div class="h4 mb-0"><?= number_format($stats['unique_users']) ?></div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <i data-feather="filter" style="width: 18px; height: 18px;" class="me-2"></i>
                    <strong>Filter Access Logs</strong>
                </div>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label small">Document ID</label>
                        <input type="number" class="form-control form-control-sm" name="document_id" 
                               value="<?= htmlspecialchars($_GET['document_id'] ?? '') ?>" placeholder="SLR ID">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Access Type</label>
                        <select class="form-select form-select-sm" name="access_type">
                            <option value="">All Types</option>
                            <option value="generation" <?= ($_GET['access_type'] ?? '') === 'generation' ? 'selected' : '' ?>>Generation</option>
                            <option value="download" <?= ($_GET['access_type'] ?? '') === 'download' ? 'selected' : '' ?>>Download</option>
                            <option value="view" <?= ($_GET['access_type'] ?? '') === 'view' ? 'selected' : '' ?>>View</option>
                            <option value="archive" <?= ($_GET['access_type'] ?? '') === 'archive' ? 'selected' : '' ?>>Archive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">User</label>
                        <select class="form-select form-select-sm" name="user_id">
                            <option value="">All Users</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= ($_GET['user_id'] ?? '') == $u['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['name']) ?> (<?= $u['role'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Date From</label>
                        <input type="date" class="form-control form-control-sm" name="date_from" 
                               value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Date To</label>
                        <input type="date" class="form-control form-control-sm" name="date_to" 
                               value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-fill">
                            <i data-feather="search" style="width: 12px; height: 12px;"></i> Filter
                        </button>
                        <a href="access-log.php" class="btn btn-sm btn-secondary">
                            <i data-feather="x" style="width: 12px; height: 12px;"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Access Logs Table -->
        <div class="card shadow-sm">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i data-feather="list" style="width: 18px; height: 18px;" class="me-2"></i>
                        <strong>Access History</strong>
                        <span class="badge bg-secondary ms-2"><?= number_format($totalLogs) ?> total</span>
                    </div>
                    <div class="text-muted small">
                        Showing <?= count($accessLogs) ?> of <?= number_format($totalLogs) ?> records
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($accessLogs)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 140px;">Date/Time</th>
                                    <th>Action</th>
                                    <th>Document</th>
                                    <th>Client/Loan</th>
                                    <th>User</th>
                                    <th>IP Address</th>
                                    <th>Reason</th>
                                    <th style="width: 60px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($accessLogs as $log): ?>
                                    <tr>
                                        <td class="small text-nowrap">
                                            <?= date('M d, Y', strtotime($log['accessed_at'])) ?><br>
                                            <span class="text-muted"><?= date('h:i A', strtotime($log['accessed_at'])) ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $actionBadges = [
                                                'generation' => 'bg-success',
                                                'download' => 'bg-primary',
                                                'view' => 'bg-info',
                                                'archive' => 'bg-warning'
                                            ];
                                            $badgeClass = $actionBadges[$log['access_type']] ?? 'bg-secondary';
                                            ?>
                                            <span class="badge <?= $badgeClass ?>">
                                                <?= ucfirst($log['access_type']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($log['document_number']): ?>
                                                <code class="small"><?= htmlspecialchars($log['document_number']) ?></code><br>
                                                <span class="text-muted small">ID: <?= $log['slr_document_id'] ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($log['client_name']): ?>
                                                <div class="small">
                                                    <?= htmlspecialchars($log['client_name']) ?>
                                                </div>
                                                <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $log['loan_id'] ?>" 
                                                   class="text-muted small text-decoration-none">
                                                    Loan #<?= $log['loan_id'] ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="small"><?= htmlspecialchars($log['user_name'] ?? 'Unknown') ?></div>
                                            <span class="badge bg-light text-dark small"><?= htmlspecialchars($log['user_role'] ?? 'N/A') ?></span>
                                        </td>
                                        <td class="small text-muted">
                                            <?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?>
                                        </td>
                                        <td class="small">
                                            <?php if ($log['access_reason']): ?>
                                                <span title="<?= htmlspecialchars($log['access_reason']) ?>">
                                                    <?= substr(htmlspecialchars($log['access_reason']), 0, 30) ?>
                                                    <?= strlen($log['access_reason']) > 30 ? '...' : '' ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($log['success'] !== false && empty($log['error_message'])): ?>
                                                <i data-feather="check-circle" style="width: 16px; height: 16px; color: #10b981;"></i>
                                            <?php else: ?>
                                                <i data-feather="x-circle" style="width: 16px; height: 16px; color: #ef4444;" 
                                                   title="<?= htmlspecialchars($log['error_message'] ?? 'Failed') ?>"></i>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    Page <?= $page ?> of <?= $totalPages ?>
                                </div>
                                <nav>
                                    <ul class="pagination pagination-sm mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                                    Previous
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php
                                        $startPage = max(1, $page - 2);
                                        $endPage = min($totalPages, $page + 2);
                                        for ($i = $startPage; $i <= $endPage; $i++):
                                        ?>
                                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                                    Next
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i data-feather="inbox" style="width: 48px; height: 48px;" class="mb-3"></i>
                        <p>No access logs found</p>
                        <?php if (!empty($filters)): ?>
                            <a href="access-log.php" class="btn btn-sm btn-primary">Clear Filters</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card shadow-sm border-info mt-4">
            <div class="card-body">
                <h6 class="card-title">
                    <i data-feather="info" style="width: 16px; height: 16px;" class="me-1"></i>
                    About Access Logs
                </h6>
                <p class="card-text small mb-2">
                    This audit trail records every interaction with SLR documents including who accessed them, 
                    when, from what IP address, and what action was performed. This ensures complete transparency 
                    and accountability for document handling.
                </p>
                <hr>
                <div class="row small">
                    <div class="col-md-6">
                        <strong>Tracked Actions:</strong>
                        <ul class="mb-0">
                            <li><span class="badge bg-success">Generation</span> - Document created</li>
                            <li><span class="badge bg-primary">Download</span> - Document downloaded</li>
                            <li><span class="badge bg-info">View</span> - Document viewed</li>
                            <li><span class="badge bg-warning">Archive</span> - Document archived</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <strong>Recorded Information:</strong>
                        <ul class="mb-0">
                            <li>Date and time of access</li>
                            <li>User who performed action</li>
                            <li>IP address and browser info</li>
                            <li>Success/failure status</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>
