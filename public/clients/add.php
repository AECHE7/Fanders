<?php
/**
 * Add Client Controller (add.php)
 * Role: Handles the form submission for creating a new client account.
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control (Only Admin/Manager can create new clients)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager']);

// Initialize client service
$clientService = new ClientService();

// Default structure for the form (used to retain values after a failed submission)
$newClient = [
    'name' => '',
    'email' => '',
    'phone_number' => '',
    'address' => '',
    'date_of_birth' => '',
    'identification_type' => '',
    'identification_number' => '',
    'status' => 'active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validate CSRF token (don't regenerate to avoid interference with session timeout checks)
    if (!$csrf->validateRequest(false)) {
        // Debug information in development mode
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("CSRF Token Validation Failed for Client Add");
            error_log("POST Token: " . ($_POST['csrf_token'] ?? 'MISSING'));
            error_log("Session Token: " . $csrf->getToken());
        }
        $session->setFlash('error', 'Invalid security token. Please refresh and try again.');
        header('Location: ' . APP_URL . '/public/clients/add.php');
        exit;
    }

    // 2. Gather and sanitize input
    $newClient = [
        'name' => $_POST['name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone_number' => $_POST['phone_number'] ?? '',
        'address' => $_POST['address'] ?? '',
        'date_of_birth' => $_POST['date_of_birth'] ?? '',
        'identification_type' => $_POST['identification_type'] ?? '',
        'identification_number' => $_POST['identification_number'] ?? '',
        // Status is forced to active upon creation
        'status' => ClientModel::STATUS_ACTIVE,
    ];

    // 3. Attempt to create client via service
    $clientId = $clientService->createClient($newClient);

    if ($clientId) {
        // Success: Redirect to the index page with a success message
        $session->setFlash('success', "Client '{$newClient['name']}' added successfully (ID: {$clientId}).");
        header('Location: ' . APP_URL . '/public/clients/index.php');
        exit;
    } else {
        // Failure: Store the specific error message from the service
        $error = $clientService->getErrorMessage() ?: "Failed to add client due to an unknown error.";
        
        // Log the error for debugging
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("Client Creation Failed: " . $error);
            error_log("Client Data: " . json_encode($newClient));
        }
        
        $session->setFlash('error', $error);
        // Note: $newClient still holds the submitted data for repopulating the form
    }
}

// --- Display View ---
$pageTitle = "Add New Client";
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Add New Client Account</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/clients/index.php" class="btn btn-sm btn-outline-secondary">
                <i data-feather="arrow-left"></i> Back to Clients List
            </a>
        </div>
    </div>
    
    <?php if ($session->hasFlash('error')): ?>
        <div class="alert alert-danger">
            <?= $session->getFlash('error') ?>
        </div>
    <?php endif; ?>
    
    <!-- Client Form -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php
            // The form template expects $clientData, so we assign $newClient to $clientData
            $clientData = $newClient;
            include_once BASE_PATH . '/templates/clients/form.php';
            ?>
        </div>
    </div>
</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>