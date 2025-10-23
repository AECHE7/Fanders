<?php
/**
 * Collection Sheets - Index
 * Lists recent sheets and provides AO quick-start for today's draft.
 */
require_once __DIR__ . '/../init.php';
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account_officer', 'cashier']);

$pageTitle = 'Collection Sheets';
$service = new CollectionSheetService();

// Database connection for lightweight stats
$database = Database::getInstance();
$db = $database->getConnection();

// Flash helpers
$successMsg = $session->hasFlash('success') ? $session->getFlash('success') : null;
$errorMsg = $session->hasFlash('error') ? $session->getFlash('error') : null;

// AO quick create action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$csrf->validateRequest(false)) {
        $session->setFlash('error', 'Invalid security token.');
        header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
        exit;
    }

    if ($_POST['action'] === 'create_today') {
        $draft = $service->createDraftSheet($user['id'], date('Y-m-d'));
        if ($draft) {
            header('Location: ' . APP_URL . '/public/collection-sheets/add.php?id=' . $draft['id']);
            exit;
        }
        $session->setFlash('error', $service->getErrorMessage() ?: 'Failed to create draft.');
        header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
        exit;
    } elseif ($_POST['action'] === 'create_direct_today') {
        // Create draft sheet for super-admin direct collection
        $draft = $service->createDraftSheet($user['id'], date('Y-m-d'));
        if ($draft) {
            // Mark as direct collection sheet
            $service->enableAutomatedMode($draft['id'], [
                'auto_calculate' => true,
                'lock_after_add' => false, // Allow super-admin flexibility
                'auto_submit_when_complete' => false,
                'prevent_manual_entry' => false,
                'direct_post_enabled' => true
            ]);
            header('Location: ' . APP_URL . '/public/collection-sheets/add.php?id=' . $draft['id'] . '&direct=1');
            exit;
        }
        $session->setFlash('error', $service->getErrorMessage() ?: 'Failed to create direct collection sheet.');
        header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
        exit;
    }
}

// Parse filters from query
$filters = [];
if (isset($_GET['status']) && in_array($_GET['status'], ['draft','submitted','approved','posted'])) {
  $filters['status'] = $_GET['status'];
}
if (!empty($_GET['date'])) {
  $dateParam = $_GET['date'];
  if (@strtotime($dateParam) !== false) { $filters['date'] = date('Y-m-d', strtotime($dateParam)); }
}
if (!empty($_GET['officer_id']) && ctype_digit($_GET['officer_id'])) {
  $filters['officer_id'] = (int)$_GET['officer_id'];
}

// Role-aware default filter: AO sees own by default
if ($userRole === 'account_officer') {
  $filters['officer_id'] = $user['id'];
}
// Limit with simple "load more" support
$limit = isset($_GET['limit']) && ctype_digit($_GET['limit']) ? max(5, min((int)$_GET['limit'], 200)) : 20;
$filters['limit'] = $limit;
$sheets = $service->listSheets($filters);

// --- Dashboard statistics (focused on payments processing) ---
// Today stats by status
$today = date('Y-m-d');
$draftCount = $submittedCount = $approvedCount = $postedCount = 0;
$todayTotalAmount = 0.0;
try {
  $stmt = $db->prepare("SELECT status, COUNT(*) as cnt, COALESCE(SUM(total_amount),0) as total
              FROM collection_sheets WHERE sheet_date = ? GROUP BY status");
  $stmt->execute([$today]);
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $status = $row['status'] ?? '';
    if ($status === 'draft') $draftCount = (int)$row['cnt'];
    elseif ($status === 'submitted') $submittedCount = (int)$row['cnt'];
    elseif ($status === 'approved') $approvedCount = (int)$row['cnt'];
    elseif ($status === 'posted') $postedCount = (int)$row['cnt'];
    $todayTotalAmount += (float)$row['total'];
  }
} catch (Exception $e) { /* ignore, keep zeros */ }

