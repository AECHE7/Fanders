<?php
/**
 * SLR Document Generator Controller
 * Generates Statement of Loan Repayment documents for clients.
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../init.php';
require_once BASE_PATH . '/app/utilities/Permissions.php';

// Enforce role-based access control (Staff roles can generate SLR documents)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier', 'account-officer']);

$currentUser = $auth->getCurrentUser() ?: [];
$currentRole = $currentUser['role'] ?? '';

// Initialize services
$slrService = new SLRDocumentService();
$loanService = new LoanService();
$clientService = new ClientService();

// Get action from URL parameter
$action = isset($_GET['action']) ? $_GET['action'] : 'view';

// Handle different actions
switch ($action) {
    case 'generate':
        handleGenerateSLR();
        break;
    case 'bulk':
        handleBulkSLR();
        break;
    case 'client':
        handleClientSLR();
        break;
    default:
        showSLRInterface();
        break;
}

/**
 * Show the SLR document generation interface
 */
function showSLRInterface() {
    global $loanService, $clientService, $session, $csrf, $auth;

    // Resolve current role within function scope for permission checks
    $currentUser = $auth && method_exists($auth, 'getCurrentUser') ? ($auth->getCurrentUser() ?: []) : [];
    $currentRole = $currentUser['role'] ?? '';

    // Get active loans for selection
    $activeLoans = $loanService->getAllActiveLoansWithClients();

    // Get all clients for client-specific SLR generation
    $clients = $clientService->getAllForSelect();

    $pageTitle = "SLR Document Generation";

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
                            <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #fff0f0;">
                                <i data-feather="file-text" style="width: 24px; height: 24px; color: rgb(220, 53, 69);"></i>
                            </div>
                        </div>
                        <h1 class="notion-page-title mb-0">
                            <i class="fas fa-file-pdf"></i> SLR Document Generation
                        </h1>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <?php if (Permissions::canAccessSLRDocuments($currentRole)): ?>
                            <a href="<?= APP_URL ?>/public/dashboard.php" class="btn btn-sm btn-outline-secondary">
                                <i data-feather="arrow-left" class="me-1" style="width: 14px; height: 14px;"></i> Back to Dashboard
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <!-- Flash Messages -->
        <?php if ($session->hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?= $session->getFlash('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($session->hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?= $session->getFlash('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php include_once BASE_PATH . '/templates/documents/slr_interface.php'; ?>
        </div>
    </main>

    <?php
    include_once BASE_PATH . '/templates/layout/footer.php';
}

/**
 * Handle single SLR generation
 */
function handleGenerateSLR() {
    global $slrService, $session;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: slr.php');
        exit;
    }

    $loanId = isset($_POST['loan_id']) ? (int)$_POST['loan_id'] : 0;

    if ($loanId <= 0) {
        $session->setFlash('error', 'Invalid loan ID.');
        header('Location: slr.php');
        exit;
    }

    // Generate SLR document
    $pdfContent = $slrService->generateSLRDocument($loanId);

    if ($pdfContent === false) {
        $session->setFlash('error', $slrService->getErrorMessage());
        header('Location: slr.php');
        exit;
    }

    // Clear any output buffers to prevent PDF corruption
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Output PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="SLR_Loan_' . $loanId . '_' . date('Y-m-d') . '.pdf"');
    header('Content-Length: ' . strlen($pdfContent));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    echo $pdfContent;
    exit;
}

/**
 * Handle bulk SLR generation
 */
function handleBulkSLR() {
    global $slrService, $session;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: slr.php');
        exit;
    }

    $loanIds = isset($_POST['loan_ids']) ? $_POST['loan_ids'] : [];

    if (empty($loanIds)) {
        $session->setFlash('error', 'No loans selected for bulk SLR generation.');
        header('Location: slr.php');
        exit;
    }

    // Generate bulk SLRs
    $documents = $slrService->generateBulkSLRDocuments($loanIds);

    if (empty($documents)) {
        $session->setFlash('error', 'Failed to generate SLR documents.');
        header('Location: slr.php');
        exit;
    }

    // Create ZIP file
    $zipFile = tempnam(sys_get_temp_dir(), 'slr_bulk_') . '.zip';

    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
        $session->setFlash('error', 'Failed to create ZIP file.');
        header('Location: slr.php');
        exit;
    }

    foreach ($documents as $loanId => $pdfContent) {
        $zip->addFromString("SLR_Loan_{$loanId}_" . date('Y-m-d') . '.pdf', $pdfContent);
    }

    $zip->close();

    // Clear any output buffers to prevent ZIP corruption
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Output ZIP file
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="SLR_Bulk_' . date('Y-m-d') . '.zip"');
    header('Content-Length: ' . filesize($zipFile));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    readfile($zipFile);
    unlink($zipFile);
    exit;
}

/**
 * Handle client SLR generation
 */
function handleClientSLR() {
    global $slrService, $session;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: slr.php');
        exit;
    }

    $clientId = isset($_POST['client_id']) ? (int)$_POST['client_id'] : 0;

    if ($clientId <= 0) {
        $session->setFlash('error', 'Invalid client ID.');
        header('Location: slr.php');
        exit;
    }

    // Generate client SLRs
    $documents = $slrService->generateClientSLRDocuments($clientId);

    if (empty($documents)) {
        $session->setFlash('error', 'No SLR documents could be generated for this client.');
        header('Location: slr.php');
        exit;
    }

    if (count($documents) === 1) {
        // Single document - output directly as PDF
        $loanId = key($documents);
        $pdfContent = current($documents);

        // Clear any output buffers to prevent PDF corruption
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="SLR_Client_' . $clientId . '_Loan_' . $loanId . '_' . date('Y-m-d') . '.pdf"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        echo $pdfContent;
    } else {
        // Multiple documents - create ZIP
        $zipFile = tempnam(sys_get_temp_dir(), 'slr_client_') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) !== TRUE) {
            $session->setFlash('error', 'Failed to create ZIP file.');
            header('Location: slr.php');
            exit;
        }

        foreach ($documents as $loanId => $pdfContent) {
            $zip->addFromString("SLR_Client_{$clientId}_Loan_{$loanId}_" . date('Y-m-d') . '.pdf', $pdfContent);
        }

        $zip->close();

        // Clear any output buffers to prevent ZIP corruption
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="SLR_Client_' . $clientId . '_' . date('Y-m-d') . '.zip"');
        header('Content-Length: ' . filesize($zipFile));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($zipFile);
        unlink($zipFile);
    }

    exit;
}
?>
