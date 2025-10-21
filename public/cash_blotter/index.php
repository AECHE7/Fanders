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

// --- 2. Handle PDF Export ---
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    try {
        require_once '../../app/services/ReportService.php';
        $reportService = new ReportService();
        $blotterData = $cashBlotterService->getBlotterRange($filters['date_from'], $filters['date_to']);
        $currentBalance = $cashBlotterService->getCurrentBalance();
        $summary = $cashBlotterService->getCashFlowSummary($filters['date_from'], $filters['date_to']);
        $reportService->exportCashBlotterPDF($blotterData, $summary, $currentBalance, $filters);
    } catch (Exception $e) {
        $session->setFlash('error', 'Error exporting PDF: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/cash_blotter/index.php?' . http_build_query($filters));
        exit;
    }
    exit;
}

// --- 3. Handle Excel Export ---
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    try {
        require_once '../../app/services/ReportService.php';
        $reportService = new ReportService();
        $blotterData = $cashBlotterService->getBlotterRange($filters['date_from'], $filters['date_to']);
        $currentBalance = $cashBlotterService->getCurrentBalance();
        $summary = $cashBlotterService->getCashFlowSummary($filters['date_from'], $filters['date_to']);
        $reportService->exportCashBlotterExcel($blotterData, $summary, $currentBalance, $filters);
    } catch (Exception $e) {
        $session->setFlash('error', 'Error exporting Excel: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/cash_blotter/index.php?' . http_build_query($filters));
        exit;
    }
    exit;
}

// --- 4. Fetch Cash Blotter Data ---
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

<main class="main-content">
    <div class="content-wrapper">
    <!-- Dashboard Header with Title, Date and Reports Links -->
    <div class="notion-page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0f4fd;">
                        <i data-feather="book-open" style="width: 24px; height: 24px; color:rgb(0, 0, 0);"></i>
                    </div>
                </div>
                <h1 class="notion-page-title mb-0">Cash Blotter</h1>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <div class="text-muted d-none d-md-block me-3">
                    <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i>
                    <?= date('l, F j, Y') ?>
                </div>
                <a href="<?= APP_URL ?>/public/reports/cash_blotter.php" class="btn btn-sm btn-outline-secondary px-3">
                    <i data-feather="file-text" class="me-1" style="width: 14px; height: 14px;"></i> Cash Blotter Report
                </a>
                <button type="button" class="btn btn-sm btn-primary" onclick="recalculateBlotter()">
                    <i data-feather="refresh-cw" class="me-1" style="width: 14px; height: 14px;"></i> Recalculate
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
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Cash Blotter Transactions</h5>
                <div class="btn-group">
                    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'pdf'])) ?>" class="btn btn-sm btn-success">
                        <i data-feather="download"></i> Export PDF
                    </a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>" class="btn btn-sm btn-outline-success">
                        <i data-feather="file"></i> Export Excel
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php include_once BASE_PATH . '/templates/cash_blotter/list_fixed.php'; ?>
        </div>
    </div>
</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>
