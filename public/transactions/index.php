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
    // Get filtered transactions
    $transactions = $transactionService->getTransactionHistory($filters, $filters['limit'], $offset);
    $totalTransactions = $transactionService->getTransactionCount($filters);
    $totalPages = ceil($totalTransactions / $filters['limit']);

    // Get transaction statistics
    $stats = $transactionService->getTransactionStats(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));

    // Format stats for display
    $stats = [
        'total_transactions' => $stats['total'] ?? 0,
        'recent_transactions' => $stats['daily'][count($stats['daily']) - 1]['count'] ?? 0,
        'transactions_by_type' => $stats['by_type'] ?? [],
        'total_amount' => 0 // This would need to be calculated from actual financial transactions
    ];

} catch (Exception $e) {
    error_log("Transaction log error: " . $e->getMessage());
    $transactions = [];
    $stats = ['total_transactions' => 0, 'recent_transactions' => 0, 'transactions_by_type' => [], 'total_amount' => 0];
    $totalPages = 1;
}

// --- 3. Page Setup ---
$pageTitle = "Transaction Audit Log";

include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
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
                            <h6 class="card-title text-uppercase small">Total Amount</h6>
                            <h3 class="mb-0">₱<?= number_format($stats['total_amount'] ?? 0, 2) ?></h3>
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
                    <label for="transaction_type" class="form-label">Type</label>
                    <select class="form-select" id="transaction_type" name="transaction_type">
                        <option value="">All Types</option>
                        <?php
                        $transactionTypes = TransactionModel::getTransactionTypes();
                        foreach ($transactionTypes as $key => $label) {
                            $selected = ($filters['transaction_type'] === $key) ? 'selected' : '';
                            echo "<option value=\"$key\" $selected>$label</option>";
                        }
                        ?>
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
