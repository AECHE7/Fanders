<?php
/**
 * Payments Index Controller
 * Displays a list of all payments with filtering and search capabilities.
 * Integrates: PaymentService, LoanService
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../init.php';

// Enforce role-based access control (Staff roles can view payments)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier', 'account-officer']);

// Initialize services
$paymentService = new PaymentService();
$loanService = new LoanService();
$clientService = new ClientService();

// --- 1. Handle Filters and Search ---
require_once '../../app/utilities/FilterUtility.php';

// Enhanced filter handling
$filters = FilterUtility::sanitizeFilters($_GET);

// Set default date range for payments (last 30 days) if not specified
if (empty($filters['date_from'])) {
    $filters['date_from'] = date('Y-m-d', strtotime('-30 days'));
}
if (empty($filters['date_to'])) {
    $filters['date_to'] = date('Y-m-d');
}

$filters = FilterUtility::validateDateRange($filters);

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

// --- 2. Handle PDF Export ---
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    try {
        $reportService = new ReportService();
        $exportData = $paymentService->getAllPayments($filters, 1, 10000); // Get all data without pagination
        $reportService->exportPaymentReportPDF($exportData, $filters);
    } catch (Exception $e) {
        $session->setFlash('error', 'Error exporting PDF: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/payments/index.php?' . http_build_query($filters));
        exit;
    }
    exit;
}

// --- 3. Fetch Payments Data ---
try {
    $payments = $paymentService->getAllPayments($filters, $page, $limit);
    $totalPayments = $paymentService->getTotalPaymentsCount($filters);
} catch (Exception $e) {
    error_log("Payments fetch error: " . $e->getMessage());
    $payments = [];
    $pagination = [];
    $totalPayments = 0;
}

$totalPages = ceil($totalPayments / $limit);

// Initialize pagination utility
require_once '../../app/utilities/PaginationUtility.php';
$pagination = new PaginationUtility($totalPayments, $page, $limit, 'page');

// --- 3. Get Additional Data for Display ---

// Get clients for filter dropdown (active clients only)
$clients = $clientService->getAllForSelect(['status' => 'active']);

// Get recent loans for filter dropdown
$loans = $loanService->getAllActiveLoansWithClients();

// Prepare filter summary for display
$filterSummary = FilterUtility::getFilterSummary($filters);

// --- 4. Prepare Data for Template ---
$pageTitle = "Payment Records";
$userRole = $user['role'] ?? '';

// Include template
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Payment Records</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/payments/add.php" class="btn btn-sm btn-success me-2">
                <i data-feather="plus"></i> Record Payment
            </a>
            <?php if ($userRole == 'super-admin' || $userRole == 'admin' || $userRole == 'manager'): ?>
                <a href="<?= APP_URL ?>/public/payments/export.php" class="btn btn-sm btn-outline-secondary me-2">
                    <i data-feather="download"></i> Export
                </a>
            <?php endif; ?>
            <a href="<?= APP_URL ?>/public/collection-sheets/index.php" class="btn btn-sm btn-outline-primary">
                <i data-feather="calendar"></i> Collection Sheets
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
        <div class="col-md-4">
            <div class="card text-white bg-primary shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Total Payments</h6>
                            <h3 class="mb-0"><?= number_format($totalPayments) ?></h3>
                        </div>
                        <i data="feather" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Total Amount</h6>
                            <h3 class="mb-0">₱<?= number_format(array_sum(array_column($payments, 'amount'))) ?></h3>
                        </div>
                        <i data="feather" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">This Month</h6>
                            <h3 class="mb-0">₱<?= number_format(array_sum(array_filter(array_column($payments, 'amount'), function($amount, $key) use ($payments) {
                                return date('Y-m', strtotime($payments[$key]['payment_date'])) == date('Y-m');
                            }, ARRAY_FILTER_USE_BOTH))) ?></h3>
                        </div>
                        <i data="feather" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments List -->
    <div class="card shadow-sm">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Payments List</h5>
                <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'pdf'])) ?>" class="btn btn-sm btn-success">
                    <i data-feather="download"></i> Export PDF
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($payments)): ?>
                <div class="text-center py-5">
                    <i data-feather="credit-card" class="text-muted" style="width: 4rem; height: 4rem;"></i>
                    <h5 class="text-muted mt-3">No payments found</h5>
                    <p class="text-muted">Try adjusting your filters or check back later.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Loan #</th>
                                <th>Amount</th>
                                <th>Recorded By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($payment['payment_date'])) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($payment['client_name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($payment['phone_number']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">#<?= $payment['loan_id'] ?></span>
                                    </td>
                                    <td class="fw-bold text-success">₱<?= number_format($payment['amount'], 2) ?></td>
                                    <td><?= htmlspecialchars($payment['recorded_by_name']) ?></td>
                                    <td>
                                        <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $payment['loan_id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i data-feather="eye"></i> View Loan
                                        </a>
                                        <a href="<?= APP_URL ?>/public/payments/view.php?id=<?= $payment['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                            <i data-feather="file-text"></i> View Payment
                                        </a>
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
?>
