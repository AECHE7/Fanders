<?php
/**
 * View Collection Sheet - Fanders Microfinance System
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

// Check if user has permission to view collection sheets
if (!$auth->hasRole(['administrator', 'manager', 'account_officer'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Get collection sheet ID
$sheetId = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$sheetId) {
    $session->setFlash('error', 'Invalid collection sheet ID.');
    header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
    exit;
}

// Initialize collection sheet service
$collectionSheetService = new CollectionSheetService();

// Get collection sheet details
$sheet = $collectionSheetService->getCollectionSheetWithDetails($sheetId);
if (!$sheet) {
    $session->setFlash('error', 'Collection sheet not found.');
    header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
    exit;
}

// Check if user can view this sheet (account officers can only view their own)
if ($userRole == 'account_officer' && $sheet['account_officer_id'] != $user['id']) {
    $session->setFlash('error', 'You do not have permission to view this collection sheet.');
    header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
    exit;
}

// Get collection sheet entries
$entries = $collectionSheetService->getCollectionSheetDetails($sheetId);

// Calculate summary
$summary = [
    'total_loans' => count($entries),
    'total_expected' => array_sum(array_column($entries, 'expected_payment')),
    'total_collected' => array_sum(array_column($entries, 'actual_payment')),
    'collection_rate' => count($entries) > 0 ? (array_sum(array_column($entries, 'actual_payment')) / array_sum(array_column($entries, 'expected_payment'))) * 100 : 0
];

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';

?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2">Collection Sheet Details</h1>
            <p class="text-muted mb-0">
                Collection Date: <?= date('F d, Y', strtotime($sheet['collection_date'])) ?> |
                Account Officer: <?= htmlspecialchars($sheet['account_officer_name'] ?? 'N/A') ?> |
                Status: <span class="badge bg-<?= $sheet['status'] == 'approved' ? 'success' : ($sheet['status'] == 'submitted' ? 'warning' : 'secondary') ?>"><?= ucfirst($sheet['status']) ?></span>
            </p>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <?php if ($userRole == 'account_officer' && $sheet['status'] == 'draft'): ?>
                    <a href="<?= APP_URL ?>/public/collection-sheets/edit.php?id=<?= $sheetId ?>" class="btn btn-sm btn-outline-warning">
                        <i data-feather="edit"></i> Edit
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="submitSheet(<?= $sheetId ?>)">
                        <i data-feather="send"></i> Submit for Approval
                    </button>
                <?php elseif (($userRole == 'administrator' || $userRole == 'manager') && $sheet['status'] == 'submitted'): ?>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="approveSheet(<?= $sheetId ?>)">
                        <i data-feather="check"></i> Approve
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="rejectSheet(<?= $sheetId ?>)">
                        <i data-feather="x"></i> Reject
                    </button>
                <?php endif; ?>
            </div>
            <a href="<?= APP_URL ?>/public/collection-sheets/index.php" class="btn btn-sm btn-secondary">
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

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?= $summary['total_loans'] ?></h5>
                    <p class="card-text">Total Loans</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-info">₱<?= number_format($summary['total_expected'], 2) ?></h5>
                    <p class="card-text">Expected Amount</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success">₱<?= number_format($summary['total_collected'], 2) ?></h5>
                    <p class="card-text">Collected Amount</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-<?= $summary['collection_rate'] >= 80 ? 'success' : ($summary['collection_rate'] >= 50 ? 'warning' : 'danger') ?>">
                        <?= number_format($summary['collection_rate'], 1) ?>%
                    </h5>
                    <p class="card-text">Collection Rate</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Collection Sheet Details -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Loan Collection Details</h5>
            <?php if ($userRole == 'account_officer' && $sheet['status'] == 'draft'): ?>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="recordPaymentModal()">
                    <i data-feather="plus"></i> Record Payment
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($entries)): ?>
                <div class="text-center py-5">
                    <i data-feather="file-text" class="text-muted mb-3" style="width:48px;height:48px;"></i>
                    <h5 class="text-muted">No Loans in Collection Sheet</h5>
                    <p class="text-muted">This collection sheet has no loan entries.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Client Name</th>
                                <th>Loan Amount</th>
                                <th>Weekly Payment</th>
                                <th>Expected Payment</th>
                                <th>Actual Payment</th>
                                <th>Status</th>
                                <th>Payment Date</th>
                                <?php if ($userRole == 'account_officer' && $sheet['status'] == 'draft'): ?>
                                    <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $entry): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($entry['client_name']) ?></strong><br>
                                        <small class="text-muted">ID: <?= $entry['client_id'] ?></small>
                                    </td>
                                    <td>₱<?= number_format($entry['loan_amount'], 2) ?></td>
                                    <td>₱<?= number_format($entry['weekly_payment'], 2) ?></td>
                                    <td>₱<?= number_format($entry['expected_payment'], 2) ?></td>
                                    <td class="text-success">
                                        ₱<?= number_format($entry['actual_payment'] ?? 0, 2) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= ($entry['actual_payment'] ?? 0) >= $entry['expected_payment'] ? 'success' : 'warning' ?>">
                                            <?= ($entry['actual_payment'] ?? 0) >= $entry['expected_payment'] ? 'Paid' : 'Partial' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= isset($entry['collected_at']) ? date('M d, Y', strtotime($entry['collected_at'])) : '-' ?>
                                    </td>
                                    <?php if ($userRole == 'account_officer' && $sheet['status'] == 'draft'): ?>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-success"
                                                    onclick="recordPayment(<?= $entry['loan_id'] ?>, '<?= htmlspecialchars($entry['client_name']) ?>', <?= $entry['expected_payment'] ?>)">
                                                <i data-feather="dollar-sign" style="width:14px;height:14px;"></i>
                                            </button>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Additional Information -->
    <?php if ($sheet['notes'] || $sheet['submitted_at'] || $sheet['approved_at']): ?>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Additional Information</h6>
                    </div>
                    <div class="card-body">
                        <?php if ($sheet['notes']): ?>
                            <div class="mb-3">
                                <strong>Notes:</strong><br>
                                <?= nl2br(htmlspecialchars($sheet['notes'])) ?>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <?php if ($sheet['submitted_at']): ?>
                                <div class="col-md-6">
                                    <strong>Submitted:</strong><br>
                                    <?= date('F d, Y H:i', strtotime($sheet['submitted_at'])) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($sheet['approved_at']): ?>
                                <div class="col-md-6">
                                    <strong>Approved:</strong><br>
                                    <?= date('F d, Y H:i', strtotime($sheet['approved_at'])) ?>
                                    <?php if ($sheet['approved_by_name']): ?>
                                        by <?= htmlspecialchars($sheet['approved_by_name']) ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<!-- Record Payment Modal -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="recordPaymentForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf->generateToken() ?>">
                <input type="hidden" name="sheet_id" value="<?= $sheetId ?>">
                <input type="hidden" id="payment_loan_id" name="loan_id" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Client</label>
                        <input type="text" class="form-control" id="payment_client_name" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="payment_amount" class="form-label">Payment Amount *</label>
                        <input type="number" class="form-control" id="payment_amount" name="amount" step="0.01" min="0" required>
                        <div class="form-text">Expected: ₱<span id="expected_amount">0.00</span></div>
                    </div>
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="check">Check</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="payment_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="payment_notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Initialize Feather icons
if (typeof feather !== 'undefined') {
    feather.replace();
}

// Submit sheet for approval
function submitSheet(sheetId) {
    if (confirm('Are you sure you want to submit this collection sheet for approval? You will not be able to edit it after submission.')) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= APP_URL ?>/public/collection-sheets/submit.php';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'sheet_id';
        idInput.value = sheetId;

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?= $csrf->generateToken() ?>';

        form.appendChild(idInput);
        form.appendChild(csrfInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Approve collection sheet
function approveSheet(sheetId) {
    if (confirm('Are you sure you want to approve this collection sheet?')) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= APP_URL ?>/public/collection-sheets/approve.php';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'sheet_id';
        idInput.value = sheetId;

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?= $csrf->generateToken() ?>';

        form.appendChild(idInput);
        form.appendChild(csrfInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Reject collection sheet
function rejectSheet(sheetId) {
    const reason = prompt('Please provide a reason for rejection:');
    if (reason !== null && reason.trim() !== '') {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= APP_URL ?>/public/collection-sheets/reject.php';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'sheet_id';
        idInput.value = sheetId;

        const reasonInput = document.createElement('input');
        reasonInput.type = 'hidden';
        reasonInput.name = 'rejection_reason';
        reasonInput.value = reason;

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?= $csrf->generateToken() ?>';

        form.appendChild(idInput);
        form.appendChild(reasonInput);
        form.appendChild(csrfInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Record payment modal
function recordPaymentModal() {
    const modal = new bootstrap.Modal(document.getElementById('recordPaymentModal'));
    modal.show();
}

// Record payment for specific loan
function recordPayment(loanId, clientName, expectedAmount) {
    document.getElementById('payment_loan_id').value = loanId;
    document.getElementById('payment_client_name').value = clientName;
    document.getElementById('expected_amount').textContent = expectedAmount.toFixed(2);
    document.getElementById('payment_amount').value = expectedAmount;

    const modal = new bootstrap.Modal(document.getElementById('recordPaymentModal'));
    modal.show();
}

// Handle payment form submission
document.getElementById('recordPaymentForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('<?= APP_URL ?>/public/collection-sheets/record-payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal and reload page
            const modal = bootstrap.Modal.getInstance(document.getElementById('recordPaymentModal'));
            modal.hide();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while recording the payment.');
    });
});
</script>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';


// End output buffering and flush output
ob_end_flush();
?>