// Pending review (submitted, any date recent)
$pendingReviewCount = 0;
try {
  $stmt = $db->prepare("SELECT COUNT(*) FROM collection_sheets WHERE status = 'submitted'");
  $stmt->execute();
  $pendingReviewCount = (int)$stmt->fetchColumn();
} catch (Exception $e) { /* ignore */ }

// Items processed today (posted items) and pending items today
$itemsPostedToday = 0; $itemsPendingToday = 0; $itemsAmountToday = 0.0;
try {
  // Posted items today
  $stmt = $db->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(i.amount),0) AS amt
              FROM collection_sheet_items i
              JOIN collection_sheets s ON s.id = i.sheet_id
              WHERE DATE(i.posted_at) = ?");
  $stmt->execute([$today]);
  $r = $stmt->fetch(PDO::FETCH_ASSOC);
  $itemsPostedToday = (int)($r['cnt'] ?? 0);
  $itemsAmountToday = (float)($r['amt'] ?? 0);

  // Pending items for today's sheets (not posted yet)
  $stmt = $db->prepare("SELECT COUNT(*) AS cnt
              FROM collection_sheet_items i
              JOIN collection_sheets s ON s.id = i.sheet_id
              WHERE s.sheet_date = ? AND (i.status IN ('draft','submitted') OR i.posted_at IS NULL)");
  $stmt->execute([$today]);
  $itemsPendingToday = (int)$stmt->fetchColumn();
} catch (Exception $e) { /* ignore */ }

