<?php
/**
 * Collection Sheets - Add/Edit Draft
 */
require_once __DIR__ . '/../init.php';
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account_officer']);

$service = new CollectionSheetService();
$pageTitle = 'New Collection Sheet';

// Resolve sheet id: if none and AO, create today's draft
$sheetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($sheetId === 0 && $userRole === 'account_officer') {
    $draft = $service->createDraftSheet($user['id'], date('Y-m-d'));
    if ($draft) { header('Location: ' . APP_URL . '/public/collection-sheets/add.php?id=' . $draft['id']); exit; }
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!$csrf->validateRequest(false)) {
        $session->setFlash('error', 'Invalid security token.');
        header('Location: ' . APP_URL . '/public/collection-sheets/add.php?id=' . (int)$_POST['sheet_id']);
        exit;
    }
    $sheetId = (int)($_POST['sheet_id'] ?? 0);
    switch ($_POST['action']) {
        case 'add_item':
            $clientId = (int)($_POST['client_id'] ?? 0);
            $loanId = (int)($_POST['loan_id'] ?? 0);
            $amount = (float)($_POST['amount'] ?? 0);
            $notes = trim($_POST['notes'] ?? '');
            $ok = $service->addItem($sheetId, $clientId, $loanId, $amount, $notes);
            if ($ok) { $session->setFlash('success', 'Item added.'); }
            else { $session->setFlash('error', $service->getErrorMessage() ?: 'Failed to add item.'); }
            header('Location: ' . APP_URL . '/public/collection-sheets/add.php?id=' . $sheetId);
            exit;
        case 'submit_sheet':
            $ok = $service->submitSheet($sheetId);
            if ($ok) { $session->setFlash('success', 'Sheet submitted for review.');
                header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
            } else { $session->setFlash('error', $service->getErrorMessage() ?: 'Failed to submit.');
                header('Location: ' . APP_URL . '/public/collection-sheets/add.php?id=' . $sheetId);
            }
            exit;
    }
}

// Load details
$details = $sheetId ? $service->getSheetDetails($sheetId) : false;
if (!$details) { $session->setFlash('error', 'Sheet not found.'); header('Location: ' . APP_URL . '/public/collection-sheets/index.php'); exit; }
$sheet = $details['sheet'];
$items = $details['items'];

