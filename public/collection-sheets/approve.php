<?php
/**
 * Collection Sheet Approval - Cashier Workflow
 * Allows cashiers to review and approve/post collection sheets
 */
require_once __DIR__ . '/../init.php';
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier']);

$service = new CollectionSheetService();
$paymentService = new PaymentService();
$pageTitle = 'Review Collection Sheet';

// Get sheet ID
$sheetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$sheetId) {
    $session->setFlash('error', 'Invalid sheet ID.');
    header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
    exit;
}

// Handle approval actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$csrf->validateRequest(false)) {
        $session->setFlash('error', 'Invalid security token.');
        header('Location: ' . APP_URL . '/public/collection-sheets/approve.php?id=' . $sheetId);
        exit;
    }
    
    switch ($_POST['action']) {
        case 'approve':
            // Approve the sheet
            $ok = $service->approveSheet($sheetId, $user['id']);
            if ($ok) {
                $session->setFlash('success', 'Collection sheet approved successfully!');
                header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
            } else {
                $session->setFlash('error', $service->getErrorMessage() ?: 'Failed to approve sheet.');
                header('Location: ' . APP_URL . '/public/collection-sheets/approve.php?id=' . $sheetId);
            }
            exit;
            
        case 'post_payments':
            // Post all items as payments
            $ok = $service->postSheetPayments($sheetId, $user['id']);
            if ($ok) {
                $session->setFlash('success', 'All payments posted successfully!');
                header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
            } else {
                $session->setFlash('error', $service->getErrorMessage() ?: 'Failed to post payments.');
                header('Location: ' . APP_URL . '/public/collection-sheets/approve.php?id=' . $sheetId);
            }
            exit;
            
        case 'reject':
            // Reject and send back to officer
            $reason = trim($_POST['rejection_reason'] ?? '');
            $ok = $service->rejectSheet($sheetId, $user['id'], $reason);
            if ($ok) {
                $session->setFlash('success', 'Collection sheet rejected and returned to officer.');
                header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
            } else {
                $session->setFlash('error', $service->getErrorMessage() ?: 'Failed to reject sheet.');
                header('Location: ' . APP_URL . '/public/collection-sheets/approve.php?id=' . $sheetId);
            }
            exit;
    }
}

// Load sheet details
$details = $service->getSheetDetails($sheetId);
if (!$details) {
    $session->setFlash('error', 'Sheet not found.');
    header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
    exit;
}

$sheet = $details['sheet'];
$items = $details['items'];

// Only show submitted sheets to cashier
if ($sheet['status'] !== 'submitted' && $sheet['status'] !== 'approved') {
    $session->setFlash('error', 'This sheet is not ready for review.');
    header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
    exit;
}

