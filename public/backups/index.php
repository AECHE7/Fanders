<?php
/**
 * Backup Management Interface
 * Allows administrators to view, create, and manage database backups
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control for Super Admin and Admin
$auth->checkRoleAccess(['super-admin', 'admin']);

// Initialize services
$backupService = new BackupService();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$csrf->validateRequest()) {
        $session->setFlash('error', 'Invalid security token. Please try again.');
        header('Location: ' . APP_URL . '/public/backups/index.php');
        exit;
    }

    try {
        switch ($_POST['action']) {
            case 'create_backup':
                $backupType = $_POST['backup_type'] ?? 'manual';
                $result = $backupService->createDatabaseBackup($backupType);

                if ($result) {
                    $session->setFlash('success', "Backup created successfully. File: {$result['filename']} ({$result['size']} bytes)");
                } else {
                    $session->setFlash('error', 'Failed to create backup: ' . $backupService->getErrorMessage());
                }
                break;

            case 'restore_backup':
                $backupId = (int)($_POST['backup_id'] ?? 0);
                if ($backupId > 0) {
                    $result = $backupService->restoreFromBackup($backupId);
                    if ($result) {
                        $session->setFlash('success', 'Database restored successfully from backup.');
                    } else {
                        $session->setFlash('error', 'Failed to restore backup: ' . $backupService->getErrorMessage());
                    }
                } else {
                    $session->setFlash('error', 'Invalid backup ID specified.');
                }
                break;

            case 'delete_backup':
                $backupId = (int)($_POST['backup_id'] ?? 0);
                if ($backupId > 0) {
                    $backupModel = new BackupModel();
                    $result = $backupModel->delete($backupId);
                    if ($result) {
                        $session->setFlash('success', 'Backup deleted successfully.');
                    } else {
                        $session->setFlash('error', 'Failed to delete backup.');
                    }
                } else {
                    $session->setFlash('error', 'Invalid backup ID specified.');
                }
                break;

            default:
                $session->setFlash('error', 'Invalid action specified.');
        }
    } catch (Exception $e) {
        $session->setFlash('error', 'An error occurred: ' . $e->getMessage());
    }

    header('Location: ' . APP_URL . '/public/backups/index.php');
    exit;
}

// Get filters and pagination
$filters = [];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

if (!empty($_GET['type'])) {
    $filters['type'] = $_GET['type'];
}
if (!empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (!empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}

// Get backups
$backupModel = new BackupModel();
$backups = $backupModel->getBackups($filters, $page, $limit);
$totalBackups = $backupModel->getTotalCount($filters);
$totalPages = ceil($totalBackups / $limit);

// Get backup statistics
$backupStats = $backupService->getBackupStats();

$pageTitle = "Backup Management";
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <!-- Dashboard Header with Title, Date and Reports Links -->
        <div class="notion-page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0f4fd;">
                            <i data-feather="database" style="width: 24px; height: 24px; color:rgb(0, 0, 0);"></i>
                        </div>
                    </div>
                    <h1 class="notion-page-title mb-0">Backup Management</h1>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <div class="text-muted d-none d-md-block me-3">
                        <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i>
                        <?= date('l, F j, Y') ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                        <i data-feather="plus" class="me-1" style="width: 14px; height: 14px;"></i> Create Backup
                    </button>
                </div>
            </div>
            <div class="notion-divider my-3"></div>
        </div>

        <!-- Flash Messages -->
        <?php if ($session->hasFlash('success')): ?>
            <div class="alert alert-success">
                <?= $session->getFlash('success') ?>
            </div>
        <?php endif; ?>

        <?php if ($session->hasFlash('error')): ?>
            <div class="alert alert-danger">
                <?= $session->getFlash('error') ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Total Backups</h6>
                                <h3 class="mb-0"><?= number_format($backupStats['total_backups'] ?? 0) ?></h3>
                            </div>
                            <i data-feather="database" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Scheduled</h6>
                                <h3 class="mb-0"><?= number_format($backupStats['scheduled_backups'] ?? 0) ?></h3>
                            </div>
                            <i data-feather="clock" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Manual</h6>
                                <h3 class="mb-0"><?= number_format($backupStats['manual_backups'] ?? 0) ?></h3>
                            </div>
                            <i data-feather="user" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Total Size</h6>
                                <h3 class="mb-0"><?= number_format(($backupStats['total_size'] ?? 0) / 1024 / 1024, 1) ?>MB</h3>
                            </div>
                            <i data-feather="hard-drive" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <form action="<?= APP_URL ?>/public/backups/index.php" method="get" class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label for="type" class="form-label">Type</label>
                        <select name="type" id="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="scheduled" <?= ($filters['type'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                            <option value="manual" <?= ($filters['type'] ?? '') === 'manual' ? 'selected' : '' ?>>Manual</option>
                            <option value="full" <?= ($filters['type'] ?? '') === 'full' ? 'selected' : '' ?>>Full</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="failed" <?= ($filters['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
                            <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from"
                            value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                    </div>

                    <div class="col-md-2">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to"
                            value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                        <a href="<?= APP_URL ?>/public/backups/index.php" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Backups List -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Database Backups</h5>
            </div>
            <div class="card-body">
                <?php if (empty($backups)): ?>
                    <div class="text-center py-5">
                        <i data-feather="database" class="text-muted" style="width: 4rem; height: 4rem;"></i>
                        <h5 class="text-muted mt-3">No backups found</h5>
                        <p class="text-muted">Create your first backup to get started.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Filename</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i data-feather="file" class="me-2 text-muted" style="width: 16px; height: 16px;"></i>
                                                <div>
                                                    <div class="fw-bold"><?= htmlspecialchars($backup['filename']) ?></div>
                                                    <small class="text-muted">ID: <?= $backup['id'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $backup['type'] === 'scheduled' ? 'success' : 'primary' ?>">
                                                <?= ucfirst($backup['type']) ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold">
                                            <?= number_format($backup['size'] / 1024 / 1024, 2) ?> MB
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $backup['status'] === 'completed' ? 'success' : ($backup['status'] === 'failed' ? 'danger' : 'warning') ?>">
                                                <?= ucfirst($backup['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <div><?= date('M d, Y H:i', strtotime($backup['created_at'])) ?></div>
                                                <small class="text-muted">by <?= htmlspecialchars($backup['created_by']) ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <?php if ($backup['status'] === 'completed'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-success"
                                                            onclick="restoreBackup(<?= $backup['id'] ?>, '<?= htmlspecialchars($backup['filename']) ?>')">
                                                        <i data-feather="rotate-ccw" style="width: 14px; height: 14px;"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                        onclick="deleteBackup(<?= $backup['id'] ?>, '<?= htmlspecialchars($backup['filename']) ?>')">
                                                    <i data-feather="trash-2" style="width: 14px; height: 14px;"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Backup pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Create Backup Modal -->
<div class="modal fade" id="createBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Database Backup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= APP_URL ?>/public/backups/index.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf->generateToken() ?>">
                    <input type="hidden" name="action" value="create_backup">

                    <div class="mb-3">
                        <label for="backup_type" class="form-label">Backup Type</label>
                        <select name="backup_type" id="backup_type" class="form-select" required>
                            <option value="manual">Manual Backup</option>
                            <option value="full">Full Backup</option>
                        </select>
                        <div class="form-text">Manual backups are created on-demand, full backups include all data.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Backup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden forms for actions -->
<form id="restoreForm" action="<?= APP_URL ?>/public/backups/index.php" method="post" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= $csrf->generateToken() ?>">
    <input type="hidden" name="action" value="restore_backup">
    <input type="hidden" name="backup_id" id="restore_backup_id">
</form>

<form id="deleteForm" action="<?= APP_URL ?>/public/backups/index.php" method="post" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?= $csrf->generateToken() ?>">
    <input type="hidden" name="action" value="delete_backup">
    <input type="hidden" name="backup_id" id="delete_backup_id">
</form>

<script>
function restoreBackup(backupId, filename) {
    if (confirm(`Are you sure you want to restore the database from backup "${filename}"?\n\nThis will overwrite the current database and cannot be undone.`)) {
        document.getElementById('restore_backup_id').value = backupId;
        document.getElementById('restoreForm').submit();
    }
}

function deleteBackup(backupId, filename) {
    if (confirm(`Are you sure you want to delete the backup "${filename}"?\n\nThis action cannot be undone.`)) {
        document.getElementById('delete_backup_id').value = backupId;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>
