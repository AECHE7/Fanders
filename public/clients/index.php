<?php
/**
 * Client List Controller (index.php)
 * Role: Loads and filters all client data and handles client status/delete POST actions.
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control for Fanders Microfinance Staff
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account-officer']);

// Initialize services
$clientService = new ClientService();

// --- 1. Process Filters ---
require_once '../../app/utilities/FilterUtility.php';
$filters = FilterUtility::sanitizeFilters($_GET);
$filters = FilterUtility::validateDateRange($filters);

// --- 2. Handle PDF Export ---
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    try {
        $reportService = new ReportService();
        $exportData = $clientService->getAllClients(1, 10000, $filters); // Get all data without pagination
        $reportService->exportClientReportPDF($exportData, $filters);
    } catch (Exception $e) {
        $session->setFlash('error', 'Error exporting PDF: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/clients/index.php?' . http_build_query($filters));
        exit;
    }
    exit;
}

// --- 3. Handle POST Actions (Status Change, Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$csrf->validateRequest()) {
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
                case 'delete':
                    $success = $clientService->deleteClient($clientId);
                    $message = $success ? 'Client record permanently deleted.' : $clientService->getErrorMessage();
                    break;
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

// --- 3. Fetch Data for View ---

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

// Apply date filtering if specified (client-side for now, can be moved to service later)
if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
    $clients = array_filter($clients, function($client) use ($filters) {
        $createdDate = strtotime($client['created_at']);
        $fromCheck = empty($filters['date_from']) || $createdDate >= strtotime($filters['date_from']);
        $toCheck = empty($filters['date_to']) || $createdDate <= strtotime($filters['date_to'] . ' 23:59:59');
        return $fromCheck && $toCheck;
    });
}

// Prepare client status display map for the view
$statusMap = [
    'active' => ['class' => 'bg-success', 'text' => 'Active'],
    'inactive' => ['class' => 'bg-warning', 'text' => 'Inactive'],
    'blacklisted' => ['class' => 'bg-danger', 'text' => 'Blacklisted']
];

$pageTitle = "Manage Clients";
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Client Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/clients/add.php" class="btn btn-sm btn-primary">
                <i data-feather="user-plus"></i> Add New Client
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
                <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'pdf'])) ?>" class="btn btn-sm btn-success">
                    <i data-feather="download"></i> Export PDF
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
