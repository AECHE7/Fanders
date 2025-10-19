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

// Enhanced filter handling with validation
$filterOptions = [
    'allowed_statuses' => ['active', 'inactive', 'blacklisted']
];
$filters = FilterUtility::sanitizeFilters($_GET, $filterOptions);
$filters = FilterUtility::validateDateRange($filters);

// --- 2. Handle POST Actions (Status Change, Delete) --- (keeping existing POST logic unchanged)

// --- 2. Handle POST Actions (Status Change, Delete) ---
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

// --- 3. Fetch Data for View with Enhanced Filtering ---

try {
    // Get paginated clients with enhanced filtering
    $paginatedClients = $clientService->getPaginatedClients($filters);
    $clients = $paginatedClients['data'];
    $pagination = $paginatedClients['pagination'];

    // No need for client-side date filtering anymore as it's handled in the service

    // Prepare client status display map for the view
    $statusMap = [
        'active' => ['class' => 'bg-success', 'text' => 'Active'],
        'inactive' => ['class' => 'bg-warning', 'text' => 'Inactive'],
        'blacklisted' => ['class' => 'bg-danger', 'text' => 'Blacklisted']
    ];

    // Prepare filter summary for display
    $filterSummary = FilterUtility::getFilterSummary($filters);
} catch (Exception $e) {
    require_once '../../app/utilities/ErrorHandler.php';
    $errorMessage = ErrorHandler::handleApplicationError('loading client data', $e, [
        'filters' => $filters,
        'user_id' => $auth->getCurrentUser()['id'] ?? null
    ]);
    
    $session->setFlash('error', $errorMessage);
    
    // Set default empty values
    $clients = [];
    $pagination = ['total_records' => 0, 'current_page' => 1, 'total_pages' => 1];
    $statusMap = [
        'active' => ['class' => 'bg-success', 'text' => 'Active'],
        'inactive' => ['class' => 'bg-warning', 'text' => 'Inactive'],
        'blacklisted' => ['class' => 'bg-danger', 'text' => 'Blacklisted']
    ];
    $filterSummary = [];
}

$pageTitle = "Manage Clients";
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
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
    <?php include_once BASE_PATH . '/templates/clients/list.php'; ?>
</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>
