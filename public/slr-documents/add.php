<?php
/**
 * Add SLR Document - Fanders Microfinance System
 */

// Include configuration
require_once '../../app/config/config.php';

// Start output buffering
ob_start();

// Include all required files
function autoload($className) {
    // Define the directories to look in
    $directories = [
        'app/core/',
        'app/models/',
        'app/services/',
        'app/utilities/'
    ];

    // Try to find the class file
    foreach ($directories as $directory) {
        $file = BASE_PATH . '/' . $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}

// Register autoloader
spl_autoload_register('autoload');

// Initialize session management
$session = new Session();

// Initialize authentication service
$auth = new AuthService();

// Initialize CSRF protection
$csrf = new CSRF();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    // Redirect to login page
    $session->setFlash('error', 'Please login to access this page.');
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

// Check for session timeout
if ($auth->checkSessionTimeout()) {
    // Session has timed out, redirect to login page with message
    $session->setFlash('error', 'Your session has expired. Please login again.');
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

// Get current user data
$user = $auth->getCurrentUser();
$userRole = $user['role'];

// Check if user has permission to create SLR documents
if (!$auth->hasRole(['administrator', 'manager', 'account_officer'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Initialize services
$slrDocumentService = new SlrDocumentService();
$loanService = new LoanService();

// Get loan ID from URL if provided
$loanId = isset($_GET['loan_id']) ? (int)$_GET['loan_id'] : null;
$loan = null;

if ($loanId) {
    $loan = $loanService->getLoanWithDetails($loanId);
    if (!$loan) {
        $session->setFlash('error', 'Loan not found.');
        header('Location: ' . APP_URL . '/public/loans/index.php');
        exit;
    }

    // Check if loan is approved
    if ($loan['status'] !== 'approved') {
        $session->setFlash('error', 'SLR document can only be created for approved loans.');
        header('Location: ' . APP_URL . '/public/loans/view.php?id=' . $loanId);
        exit;
    }

    // Check if SLR already exists for this loan
    $existingSlr = $slrDocumentService->getSlrDetails($loanId);
    if ($existingSlr) {
        $session->setFlash('error', 'SLR document already exists for this loan.');
        header('Location: ' . APP_URL . '/public/loans/view.php?id=' . $loanId);
        exit;
    }
}

// Initialize form data
$formData = [
    'loan_id' => $loanId ?? '',
    'disbursement_amount' => $loan ? $loan['loan_amount'] : '',
    'disbursement_date' => date('Y-m-d'),
    'client_present' => true,
    'witness_name' => '',
    'notes' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrf->validateToken($_POST['csrf_token'] ?? '')) {
        $session->setFlash('error', 'Invalid security token. Please try again.');
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    // Get form data
    $formData = [
        'loan_id' => $_POST['loan_id'] ?? '',
        'disbursement_amount' => $_POST['disbursement_amount'] ?? '',
        'disbursement_date' => $_POST['disbursement_date'] ?? '',
        'client_present' => isset($_POST['client_present']),
        'witness_name' => $_POST['witness_name'] ?? '',
        'notes' => $_POST['notes'] ?? ''
    ];

    // Validate form data
    $errors = [];

    if (empty($formData['loan_id'])) {
        $errors[] = 'Loan is required.';
    }

    if (empty($formData['disbursement_amount']) || !is_numeric($formData['disbursement_amount']) || $formData['disbursement_amount'] <= 0) {
        $errors[] = 'Valid disbursement amount is required.';
    }

    if (empty($formData['disbursement_date'])) {
        $errors[] = 'Disbursement date is required.';
    }

    if (empty($errors)) {
        // Get loan details
        $loan = $loanService->getLoanWithDetails($formData['loan_id']);
        if (!$loan) {
            $session->setFlash('error', 'Loan not found.');
        } elseif ($loan['status'] !== 'approved') {
            $session->setFlash('error', 'SLR document can only be created for approved loans.');
        } else {
            // Create SLR document
            $slrData = [
                'loan_id' => $formData['loan_id'],
                'disbursement_amount' => $formData['disbursement_amount'],
                'disbursement_date' => $formData['disbursement_date'],
                'disbursed_by' => $user['id'],
                'client_present' => $formData['client_present'],
                'witness_name' => $formData['witness_name'],
                'notes' => $formData['notes']
            ];

            $slrId = $slrDocumentService->createSlrDocument($formData['loan_id'], $slrData);

            if ($slrId) {
                $session->setFlash('success', 'SLR document created successfully.');
                header('Location: ' . APP_URL . '/public/slr-documents/view.php?id=' . $slrId);
                exit;
            } else {
                $session->setFlash('error', 'Failed to create SLR document: ' . $slrDocumentService->getLastError());
            }
        }
    } else {
        $session->setFlash('error', implode('<br>', $errors));
    }
}

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';

?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Create SLR Document</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/slr-documents/index.php" class="btn btn-secondary">
                <i data-feather="arrow-left"></i> Back to List
            </a>
        </div>
    </div>

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
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">SLR Document Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= $csrf->generateToken() ?>">

                        <div class="mb-3">
                            <label for="loan_id" class="form-label">Loan *</label>
                            <?php if ($loan): ?>
                                <input type="hidden" name="loan_id" value="<?= $loan['id'] ?>">
                                <div class="border rounded p-3 bg-light">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Client:</strong> <?= htmlspecialchars($loan['client_name']) ?>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Loan Amount:</strong> ₱<?= number_format($loan['loan_amount'], 2) ?>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Interest Rate:</strong> <?= $loan['interest_rate'] ?>%
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Status:</strong> <span class="badge bg-success">Approved</span>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <select class="form-select" id="loan_id" name="loan_id" required>
                                    <option value="">Select Approved Loan</option>
                                    <?php
                                    $approvedLoans = $loanService->getLoansByStatus('approved');
                                    foreach ($approvedLoans as $approvedLoan):
                                    ?>
                                        <option value="<?= $approvedLoan['id'] ?>" <?= $formData['loan_id'] == $approvedLoan['id'] ? 'selected' : '' ?>>
                                            #<?= $approvedLoan['id'] ?> - <?= htmlspecialchars($approvedLoan['client_name']) ?> (₱<?= number_format($approvedLoan['loan_amount'], 2) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="disbursement_amount" class="form-label">Disbursement Amount *</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="disbursement_amount" name="disbursement_amount"
                                       value="<?= htmlspecialchars($formData['disbursement_amount']) ?>" step="0.01" min="0" required>
                            </div>
                            <div class="form-text">
                                Amount to be disbursed to the client (should match or be less than loan amount)
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="disbursement_date" class="form-label">Disbursement Date *</label>
                            <input type="date" class="form-control" id="disbursement_date" name="disbursement_date"
                                   value="<?= htmlspecialchars($formData['disbursement_date']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="client_present" name="client_present"
                                       value="1" <?= $formData['client_present'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="client_present">
                                    Client was present during disbursement
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="witness_name" class="form-label">Witness Name</label>
                            <input type="text" class="form-control" id="witness_name" name="witness_name"
                                   value="<?= htmlspecialchars($formData['witness_name']) ?>">
                            <div class="form-text">
                                Name of the witness present during disbursement (optional)
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"
                                      placeholder="Any additional notes about the disbursement..."><?= htmlspecialchars($formData['notes']) ?></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="plus"></i> Create SLR Document
                            </button>
                            <a href="<?= APP_URL ?>/public/slr-documents/index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Information</h6>
                </div>
                <div class="card-body">
                    <h6>What is an SLR Document?</h6>
                    <p class="small text-muted">
                        SLR (Summary of Loan Release) is an official document that records the disbursement of loan funds to a client.
                        It serves as proof that the loan amount has been properly disbursed and acknowledged by the client.
                    </p>

                    <h6>Requirements</h6>
                    <ul class="small text-muted">
                        <li>Loan must be approved</li>
                        <li>Client should be present (recommended)</li>
                        <li>Witness presence is optional but recommended</li>
                        <li>Disbursement amount should match loan amount</li>
                    </ul>

                    <h6>Next Steps</h6>
                    <p class="small text-muted">
                        After creating the SLR document, you can:
                    </p>
                    <ul class="small text-muted">
                        <li>Print the document for records</li>
                        <li>Mark the loan as disbursed</li>
                        <li>Begin collection of weekly payments</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Initialize Feather icons
if (typeof feather !== 'undefined') {
    feather.replace();
}
</script>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';


// End output buffering and flush output
ob_end_flush();
?>
