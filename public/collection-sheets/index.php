<?php
/**
 * Collection Sheets - Index
 * Lists recent sheets and provides AO quick-start for today's draft.
 */
require_once __DIR__ . '/../init.php';
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account_officer', 'cashier']);

$pageTitle = 'Collection Sheets';
$service = new CollectionSheetService();

// Flash helpers
$successMsg = $session->hasFlash('success') ? $session->getFlash('success') : null;
$errorMsg = $session->hasFlash('error') ? $session->getFlash('error') : null;

// AO quick create action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_today') {
    if (!$csrf->validateRequest(false)) {
        $session->setFlash('error', 'Invalid security token.');
        header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
        exit;
    }
    $draft = $service->createDraftSheet($user['id'], date('Y-m-d'));
    if ($draft) {
        header('Location: ' . APP_URL . '/public/collection-sheets/add.php?id=' . $draft['id']);
        exit;
    }
    $session->setFlash('error', $service->getErrorMessage() ?: 'Failed to create draft.');
    header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
    exit;
}

// Load sheets (AO sees own; others see recent)
$filters = [];
if ($userRole === 'account_officer') { $filters['officer_id'] = $user['id']; }
$filters['limit'] = 20;
$sheets = $service->listSheets($filters);

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
              <i data-feather="clipboard" style="width: 24px; height: 24px; color:#000;"></i>
            </div>
          </div>
          <h1 class="notion-page-title mb-0">Collection Sheets</h1>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <div class="text-muted d-none d-md-block me-2">
            <i data-feather="calendar" class="me-1" style="width:14px;height:14px;"></i>
            <?= date('l, F j, Y') ?>
          </div>
          <?php if ($userRole === 'account_officer'): ?>
          <form method="post" class="m-0">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="create_today">
            <button type="submit" class="btn btn-sm btn-primary">
              <i data-feather="plus-circle" class="me-1" style="width:14px;height:14px;"></i>
              New Draft (Today)
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>
      <div class="notion-divider my-3"></div>
    </div>

    <?php if ($successMsg): ?><div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div><?php endif; ?>
    <?php if ($errorMsg): ?><div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

    <div class="card shadow-sm">
      <div class="card-header d-flex align-items-center">
        <i data-feather="list" class="me-2" style="width:18px;height:18px;"></i>
        <h5 class="mb-0">Recent Sheets</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Officer</th>
                <th>Status</th>
                <th class="text-end">Total Amount</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($sheets)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">No sheets found.</td></tr>
              <?php else: foreach ($sheets as $s): ?>
                <tr>
                  <td><?= (int)$s['id'] ?></td>
                  <td><?= htmlspecialchars($s['sheet_date']) ?></td>
                  <td><?= htmlspecialchars($s['officer_name']) ?></td>
                  <td><span class="badge bg-secondary"><?= htmlspecialchars($s['status']) ?></span></td>
                  <td class="text-end">₱<?= number_format((float)$s['total_amount'], 2) ?></td>
                  <td>
                    <?php if ($userRole === 'account_officer' && $s['status'] === 'draft'): ?>
                      <a class="btn btn-sm btn-outline-primary" href="<?= APP_URL ?>/public/collection-sheets/add.php?id=<?= (int)$s['id'] ?>">Edit</a>
                    <?php endif; ?>
                    <?php if (in_array($userRole, ['super-admin','admin','manager','cashier']) && $s['status'] === 'submitted'): ?>
                      <a class="btn btn-sm btn-success" href="<?= APP_URL ?>/public/collection-sheets/review.php?id=<?= (int)$s['id'] ?>">Review/Post</a>
                    <?php endif; ?>
                    <a class="btn btn-sm btn-light" href="<?= APP_URL ?>/public/collection-sheets/view.php?id=<?= (int)$s['id'] ?>">View</a>
                  </td>
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
