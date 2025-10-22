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
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone_number' => trim($_POST['phone_number'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'date_of_birth' => trim($_POST['date_of_birth'] ?? ''),
        'identification_type' => trim($_POST['identification_type'] ?? ''),
        'identification_number' => trim($_POST['identification_number'] ?? ''),
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

<main class="main-content">
    <div class="content-wrapper">
        <!-- Page Header with Icon -->
        <div class="notion-page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #e3f2fd;">
                            <i data-feather="user-plus" style="width: 24px; height: 24px; color: rgb(33, 150, 243);"></i>
                        </div>
                    </div>
                    <h1 class="notion-page-title mb-0">Add New Client Account</h1>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <a href="<?= APP_URL ?>/public/clients/index.php" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="arrow-left" class="me-1" style="width: 14px; height: 14px;"></i> Back to Clients
                    </a>
                </div>
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
    </div>
</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>