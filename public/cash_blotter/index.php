<?php
/**
 * Cash Blotter Controller
 * Displays daily cash flow tracking and balance management
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control (Admins, Managers, and Cashiers can view cash blotter)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier']);

// Include required services
require_once '../../app/services/CashBlotterService.php';

// Initialize service
$cashBlotterService = new CashBlotterService();

// --- 1. Handle Filters ---
require_once '../../app/utilities/FilterUtility.php';
$filters = FilterUtility::sanitizeFilters($_GET);

// Set default date range for cash blotter (last 30 days)
if (empty($filters['date_from'])) {
    $filters['date_from'] = date('Y-m-d', strtotime('-30 days'));
}
if (empty($filters['date_to'])) {
    $filters['date_to'] = date('Y-m-d');
}

$filters = FilterUtility::validateDateRange($filters);

// --- 2. Fetch Cash Blotter Data ---
try {
    $blotterData = $cashBlotterService->getBlotterRange($filters['date_from'], $filters['date_to']);
    $currentBalance = $cashBlotterService->getCurrentBalance();
    $summary = $cashBlotterService->getCashFlowSummary($filters['date_from'], $filters['date_to']);
    $alerts = $cashBlotterService->getCashAlerts();

} catch (Exception $e) {
    error_log("Cash blotter error: " . $e->getMessage());
    $blotterData = [];
    $currentBalance = 0.00;
    $summary = ['total_inflow' => 0, 'total_outflow' => 0, 'net_flow' => 0];
    $alerts = [];
}

// --- 3. Page Setup ---
$pageTitle = "Cash Blotter";

include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Cash Blotter</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/cash_blotter/index.php" class="btn btn-sm btn-outline-secondary me-2">
                <i data-feather="refresh-cw"></i> Refresh
            </a>
            <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="exportBlotter()">
                <i data-feather="download"></i> Export
            </button>
            <button type="button" class="btn btn-sm btn-primary" onclick="recalculateBlotter()">
                <i data-feather="refresh-cw"></i> Recalculate
            </button>
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
                            <h6 class="card-title text-uppercase small">Current Balance</h6>
                            <h3 class="mb-0">₱<?= number_format($currentBalance, 2) ?></h3>
                        </div>
                        <i data-feather="dollar-sign" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Total Inflow</h6>
                            <h3 class="mb-0">₱<?= number_format($summary['total_inflow'] ?? 0, 2) ?></h3>
                        </div>
                        <i data-feather="trending-up" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Total Outflow</h6>
                            <h3 class="mb-0">₱<?= number_format($summary['total_outflow'] ?? 0, 2) ?></h3>
                        </div>
                        <i data-feather="trending-down" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Net Flow</h6>
                            <h3 class="mb-0">₱<?= number_format(($summary['total_inflow'] ?? 0) - ($summary['total_outflow'] ?? 0), 2) ?></h3>
                        </div>
                        <i data-feather="bar-chart-2" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="<?= APP_URL ?>/public/cash_blotter/index.php" class="row g-3">
                <div class="col-md-3">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from"
                        value="<?= htmlspecialchars($filters['date_from']) ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to"
                        value="<?= htmlspecialchars($filters['date_to']) ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="<?= APP_URL ?>/public/cash_blotter/index.php" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Cash Blotter List -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php include_once BASE_PATH . '/templates/cash_blotter/list_fixed.php'; ?>
        </div>
    </div>
</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>
