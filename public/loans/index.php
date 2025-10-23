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

// Enhanced filter handling with validation
$filterOptions = [
    'allowed_statuses' => ['application', 'approved', 'active', 'completed', 'defaulted']
];
$filters = FilterUtility::sanitizeFilters($_GET, $filterOptions);

// Rename start_date/end_date to date_from/date_to for consistency
if (isset($_GET['start_date'])) {
    $filters['date_from'] = $_GET['start_date'];
}
if (isset($_GET['end_date'])) {
    $filters['date_to'] = $_GET['end_date'];
}

$filters = FilterUtility::validateDateRange($filters);

// --- 2. Fetch Core Data with Enhanced Pagination ---

try {
    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

    // Inject pagination into filters and get loans with pagination
    $filters['page'] = $page;
    $filters['limit'] = $limit;
    $loans = $loanService->getAllLoansWithClients($filters);

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
    // Map/augment stats for template compatibility
    $loanStats['total_disbursed'] = $loanStats['total_principal_disbursed'] ?? 0;
    $loanStats['active_loans'] = $loanService->getTotalLoansCount(['status' => 'Active']);

    // Prepare filter summary for display
    $filterSummary = FilterUtility::getFilterSummary($filters);
} catch (Exception $e) {
    require_once '../../app/utilities/ErrorHandler.php';
    $errorMessage = ErrorHandler::handleApplicationError('loading loan data', $e, [
        'filters' => $filters,
        'user_id' => $auth->getCurrentUser()['id'] ?? null
    ]);
    
    $session->setFlash('error', $errorMessage);
    
    // Set default empty values
    $loans = [];
    $pagination = ['total_records' => 0, 'current_page' => 1, 'total_pages' => 1];
    $clients = [];
    $loanStats = [];
    $filterSummary = [];
}


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

// --- 3b. Handle Excel Export ---
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    try {
        $reportService = new ReportService();
        $exportData = $loanService->getAllLoansWithClients($filters, 1, 10000); // Get all data without pagination
        $reportService->exportLoanReportExcel($exportData, $filters);
    } catch (Exception $e) {
        $session->setFlash('error', 'Error exporting Excel: ' . $e->getMessage());
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
    $status = strtolower($status ?? '');
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
    <!-- Dashboard Header with Title, Date and Reports Links -->
    <div class="notion-page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0f4fd;">
                        <i data-feather="file-text" style="width: 24px; height: 24px; color:rgb(0, 0, 0);"></i>
                    </div>
                </div>
                <h1 class="notion-page-title mb-0">Loans Management</h1>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <div class="text-muted d-none d-md-block me-3">
                    <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i>
                    <?= date('l, F j, Y') ?>
                </div>
                <a href="<?= APP_URL ?>/public/reports/loans.php" class="btn btn-sm btn-outline-secondary px-3">
                    <i data-feather="file-text" class="me-1" style="width: 14px; height: 14px;"></i> Loans Report
                </a>
                <a href="<?= APP_URL ?>/public/loans/add.php" class="btn btn-sm btn-success">
                    <i data-feather="plus" class="me-1" style="width: 14px; height: 14px;"></i> New Loan
                </a>
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

    <!-- Quick Actions -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <a href="<?= APP_URL ?>/public/loans/add.php" class="btn btn-success w-100">
                <i data-feather="plus" class="me-2"></i>New Loan
            </a>
        </div>
        <div class="col-12 col-md-3">
            <a href="<?= APP_URL ?>/public/loans/index.php?status=application" class="btn btn-outline-secondary w-100">
                <i data-feather="inbox" class="me-2"></i>Applications Queue
            </a>
        </div>
        <div class="col-12 col-md-3">
            <a href="<?= APP_URL ?>/public/loans/index.php?status=active" class="btn btn-outline-primary w-100">
                <i data-feather="activity" class="me-2"></i>Active Loans
            </a>
        </div>
        <div class="col-12 col-md-3">
            <a href="<?= APP_URL ?>/public/reports/index.php?type=loans" class="btn btn-outline-dark w-100">
                <i data-feather="file-text" class="me-2"></i>Loans Report
            </a>
        </div>
    </div>

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
            <?php include_once BASE_PATH . '/templates/loans/list.php'; ?>
        </div>
    </div>
    </div>
</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>
