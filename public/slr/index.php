<?php
/**
 * SLR (Summary of Loan Release) Module Dashboard
 * 
 * Main landing page for the SLR module showing:
 * - Statistics and metrics
 * - Recent SLR documents
 * - Quick actions and navigation
 * - System status
 */

require_once '../init.php';
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier']);

$slrService = new SLRServiceAdapter();
$userRole = $user['role'] ?? '';

// Get SLR statistics
require_once BASE_PATH . '/app/services/SLR/SLRRepository.php';
$database = Database::getInstance();
$slrRepository = new App\Services\SLR\SLRRepository($database->getConnection());

// Calculate statistics
$totalDocuments = $slrRepository->countSLRDocuments([]);
$activeDocuments = $slrRepository->countSLRDocuments(['status' => 'active']);
$archivedDocuments = $slrRepository->countSLRDocuments(['status' => 'archived']);

// Get recent SLR documents (last 10)
$recentDocuments = $slrService->listSLRDocuments([], 10, 0);

// Get total downloads and storage info
try {
    $stmt = $database->getConnection()->query("
        SELECT 
            COALESCE(SUM(download_count), 0) as total_downloads,
            COALESCE(SUM(file_size), 0) as total_storage
        FROM slr_documents
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalDownloads = (int)$stats['total_downloads'];
    $totalStorage = (int)$stats['total_storage'];
} catch (Exception $e) {
    $totalDownloads = 0;
    $totalStorage = 0;
}

// Get generation rules status
try {
    $stmt = $database->getConnection()->query("
        SELECT 
            trigger_event,
            auto_generate,
            is_active
        FROM slr_generation_rules
        ORDER BY id
    ");
    $generationRules = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $generationRules = [];
}

// Format storage size
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

$pageTitle = 'SLR Document System';
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
                             style="width: 50px; height: 50px; background-color: #e8f5e8;">
                            <i data-feather="file-text" style="width:24px;height:24px;color:#16a34a;"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="notion-page-title mb-0">SLR Document System</h1>
                        <div class="text-muted small">
                            Summary of Loan Receipt Management
                        </div>
                    </div>
                </div>
                <div>
                    <a href="<?= APP_URL ?>/public/dashboard.php" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="arrow-left" style="width: 14px; height: 14px;" class="me-1"></i> Dashboard
                    </a>
                </div>
            </div>
            <div class="notion-divider my-3"></div>
        </div>

        <!-- Flash Messages -->
        <?php if ($session->hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $session->getFlash('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($session->hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $session->getFlash('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <!-- Total Documents -->
            <div class="col-md-3">
                <div class="p-4 rounded shadow-sm" style="background-color: #E0F2FE;">
                    <div class="d-flex mb-3 align-items-center">
                        <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #0284c7; display: flex; align-items: center; justify-content: center;">
                            <i data-feather="file-text" style="width: 20px; height: 20px; color: white;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Total Documents</h6>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <p class="stat-value display-5 fw-bold mb-0"><?= number_format($totalDocuments) ?></p>
                        <p class="card-text text-muted mb-0 small">All SLRs</p>
                    </div>
                </div>
            </div>

            <!-- Active Documents -->
            <div class="col-md-3">
                <div class="p-4 rounded shadow-sm" style="background-color: #D1FAE5;">
                    <div class="d-flex mb-3 align-items-center">
                        <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #10b981; display: flex; align-items: center; justify-content: center;">
                            <i data-feather="check-circle" style="width: 20px; height: 20px; color: white;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Active</h6>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <p class="stat-value display-5 fw-bold mb-0"><?= number_format($activeDocuments) ?></p>
                        <p class="card-text text-muted mb-0 small">In use</p>
                    </div>
                </div>
            </div>

            <!-- Archived Documents -->
            <div class="col-md-3">
                <div class="p-4 rounded shadow-sm" style="background-color: #FEF3C7;">
                    <div class="d-flex mb-3 align-items-center">
                        <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #f59e0b; display: flex; align-items: center; justify-content: center;">
                            <i data-feather="archive" style="width: 20px; height: 20px; color: white;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Archived</h6>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <p class="stat-value display-5 fw-bold mb-0"><?= number_format($archivedDocuments) ?></p>
                        <p class="card-text text-muted mb-0 small">Stored</p>
                    </div>
                </div>
            </div>

            <!-- Total Downloads -->
            <div class="col-md-3">
                <div class="p-4 rounded shadow-sm" style="background-color: #E9D5FF;">
                    <div class="d-flex mb-3 align-items-center">
                        <div class="rounded me-3" style="width: 40px; height: 40px; background-color: #a855f7; display: flex; align-items: center; justify-content: center;">
                            <i data-feather="download" style="width: 20px; height: 20px; color: white;"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Downloads</h6>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <p class="stat-value display-5 fw-bold mb-0"><?= number_format($totalDownloads) ?></p>
                        <p class="card-text text-muted mb-0 small">Total</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <i data-feather="zap" style="width: 18px; height: 18px;" class="me-2"></i>
                    <strong>Quick Actions</strong>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-success w-100 py-3">
                            <i data-feather="file-plus" style="width: 20px; height: 20px;" class="me-2"></i>
                            <span class="fw-bold">Generate SLR</span>
                            <br>
                            <small class="text-white-50">From loan list</small>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="<?= APP_URL ?>/public/slr/manage.php" class="btn btn-primary w-100 py-3">
                            <i data-feather="list" style="width: 20px; height: 20px;" class="me-2"></i>
                            <span class="fw-bold">View All Documents</span>
                            <br>
                            <small class="text-white-50">Browse and manage</small>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="<?= APP_URL ?>/public/slr/bulk.php" class="btn btn-info w-100 py-3">
                            <i data-feather="layers" style="width: 20px; height: 20px;" class="me-2"></i>
                            <span class="fw-bold">Bulk Generate</span>
                            <br>
                            <small class="text-white-50">Multiple loans</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Documents -->
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i data-feather="clock" style="width: 18px; height: 18px;" class="me-2"></i>
                                <strong>Recent SLR Documents</strong>
                                <span class="badge bg-secondary ms-2"><?= count($recentDocuments) ?></span>
                            </div>
                            <a href="<?= APP_URL ?>/public/slr/manage.php" class="btn btn-sm btn-outline-primary">
                                View All <i data-feather="arrow-right" style="width: 12px; height: 12px;" class="ms-1"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($recentDocuments)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Document No.</th>
                                            <th>Loan ID</th>
                                            <th>Client</th>
                                            <th>Generated</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentDocuments as $doc): ?>
                                            <tr>
                                                <td>
                                                    <code class="small"><?= htmlspecialchars($doc['document_number']) ?></code>
                                                </td>
                                                <td>
                                                    <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $doc['loan_id'] ?>" 
                                                       class="text-decoration-none">
                                                        #<?= $doc['loan_id'] ?>
                                                    </a>
                                                </td>
                                                <td><?= htmlspecialchars($doc['client_name'] ?? 'N/A') ?></td>
                                                <td class="text-muted small">
                                                    <?= date('M d, Y', strtotime($doc['generated_at'])) ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $doc['status'] === 'active' ? 'success' : ($doc['status'] === 'archived' ? 'secondary' : 'warning') ?>">
                                                        <?= ucfirst($doc['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($doc['status'] === 'active'): ?>
                                                        <a href="<?= APP_URL ?>/public/slr/generate.php?action=download&loan_id=<?= $doc['loan_id'] ?>" 
                                                           class="btn btn-sm btn-success" 
                                                           title="Download">
                                                            <i data-feather="download" style="width: 12px; height: 12px;"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Archived</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <i data-feather="inbox" style="width: 48px; height: 48px;" class="mb-3"></i>
                                <p>No SLR documents generated yet</p>
                                <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-sm btn-primary">
                                    Generate First SLR
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- System Info -->
            <div class="col-md-4">
                <!-- Storage Info -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <i data-feather="hard-drive" style="width: 18px; height: 18px;" class="me-2"></i>
                            <strong>Storage</strong>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted small">Total Used</span>
                                <strong><?= formatBytes($totalStorage) ?></strong>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-info" role="progressbar" 
                                     style="width: <?= min(($totalStorage / (100 * 1024 * 1024)) * 100, 100) ?>%"></div>
                            </div>
                        </div>
                        <hr>
                        <div class="small">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Avg. File Size</span>
                                <span><?= $totalDocuments > 0 ? formatBytes($totalStorage / $totalDocuments) : '0 B' ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Total Files</span>
                                <span><?= number_format($totalDocuments) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Generation Rules -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <i data-feather="settings" style="width: 18px; height: 18px;" class="me-2"></i>
                            <strong>Generation Rules</strong>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($generationRules)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($generationRules as $rule): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="small fw-bold text-capitalize">
                                                    <?= str_replace('_', ' ', $rule['trigger_event']) ?>
                                                </div>
                                                <div class="small text-muted">
                                                    <?= $rule['auto_generate'] ? '⚡ Auto-generate' : '👤 Manual only' ?>
                                                </div>
                                            </div>
                                            <div>
                                                <?php if ($rule['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small mb-0">No generation rules configured</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card shadow-sm border-info">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i data-feather="info" style="width: 16px; height: 16px;" class="me-1"></i>
                            About SLR Documents
                        </h6>
                        <p class="card-text small text-muted mb-2">
                            Summary of Loan Receipt (SLR) documents provide official records of loan disbursements 
                            to clients. They include loan details, payment schedules, and client information.
                        </p>
                        <hr>
                        <p class="card-text small mb-0">
                            <strong>Generation Methods:</strong>
                        </p>
                        <ul class="small text-muted mb-0">
                            <li>From loan list (individual)</li>
                            <li>Bulk generation (multiple)</li>
                            <li>Auto-generate (on approval/disbursement)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>
