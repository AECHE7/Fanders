<?php
/**
 * Collection Sheets - View
 */
require_once __DIR__ . '/../init.php';
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier', 'account_officer']);

$service = new CollectionSheetService();
$pageTitle = 'View Collection Sheet';

$sheetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$details = $sheetId ? $service->getSheetDetails($sheetId) : false;
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
              <i data-feather="eye" style="width:24px;height:24px;color:#000;"></i>
            </div>
          </div>
          <div>
            <h1 class="notion-page-title mb-0">Sheet #<?= (int)$sheet['id'] ?> (<?= htmlspecialchars(ucfirst($sheet['status'])) ?>)</h1>
            <div class="text-muted small">Date: <?= htmlspecialchars($sheet['sheet_date']) ?> • Officer ID: <?= (int)$sheet['officer_id'] ?></div>
          </div>
        </div>
        <div>
          <a href="<?= APP_URL ?>/public/collection-sheets/index.php" class="btn btn-sm btn-outline-secondary">Back</a>
        </div>
      </div>
      <div class="notion-divider my-3"></div>
    </div>

    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Items</strong>
        <div>Total: <strong>₱<?= number_format((float)$sheet['total_amount'], 2) ?></strong></div>
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
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($items)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No items.</td></tr>
              <?php else: foreach ($items as $i): ?>
                <tr>
                  <td><?= (int)$i['id'] ?></td>
                  <td><?= htmlspecialchars($i['client_name']) ?> (ID: <?= (int)$i['client_id'] ?>)</td>
                  <td>#<?= (int)$i['loan_id'] ?> (<?= htmlspecialchars($i['loan_status']) ?>)</td>
                  <td class="text-end">₱<?= number_format((float)$i['amount'], 2) ?></td>
                  <td><span class="badge bg-secondary"><?= htmlspecialchars($i['status']) ?></span></td>
                  <td><?= htmlspecialchars($i['notes'] ?? '') ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>
<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>
