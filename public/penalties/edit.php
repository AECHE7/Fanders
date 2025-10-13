<?php
/**
 * Edit Penalty Record Page
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

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    $session->setFlash('error', 'Invalid penalty ID.');
    header('Location: ' . APP_URL . '/public/penalties/index.php');
    exit;
}

$penaltyModel = new PenaltyModel();
$penalty = $penaltyModel->findById($id);

if (!$penalty) {
    $session->setFlash('error', 'Penalty record not found.');
    header('Location: ' . APP_URL . '/public/penalties/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $csrf->validateRequest()) {
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0.0;
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'pending';

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
        // Use PenaltyService to update penalty for consistency
        $penaltyService = new PenaltyService();
        $data = [
            'amount' => $amount,
            'reason' => $reason,
            'status' => $status,
            'paid_at' => $status === 'paid' ? date('Y-m-d H:i:s') : null,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $result = $penaltyService->update($id, $data);
        if ($result) {
            $success = true;
            $session->setFlash('success', 'Penalty record updated successfully.');
            header('Location: ' . APP_URL . '/public/penalties/index.php');
            exit;
        } else {
            $errors[] = 'Failed to update penalty record.';
        }
    }
} else {
    // Pre-fill form with existing data
    $amount = $penalty['amount'];
    $reason = $penalty['reason'];
    $status = $penalty['status'];
}

include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="container py-4">
    <h1>Edit Penalty Record</h1>

    <?php if ($success): ?>
        <div class="alert alert-success">Penalty record updated successfully.</div>
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

    <form method="post" action="<?= $_SERVER['PHP_SELF'] . '?id=' . $id ?>">
        <?= $csrf->getTokenField() ?>

        <div class="mb-3">
            <label for="amount" class="form-label">Penalty Amount</label>
            <input type="number" step="0.01" name="amount" id="amount" class="form-control" required value="<?= htmlspecialchars($amount) ?>">
        </div>

        <div class="mb-3">
            <label for="reason" class="form-label">Reason</label>
            <textarea name="reason" id="reason" class="form-control" rows="3" required><?= htmlspecialchars($reason) ?></textarea>
        </div>

        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select" required>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>Paid</option>
                <option value="waived" <?= $status === 'waived' ? 'selected' : '' ?>>Waived</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Update Penalty</button>
        <a href="<?= APP_URL ?>/public/penalties/index.php" class="btn btn-secondary">Cancel</a>
    </form>
</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>
