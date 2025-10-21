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
    <!-- Dashboard Header with Title, Date and Reports Links -->
    <div class="notion-page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0f4fd;">
                        <i data-feather="activity" style="width: 24px; height: 24px; color:rgb(0, 0, 0);"></i>
                    </div>
                </div>
                <h1 class="notion-page-title mb-0">Transaction Audit Log</h1>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <div class="text-muted d-none d-md-block me-3">
                    <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i>
                    <?= date('l, F j, Y') ?>
                </div>
                <a href="<?= APP_URL ?>/public/reports/transactions.php" class="btn btn-sm btn-outline-secondary px-3">
                    <i data-feather="file-text" class="me-1" style="width: 14px; height: 14px;"></i> Transaction Report
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportTransactions()">
                    <i data-feather="download" class="me-1" style="width: 14px; height: 14px;"></i> Export
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