include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
  <div class="content-wrapper">
    <!-- Page Header -->
    <div class="notion-page-header mb-4">
      <div class="d-flex justify-content-between align-items-center flex-wrap">
        <div class="d-flex align-items-center mb-2 mb-md-0">
          <div class="me-3">
            <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #fef3c7;">
              <i data-feather="check-circle" style="width:24px;height:24px;color:#f59e0b;"></i>
            </div>
          </div>
          <div>
            <h1 class="notion-page-title mb-0">Review Collection Sheet #<?= (int)$sheet['id'] ?></h1>
            <div class="text-muted small">
              <span class="badge bg-<?= $sheet['status'] === 'submitted' ? 'warning' : 'info' ?> me-2">
                <?= htmlspecialchars(ucfirst($sheet['status'])) ?>
              </span>
              Date: <?= htmlspecialchars(date('F d, Y', strtotime($sheet['sheet_date']))) ?>
            </div>
          </div>
        </div>
        <div>
          <a href="<?= APP_URL ?>/public/collection-sheets/index.php" class="btn btn-sm btn-outline-secondary">
            <i data-feather="arrow-left" style="width: 14px; height: 14px;" class="me-1"></i> Back to List
          </a>
        </div>
      </div>
      <div class="notion-divider my-3"></div>
    </div>

    <!-- Flash Messages -->
    <?php if ($session->hasFlash('success')): ?><div class="alert alert-success alert-dismissible fade show">
      <?= $session->getFlash('success') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div><?php endif; ?>
    <?php if ($session->hasFlash('error')): ?><div class="alert alert-danger alert-dismissible fade show">
      <?= $session->getFlash('error') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div><?php endif; ?>

    <!-- Sheet Summary -->
    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="background-color: #e0f2fe;">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <i data-feather="hash" style="width: 24px; height: 24px; color: #0b76ef;" class="me-2"></i>
              <div>
                <small class="text-muted">Total Items</small>
                <h5 class="mb-0"><?= count($items) ?></h5>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="background-color: #dcfce7;">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <i data-feather="dollar-sign" style="width: 24px; height: 24px; color: #16a34a;" class="me-2"></i>
              <div>
                <small class="text-muted">Total Amount</small>
                <h5 class="mb-0">₱<?= number_format((float)$sheet['total_amount'], 2) ?></h5>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="background-color: #f5f3ff;">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <i data-feather="user" style="width: 24px; height: 24px; color: #9333ea;" class="me-2"></i>
              <div>
                <small class="text-muted">Account Officer</small>
                <h6 class="mb-0"><?= htmlspecialchars($sheet['officer_name'] ?? 'Officer ID ' . $sheet['officer_id']) ?></h6>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="background-color: #fef3e4;">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <i data-feather="calendar" style="width: 24px; height: 24px; color: #ec7211;" class="me-2"></i>
              <div>
                <small class="text-muted">Submitted</small>
                <h6 class="mb-0"><?= isset($sheet['submitted_at']) ? date('M d, h:i A', strtotime($sheet['submitted_at'])) : 'N/A' ?></h6>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Collection Items -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-transparent py-3">
        <h5 class="mb-0">
          <i data-feather="list" class="me-2" style="width: 18px; height: 18px;"></i>
          Collection Items
        </h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-4">#</th>
                <th>Client</th>
                <th>Loan</th>
                <th class="text-end">Amount</th>
                <th>Status</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($items)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No items in this sheet.</td></tr>
              <?php else: foreach ($items as $item): ?>
                <tr>
                  <td class="ps-4"><?= (int)$item['id'] ?></td>
                  <td>
                    <div>
                      <strong><?= htmlspecialchars($item['client_name']) ?></strong>
                      <small class="text-muted d-block">ID: <?= (int)$item['client_id'] ?></small>
                    </div>
                  </td>
                  <td>
                    <div>
                      Loan #<?= (int)$item['loan_id'] ?>
                      <span class="badge bg-<?= strtolower($item['loan_status']) === 'active' ? 'success' : 'secondary' ?> ms-1">
                        <?= htmlspecialchars($item['loan_status']) ?>
                      </span>
                    </div>
                  </td>
                  <td class="text-end">
                    <strong>₱<?= number_format((float)$item['amount'], 2) ?></strong>
                  </td>
                  <td>
                    <span class="badge bg-secondary"><?= htmlspecialchars($item['status']) ?></span>
                  </td>
                  <td>
                    <small><?= htmlspecialchars($item['notes'] ?? '-') ?></small>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
            <tfoot class="table-light">
              <tr>
                <th colspan="3" class="text-end ps-4">Total:</th>
                <th class="text-end">₱<?= number_format((float)$sheet['total_amount'], 2) ?></th>
                <th colspan="2"></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <?php if ($sheet['status'] === 'submitted'): ?>
    <div class="card shadow-sm">
      <div class="card-header bg-warning bg-opacity-10">
        <h5 class="mb-0">
          <i data-feather="help-circle" class="me-2" style="width: 18px; height: 18px;"></i>
          Cashier Actions
        </h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <form method="post" onsubmit="return confirm('Approve this collection sheet?');">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
              <input type="hidden" name="action" value="approve">
              <button type="submit" class="btn btn-success w-100">
                <i data-feather="check" class="me-2" style="width: 16px; height: 16px;"></i>
                Approve Sheet
              </button>
              <small class="text-muted d-block mt-2">Verify collections are accurate</small>
            </form>
          </div>
          <div class="col-md-4">
            <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
              <i data-feather="x-circle" class="me-2" style="width: 16px; height: 16px;"></i>
              Reject Sheet
            </button>
            <small class="text-muted d-block mt-2">Return to officer for corrections</small>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($sheet['status'] === 'approved'): ?>
    <div class="card shadow-sm">
      <div class="card-header bg-success bg-opacity-10">
        <h5 class="mb-0">
          <i data-feather="check-circle" class="me-2" style="width: 18px; height: 18px;"></i>
          Post Payments
        </h5>
      </div>
      <div class="card-body">
        <div class="alert alert-info">
          <i data-feather="info" class="me-2" style="width: 16px; height: 16px;"></i>
          This sheet has been approved. Post all items as payments to complete the process.
        </div>
        <form method="post" onsubmit="return confirm('Post all <?= count($items) ?> items as payments? This action cannot be undone.');">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
          <input type="hidden" name="action" value="post_payments">
          <button type="submit" class="btn btn-primary btn-lg">
            <i data-feather="upload" class="me-2" style="width: 18px; height: 18px;"></i>
            Post All Payments (<?= count($items) ?> items)
          </button>
        </form>
      </div>
    </div>
    <?php endif; ?>

  </div>
</main>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title">Reject Collection Sheet</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
          <input type="hidden" name="action" value="reject">
          <div class="mb-3">
            <label class="form-label">Reason for Rejection *</label>
            <textarea name="rejection_reason" class="form-control" rows="4" required 
                      placeholder="Enter reason why this sheet is being rejected..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Reject Sheet</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>
