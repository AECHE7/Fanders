<?php
/**
 * Loan Approvals Controller (approvals.php)
 * Role: Dedicated interface for managing loan applications and approvals
 * Features: Application queue, approval workflow, disbursement tracking
 * Integrates: LoanService, ClientService
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control (Managers and Admins primarily)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account-officer']);

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

// Default focus on approvals queue (pending applications only)
if (empty($filters['status'])) {
    $filters['status'] = 'application';
}

// Force show only loans that need approval (application status)
$filters['status'] = 'application';

// Rename start_date/end_date to date_from/date_to for consistency
if (isset($_GET['start_date'])) {
    $filters['date_from'] = $_GET['start_date'];
}
if (isset($_GET['end_date'])) {
    $filters['date_to'] = $_GET['end_date'];
}

$filters = FilterUtility::validateDateRange($filters);

// --- 2. Fetch Core Data with Enhanced Error Handling ---

try {
    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

    // Inject pagination into filters
    $filters['page'] = $page;
    $filters['limit'] = $limit;

    // Get loans based on applied filters with pagination
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
    
    // Get approval-specific stats
    $approvalStats = [
        'pending_applications' => $loanService->getTotalLoansCount(['status' => 'application']),
        'approved_pending_disbursement' => $loanService->getTotalLoansCount(['status' => 'approved']),
        'disbursed_today' => $loanService->getDisbursedTodayCount(),
        'approved_this_week' => $loanService->getApprovedThisWeekCount()
    ];
} catch (Exception $e) {
    require_once '../../app/utilities/ErrorHandler.php';
    $errorMessage = ErrorHandler::handleApplicationError('loading loan approval data', $e, [
        'filters' => $filters,
        'user_id' => $auth->getCurrentUser()['id'] ?? null
    ]);
    
    $session->setFlash('error', $errorMessage);
    
    // Set default empty values
    $loans = [];
    $pagination = ['total_records' => 0, 'current_page' => 1, 'total_pages' => 1];
    $clients = [];
    $loanStats = [];
    $approvalStats = [
        'pending_applications' => 0,
        'approved_pending_disbursement' => 0,
        'disbursed_today' => 0,
        'approved_this_week' => 0
    ];
}

// --- 3. Handle PDF Export with Safe Wrapper ---
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    try {
        $reportService = new ReportService();
        $exportData = $loanService->getAllLoansWithClients(array_merge($filters, ['page' => 1, 'limit' => 10000]));
        
        // Use SafeExportWrapper for enhanced reliability
        require_once '../../app/utilities/SafeExportWrapper.php';
        SafeExportWrapper::wrapExport(function() use ($reportService, $exportData, $filters) {
            $reportService->exportLoanReportPDF($exportData, $filters);
        }, 'Loan Approvals Report', 'pdf');
    } catch (Exception $e) {
        $session->setFlash('error', 'Error exporting PDF: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/loans/approvals.php?' . http_build_query($filters));
        exit;
    }
    exit;
}

// --- 3b. Handle Excel Export ---
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    try {
        $reportService = new ReportService();
        $exportData = $loanService->getAllLoansWithClients(array_merge($filters, ['page' => 1, 'limit' => 10000]));
        
        require_once '../../app/utilities/SafeExportWrapper.php';
        SafeExportWrapper::wrapExport(function() use ($reportService, $exportData, $filters) {
            $reportService->exportLoanReportExcel($exportData, $filters);
        }, 'Loan Approvals Report', 'excel');
    } catch (Exception $e) {
        $session->setFlash('error', 'Error exporting Excel: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/loans/approvals.php?' . http_build_query($filters));
        exit;
    }
    exit;
}

// --- 4. Handle POST Actions (Approve/Disburse/Cancel) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$csrf->validateRequest()) {
        $session->setFlash('error', 'Invalid security token. Please try again.');
        header('Location: ' . APP_URL . '/public/loans/approvals.php');
        exit;
    }

    // Only Managers/Admins can perform approval/disbursement
    if (!$auth->hasRole(['super-admin', 'admin', 'manager'])) {
        $session->setFlash('error', 'You do not have permission for this action.');
        header('Location: ' . APP_URL . '/public/loans/approvals.php');
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
                    $message = $success ? 'Funds disbursed successfully. Loan is now active.' : $loanService->getErrorMessage();
                    break;
                case 'cancel':
                    $success = $loanService->cancelLoan($loanId, $userId);
                    $message = $success ? 'Loan application cancelled.' : $loanService->getErrorMessage();
                    break;
                default:
                    $message = 'Invalid action specified.';
            }
        } catch (Exception $e) {
            $message = "Database error during action: " . $e->getMessage();
        }
    }

    $session->setFlash($success ? 'success' : 'error', $message);
    header('Location: ' . APP_URL . '/public/loans/approvals.php?' . http_build_query($filters));
    exit;
}

// --- 5. Display View ---
$pageTitle = "Loan Approvals & Management";

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
                            <i data-feather="check-square" style="width: 24px; height: 24px; color:rgb(0, 0, 0);"></i>
                        </div>
                    </div>
                    <h1 class="notion-page-title mb-0">Loan Approvals</h1>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <div class="text-muted d-none d-md-block me-3">
                        <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i>
                        <?= date('l, F j, Y') ?>
                    </div>
                    <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-sm btn-outline-secondary px-3">
                        <i data-feather="list" class="me-1" style="width: 14px; height: 14px;"></i> All Loans
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
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i data-feather="check-circle" class="me-2"></i>
                <?= $session->getFlash('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($session->hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i data-feather="alert-triangle" class="me-2"></i>
                <?= $session->getFlash('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>



        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-warning shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Pending Applications</h6>
                                <h3 class="mb-0"><?= $approvalStats['pending_applications'] ?? 0 ?></h3>
                            </div>
                            <i data-feather="inbox" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Approved (Pending Disbursement)</h6>
                                <h3 class="mb-0"><?= $approvalStats['approved_pending_disbursement'] ?? 0 ?></h3>
                            </div>
                            <i data-feather="check" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Approved This Week</h6>
                                <h3 class="mb-0"><?= $approvalStats['approved_this_week'] ?? 0 ?></h3>
                            </div>
                            <i data-feather="check-circle" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-primary shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Disbursed Today</h6>
                                <h3 class="mb-0"><?= $approvalStats['disbursed_today'] ?? 0 ?></h3>
                            </div>
                            <i data-feather="trending-up" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <form method="GET" action="<?= APP_URL ?>/public/loans/approvals.php" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search Client/ID</label>
                        <input type="text" class="form-control" id="search" name="search"
                            value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                            placeholder="Client name, phone, or loan ID...">
                    </div>
                    <div class="col-md-3">
                        <label for="client_id" class="form-label">Client</label>
                        <select class="form-select" id="client_id" name="client_id">
                            <option value="">All Clients</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>" <?= ($filters['client_id'] ?? '') == $client['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($client['name']) ?> (ID: <?= $client['id'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from"
                            value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to"
                            value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Filter</button>
                        <a href="<?= APP_URL ?>/public/loans/approvals.php" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Loans Approval List Table Card -->
        <div class="card shadow-sm">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Loans Pending Approval</h5>
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
                <?php
                // Provide template variables expected by `templates/loans/list_approval.php`
                // Ensure CSRF token is available for the hidden POST form and action handlers
                $csrfToken = isset($csrf) && method_exists($csrf, 'getToken') ? $csrf->getToken() : '';
                // Provide current user role for role-specific UI checks inside the template
                $userRole = $auth->getCurrentUser()['role'] ?? null;

                include_once BASE_PATH . '/templates/loans/list_approval.php';
                ?>
            </div>
        </div>
    </div>
</main>

<style>
    .hover-shadow {
        transition: all 0.3s ease;
    }
    .hover-shadow:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
    }
    .card {
        border-radius: 12px;
    }
    .btn {
        border-radius: 8px;
    }
    .badge {
        font-weight: 600;
    }
</style>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>
