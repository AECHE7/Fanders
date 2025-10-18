<?php
/**
 * Transaction Audit Log Controller
 * Displays system activity logs with filtering and search
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control (Admins and Managers can view transaction logs)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager']);

// Include required services
require_once '../../app/services/TransactionService.php';

// Initialize service
$transactionService = new TransactionService();

// --- 1. Handle Filters and Search ---
require_once '../../app/utilities/FilterUtility.php';
$filters = FilterUtility::sanitizeFilters($_GET);
$filters = FilterUtility::validateDateRange($filters);

$offset = ($filters['page'] - 1) * $filters['limit'];

// --- 2. Fetch Transaction Data ---
try {
    if (!empty($filters['search'])) {
        $transactions = $transactionService->searchTransactions($filters['search'], $filters['limit']);
        $totalTransactions = count($transactions);
    } elseif (!empty($filters['type'])) {
        $transactions = $transactionService->getTransactionsByType($filters['type'], $filters['limit']);
        $totalTransactions = count($transactions);
    } else {
        $transactions = $transactionService->getTransactionHistory($filters['limit'], $offset);
        $totalTransactions = $transactionService->getTotalTransactionCount();
    }

    $stats = $transactionService->getTransactionStats();
    $totalPages = ceil($totalTransactions / $filters['limit']);

} catch (Exception $e) {
    error_log("Transaction log error: " . $e->getMessage());
    $transactions = [];
    $stats = ['total_transactions' => 0, 'recent_transactions' => 0, 'transactions_by_type' => []];
    $totalPages = 1;
}

// --- 3. Page Setup ---
$pageTitle = "Transaction Audit Log";

include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Transaction Audit Log</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-sm btn-outline-secondary me-2">
                <i data-feather="refresh-cw"></i> Refresh
            </a>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportTransactions()">
                <i data-feather="download"></i> Export
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
                            <h6 class="card-title text-uppercase small">Total Transactions</h6>
                            <h3 class="mb-0"><?= $stats['total_transactions'] ?? 0 ?></h3>
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
                            <h6 class="card-title text-uppercase small">This Week</h6>
                            <h3 class="mb-0"><?= $stats['recent_transactions'] ?? 0 ?></h3>
                        </div>
                        <i data-feather="calendar" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Loan Actions</h6>
                            <h3 class="mb-0"><?= ($stats['transactions_by_type']['LOAN_CREATED'] ?? 0) + ($stats['transactions_by_type']['LOAN_APPROVED'] ?? 0) + ($stats['transactions_by_type']['LOAN_DISBURSED'] ?? 0) ?></h3>
                        </div>
                        <i data-feather="file-text" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Payment Records</h6>
                            <h3 class="mb-0"><?= $stats['transactions_by_type']['PAYMENT_RECORDED'] ?? 0 ?></h3>
                        </div>
                        <i data-feather="dollar-sign" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="<?= APP_URL ?>/public/transactions/index.php" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search"
                        value="<?= htmlspecialchars($filters['search']) ?>"
                        placeholder="Search transactions...">
                </div>
                <div class="col-md-2">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">All Types</option>
                        <option value="LOAN_CREATED" <?= $filters['type'] === 'LOAN_CREATED' ? 'selected' : '' ?>>Loan Created</option>
                        <option value="LOAN_APPROVED" <?= $filters['type'] === 'LOAN_APPROVED' ? 'selected' : '' ?>>Loan Approved</option>
                        <option value="LOAN_DISBURSED" <?= $filters['type'] === 'LOAN_DISBURSED' ? 'selected' : '' ?>>Loan Disbursed</option>
                        <option value="PAYMENT_RECORDED" <?= $filters['type'] === 'PAYMENT_RECORDED' ? 'selected' : '' ?>>Payment Recorded</option>
                        <option value="CLIENT_CREATED" <?= $filters['type'] === 'CLIENT_CREATED' ? 'selected' : '' ?>>Client Created</option>
                        <option value="USER_CREATED" <?= $filters['type'] === 'USER_CREATED' ? 'selected' : '' ?>>User Created</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from"
                        value="<?= htmlspecialchars($filters['date_from']) ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to"
                        value="<?= htmlspecialchars($filters['date_to']) ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Transactions List -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php include_once BASE_PATH . '/templates/transactions/list.php'; ?>
        </div>
    </div>
</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>
