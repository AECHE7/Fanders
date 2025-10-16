<?php
/**
 * Cash Blotter Index Controller (index.php)
 * Role: Displays the current cash position and a list of historical daily blotter records.
 * Integrates: CashBlotterService
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control (Only Admin, Manager, Cashier, AO can access cash records)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier', 'account-officer']);

// Initialize cash blotter service
$cashBlotterService = new CashBlotterService();

// --- 1. Handle Today's Blotter Initialization/Update ---
// Use the operational method to get or create today's blotter and refresh totals
$todayBlotter = $cashBlotterService->getAndUpdateTodayBlotter($user['id']);

if (!$todayBlotter && $cashBlotterService->getErrorMessage()) {
    $session->setFlash('error', $cashBlotterService->getErrorMessage());
}

// Get current cash position from the latest blotter record
$currentCashPosition = $cashBlotterService->getCurrentCashPosition();


// --- 2. Process Filters for Historical View ---
$startDateFilter = $_GET['start_date'] ?? date('Y-m-01'); // Default to start of month
$endDateFilter = $_GET['end_date'] ?? date('Y-m-t');     // Default to end of month

// Fetch historical blotters based on date range
$historicalBlotters = $cashBlotterService->getBlottersByDateRange($startDateFilter, $endDateFilter);


// --- 3. Handle POST Actions (e.g., Finalize Blotter) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$csrf->validateRequest()) {
        $session->setFlash('error', 'Invalid security token. Please try again.');
        header('Location: ' . APP_URL . '/public/cash-blotter/index.php');
        exit;
    }

    // Only Managers/Admins can finalize blotters
    if (!$auth->hasRole(['super-admin', 'admin', 'manager'])) {
        $session->setFlash('error', 'You do not have permission to finalize cash blotters.');
        header('Location: ' . APP_URL . '/public/cash-blotter/index.php');
        exit;
    }
    
    $blotterId = isset($_POST['blotter_id']) ? (int)$_POST['blotter_id'] : 0;
    
    if ($_POST['action'] === 'finalize' && $blotterId > 0) {
        $success = $cashBlotterService->finalizeBlotter($blotterId);
        $message = $success
            ? 'Cash Blotter successfully **FINALIZED**.'
            : ($cashBlotterService->getErrorMessage() ?: 'Failed to finalize blotter.');

        $session->setFlash($success ? 'success' : 'error', $message);
        header('Location: ' . APP_URL . '/public/cash-blotter/index.php');
        exit;
    }
}


// --- 4. Display View ---
$pageTitle = "Cash Blotter";

include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Daily Cash Blotter</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/reports/cash-blotter.php" class="btn btn-sm btn-outline-secondary">
                <i data-feather="file-text"></i> Generate Report
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

    <!-- Current Cash Position Card -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1 text-uppercase small">Current Cash Position (Estimate)</h5>
                            <h2 class="mb-0">₱<?= number_format($currentCashPosition, 2) ?></h2>
                        </div>
                        <div class="text-end">
                            <i data-feather="dollar-sign" class="opacity-50" style="width: 4rem; height: 4rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Today's Blotter / Current Draft -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Today's Blotter (<?= date('M d, Y') ?>)</h5>
            <?php if ($todayBlotter && $todayBlotter['status'] === 'draft' && $auth->hasRole(['super-admin', 'admin'])): ?>
                <button type="button" class="btn btn-sm btn-success" title="Finalize Today's Blotter" onclick="finalizeBlotter(<?= $todayBlotter['id'] ?>)">
                    <i data-feather="check-circle" style="width:16px;height:16px;"></i> Finalize Blotter
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if ($todayBlotter): ?>
                <table class="table table-sm table-striped mb-0">
                    <tbody>
                        <tr>
                            <td class="fw-bold" style="width: 30%;">Status</td>
                            <td>
                                <span class="badge bg-<?= $todayBlotter['status'] === 'finalized' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($todayBlotter['status']) ?>
                                </span>
                            </td>
                        </tr>
                         <tr>
                            <td class="fw-bold">Opening Balance</td>
                            <td>₱<?= number_format($todayBlotter['opening_balance'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Total Collections (Inflow)</td>
                            <td class="text-success fw-bold">+₱<?= number_format($todayBlotter['total_collections'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Total Loan Releases (Outflow)</td>
                            <td class="text-danger fw-bold">-₱<?= number_format($todayBlotter['total_loan_releases'], 2) ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Total Expenses (Manual Outflow)</td>
                            <td class="text-danger fw-bold">-₱<?= number_format($todayBlotter['total_expenses'], 2) ?></td>
                        </tr>
                        <tr class="table-primary">
                            <td class="fw-bold">Closing Balance</td>
                            <td>**₱<?= number_format($todayBlotter['closing_balance'], 2) ?>**</td>
                        </tr>
                         <tr>
                            <td class="fw-bold">Recorded By</td>
                            <td><?= htmlspecialchars($todayBlotter['recorded_by_name'] ?? 'N/A') ?></td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info m-0 rounded-0">
                    No blotter record exists for today. It will be created automatically upon the first payment or loan disbursement.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Options for Historical View -->
    <div class="row mb-3">
        <div class="col-md-12">
            <h5 class="mb-3">Historical Blotters</h5>
            <form action="<?= $_SERVER['PHP_SELF'] ?>" method="get" class="row g-3">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDateFilter) ?>">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDateFilter) ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter Records</button>
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Historical Cash Blotter Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (empty($historicalBlotters)): ?>
                <div class="text-center py-5">
                    <h5 class="text-muted">No historical records found for this period.</h5>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Opening</th>
                                <th>Collections</th>
                                <th>Releases</th>
                                <th>Expenses</th>
                                <th>Closing Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historicalBlotters as $blotter): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($blotter['blotter_date'])) ?></td>
                                    <td>₱<?= number_format($blotter['opening_balance'], 2) ?></td>
                                    <td class="text-success fw-medium">+₱<?= number_format($blotter['total_collections'], 2) ?></td>
                                    <td class="text-danger fw-medium">-₱<?= number_format($blotter['total_loan_releases'], 2) ?></td>
                                    <td class="text-danger fw-medium">-₱<?= number_format($blotter['total_expenses'], 2) ?></td>
                                    <td><strong>₱<?= number_format($blotter['closing_balance'], 2) ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?= $blotter['status'] == 'finalized' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($blotter['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= APP_URL ?>/public/cash-blotter/view.php?id=<?= $blotter['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Detailed Entries">
                                            <i data-feather="file-text" style="width:14px;height:14px;"></i>
                                        </a>
                                        <?php if ($blotter['status'] === 'draft' && $auth->hasRole(['super-admin', 'admin'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success" title="Finalize" onclick="finalizeBlotter(<?= $blotter['id'] ?>)">
                                                <i data-feather="check" style="width:14px;height:14px;"></i>
                                            </button>
                                        <?php endif; ?>
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

<form id="finalizeForm" method="POST" action="<?= APP_URL ?>/public/cash-blotter/index.php" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf->generateToken()) ?>">
    <input type="hidden" name="blotter_id" id="finalizeBlotterId">
    <input type="hidden" name="action" value="finalize">
</form>

<script>
// Finalize blotter function
function finalizeBlotter(blotterId) {
    if (confirm('Are you absolutely sure you want to FINALIZE this cash blotter? This action cannot be undone and locks the record.')) {
        const finalizeBlotterId = document.getElementById('finalizeBlotterId');
        const finalizeForm = document.getElementById('finalizeForm');
        
        finalizeBlotterId.value = blotterId;
        finalizeForm.submit();
    }
}

// Initialize Feather icons
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>