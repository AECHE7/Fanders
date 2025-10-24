<?php
/**
 * Transaction Reports Controller
 * Displays transaction audit logs with filtering and export capabilities.
 * Integrates: ReportService, TransactionService
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control (Admins and Managers can view transaction reports)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager']);

// Initialize services
$reportService = new ReportService();
$transactionService = new TransactionService();

// --- 1. Process Filters from GET parameters ---
require_once '../../app/utilities/FilterUtility.php';
$filters = FilterUtility::sanitizeFilters($_GET);
$filters = FilterUtility::validateDateRange($filters);

// Set default date range to last 30 days if not specified
if (empty($filters['date_from'])) {
    $filters['date_from'] = date('Y-m-d', strtotime('-30 days'));
}
if (empty($filters['date_to'])) {
    $filters['date_to'] = date('Y-m-d');
}

// --- 2. Generate Report Data ---
$reportData = $reportService->generateTransactionReport($filters);

// Get transaction statistics
$stats = $transactionService->getTransactionStats($filters['date_from'], $filters['date_to']);

// --- 3. Handle PDF Export ---
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    try {
        $reportService->exportTransactionReportPDF($reportData, $filters);
    } catch (Exception $e) {
        $session->setFlash('error', 'Error exporting PDF: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/reports/transactions.php?' . http_build_query($filters));
        exit;
    }
    exit;
}

// --- 4. Display View ---
$pageTitle = "Transaction Reports";

include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Transaction Reports</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/reports/index.php" class="btn btn-sm btn-outline-secondary me-2">
                <i data-feather="arrow-left"></i> Back to Reports
            </a>
            <a href="?<?= http_build_query(array_merge($filters, ['export' => 'pdf'])) ?>"
               class="btn btn-sm btn-success">
                <i data-feather="download"></i> Export PDF
            </a>
        </div>
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
                            <h6 class="card-title text-uppercase small">Total Transactions</h6>
                            <h3 class="mb-0"><?= count($reportData) ?></h3>
                        </div>
                        <i data-feather="activity" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Loan Actions</h6>
                            <h3 class="mb-0"><?= count(array_filter($reportData, fn($t) => $t['entity_type'] === 'loan')) ?></h3>
                        </div>
                        <i data-feather="file-text" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Payment Records</h6>
                            <h3 class="mb-0"><?= count(array_filter($reportData, fn($t) => $t['entity_type'] === 'payment')) ?></h3>
                        </div>
                        <i data-feather="dollar-sign" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">User Actions</h6>
                            <h3 class="mb-0"><?= count(array_filter($reportData, fn($t) => $t['entity_type'] === 'user')) ?></h3>
                        </div>
                        <i data-feather="user-check" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($reportData)): ?>
                <div class="text-center py-5">
                    <i data-feather="file-x" class="text-muted" style="width: 4rem; height: 4rem;"></i>
                    <h5 class="text-muted mt-3">No transactions found</h5>
                    <p class="text-muted">Try adjusting your filters or check back later.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date & Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>Reference ID</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData as $transaction): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                <i data-feather="clock" style="width: 14px; height: 14px; color: #6c757d;"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?= date('M j, Y', strtotime($transaction['timestamp'])) ?></div>
                                                <small class="text-muted"><?= date('H:i:s', strtotime($transaction['timestamp'])) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-2" style="width: 32px; height: 32px; background-color: #e9ecef; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <?php $displayName = $transaction['user_name'] ?? ''; ?>
                                                <span class="fw-bold text-muted" style="font-size: 12px;"><?= strtoupper(substr($displayName, 0, 1)) ?></span>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($transaction['user_name'] ?? '') ?></div>
                                                <small class="text-muted"><?= ucfirst($transaction['role'] ?? '') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= getActionBadgeClass($transaction['action'] ?? '') ?>">
                                            <?= ucfirst(str_replace('_', ' ', ($transaction['action'] ?? ''))) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= ucfirst((string)($transaction['entity_type'] ?? 'system')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">#<?= htmlspecialchars((string)($transaction['entity_id'] ?? '-')) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $details = json_decode($transaction['details'], true);
                                        if ($details && isset($details['amount'])) {
                                            echo '₱' . number_format($details['amount'], 2);
                                        } elseif ($details && isset($details['principal'])) {
                                            echo '₱' . number_format($details['principal'], 2);
                                        } elseif ($details && isset($details['message'])) {
                                            echo htmlspecialchars(substr($details['message'], 0, 30));
                                        } else {
                                            echo '<span class="text-muted">-</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';

// Helper function for action badge classes
function getActionBadgeClass($action) {
    $classes = [
        'created' => 'success',
        'approved' => 'info',
        'disbursed' => 'primary',
        'recorded' => 'success',
        'updated' => 'secondary',
        'cancelled' => 'warning',
        'completed' => 'success'
    ];
    return $classes[$action] ?? 'secondary';
}
?>
