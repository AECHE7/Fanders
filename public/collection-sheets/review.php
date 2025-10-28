<?php
/**
 * Collection Sheets - Review & Post (Happy Path)
 */
require_once __DIR__ . '/../init.php';
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier']);

$service = new CollectionSheetService();
$pageTitle = 'Review Collection Sheet';

$sheetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_sheet') {
    if (!$csrf->validateRequest(false)) {
        $session->setFlash('error', 'Invalid security token.');
        header('Location: ' . APP_URL . '/public/collection-sheets/review.php?id=' . (int)$_POST['sheet_id']);
        exit;
    }
    $sid = (int)($_POST['sheet_id'] ?? 0);
    $ok = $service->approveAndPost($sid, $user['id']);
    if ($ok) { $session->setFlash('success', 'Sheet posted successfully.');
        header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
    } else { $session->setFlash('error', $service->getErrorMessage() ?: 'Failed to post sheet.');
        header('Location: ' . APP_URL . '/public/collection-sheets/review.php?id=' . $sid);
    }
    exit;
}

$details = $service->getSheetDetails($sheetId);
if (!$details) { $session->setFlash('error', 'Sheet not found.'); header('Location: ' . APP_URL . '/public/collection-sheets/index.php'); exit; }
$sheet = $details['sheet'];
$items = $details['items'];

include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>
<main class="main-content">
  <div class="content-wrapper">
    <div class="notion-page-header mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <div class="me-3">
            <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0f4fd;">
              <i data-feather="check-square" style="width:24px;height:24px;color:#000;"></i>
            </div>
          </div>
          <div>
            <h1 class="notion-page-title mb-0">Review & Post – Sheet #<?= (int)$sheet['id'] ?></h1>
            <div class="text-muted small">Date: <?= htmlspecialchars($sheet['sheet_date']) ?> • Officer: <?= htmlspecialchars($sheet['officer_name'] ?? 'Officer ID: ' . $sheet['officer_id']) ?> • Status: <?= htmlspecialchars($sheet['status']) ?></div>
          </div>
        </div>
        <div>
          <a href="<?= APP_URL ?>/public/collection-sheets/index.php" class="btn btn-sm btn-outline-secondary">Back</a>
        </div>
      </div>
      <div class="notion-divider my-3"></div>
    </div>

    <?php if ($session->hasFlash('success')): ?><div class="alert alert-success"><?= $session->getFlash('success') ?></div><?php endif; ?>
    <?php if ($session->hasFlash('error')): ?><div class="alert alert-danger"><?= $session->getFlash('error') ?></div><?php endif; ?>

    <div class="card shadow-sm mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Submitted Items</strong>
        <div>Sheet Total: <strong>₱<?= number_format((float)$sheet['total_amount'], 2) ?></strong></div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Client</th>
                <th>Loan</th>
                <th class="text-end">Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($items)): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">No items.</td></tr>
              <?php else: foreach ($items as $i): ?>
                <tr>
                  <td><?= (int)$i['id'] ?></td>
                  <td><?= htmlspecialchars($i['client_name']) ?> (ID: <?= (int)$i['client_id'] ?>)</td>
                  <td>#<?= (int)$i['loan_id'] ?> (<?= htmlspecialchars($i['loan_status']) ?>)</td>
                  <td class="text-end">₱<?= number_format((float)$i['amount'], 2) ?></td>
                  <td><span class="badge bg-secondary"><?= htmlspecialchars($i['status']) ?></span></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php if ($sheet['status'] === 'submitted'): ?>
    <form method="post" class="text-end">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="action" value="post_sheet">
      <input type="hidden" name="sheet_id" value="<?= (int)$sheet['id'] ?>">
      <button type="submit" class="btn btn-success">Post to Payments</button>
    </form>
    <?php endif; ?>

  </div>
</main>
<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>
