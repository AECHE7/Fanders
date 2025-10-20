<?php
/**
 * Loans Index Controller (index.php)
 * Role: Displays a list of all loan applications, pending approvals, and active/completed loans.
 * Integrates: LoanService, ClientService.
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control (All staff roles can view loans)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account-officer', 'cashier']);

// Initialize services
$loanService = new LoanService();
$clientService = new ClientService();

// --- 1. Prepare Filters from GET parameters ---
require_once '../../app/utilities/FilterUtility.php';
$filters = FilterUtility::sanitizeFilters($_GET);

// Rename start_date/end_date to date_from/date_to for consistency
if (isset($filters['start_date'])) {
    $filters['date_from'] = $filters['start_date'];
    unset($filters['start_date']);
}
if (isset($filters['end_date'])) {
    $filters['date_to'] = $filters['end_date'];
    unset($filters['end_date']);
}

$filters = FilterUtility::validateDateRange($filters);

// --- 2. Fetch Core Data ---

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

// Get loans based on applied filters with pagination
$loans = $loanService->getAllLoansWithClients($filters, $page, $limit);

// Get total count for pagination
$totalLoans = $loanService->getTotalLoansCount($filters);
$totalPages = ceil($totalLoans / $limit);

// Initialize pagination utility
require_once '../../app/utilities/PaginationUtility.php';
$pagination = new PaginationUtility($totalLoans, $page, $limit, 'page');

// Get all clients for the filter dropdown
$clients = $clientService->getAllForSelect();

// Get loan statistics for the dashboard cards
$loanStats = $loanService->getLoanStats();


// --- 3. Handle PDF Export ---
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    try {
        $reportService = new ReportService();
        $exportData = $loanService->getAllLoansWithClients($filters, 1, 10000); // Get all data without pagination
        $reportService->exportLoanReportPDF($exportData, $filters);
    } catch (Exception $e) {
        $session->setFlash('error', 'Error exporting PDF: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/loans/index.php?' . http_build_query($filters));
        exit;
    }
    exit;
}

// --- 4. Handle POST Actions (e.g., Approve/Disburse) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$csrf->validateRequest()) {
        $session->setFlash('error', 'Invalid security token. Please try again.');
        header('Location: ' . APP_URL . '/public/loans/index.php');
        exit;
    }

    // Only Managers/Admins can perform approval/disbursement
    if (!$auth->hasRole(['super-admin', 'admin', 'manager'])) {
        $session->setFlash('error', 'You do not have permission for this action.');
        header('Location: ' . APP_URL . '/public/loans/index.php');
        exit;
    }

    $loanId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $success = false;
    $message = 'Action failed.';
    $userId = $auth->getCurrentUser()['id'];

    if ($loanId > 0) {
        try {
            switch ($_POST['action']) {
                case 'approve':
                    $success = $loanService->approveLoan($loanId, $userId);
                    $message = $success ? 'Loan successfully approved. Ready for disbursement.' : $loanService->getErrorMessage();
                    break;
                case 'disburse':
                    $success = $loanService->disburseLoan($loanId, $userId);
                    $message = $success ? 'Funds disbursed. Loan is now active.' : $loanService->getErrorMessage();
                    break;
                case 'cancel': // Example of a loan cancellation action
                    $success = $loanService->cancelLoan($loanId, $userId);
                    $message = $success ? 'Loan application cancelled.' : $loanService->getErrorMessage();
                    break;
            }
        } catch (Exception $e) {
            $message = "Database error during action: " . $e->getMessage();
        }
    }

    $session->setFlash($success ? 'success' : 'error', $message);
    header('Location: ' . APP_URL . '/public/loans/index.php');
    exit;
}

// --- 4. Display View ---
$pageTitle = "Loans Management";

// Helper function to get loan status badge class (used in the template)
function getLoanStatusBadgeClass($status) {
    switch($status) {
        case 'active': return 'primary';
        case 'application': return 'info';
        case 'approved': return 'warning';
        case 'completed': return 'success';
        case 'defaulted': return 'danger';
        default: return 'secondary';
    }
}

include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Loans Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/loans/add.php" class="btn btn-sm btn-success">
                <i data-feather="plus"></i> New Loan Application
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
                            <h6 class="card-title text-uppercase small">Total Loans</h6>
                            <h3 class="mb-0"><?= $loanStats['total_loans'] ?? 0 ?></h3>
                        </div>
                        <i data-feather="file-text" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Total Disbursed</h6>
                            <h3 class="mb-0">₱<?= number_format($loanStats['total_disbursed'] ?? 0, 2) ?></h3>
                        </div>
                        <i data-feather="dollar-sign" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Active Loans</h6>
                            <h3 class="mb-0"><?= $loanStats['active_loans'] ?? 0 ?></h3>
                        </div>
                        <i data-feather="check-circle" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title text-uppercase small">Overdue Loans</h6>
                            <!-- NOTE: Total Outstanding is a Phase 2 calculation, using basic count for now -->
                            <h3 class="mb-0"><?= $loanStats['overdue_loans_count'] ?? 0 ?></h3>
                        </div>
                        <i data-feather="alert-triangle" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="<?= APP_URL ?>/public/loans/index.php" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search Client/ID</label>
                    <input type="text" class="form-control" id="search" name="search"
                        value="<?= htmlspecialchars($filters['search']) ?>"
                        placeholder="Client name, phone, or loan ID...">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="application" <?= $filters['status'] === 'application' ? 'selected' : '' ?>>Application (Pending)</option>
                        <option value="approved" <?= $filters['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active (Paying)</option>
                        <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="defaulted" <?= $filters['status'] === 'defaulted' ? 'selected' : '' ?>>Defaulted</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="client_id" class="form-label">Client</label>
                    <select class="form-select" id="client_id" name="client_id">
                        <option value="">All Clients</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id'] ?>" <?= $filters['client_id'] == $client['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($client['name']) ?> (ID: <?= $client['id'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
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
                    <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Loans List Table -->
    <div class="card shadow-sm">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Loans List</h5>
                <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'pdf'])) ?>" class="btn btn-sm btn-success">
                    <i data-feather="download"></i> Export PDF
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php include_once BASE_PATH . '/templates/loans/list.php'; ?>
        </div>
    </div>
    </div>
</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>