// Get active clients and their active loans for dropdown
$clientService = new ClientService();
$loanService = new LoanService();
$activeClients = $clientService->getAllClients(['status' => 'active', 'limit' => 1000]);

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
            <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #e0f2fe;">
              <i data-feather="clipboard" style="width:24px;height:24px;color:#0b76ef;"></i>
            </div>
          </div>
          <div>
            <h1 class="notion-page-title mb-0">Collection Sheet #<?= (int)$sheet['id'] ?></h1>
            <div class="text-muted small">
              <span class="badge bg-<?= $sheet['status'] === 'draft' ? 'warning' : ($sheet['status'] === 'submitted' ? 'info' : 'success') ?> me-2">
                <?= htmlspecialchars(ucfirst($sheet['status'])) ?>
              </span>
              Date: <?= htmlspecialchars(date('F d, Y', strtotime($sheet['sheet_date']))) ?> • Officer: <?= htmlspecialchars($user['name']) ?>
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

    <!-- Add Item Form (Only for Draft Status) -->
    <?php if ($sheet['status'] === 'draft' && $userRole === 'account_officer'): ?>
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-primary text-white">
        <div class="d-flex align-items-center">
          <i data-feather="plus-circle" style="width: 18px; height: 18px;" class="me-2"></i>
          <strong>Add Collection Item</strong>
        </div>
      </div>
      <div class="card-body">
        <form method="post" class="row g-3" id="addItemForm">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
          <input type="hidden" name="action" value="add_item">
          <input type="hidden" name="sheet_id" value="<?= (int)$sheet['id'] ?>">
          
          <div class="col-md-6">
            <label class="form-label">
              <i data-feather="user" style="width: 14px; height: 14px;"></i> Client *
            </label>
            <select class="form-select" name="client_id" id="clientSelect" required>
              <option value="">-- Select Client --</option>
              <?php foreach ($activeClients as $client): ?>
                <option value="<?= $client['id'] ?>" data-name="<?= htmlspecialchars($client['name']) ?>">
                  <?= htmlspecialchars($client['name']) ?> - <?= htmlspecialchars($client['phone_number'] ?? 'No phone') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="col-md-6">
            <label class="form-label">
              <i data-feather="file-text" style="width: 14px; height: 14px;"></i> Loan *
            </label>
            <select class="form-select" name="loan_id" id="loanSelect" required disabled>
              <option value="">-- Select client first --</option>
            </select>
            <small class="text-muted">Active loans for selected client</small>
          </div>
          
          <div class="col-md-4">
            <label class="form-label">
              <i data-feather="dollar-sign" style="width: 14px; height: 14px;"></i> Payment Amount (₱) *
            </label>
            <input type="number" step="0.01" min="0.01" class="form-control" name="amount" id="amountInput" placeholder="0.00" required>
          </div>
          
          <div class="col-md-8">
            <label class="form-label">
              <i data-feather="message-circle" style="width: 14px; height: 14px;"></i> Notes (Optional)
            </label>
            <input type="text" class="form-control" name="notes" placeholder="Add any remarks or notes">
          </div>
          
          <div class="col-12">
            <button type="submit" class="btn btn-primary">
              <i data-feather="plus" style="width: 14px; height: 14px;" class="me-1"></i> Add to Collection Sheet
            </button>
            <button type="reset" class="btn btn-outline-secondary">
              <i data-feather="x" style="width: 14px; height: 14px;" class="me-1"></i> Clear Form
            </button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- Items List -->
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Items</strong>
        <div>
          <span class="me-3">Total: <strong>₱<?= number_format((float)$sheet['total_amount'], 2) ?></strong></span>
          <?php if ($sheet['status'] === 'draft' && $userRole === 'account_officer'): ?>
          <form method="post" class="d-inline">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="submit_sheet">
            <input type="hidden" name="sheet_id" value="<?= (int)$sheet['id'] ?>">
            <button type="submit" class="btn btn-success">Submit for Review</button>
          </form>
          <?php endif; ?>
        </div>
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
                <tr><td colspan="6" class="text-center text-muted py-4">No items yet.</td></tr>
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

<!-- JavaScript for dynamic loan loading -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const clientSelect = document.getElementById('clientSelect');
    const loanSelect = document.getElementById('loanSelect');
    const amountInput = document.getElementById('amountInput');
    
    if (clientSelect && loanSelect) {
        clientSelect.addEventListener('change', async function() {
            const clientId = this.value;
            loanSelect.disabled = true;
            loanSelect.innerHTML = '<option value="">Loading...</option>';
            
            if (!clientId) {
                loanSelect.innerHTML = '<option value="">-- Select client first --</option>';
                return;
            }
            
            try {
                // Fetch active loans for the selected client
                const response = await fetch(`<?= APP_URL ?>/public/api/get_client_loans.php?client_id=${clientId}&status=active`);
                const data = await response.json();
                
                if (data.success && data.loans && data.loans.length > 0) {
                    loanSelect.innerHTML = '<option value="">-- Select Loan --</option>';
                    data.loans.forEach(loan => {
                        const weeklyPayment = (loan.total_loan_amount / loan.term_weeks).toFixed(2);
                        const option = document.createElement('option');
                        option.value = loan.id;
                        option.textContent = `Loan #${loan.id} - ₱${parseFloat(loan.principal).toFixed(2)} (Weekly: ₱${weeklyPayment})`;
                        option.dataset.weeklyPayment = weeklyPayment;
                        loanSelect.appendChild(option);
                    });
                    loanSelect.disabled = false;
                } else {
                    loanSelect.innerHTML = '<option value="">No active loans for this client</option>';
                }
            } catch (error) {
                console.error('Error fetching loans:', error);
                loanSelect.innerHTML = '<option value="">Error loading loans</option>';
            }
        });
        
        // Auto-fill amount when loan is selected
        loanSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset.weeklyPayment) {
                amountInput.value = selectedOption.dataset.weeklyPayment;
            }
        });
    }
});
</script>

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>
