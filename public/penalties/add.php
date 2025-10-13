<?php
/**
 * Add Penalty Record Page
 */

require_once '../../app/config/config.php';

function autoload($className) {
    $directories = [
        'app/core/',
        'app/models/',
        'app/services/',
        'app/utilities/'
    ];
    foreach ($directories as $directory) {
        $file = BASE_PATH . '/' . $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}

spl_autoload_register('autoload');

$session = new Session();
$auth = new AuthService();
$csrf = new CSRF();

if (!$auth->isLoggedIn()) {
    $session->setFlash('error', 'Please login to access this page.');
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

if ($auth->checkSessionTimeout()) {
    $session->setFlash('error', 'Your session has expired. Please login again.');
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

if (!$auth->hasRole(['super-admin', 'admin'])) {
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

$penaltyService = new PenaltyService();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $csrf->validateRequest()) {
    $transactionId = isset($_POST['transaction_id']) ? intval($_POST['transaction_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0.0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'pending';

    if ($transactionId <= 0) {
        $errors[] = 'Invalid transaction ID.';
    }
    if ($amount <= 0) {
        $errors[] = 'Amount must be greater than zero.';
    }
    if (empty($reason)) {
        $errors[] = 'Reason is required.';
    }
    if (!in_array($status, ['pending', 'paid', 'waived'])) {
        $errors[] = 'Invalid status.';
    }

if (empty($errors)) {
    // Create penalty record using PenaltyService for consistency
    $penaltyService = new PenaltyService();
    $created = $penaltyService->createOrUpdatePenalty(0, $transactionId, $amount); // userId unknown here, set 0 or fetch if possible
    if ($created) {
        $success = true;
        $session->setFlash('success', 'Penalty record added successfully.');
        header('Location: ' . APP_URL . '/public/penalties/index.php');
        exit;
    } else {
        $errors[] = 'Failed to add penalty record.';
    }
}
}

include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="container py-4">
    <h1>Add Penalty Record</h1>

    <?php if ($success): ?>
        <div class="alert alert-success">Penalty record added successfully.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <?= $csrf->getTokenField() ?>

        <div class="mb-3">
            <label for="transaction_id" class="form-label">Transaction ID</label>
            <input type="number" name="transaction_id" id="transaction_id" class="form-control" required value="<?= isset($_POST['transaction_id']) ? htmlspecialchars($_POST['transaction_id']) : '' ?>">
        </div>

        <div class="mb-3">
            <label for="amount" class="form-label">Penalty Amount</label>
            <input type="number" step="0.01" name="amount" id="amount" class="form-control" required value="<?= isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : '' ?>">
        </div>

        <div class="mb-3">
            <label for="reason" class="form-label">Reason</label>
            <textarea name="reason" id="reason" class="form-control" rows="3" required><?= isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : '' ?></textarea>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select" required>
                <option value="pending" <?= (isset($_POST['status']) && $_POST['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                <option value="paid" <?= (isset($_POST['status']) && $_POST['status'] === 'paid') ? 'selected' : '' ?>>Paid</option>
                <option value="waived" <?= (isset($_POST['status']) && $_POST['status'] === 'waived') ? 'selected' : '' ?>>Waived</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Add Penalty</button>
        <a href="<?= APP_URL ?>/public/penalties/index.php" class="btn btn-secondary">Cancel</a>
    </form>
</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>
