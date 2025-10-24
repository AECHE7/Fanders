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

// Default focus on approvals queue (pending applications)
if (empty($filters['status'])) {
    $filters['status'] = 'application';
}

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
        <!-- Modern Page Header -->
        <div class="notion-page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="page-icon rounded d-flex align-items-center justify-content-center" 
                             style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i data-feather="check-square" style="width: 24px; height: 24px; color: white;"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="notion-page-title mb-0">Loan Approvals</h1>
                        <p class="text-muted small mb-0">Manage loan applications, approvals, and disbursements</p>
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <div class="text-muted d-none d-md-block me-3">
                        <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i>
                        <?= date('l, F j, Y') ?>
                    </div>
                    <a href="<?= APP_URL ?>/public/loans/add.php" class="btn btn-sm btn-primary">
                        <i data-feather="plus" class="me-1" style="width: 14px; height: 14px;"></i> New Loan
                    </a>
                    <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-sm btn-outline-secondary px-3">
                        <i data-feather="list" class="me-1" style="width: 14px; height: 14px;"></i> All Loans
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

        <!-- Quick Actions Tabs -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6 col-lg-3">
                <a href="<?= APP_URL ?>/public/loans/approvals.php?status=application" 
                   class="card h-100 text-decoration-none <?= $filters['status'] === 'application' ? 'border-primary' : '' ?> hover-shadow">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px; background-color: #e3f2fd;">
                                <i data-feather="inbox" style="width: 24px; height: 24px; color: #1976d2;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0 text-dark">Pending</h6>
                            <p class="text-muted small mb-0">Applications</p>
                        </div>
                        <div class="badge bg-primary rounded-pill"><?= $approvalStats['pending_applications'] ?></div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="<?= APP_URL ?>/public/loans/approvals.php?status=approved" 
                   class="card h-100 text-decoration-none <?= $filters['status'] === 'approved' ? 'border-warning' : '' ?> hover-shadow">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px; background-color: #fff3e0;">
                                <i data-feather="check" style="width: 24px; height: 24px; color: #f57c00;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0 text-dark">Approved</h6>
                            <p class="text-muted small mb-0">Awaiting Disbursement</p>
                        </div>
                        <div class="badge bg-warning rounded-pill"><?= $approvalStats['approved_pending_disbursement'] ?></div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="<?= APP_URL ?>/public/loans/index.php?status=active" 
                   class="card h-100 text-decoration-none hover-shadow">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px; background-color: #e8f5e9;">
                                <i data-feather="activity" style="width: 24px; height: 24px; color: #388e3c;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0 text-dark">Active</h6>
                            <p class="text-muted small mb-0">Loans</p>
                        </div>
                        <div class="badge bg-success rounded-pill"><?= $loanStats['active_loans'] ?? 0 ?></div>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="<?= APP_URL ?>/public/loans/index.php" 
                   class="card h-100 text-decoration-none hover-shadow">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px; background-color: #f3e5f5;">
                                <i data-feather="list" style="width: 24px; height: 24px; color: #7b1fa2;"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0 text-dark">All Loans</h6>
                            <p class="text-muted small mb-0">View All</p>
                        </div>
                        <div class="badge bg-secondary rounded-pill"><?= $loanStats['total_loans'] ?? 0 ?></div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Statistics Cards Row -->
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm h-100 border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body text-white">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <p class="mb-1 opacity-75 small text-uppercase">Total Disbursed</p>
                                <h3 class="mb-0 fw-bold">₱<?= number_format($loanStats['total_disbursed'] ?? 0, 2) ?></h3>
                            </div>
                            <div class="bg-white bg-opacity-25 rounded p-2">
                                <i data-feather="dollar-sign" style="width: 24px; height: 24px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm h-100 border-0" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="card-body text-white">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <p class="mb-1 opacity-75 small text-uppercase">Disbursed Today</p>
                                <h3 class="mb-0 fw-bold"><?= $approvalStats['disbursed_today'] ?></h3>
                            </div>
                            <div class="bg-white bg-opacity-25 rounded p-2">
                                <i data-feather="trending-up" style="width: 24px; height: 24px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm h-100 border-0" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="card-body text-white">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <p class="mb-1 opacity-75 small text-uppercase">Approved This Week</p>
                                <h3 class="mb-0 fw-bold"><?= $approvalStats['approved_this_week'] ?></h3>
                            </div>
                            <div class="bg-white bg-opacity-25 rounded p-2">
                                <i data-feather="check-circle" style="width: 24px; height: 24px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card shadow-sm h-100 border-0" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <div class="card-body text-white">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <p class="mb-1 opacity-75 small text-uppercase">Overdue Loans</p>
                                <h3 class="mb-0 fw-bold"><?= $loanStats['overdue_loans_count'] ?? 0 ?></h3>
                            </div>
                            <div class="bg-white bg-opacity-25 rounded p-2">
                                <i data-feather="alert-triangle" style="width: 24px; height: 24px;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced Filters Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i data-feather="filter" class="me-2" style="width: 18px; height: 18px;"></i>
                        Search & Filter
                    </h5>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#filterCollapse" aria-expanded="true">
                        <i data-feather="chevron-down" style="width: 16px; height: 16px;"></i>
                    </button>
                </div>
            </div>
            <div class="collapse show" id="filterCollapse">
                <div class="card-body">
                    <form method="GET" action="<?= APP_URL ?>/public/loans/approvals.php" class="row g-3">
                        <div class="col-md-4">
                            <label for="search" class="form-label fw-semibold">Search</label>
                            <div class="input-group">
                                <span class="input-group-text"><i data-feather="search" style="width: 16px; height: 16px;"></i></span>
                                <input type="text" class="form-control" id="search" name="search"
                                    value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
                                    placeholder="Client name, phone, or loan ID...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="application" <?= ($filters['status'] ?? '') === 'application' ? 'selected' : '' ?>>Application (Pending)</option>
                                <option value="approved" <?= ($filters['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active (Paying)</option>
                                <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="defaulted" <?= ($filters['status'] ?? '') === 'defaulted' ? 'selected' : '' ?>>Defaulted</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="client_id" class="form-label fw-semibold">Client</label>
                            <select class="form-select" id="client_id" name="client_id">
                                <option value="">All Clients</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?= $client['id'] ?>" <?= ($filters['client_id'] ?? '') == $client['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($client['name']) ?> (ID: <?= $client['id'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="limit" class="form-label fw-semibold">Items per page</label>
                            <select class="form-select" id="limit" name="limit">
                                <?php foreach ([10,20,50,100] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= (int)($limit ?? 20) === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_from" class="form-label fw-semibold">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from"
                                value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label fw-semibold">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to"
                                value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="filter" class="me-1" style="width: 16px; height: 16px;"></i> Apply Filters
                            </button>
                            <a href="<?= APP_URL ?>/public/loans/approvals.php" class="btn btn-outline-secondary">
                                <i data-feather="x" class="me-1" style="width: 16px; height: 16px;"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Loans List Table Card -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">
                            <?php
                            $statusLabel = '';
                            switch($filters['status'] ?? '') {
                                case 'application': $statusLabel = 'Pending Applications'; break;
                                case 'approved': $statusLabel = 'Approved Loans'; break;
                                case 'active': $statusLabel = 'Active Loans'; break;
                                case 'completed': $statusLabel = 'Completed Loans'; break;
                                case 'defaulted': $statusLabel = 'Defaulted Loans'; break;
                                default: $statusLabel = 'All Loans';
                            }
                            echo $statusLabel;
                            ?>
                        </h5>
                        <p class="text-muted small mb-0">
                            Showing <?= count($loans) ?> of <?= $totalLoans ?> loans
                        </p>
                    </div>
                    <div class="btn-group" role="group">
                        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'pdf'])) ?>" 
                           class="btn btn-sm btn-outline-danger">
                            <i data-feather="file-text" style="width: 14px; height: 14px;"></i> PDF
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>" 
                           class="btn btn-sm btn-outline-success">
                            <i data-feather="download" style="width: 14px; height: 14px;"></i> Excel
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php
                // Provide template variables expected by `templates/loans/listapp.php`
                // Ensure CSRF token is available for the hidden POST form and action handlers
                $csrfToken = isset($csrf) && method_exists($csrf, 'getToken') ? $csrf->getToken() : '';
                // Provide current user role for role-specific UI checks inside the template
                $userRole = $auth->getCurrentUser()['role'] ?? null;

                include_once BASE_PATH . '/templates/loans/listapp.php';
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
