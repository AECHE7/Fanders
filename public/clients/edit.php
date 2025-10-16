<?php
/**
 * Edit Client Controller (edit.php)
 * Role: Handles fetching existing client data and processing updates to the profile.
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control (Only Admin/Manager can edit clients)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager']);

// --- 1. Get Client ID and Initial Data ---
$clientId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($clientId <= 0) {
    $session->setFlash('error', 'Client ID is missing or invalid.');
    header('Location: ' . APP_URL . '/public/clients/index.php');
    exit;
}

// Initialize client service
$clientService = new ClientService();

// Fetch existing client data
$clientData = $clientService->getById($clientId);

if (!$clientData) {
    $session->setFlash('error', 'Client not found.');
    header('Location: ' . APP_URL . '/public/clients/index.php');
    exit;
}

// --- 2. Process Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrf->validateRequest()) {
        $session->setFlash('error', 'Invalid security token. Please refresh and try again.');
        header('Location: ' . APP_URL . '/public/clients/edit.php?id=' . $clientId);
        exit;
    }

    // Gather and sanitize input (status is only updatable by high-level admins in the service)
    $updatedData = [
        'name' => $_POST['name'] ?? $clientData['name'],
        'email' => $_POST['email'] ?? $clientData['email'],
        'phone_number' => $_POST['phone_number'] ?? $clientData['phone_number'],
        'address' => $_POST['address'] ?? $clientData['address'],
        'date_of_birth' => $_POST['date_of_birth'] ?? $clientData['date_of_birth'],
        'identification_type' => $_POST['identification_type'] ?? $clientData['identification_type'],
        'identification_number' => $_POST['identification_number'] ?? $clientData['identification_number'],
        // Include status if user is allowed to post it (handled by validation in service layer)
        'status' => $_POST['status'] ?? $clientData['status']
    ];

    // Attempt to update client via service
    if ($clientService->updateClient($clientId, $updatedData)) {
        // Success: Redirect to the view page with a success message
        $session->setFlash('success', "Client '{$updatedData['name']}' updated successfully.");
        header('Location: ' . APP_URL . '/public/clients/view.php?id=' . $clientId);
        exit;
    } else {
        // Failure: Store the specific error message from the service
        $error = $clientService->getErrorMessage() ?: "Failed to update client due to an unknown error.";
        $session->setFlash('error', $error);
        // Fallback: Update $clientData with posted values so the form reflects the failed input
        $clientData = array_merge($clientData, $updatedData);
    }
}

// --- Display View ---
$pageTitle = "Edit Client: " . htmlspecialchars($clientData['name'] ?? 'N/A');
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Edit Client Information</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/clients/view.php?id=<?= $clientId ?>" class="btn btn-sm btn-outline-secondary me-2">
                <i data-feather="eye"></i> View Profile
            </a>
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
    
    <!-- Client Edit Form -->
    <div class="card shadow-sm">
        <div class="card-body">
            <?php
            // Pass the existing client data to the form template
            $clientData['is_editing'] = true; // Flag for form template logic if needed
            include_once BASE_PATH . '/templates/clients/form.php';
            ?>
        </div>
    </div>
</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>