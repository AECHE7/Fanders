<?php
/**
 * View Client Controller (view.php)
 * Role: Displays a client's detailed profile and their complete loan history.
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';
require_once BASE_PATH . '/app/utilities/Permissions.php';

// Enforce role-based access control (Staff roles: Admin, Manager, Cashier, AO)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier', 'account-officer']);

// --- 1. Get Client ID and Initial Data ---
$clientId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($clientId <= 0) {
    $session->setFlash('error', 'Client ID is missing or invalid.');
    header('Location: ' . APP_URL . '/public/clients/index.php');
    exit;
}

// Initialize services
$clientService = new ClientService();
$loanService = new LoanService();
$csrfToken = $csrf->generateToken(); // Pass token to template for status forms
$currentUser = $auth->getCurrentUser() ?: [];
$currentRole = $currentUser['role'] ?? '';

// Fetch client profile
$clientData = $clientService->getById($clientId);

if (!$clientData) {
    $session->setFlash('error', 'Client profile not found.');
    header('Location: ' . APP_URL . '/public/clients/index.php');
    exit;
}

// --- 2. Fetch Core Financial Data ---
// Get all loan history (active, completed, defaulted) for this client
$loanHistory = $loanService->getLoansByClient($clientId);

// Determine if the client has any active loan (critical for management decisions)
$hasActiveLoan = false;
foreach ($loanHistory as $loan) {
    if ($loan['status'] === LoanModel::STATUS_ACTIVE) {
        $hasActiveLoan = true;
        break;
    }
}

// --- 3. Handle POST Actions (Status Management) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$csrf->validateRequest(false)) {
        $session->setFlash('error', 'Invalid security token. Please try again.');
        header('Location: ' . APP_URL . '/public/clients/view.php?id=' . $clientId);
        exit;
    }

    // Only high-level staff can manage client status
    if (!$auth->hasRole(['super-admin', 'admin'])) {
        $session->setFlash('error', 'You do not have permission to manage client status.');
        header('Location: ' . APP_URL . '/public/clients/view.php?id=' . $clientId);
        exit;
    }

    $success = false;
    $action = $_POST['action'];

    switch ($action) {
        case 'activate':
            $success = $clientService->activateClient($clientId);
            break;
        case 'deactivate':
            $success = $clientService->deactivateClient($clientId);
            break;
        case 'blacklist':
            $success = $clientService->blacklistClient($clientId);
            break;
        case 'delete':
            $success = $clientService->deleteClient($clientId);
            if ($success) {
                $session->setFlash('success', 'Client record deleted and associated data archived.');
                header('Location: ' . APP_URL . '/public/clients/index.php');
                exit;
            }
            break;
    }

    if ($success) {
        $session->setFlash('success', "Client status successfully updated to " . strtoupper($action) . ".");
    } else {
        $session->setFlash('error', $clientService->getErrorMessage() ?: "Failed to perform action '{$action}'.");
    }

    // Redirect back to refresh data
    header('Location: ' . APP_URL . '/public/clients/view.php?id=' . $clientId);
    exit;
}

// --- 4. Display View ---
$pageTitle = "Client Profile: " . htmlspecialchars($clientData['name'] ?? 'N/A');
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';

// Helper function to get status badge class (used in the HTML below)
function getClientStatusBadgeClass($status) {
    switch($status) {
        case 'active': return 'success';
        case 'inactive': return 'warning';
        case 'blacklisted': return 'danger';
        default: return 'secondary';
    }
}
?>

<main class="main-content">
    <div class="content-wrapper">
        <!-- Page Header with Icon -->
        <div class="notion-page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f3e5f5;">
                            <i data-feather="user" style="width: 24px; height: 24px; color: rgb(156, 39, 176);"></i>
                        </div>
                    </div>
                    <h1 class="notion-page-title mb-0">Client Profile: <?= htmlspecialchars($clientData['name'] ?? 'N/A') ?></h1>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <?php if (Permissions::canCreateLoan($currentRole)): ?>
                        <a href="<?= APP_URL ?>/public/loans/add.php?client_id=<?= $clientId ?>" class="btn btn-sm btn-success <?= $hasActiveLoan ? 'disabled' : '' ?>" title="<?= $hasActiveLoan ? 'Client has an active loan' : 'Create New Loan' ?>">
                            <i data-feather="plus-circle" class="me-1" style="width: 14px; height: 14px;"></i> Apply New Loan
                        </a>
                    <?php endif; ?>
                    <?php if (Permissions::canManageClients($currentRole)): ?>
                        <a href="<?= APP_URL ?>/public/clients/edit.php?id=<?= $clientId ?>" class="btn btn-sm btn-outline-secondary">
                            <i data-feather="edit" class="me-1" style="width: 14px; height: 14px;"></i> Edit Profile
                        </a>
                    <?php endif; ?>
                    <a href="<?= APP_URL ?>/public/clients/index.php" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="arrow-left" class="me-1" style="width: 14px; height: 14px;"></i> Back to Clients
                    </a>
                </div>
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
    
    <div class="row">
        <!-- Client Details Card (Left Column) -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <h5 class="mb-0">Client Information (ID: <?= $clientId ?>)</h5>
                    <span class="badge text-bg-<?= getClientStatusBadgeClass($clientData['status']) ?>">
                        <?= htmlspecialchars(ucfirst($clientData['status'])) ?>
                    </span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Full Name:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($clientData['name'] ?? 'N/A') ?></dd>
                        
                        <dt class="col-sm-5">Phone Number:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($clientData['phone_number'] ?? 'N/A') ?></dd>
                        
                        <dt class="col-sm-5">Email:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($clientData['email'] ?? 'N/A') ?></dd>
                        
                        <dt class="col-sm-5">Date of Birth:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($clientData['date_of_birth'] ? date('M d, Y', strtotime($clientData['date_of_birth'])) : 'N/A') ?></dd>
                        
                        <dt class="col-sm-5">Address:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($clientData['address'] ?? 'N/A') ?></dd>
                        
                        <dt class="col-sm-5">Primary ID:</dt>
                        <dd class="col-sm-7"><?= htmlspecialchars($clientData['identification_type'] ? ucfirst($clientData['identification_type']) . " - " . $clientData['identification_number'] : 'N/A') ?></dd>
                        
                        <dt class="col-sm-5 text-muted">Joined On:</dt>
                        <dd class="col-sm-7 text-muted small"><?= htmlspecialchars($clientData['created_at'] ? date('M d, Y H:i A', strtotime($clientData['created_at'])) : 'N/A') ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Status Management Card (Right Column) -->
        <?php if (Permissions::canManageClients($currentRole)): ?>
        <div class="col-md-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">Management Actions</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Quick actions to manage client status. Note: **Deactivating** a client prevents new loans but preserves history.</p>

                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($clientData['status'] !== 'active'): ?>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#activateClientModal">
                                <i data-feather="user-check"></i> Activate
                            </button>
                        <?php endif; ?>

                        <?php if ($clientData['status'] === 'active'): ?>
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#deactivateClientModal">
                                <i data-feather="user-minus"></i> Deactivate
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($clientData['status'] !== 'blacklisted'): ?>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#blacklistClientModal">
                                <i data-feather="slash"></i> Blacklist
                            </button>
                        <?php endif; ?>

                        <?php if (!$hasActiveLoan && Permissions::isAllowed($currentRole, ['super-admin', 'admin'])): ?>
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteClientModal">
                                <i data-feather="trash-2"></i> Delete Record
                            </button>
                        <?php endif; ?>
                    </div>

                    <?php if ($hasActiveLoan): ?>
                        <div class="alert alert-info mt-3 mb-0">
                            Client has an **Active Loan**. Status changes may be limited, and the record **cannot be deleted**.
                        </div>
                    <?php endif; ?>

                </div>
            </div>
    </div>
    <?php endif; ?>
    </div>

    <!-- Loan History Card (Bottom Section) -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Loan History (<?= count($loanHistory) ?> total loans)</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($loanHistory)): ?>
                <div class="alert alert-info m-4" role="alert">
                    This client has no loan history on record.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">Loan ID</th>
                                <th style="width: 15%;">Principal</th>
                                <th style="width: 15%;">Total Due</th>
                                <th style="width: 10%;">Weekly Pay</th>
                                <th style="width: 15%;">Disbursed Date</th>
                                <th style="width: 15%;">Status</th>
                                <th style="width: 25%;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($loanHistory as $loan): ?>
                                <tr>
                                    <td><?= htmlspecialchars($loan['id']) ?></td>
                                    <td>₱<?= number_format($loan['principal'] ?? 0, 2) ?></td>
                                    <td>₱<?= number_format($loan['total_loan_amount'] ?? 0, 2) ?></td>
                                    <td>₱<?= number_format($loan['weekly_payment'] ?? 0, 2) ?></td>
                                    <td><?= htmlspecialchars($loan['disbursement_date'] ? date('M d, Y', strtotime($loan['disbursement_date'])) : 'Pending') ?></td>
                                    <td>
                                        <span class="badge text-bg-<?= $loan['status'] === 'active' ? 'primary' : ($loan['status'] === 'completed' ? 'success' : 'secondary') ?>">
                                            <?= htmlspecialchars(ucfirst($loan['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $loan['id'] ?>" class="btn btn-sm btn-outline-info">
                                            <i data-feather="file-text"></i> View Loan
                                        </a>
                                        <?php if ($loan['status'] === 'active'): ?>
                                            <a href="<?= APP_URL ?>/public/payments/record.php?loan_id=<?= $loan['id'] ?>" class="btn btn-sm btn-success">
                                                <i data-feather="dollar-sign"></i> Record Payment
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Activate Client Modal -->
<div class="modal fade" id="activateClientModal" tabindex="-1" aria-labelledby="activateClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="activateClientModalLabel">
                    <i data-feather="user-check"></i> Confirm Client Activation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>You are about to activate client:</p>
                <ul class="list-unstyled ms-3">
                    <li><strong>Name:</strong> <?= htmlspecialchars($clientData['name']) ?></li>
                    <li><strong>Current Status:</strong> <span class="badge text-bg-secondary"><?= ucfirst($clientData['status']) ?></span></li>
                </ul>
                <div class="alert alert-info mt-3">
                    <i data-feather="info"></i> Activating this client will allow them to apply for new loans.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $clientId ?>" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="action" value="activate">
                    <button type="submit" class="btn btn-success">
                        <i data-feather="user-check"></i> Confirm Activation
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Deactivate Client Modal -->
<div class="modal fade" id="deactivateClientModal" tabindex="-1" aria-labelledby="deactivateClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title" id="deactivateClientModalLabel">
                    <i data-feather="user-minus"></i> Confirm Client Deactivation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i data-feather="alert-triangle"></i> <strong>Warning:</strong> Deactivating this client will prevent new loan applications.
                </div>
                <p>Client to deactivate:</p>
                <ul class="list-unstyled ms-3">
                    <li><strong>Name:</strong> <?= htmlspecialchars($clientData['name']) ?></li>
                    <li><strong>Phone:</strong> <?= htmlspecialchars($clientData['phone_number']) ?></li>
                </ul>
                <p class="text-muted small">Note: This preserves all history and existing loans.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $clientId ?>" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="action" value="deactivate">
                    <button type="submit" class="btn btn-warning">
                        <i data-feather="user-minus"></i> Confirm Deactivation
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Blacklist Client Modal -->
<div class="modal fade" id="blacklistClientModal" tabindex="-1" aria-labelledby="blacklistClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="blacklistClientModalLabel">
                    <i data-feather="slash"></i> Confirm Client Blacklist
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i data-feather="alert-triangle"></i> <strong>DANGER:</strong> Blacklisting is permanent and prevents ALL future loans.
                </div>
                <p>Client to blacklist:</p>
                <ul class="list-unstyled ms-3">
                    <li><strong>Name:</strong> <?= htmlspecialchars($clientData['name']) ?></li>
                    <li><strong>Phone:</strong> <?= htmlspecialchars($clientData['phone_number']) ?></li>
                    <li><strong>Address:</strong> <?= htmlspecialchars($clientData['address']) ?></li>
                </ul>
                <p class="text-danger fw-bold">This action should only be taken for serious violations.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $clientId ?>" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="action" value="blacklist">
                    <button type="submit" class="btn btn-danger">
                        <i data-feather="slash"></i> Confirm Blacklist
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Client Modal -->
<div class="modal fade" id="deleteClientModal" tabindex="-1" aria-labelledby="deleteClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteClientModalLabel">
                    <i data-feather="trash-2"></i> Confirm Client Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i data-feather="alert-triangle"></i> <strong>PERMANENT DELETION:</strong> This will permanently delete the client record and all loan history.
                </div>
                <p>Client to delete:</p>
                <ul class="list-unstyled ms-3">
                    <li><strong>ID:</strong> <?= htmlspecialchars($clientData['id']) ?></li>
                    <li><strong>Name:</strong> <?= htmlspecialchars($clientData['name']) ?></li>
                    <li><strong>Phone:</strong> <?= htmlspecialchars($clientData['phone_number']) ?></li>
                </ul>
                <p class="text-danger fw-bold">THIS ACTION CANNOT BE UNDONE!</p>
                <p class="text-muted small">Note: Only available when client has no active loans.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $clientId ?>" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger">
                        <i data-feather="trash-2"></i> Confirm Deletion
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
    </div>
</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>