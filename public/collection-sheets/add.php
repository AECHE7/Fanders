<?php
/**
 * Add Collection Sheet - Fanders Microfinance System
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

// Check if user has permission to create collection sheets
if (!$auth->hasRole(['administrator', 'manager', 'account_officer'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Initialize services
$collectionSheetService = new CollectionSheetService();
$userService = new UserService();

// Get account officers for dropdown (for admin/manager)
$accountOfficers = [];
if ($userRole == 'administrator' || $userRole == 'manager') {
    $accountOfficers = $userService->getUsersByRole('account_officer');
}

// Initialize variables
$formData = [
    'account_officer_id' => $userRole == 'account_officer' ? $user['id'] : '',
    'collection_date' => date('Y-m-d'),
    'generate_automatically' => true
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
        'account_officer_id' => $_POST['account_officer_id'] ?? '',
        'collection_date' => $_POST['collection_date'] ?? '',
        'generate_automatically' => isset($_POST['generate_automatically'])
    ];

    // Validate form data
    $errors = [];

    if (empty($formData['account_officer_id'])) {
        $errors[] = 'Account officer is required.';
    }

    if (empty($formData['collection_date'])) {
        $errors[] = 'Collection date is required.';
    }

    // Check if user can select this officer
    if ($userRole == 'account_officer' && $formData['account_officer_id'] != $user['id']) {
        $errors[] = 'You can only create collection sheets for yourself.';
    }

    if (empty($errors)) {
        if ($formData['generate_automatically']) {
            // Generate collection sheet automatically
            $sheetId = $collectionSheetService->generateCollectionSheet($formData['account_officer_id'], $formData['collection_date']);
            if ($sheetId) {
                $session->setFlash('success', 'Collection sheet generated successfully.');
                header('Location: ' . APP_URL . '/public/collection-sheets/view.php?id=' . $sheetId);
                exit;
            } else {
                $session->setFlash('error', 'Failed to generate collection sheet: ' . $collectionSheetService->getLastError());
            }
        } else {
            // Create empty collection sheet
            $sheetId = $collectionSheetService->createCollectionSheet($formData['account_officer_id'], $formData['collection_date']);
            if ($sheetId) {
                $session->setFlash('success', 'Collection sheet created successfully.');
                header('Location: ' . APP_URL . '/public/collection-sheets/edit.php?id=' . $sheetId);
                exit;
            } else {
                $session->setFlash('error', 'Failed to create collection sheet: ' . $collectionSheetService->getLastError());
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
        <h1 class="h2">Create Collection Sheet</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/collection-sheets/index.php" class="btn btn-secondary">
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
                    <h5 class="mb-0">Collection Sheet Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?= $csrf->generateToken() ?>">

                        <?php if ($userRole == 'administrator' || $userRole == 'manager'): ?>
                            <div class="mb-3">
                                <label for="account_officer_id" class="form-label">Account Officer *</label>
                                <select class="form-select" id="account_officer_id" name="account_officer_id" required>
                                    <option value="">Select Account Officer</option>
                                    <?php foreach ($accountOfficers as $officer): ?>
                                        <option value="<?= $officer['id'] ?>" <?= $formData['account_officer_id'] == $officer['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($officer['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="account_officer_id" value="<?= $user['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label">Account Officer</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" readonly>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="collection_date" class="form-label">Collection Date *</label>
                            <input type="date" class="form-control" id="collection_date" name="collection_date"
                                   value="<?= htmlspecialchars($formData['collection_date']) ?>" required>
                            <div class="form-text">Select the date for which collections will be made.</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="generate_automatically" name="generate_automatically"
                                       value="1" <?= $formData['generate_automatically'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="generate_automatically">
                                    Generate collection sheet automatically
                                </label>
                            </div>
                            <div class="form-text">
                                If checked, the system will automatically include all active loans assigned to the selected account officer that are due for collection on the selected date.
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="plus"></i> Create Collection Sheet
                            </button>
                            <a href="<?= APP_URL ?>/public/collection-sheets/index.php" class="btn btn-secondary">Cancel</a>
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
                    <h6>What is a Collection Sheet?</h6>
                    <p class="small text-muted">
                        A collection sheet is a daily record of loan repayments collected by an account officer from their assigned clients.
                        It helps track collection performance and ensures accountability in the microfinance operations.
                    </p>

                    <h6 class="mt-3">Automatic Generation</h6>
                    <p class="small text-muted">
                        When automatic generation is enabled, the system will:
                    </p>
                    <ul class="small text-muted">
                        <li>Include all active loans assigned to the officer</li>
                        <li>Calculate expected weekly payments</li>
                        <li>Identify overdue payments</li>
                        <li>Generate a complete collection sheet ready for use</li>
                    </ul>

                    <h6 class="mt-3">Manual Creation</h6>
                    <p class="small text-muted">
                        Without automatic generation, you can manually add loans to the collection sheet after creation.
                    </p>
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

// Set default date to today if not set
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('collection_date');
    if (!dateInput.value) {
        dateInput.valueAsDate = new Date();
    }
});
</script>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';


// End output buffering and flush output
ob_end_flush();
?>
