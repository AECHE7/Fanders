<?php
/**
 * Client List Controller (index.php)
 * Role: Loads and filters all client data and handles client status change POST actions.
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control for Fanders Microfinance Staff
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account-officer']);

// Initialize services
$clientService = new ClientService();

// --- 1. Process Filters ---
require_once '../../app/utilities/FilterUtility.php';

// Enhanced filter handling with validation
$filterOptions = [
    'allowed_statuses' => ['active', 'inactive', 'blacklisted']
];
$filters = FilterUtility::sanitizeFilters($_GET, $filterOptions);
$filters = FilterUtility::validateDateRange($filters);



// --- 3. Handle POST Actions (Status Change) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$csrf->validateRequest(false)) {
        $session->setFlash('error', 'Invalid security token. Please try again.');
        header('Location: ' . APP_URL . '/public/clients/index.php');
        exit;
    }

    $clientId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $success = false;
    $message = 'Action failed.';

    if ($clientId > 0) {
        try {
            switch ($_POST['action']) {
                case 'activate':
                    $success = $clientService->activateClient($clientId);
                    $message = $success ? 'Client status updated to Active.' : $clientService->getErrorMessage();
                    break;
                case 'deactivate':
                    // We must prevent deactivating clients with active loans, business logic is inside the service.
                    $success = $clientService->deactivateClient($clientId);
                    $message = $success ? 'Client status updated to Inactive.' : $clientService->getErrorMessage();
                    break;
                case 'blacklist':
                    $success = $clientService->blacklistClient($clientId);
                    $message = $success ? 'Client successfully blacklisted.' : $clientService->getErrorMessage();
                    break;
                default:
                    $session->setFlash('error', 'Invalid action specified.');
                    header('Location: ' . APP_URL . '/public/clients/index.php');
                    exit;
            }
        } catch (Exception $e) {
            $session->setFlash('error', "Database error during action: " . $e->getMessage());
            header('Location: ' . APP_URL . '/public/clients/index.php');
            exit;
        }
    }

    if ($success) {
        $session->setFlash('success', $message);
    } else {
        // If success is false, use the message set by the service, otherwise use the default failure message
        $session->setFlash('error', $clientService->getErrorMessage() ?: $message);
    }

    header('Location: ' . APP_URL . '/public/clients/index.php');
    exit;
}

// --- 3. Fetch Data for View with Enhanced Filtering ---

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

// Get clients based on applied filters with pagination
$clients = $clientService->getAllClients($page, $limit, $filters);

// Get total count for pagination
$totalClients = $clientService->getTotalClientsCount($filters);
$totalPages = ceil($totalClients / $limit);

// Initialize pagination utility
require_once '../../app/utilities/PaginationUtility.php';
$pagination = new PaginationUtility($totalClients, $page, $limit, 'page');

// Fetch client dashboard statistics (DB-backed)
$clientStats = [];
try {
    $clientStats = $clientService->getClientStats(true) ?: [];
} catch (Exception $e) {
    error_log('Client stats error: ' . $e->getMessage());
    $clientStats = [
        'total_clients' => 0,
        'active_clients' => 0,
        'clients_by_status' => [],
        'recent_clients' => []
    ];
}

$pageTitle = "Manage Clients";
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
                        <i data-feather="users" style="width: 24px; height: 24px; color:rgb(0, 0, 0);"></i>
                    </div>
                </div>
                <h1 class="notion-page-title mb-0">Client Management</h1>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <div class="text-muted d-none d-md-block me-3">
                    <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i>
                    <?= date('l, F j, Y') ?>
                </div>
                <a href="<?= APP_URL ?>/public/reports/clients.php" class="btn btn-sm btn-outline-secondary px-3">
                    <i data-feather="file-text" class="me-1" style="width: 14px; height: 14px;"></i> Clients Report
                </a>
                <a href="<?= APP_URL ?>/public/clients/add.php" class="btn btn-sm btn-primary">
                    <i data-feather="user-plus" class="me-1" style="width: 14px; height: 14px;"></i> Add Client
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
                <a href="<?= APP_URL ?>/public/clients/add.php" class="btn btn-primary w-100">
                    <i data-feather="user-plus" class="me-2"></i>Add Client
                </a>
            </div>
            <div class="col-12 col-md-3">
                <a href="<?= APP_URL ?>/public/reports/index.php?type=clients" class="btn btn-outline-secondary w-100">
                    <i data-feather="file-text" class="me-2"></i>Clients Report
                </a>
            </div>
            <div class="col-12 col-md-3">
                <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-outline-primary w-100">
                    <i data-feather="file-text" class="me-2"></i>Loans Module
                </a>
            </div>
            <div class="col-12 col-md-3">
                <a href="<?= APP_URL ?>/public/dashboard/index.php" class="btn btn-outline-dark w-100">
                    <i data-feather="arrow-left" class="me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-contrast shadow-sm metric-card metric-accent-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Total Clients</h6>
                                <h3 class="mb-0"><?= number_format((int)($clientStats['total_clients'] ?? 0)) ?></h3>
                            </div>
                            <i data-feather="users" class="icon-lg" style="width: 3rem; height: 3rem; color:#0d6efd;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-contrast shadow-sm metric-card metric-accent-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Active Clients</h6>
                                <h3 class="mb-0"><?= number_format((int)($clientStats['active_clients'] ?? 0)) ?></h3>
                            </div>
                            <i data-feather="check-circle" class="icon-lg" style="width: 3rem; height: 3rem; color:#198754;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-contrast shadow-sm metric-card metric-accent-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Inactive Clients</h6>
                                <?php
                                    // Derive inactive from clients_by_status if available
                                    $inactiveCount = 0;
                                    if (!empty($clientStats['clients_by_status']) && is_array($clientStats['clients_by_status'])) {
                                        foreach ($clientStats['clients_by_status'] as $row) {
                                            if (($row['status'] ?? '') === 'inactive') { $inactiveCount = (int)($row['count'] ?? 0); break; }
                                        }
                                    }
                                ?>
                                <h3 class="mb-0"><?= number_format($inactiveCount) ?></h3>
                            </div>
                            <i data-feather="pause-circle" class="icon-lg" style="width: 3rem; height: 3rem; color:#0dcaf0;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-contrast shadow-sm metric-card metric-accent-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Blacklisted</h6>
                                <?php
                                    $blacklistedCount = 0;
                                    if (!empty($clientStats['clients_by_status']) && is_array($clientStats['clients_by_status'])) {
                                        foreach ($clientStats['clients_by_status'] as $row) {
                                            if (($row['status'] ?? '') === 'blacklisted') { $blacklistedCount = (int)($row['count'] ?? 0); break; }
                                        }
                                    }
                                ?>
                                <h3 class="mb-0"><?= number_format($blacklistedCount) ?></h3>
                            </div>
                            <i data-feather="alert-triangle" class="icon-lg" style="width: 3rem; height: 3rem; color:#ffc107;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <!-- Filter/Search Form -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form action="<?= APP_URL ?>/public/clients/index.php" method="get" class="row g-3 align-items-center">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($filters['search']) ?>" placeholder="Name, email, or phone...">
                </div>

                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="" <?= $filters['status'] == '' ? 'selected' : '' ?>>All Status</option>
                        <option value="active" <?= $filters['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $filters['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="blacklisted" <?= $filters['status'] == 'blacklisted' ? 'selected' : '' ?>>Blacklisted</option>
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

                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="<?= APP_URL ?>/public/clients/index.php" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Clients List Table -->
    <div class="card shadow-sm">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Clients List</h5>
                <a href="<?= APP_URL ?>/public/reports/index.php?type=clients" class="btn btn-sm btn-outline-primary">
                    <i data-feather="file-text"></i> View Reports
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php include_once BASE_PATH . '/templates/clients/list.php'; ?>
        </div>
    </div>
</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>
