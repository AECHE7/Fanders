<?php
/**
 * Loan Summary Report Controller (loan-summary.php)
 * Role: Displays the overall financial health of the loan portfolio (FR-005).
 * Integrates: ReportService
 */

require_once '../../public/init.php';

// Enforce access for managerial roles
$auth->checkRoleAccess(['super-admin', 'admin', 'manager']);

// Initialize services
$reportService = new ReportService();

// --- 1. Fetch Summary Data ---

// Get the high-level financial summary for the portfolio
$portfolioSummary = $reportService->getLoanPortfolioSummary();

// --- 2. Get Report Period ---
// The detailed payment reports can be filtered by month, but the portfolio summary is typically all-time.
$reportPeriod = "All Active Loans & Total Portfolio Health";


// --- 3. Display View ---
$pageTitle = "Loan Portfolio Summary Report";
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';

// Helper function for styling (local, as it won't be used outside this file)
function getStatColor($value, $isMoney = true) {
    if ($isMoney) {
        if ($value > 0) return 'text-success';
        if ($value < 0) return 'text-danger';
        return 'text-secondary';
    }
    return 'text-primary';
}
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Loan Portfolio Summary</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button type="button" class="btn btn-sm btn-info" onclick="window.print()">
                <i data-feather="printer"></i> Print Report
            </button>
        </div>
    </div>
    
    <p class="text-muted border-bottom pb-2">
        Financial Snapshot as of: **<?= date('F d, Y H:i A') ?>** (<?= $reportPeriod ?>)
    </p>

    <!-- Financial Summary Cards -->
    <div class="row mb-5 g-4">
        <!-- Total Loans -->
        <div class="col-md-3">
            <div class="card shadow-sm border-primary">
                <div class="card-body">
                    <h6 class="card-title text-primary text-uppercase small">Total Loans Handled</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="mb-0 fw-bold"><?= number_format($portfolioSummary['total_loans']) ?></h2>
                        <i data-feather="file-text" class="text-primary opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Principal Lent -->
        <div class="col-md-3">
            <div class="card shadow-sm border-info">
                <div class="card-body">
                    <h6 class="card-title text-info text-uppercase small">Total Principal Lent (Capital)</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="mb-0 fw-bold">₱<?= number_format($portfolioSummary['total_principal_lent'], 2) ?></h2>
                        <i data-feather="trending-up" class="text-info opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Payments Received -->
        <div class="col-md-3">
            <div class="card shadow-sm border-success">
                <div class="card-body">
                    <h6 class="card-title text-success text-uppercase small">Total Payments Received</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="mb-0 fw-bold">₱<?= number_format($portfolioSummary['total_payments_received'], 2) ?></h2>
                        <i data-feather="credit-card" class="text-success opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Outstanding Balance -->
        <div class="col-md-3">
            <div class="card shadow-sm border-danger">
                <div class="card-body">
                    <h6 class="card-title text-danger text-uppercase small">Total Outstanding Balance</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="mb-0 fw-bold">₱<?= number_format($portfolioSummary['total_outstanding_balance'], 2) ?></h2>
                        <i data-feather="alert-triangle" class="text-danger opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Detailed Breakdown Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">Active Loan Portfolio Breakdown</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Breakdown of the estimated capital and revenue components currently outstanding on active loans.</p>
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 30%;">Metric</th>
                        <th style="width: 30%;">Value (₱)</th>
                        <th style="width: 40%;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Total Active Loans</td>
                        <td><?= number_format($portfolioSummary['total_active_amount'] > 0 ? $portfolioSummary['total_active_loans'] : 0) ?></td>
                        <td>Total number of loans currently marked as 'Active' (Disbursed).</td>
                    </tr>
                    <tr>
                        <td>**Total Original Amount Due**</td>
                        <td>**₱<?= number_format($portfolioSummary['total_amount_due'], 2) ?>**</td>
                        <td>The sum of Principal + Interest + Fees for all active and finalized loans.</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">1. Estimated Remaining Principal</td>
                        <td class="<?= getStatColor($portfolioSummary['remaining_principal']) ?> fw-bold">₱<?= number_format($portfolioSummary['remaining_principal'], 2) ?></td>
                        <td>The estimated portion of the original capital yet to be collected.</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">2. Estimated Remaining Interest/Fee</td>
                        <td class="<?= getStatColor($portfolioSummary['remaining_interest_fee']) ?> fw-bold">₱<?= number_format($portfolioSummary['remaining_interest_fee'], 2) ?></td>
                        <td>The estimated profit (interest + insurance) yet to be collected.</td>
                    </tr>
                    <tr class="table-light">
                        <td class="fw-bold">Total Net Outstanding (1 + 2)</td>
                        <td class="fw-bold text-danger">₱<?= number_format($portfolioSummary['total_outstanding_balance'], 2) ?></td>
                        <td>This value matches the 'Total Outstanding Balance' card above.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php
// Initialize Feather icons
echo '<script>document.addEventListener(\'DOMContentLoaded\', function() { if (typeof feather !== \'undefined\') { feather.replace(); } });</script>';
include_once BASE_PATH . '/templates/layout/footer.php';
?>