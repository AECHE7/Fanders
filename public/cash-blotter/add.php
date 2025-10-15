<?php
/**
 * Add/Edit Cash Blotter Entry - Fanders Microfinance System
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

// Check if user has permission to access cash blotter (Administrator, Manager)
if (!$auth->hasRole(['administrator', 'manager'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Initialize cash blotter service
$cashBlotterService = new CashBlotterService();

// Get blotter ID from URL if editing
$blotterId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEditing = $blotterId !== null;

// Initialize variables
$blotter = null;
$formData = [
    'blotter_date' => date('Y-m-d'),
    'opening_balance' => 0.00,
    'total_collections' => 0.00,
    'total_loan_releases' => 0.00,
    'total_expenses' => 0.00,
    'closing_balance' => 0.00,
    'status' => 'draft'
];

// If editing, load existing blotter data
if ($isEditing) {
    $blotter = $cashBlotterService->getBlotterById($blotterId);
    if (!$blotter) {
        $session->setFlash('error', 'Cash blotter entry not found.');
        header('Location: ' . APP_URL . '/public/cash-blotter/index.php');
        exit;
    }

    // Check if blotter is already finalized (can't edit finalized entries)
    if ($blotter['status'] === 'finalized') {
        $session->setFlash('error', 'Cannot edit a finalized cash blotter entry.');
        header('Location: ' . APP_URL . '/public/cash-blotter/index.php');
        exit;
    }

    // Populate form data
    $formData = [
        'blotter_date' => $blotter['blotter_date'],
        'opening_balance' => $blotter['opening_balance'],
        'total_collections' => $blotter['total_collections'],
        'total_loan_releases' => $blotter['total_loan_releases'],
        'total_expenses' => $blotter['total_expenses'],
        'closing_balance' => $blotter['closing_balance'],
        'status' => $blotter['status']
    ];
} else {
    // For new entries, calculate opening balance from previous day
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $yesterdayBlotter = $cashBlotterService->getBlotterByDate($yesterday);
    if ($yesterdayBlotter) {
        $formData['opening_balance'] = $yesterdayBlotter['closing_balance'];
        $formData['closing_balance'] = $yesterdayBlotter['closing_balance'];
    }
}

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
        'blotter_date' => $_POST['blotter_date'] ?? '',
        'opening_balance' => (float)($_POST['opening_balance'] ?? 0),
        'total_collections' => (float)($_POST['total_collections'] ?? 0),
        'total_loan_releases' => (float)($_POST['total_loan_releases'] ?? 0),
        'total_expenses' => (float)($_POST['total_expenses'] ?? 0),
        'closing_balance' => (float)($_POST['closing_balance'] ?? 0),
        'status' => $_POST['status'] ?? 'draft'
    ];

    // Validate form data
    $errors = [];

    if (empty($formData['blotter_date'])) {
        $errors[] = 'Blotter date is required.';
    }

    if ($formData['opening_balance'] < 0) {
        $errors[] = 'Opening balance cannot be negative.';
    }

    if ($formData['total_collections'] < 0) {
        $errors[] = 'Total collections cannot be negative.';
    }

    if ($formData['total_loan_releases'] < 0) {
        $errors[] = 'Total loan releases cannot be negative.';
    }

    if ($formData['total_expenses'] < 0) {
        $errors[] = 'Total expenses cannot be negative.';
    }

    // Validate closing balance calculation
    $calculatedClosing = $formData['opening_balance'] + $formData['total_collections'] - $formData['total_loan_releases'] - $formData['total_expenses'];
    if (abs($formData['closing_balance'] - $calculatedClosing) > 0.01) {
        $errors[] = 'Closing balance does not match the calculated amount.';
    }

    if (empty($errors)) {
        if ($isEditing) {
            // Update existing blotter
            $result = $cashBlotterService->updateBlotter($blotterId, $formData);
            if ($result) {
                $session->setFlash('success', 'Cash blotter entry updated successfully.');
                header('Location: ' . APP_URL . '/public/cash-blotter/index.php');
                exit;
            } else {
                $session->setFlash('error', 'Failed to update cash blotter entry.');
            }
        } else {
            // Create new blotter
            $formData['recorded_by'] = $user['id'];
            $result = $cashBlotterService->createBlotter($formData);
            if ($result) {
                $session->setFlash('success', 'Cash blotter entry created successfully.');
                header('Location: ' . APP_URL . '/public/cash-blotter/index.php');
                exit;
            } else {
                $session->setFlash('error', 'Failed to create cash blotter entry.');
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
        <h1 class="h2"><?php echo $isEditing ? 'Edit' : 'Add'; ?> Cash Blotter Entry</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/cash-blotter/index.php" class="btn btn-secondary">
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
                    <h5 class="mb-0">Cash Blotter Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= $csrf->generateToken() ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="blotter_date" class="form-label">Blotter Date *</label>
                                <input type="date" class="form-control" id="blotter_date" name="blotter_date"
                                       value="<?= htmlspecialchars($formData['blotter_date']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?= $formData['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                    <option value="finalized" <?= $formData['status'] === 'finalized' ? 'selected' : '' ?>>Finalized</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="opening_balance" class="form-label">Opening Balance (₱)</label>
                                <input type="number" step="0.01" class="form-control" id="opening_balance" name="opening_balance"
                                       value="<?= number_format($formData['opening_balance'], 2, '.', '') ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="total_collections" class="form-label">Total Collections (₱)</label>
                                <input type="number" step="0.01" class="form-control" id="total_collections" name="total_collections"
                                       value="<?= number_format($formData['total_collections'], 2, '.', '') ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="total_loan_releases" class="form-label">Total Loan Releases (₱)</label>
                                <input type="number" step="0.01" class="form-control" id="total_loan_releases" name="total_loan_releases"
                                       value="<?= number_format($formData['total_loan_releases'], 2, '.', '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="total_expenses" class="form-label">Total Expenses (₱)</label>
                                <input type="number" step="0.01" class="form-control" id="total_expenses" name="total_expenses"
                                       value="<?= number_format($formData['total_expenses'], 2, '.', '') ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="closing_balance" class="form-label">Closing Balance (₱)</label>
                                <input type="number" step="0.01" class="form-control" id="closing_balance" name="closing_balance"
                                       value="<?= number_format($formData['closing_balance'], 2, '.', '') ?>" readonly>
                                <div class="form-text">Calculated automatically</div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="save"></i> <?php echo $isEditing ? 'Update' : 'Create'; ?> Entry
                            </button>
                            <a href="<?= APP_URL ?>/public/cash-blotter/index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Balance Calculation</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Opening Balance:</strong> <span id="calc-opening">₱<?= number_format($formData['opening_balance'], 2) ?></span>
                    </div>
                    <div class="mb-2 text-success">
                        <strong>+ Collections:</strong> <span id="calc-collections">₱<?= number_format($formData['total_collections'], 2) ?></span>
                    </div>
                    <div class="mb-2 text-danger">
                        <strong>- Loan Releases:</strong> <span id="calc-releases">₱<?= number_format($formData['total_loan_releases'], 2) ?></span>
                    </div>
                    <div class="mb-2 text-danger">
                        <strong>- Expenses:</strong> <span id="calc-expenses">₱<?= number_format($formData['total_expenses'], 2) ?></span>
                    </div>
                    <hr>
                    <div class="mb-0">
                        <strong>Closing Balance:</strong> <span id="calc-closing">₱<?= number_format($formData['closing_balance'], 2) ?></span>
                    </div>
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

// Auto-calculate closing balance
function calculateClosingBalance() {
    const opening = parseFloat(document.getElementById('opening_balance').value) || 0;
    const collections = parseFloat(document.getElementById('total_collections').value) || 0;
    const releases = parseFloat(document.getElementById('total_loan_releases').value) || 0;
    const expenses = parseFloat(document.getElementById('total_expenses').value) || 0;

    const closing = opening + collections - releases - expenses;

    document.getElementById('closing_balance').value = closing.toFixed(2);
    document.getElementById('calc-opening').textContent = '₱' + opening.toFixed(2);
    document.getElementById('calc-collections').textContent = '₱' + collections.toFixed(2);
    document.getElementById('calc-releases').textContent = '₱' + releases.toFixed(2);
    document.getElementById('calc-expenses').textContent = '₱' + expenses.toFixed(2);
    document.getElementById('calc-closing').textContent = '₱' + closing.toFixed(2);
}

// Add event listeners for auto-calculation
document.getElementById('total_collections').addEventListener('input', calculateClosingBalance);
document.getElementById('total_loan_releases').addEventListener('input', calculateClosingBalance);
document.getElementById('total_expenses').addEventListener('input', calculateClosingBalance);

// Initial calculation
calculateClosingBalance();
</script>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';


// End output buffering and flush output
ob_end_flush();
?>
