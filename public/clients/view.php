<?php
/**
 * View Client Controller (view.php)
 * Role: Displays a client's detailed profile and their complete loan history.
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

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
    if (!$csrf->validateRequest()) {
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

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Client Profile: <?= htmlspecialchars($clientData['name'] ?? 'N/A') ?></h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="<?= APP_URL ?>/public/loans/add.php?client_id=<?= $clientId ?>" class="btn btn-sm btn-success <?= $hasActiveLoan ? 'disabled' : '' ?>" title="<?= $hasActiveLoan ? 'Client has an active loan' : 'Create New Loan' ?>">
                    <i data-feather="plus-circle"></i> Apply New Loan
                </a>
            </div>
            <a href="<?= APP_URL ?>/public/clients/edit.php?id=<?= $clientId ?>" class="btn btn-sm btn-outline-primary me-2">
                <i data-feather="edit"></i> Edit Profile
            </a>
            <a href="<?= APP_URL ?>/public/clients/index.php" class="btn btn-sm btn-outline-secondary">
                <i data-feather="arrow-left"></i> Back to Clients List
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
        <div class="col-md-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">Management Actions</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">Quick actions to manage client status. Note: **Deactivating** a client prevents new loans but preserves history.</p>

                    <div class="d-flex flex-wrap gap-2">
                        <?php if ($clientData['status'] !== 'active'): ?>
                            <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $clientId ?>" onsubmit="return confirm('Confirm activation of client: <?= htmlspecialchars($clientData['name']) ?>?');">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="action" value="activate">
                                <button type="submit" class="btn btn-success"><i data-feather="user-check"></i> Activate</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($clientData['status'] === 'active'): ?>
                            <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $clientId ?>" onsubmit="return confirm('WARNING: Deactivating this client prevents new loans. Continue?');">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="action" value="deactivate">
                                <button type="submit" class="btn btn-warning"><i data-feather="user-minus"></i> Deactivate</button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($clientData['status'] !== 'blacklisted'): ?>
                            <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $clientId ?>" onsubmit="return confirm('WARNING: Blacklisting is permanent and prevents ALL future loans. Continue?');">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="action" value="blacklist">
                                <button type="submit" class="btn btn-danger"><i data-feather="slash"></i> Blacklist</button>
                            </form>
                        <?php endif; ?>

                        <?php if (!$hasActiveLoan && $auth->hasRole(['super-admin', 'admin'])): ?>
                            <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= $clientId ?>" onsubmit="return confirm('DANGER: This will permanently DELETE the client record and all loan history (if no active loans exist). ARE YOU SURE?');">
                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn btn-outline-danger"><i data-feather="trash-2"></i> Delete Record</button>
                            </form>
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

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>