// List of submitted sheets for quick cashier access (limit 10)
$submittedSheets = [];
try {
  $stmt = $db->prepare("SELECT cs.*, u.name AS officer_name
              FROM collection_sheets cs
              JOIN users u ON cs.officer_id = u.id
              WHERE cs.status = 'submitted'
              ORDER BY cs.sheet_date DESC, cs.id DESC
              LIMIT 10");
  $stmt->execute();
  $submittedSheets = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) { $submittedSheets = []; }

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
          <?php elseif ($userRole === 'super-admin'): ?>
          <form method="post" class="m-0">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="create_direct_today">
            <button type="submit" class="btn btn-sm btn-success">
              <i data-feather="zap" class="me-1" style="width:14px;height:14px;"></i>
              Direct Collection (Today)
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>
      <div class="notion-divider my-3"></div>
    </div>

    <?php if ($successMsg): ?><div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div><?php endif; ?>
    <?php if ($errorMsg): ?><div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div><?php endif; ?>

    <!-- Quick Actions -->
    <div class="card shadow-sm mb-4">
      <div class="card-header">
        <div class="d-flex align-items-center">
          <i data-feather="zap" class="me-2" style="width:18px;height:18px;"></i>
          <strong>Quick Actions</strong>
        </div>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <?php if ($userRole === 'account_officer'): ?>
          <div class="col-md-4">
            <form method="post" class="m-0">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
              <input type="hidden" name="action" value="create_today">
              <button type="submit" class="btn btn-primary w-100 py-3">
                <i data-feather="plus-circle" class="me-2" style="width:18px;height:18px;"></i>
                Start Today's Sheet
              </button>
            </form>
          </div>
          <div class="col-md-4">
            <a href="<?= APP_URL ?>/public/collection-sheets/index.php?status=draft" class="btn btn-warning w-100 py-3">
              <i data-feather="edit-3" class="me-2" style="width:18px;height:18px;"></i>
              View My Drafts
            </a>
          </div>
          <div class="col-md-4">
            <a href="<?= APP_URL ?>/public/collection-sheets/index.php?status=submitted" class="btn btn-info w-100 py-3">
              <i data-feather="send" class="me-2" style="width:18px;height:18px;"></i>
              Submitted to Cashier
            </a>
          </div>
          <?php else: ?>
          <div class="col-md-3">
            <a href="<?= APP_URL ?>/public/collection-sheets/index.php?status=submitted#pending-review" class="btn btn-warning w-100 py-3">
              <i data-feather="inbox" class="me-2" style="width:18px;height:18px;"></i>
              Review Submitted
            </a>
          </div>
          <div class="col-md-3">
            <a href="<?= APP_URL ?>/public/collection-sheets/index.php?status=approved" class="btn btn-success w-100 py-3">
              <i data-feather="check-circle" class="me-2" style="width:18px;height:18px;"></i>
              Ready to Post
            </a>
          </div>
          <div class="col-md-3">
            <a href="<?= APP_URL ?>/public/collection-sheets/index.php?status=posted&date=<?= urlencode(date('Y-m-d')) ?>" class="btn btn-primary w-100 py-3">
              <i data-feather="calendar" class="me-2" style="width:18px;height:18px;"></i>
              Posted Today
            </a>
          </div>
          <div class="col-md-3">
            <a href="<?= APP_URL ?>/public/collection-sheets/index.php" class="btn btn-secondary w-100 py-3">
              <i data-feather="list" class="me-2" style="width:18px;height:18px;"></i>
              View All Recent
            </a>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="p-4 rounded shadow-sm" style="background-color:#E0F2FE;">
          <div class="text-muted small mb-1">Today's Total Amount</div>
          <div class="h4 mb-0">₱<?= number_format((float)$todayTotalAmount, 2) ?></div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="p-4 rounded shadow-sm" style="background-color:#FEF3C7;">
          <div class="text-muted small mb-1">Drafts (Today)</div>
          <div class="h4 mb-0"><?= (int)$draftCount ?></div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="p-4 rounded shadow-sm" style="background-color:#DBEAFE;">
          <div class="text-muted small mb-1">Submitted (Today)</div>
          <div class="h4 mb-0"><?= (int)$submittedCount ?></div>
        </div>
      </div>
      <div class="col-md-2">
        <div class="p-4 rounded shadow-sm" style="background-color:#D1FAE5;">
          <div class="text-muted small mb-1">Posted Items (Today)</div>
          <div class="h4 mb-0"><?= (int)$itemsPostedToday ?></div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="p-4 rounded shadow-sm" style="background-color:#FCE7F3;">
          <div class="text-muted small mb-1">Pending Review (All)</div>
          <div class="h4 mb-0"><?= (int)$pendingReviewCount ?></div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
      <div class="card-header d-flex align-items-center">
        <i data-feather="filter" class="me-2" style="width:18px;height:18px;"></i>
        <h6 class="mb-0">Filters</h6>
      </div>
      <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label small text-muted">Date</label>
            <input type="date" name="date" value="<?= htmlspecialchars($filters['date'] ?? '') ?>" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label small text-muted">Status</label>
            <select name="status" class="form-select">
              <?php $statuses = ['', 'draft','submitted','approved','posted']; $statusSel = $filters['status'] ?? ''; ?>
              <option value="" <?= $statusSel === '' ? 'selected' : '' ?>>Any</option>
              <?php foreach (array_slice($statuses,1) as $st): ?>
                <option value="<?= $st ?>" <?= $statusSel === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if ($userRole !== 'account_officer'): ?>
          <div class="col-md-3">
            <label class="form-label small text-muted">Officer</label>
            <select name="officer_id" class="form-select">
              <option value="">Any</option>
              <?php
                try {
                  $officers = $db->query("SELECT id, name FROM users WHERE role = 'account_officer' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) { $officers = []; }
                $offSel = (string)($filters['officer_id'] ?? '');
                foreach ($officers as $o):
              ?>
                <option value="<?= (int)$o['id'] ?>" <?= $offSel === (string)$o['id'] ? 'selected' : '' ?>><?= htmlspecialchars($o['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary flex-fill">
              <i data-feather="search" class="me-1" style="width:16px;height:16px;"></i> Apply
            </button>
            <a href="<?= APP_URL ?>/public/collection-sheets/index.php" class="btn btn-outline-secondary">
              Reset
            </a>
          </div>
        </form>
      </div>
    </div>

  <div class="card shadow-sm">
      <div class="card-header d-flex align-items-center">
        <i data-feather="list" class="me-2" style="width:18px;height:18px;"></i>
        <h5 class="mb-0">Sheets</h5>
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
                  <td><?= htmlspecialchars(date('M d, Y', strtotime($s['sheet_date']))) ?></td>
                  <td><?= htmlspecialchars($s['officer_name'] ?? 'Officer ID ' . $s['officer_id']) ?></td>
                  <td>
                    <?php
                    $statusBadge = 'secondary';
                    if ($s['status'] === 'draft') $statusBadge = 'warning';
                    elseif ($s['status'] === 'submitted') $statusBadge = 'info';
                    elseif ($s['status'] === 'approved') $statusBadge = 'success';
                    elseif ($s['status'] === 'posted') $statusBadge = 'primary';
                    ?>
                    <span class="badge bg-<?= $statusBadge ?>"><?= htmlspecialchars(ucfirst($s['status'])) ?></span>
                  </td>
                  <td class="text-end">₱<?= number_format((float)$s['total_amount'], 2) ?></td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <?php if ($s['status'] === 'draft' && $userRole === 'account_officer'): ?>
                        <a href="<?= APP_URL ?>/public/collection-sheets/add.php?id=<?= $s['id'] ?>" class="btn btn-outline-primary" title="Edit">
                          <i data-feather="edit-2" style="width: 14px; height: 14px;"></i> Edit
                        </a>
                      <?php elseif (in_array($s['status'], ['submitted', 'approved']) && in_array($userRole, ['cashier', 'admin', 'super-admin', 'manager'])): ?>
                        <a href="<?= APP_URL ?>/public/collection-sheets/approve.php?id=<?= $s['id'] ?>" class="btn btn-outline-success" title="Review">
                          <i data-feather="check-circle" style="width: 14px; height: 14px;"></i> Review
                        </a>
                      <?php else: ?>
                        <a href="<?= APP_URL ?>/public/collection-sheets/add.php?id=<?= $s['id'] ?>" class="btn btn-outline-secondary" title="View">
                          <i data-feather="eye" style="width: 14px; height: 14px;"></i> View
                        </a>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php if (in_array($userRole, ['cashier','admin','manager','super-admin'])): ?>
  <div class="card shadow-sm mt-4" id="pending-review">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <i data-feather="inbox" class="me-2" style="width:18px;height:18px;"></i>
          <h5 class="mb-0">Pending Review (Submitted)</h5>
          <span class="badge bg-secondary ms-2"><?= count($submittedSheets) ?></span>
        </div>
        <div class="text-muted small">All dates</div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Officer</th>
                <th class="text-end">Total Amount</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($submittedSheets)): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">No submitted sheets pending review.</td></tr>
              <?php else: foreach ($submittedSheets as $ss): ?>
                <tr>
                  <td><?= (int)$ss['id'] ?></td>
                  <td><?= htmlspecialchars(date('M d, Y', strtotime($ss['sheet_date']))) ?></td>
                  <td><?= htmlspecialchars($ss['officer_name'] ?? ('Officer ID ' . $ss['officer_id'])) ?></td>
                  <td class="text-end">₱<?= number_format((float)($ss['total_amount'] ?? 0), 2) ?></td>
                  <td>
                    <a href="<?= APP_URL ?>/public/collection-sheets/review.php?id=<?= $ss['id'] ?>" class="btn btn-sm btn-outline-success">
                      <i data-feather="check-circle" style="width: 14px; height: 14px;"></i> Review
                    </a>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Load More (simple pagination) -->
    <div class="d-flex justify-content-center my-4">
      <?php 
        // Preserve filters in query string
        $qs = $_GET; $qs['limit'] = $limit + 20; 
        $moreUrl = APP_URL . '/public/collection-sheets/index.php?' . http_build_query($qs);
      ?>
      <a href="<?= $moreUrl ?>" class="btn btn-outline-primary">
        <i data-feather="more-horizontal" class="me-1" style="width:16px;height:16px;"></i>
        Show more
      </a>
    </div>

  </div>
</main>

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>
