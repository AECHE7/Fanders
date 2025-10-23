<?php
/**
 * Collection Sheets - Add/Edit Draft
 */
require_once __DIR__ . '/../init.php';
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account_officer']);

$service = new CollectionSheetService();
$pageTitle = 'New Collection Sheet';

// Resolve sheet id: if none and AO/Super Admin, create today's draft
$sheetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($sheetId === 0 && in_array($userRole, ['super-admin', 'account_officer'])) {
    $draft = $service->createDraftSheet($user['id'], date('Y-m-d'));
    if ($draft) { 
        // Preserve loan_id parameter if present
        $loanParam = isset($_GET['loan_id']) ? '&loan_id=' . (int)$_GET['loan_id'] : '';
        header('Location: ' . APP_URL . '/public/collection-sheets/add.php?id=' . $draft['id'] . $loanParam); 
        exit; 
    } else {
        $session->setFlash('error', 'Failed to create collection sheet. Please try again.');
        header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
        exit;
    }
} elseif ($sheetId === 0) {
    $session->setFlash('error', 'No collection sheet specified or permission denied.');
    header('Location: ' . APP_URL . '/public/collection-sheets/index.php');
    exit;
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

// Initialize services first
$clientService = new ClientService();
$loanService = new LoanService();

// Handle loan pre-population from URL parameter
$prePopulateLoanId = isset($_GET['loan_id']) ? (int)$_GET['loan_id'] : 0;
$autoAdd = isset($_GET['auto_add']) && $_GET['auto_add'] === '1';
$autoProcess = isset($_GET['auto_process']) && $_GET['auto_process'] === '1';
$prePopulatedLoan = null;
$autoAdded = false;
$autoProcessed = false;

if ($prePopulateLoanId > 0) {
    $prePopulatedLoan = $loanService->getLoanWithClient($prePopulateLoanId);
    // Only allow active loans to be pre-populated (check for both 'Active' and 'active')
    if (!$prePopulatedLoan || (strtolower($prePopulatedLoan['status']) !== 'active')) {
        $prePopulatedLoan = null;
        $session->setFlash('warning', 'Loan not found or not active for collection.');
    } else if ($autoAdd && $sheet['status'] === 'draft') {
        // Automatically add the loan to the collection sheet
        $weeklyPayment = $prePopulatedLoan['total_loan_amount'] / 17;
        $notes = 'Auto-added from loan actions';
        $success = $service->addItem($sheet['id'], $prePopulatedLoan['client_id'], $prePopulateLoanId, $weeklyPayment, $notes);
        if ($success) {
            $autoAdded = true;
            $session->setFlash('success', 'Loan payment automatically added to collection sheet!');
            
            // If auto_process is enabled, immediately process payment
            if ($autoProcess) {
                try {
                    // Get the payment service
                    $paymentService = new PaymentService();
                    
                    // Process the payment automatically
                    $paymentData = [
                        'loan_id' => $prePopulateLoanId,
                        'amount' => $weeklyPayment,
                        'payment_date' => date('Y-m-d'),
                        'payment_method' => 'collection_sheet',
                        'notes' => 'Auto-processed payment via collection sheet',
                        'collected_by' => $_SESSION['user']['id']
                    ];
                    
                    $paymentResult = $paymentService->recordPaymentWithoutTransaction($paymentData);
                    
                    if ($paymentResult['success']) {
                        $autoProcessed = true;
                        $session->setFlash('success', 'Payment automatically processed! Amount: ₱' . number_format($weeklyPayment, 2));
                        
                        // Log the automatic payment transaction
                        $transactionService = new TransactionService();
                        $transactionService->logGeneric(
                            'payment_auto_processed',
                            'collection_sheet',
                            $sheet['id'],
                            $_SESSION['user']['id'],
                            "Auto-processed payment of ₱{$weeklyPayment} for Loan #{$prePopulateLoanId} via Collection Sheet #{$sheet['id']}"
                        );
                    } else {
                        $session->setFlash('warning', 'Loan added to collection sheet but payment processing failed: ' . $paymentResult['message']);
                    }
                } catch (Exception $e) {
                    $session->setFlash('warning', 'Loan added to collection sheet but payment auto-processing encountered an error: ' . $e->getMessage());
                }
            }
            
            // Refresh sheet details to show the new item
            $details = $service->getSheetDetails($sheet['id']);
            $sheet = $details['sheet'];
            $items = $details['items'];
        } else {
            $session->setFlash('error', 'Failed to add loan to collection sheet: ' . $service->getErrorMessage());
        }
    }
}

// Get active clients and their active loans for dropdown
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
    <?php if ($sheet['status'] === 'draft' && in_array($userRole, ['super-admin', 'account_officer'])): ?>
    
    <!-- Pre-populated Loan Alert (if loan_id in URL) -->
    <?php if ($prePopulatedLoan && !$autoAdded): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      <div class="d-flex align-items-center">
        <i data-feather="info" style="width: 18px; height: 18px;" class="me-2"></i>
        <div>
          <strong>Loan Pre-selected:</strong> 
          Loan #<?= $prePopulatedLoan['id'] ?> for <?= htmlspecialchars($prePopulatedLoan['client_name']) ?> 
          (Weekly Payment: ₱<?= number_format($prePopulatedLoan['total_loan_amount'] / 17, 2) ?>)
        </div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php elseif ($prePopulateLoanId > 0 && !$prePopulatedLoan): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
      <div class="d-flex align-items-center">
        <i data-feather="alert-triangle" style="width: 18px; height: 18px;" class="me-2"></i>
        <div>
          <strong>Note:</strong> Loan #<?= $prePopulateLoanId ?> could not be loaded for pre-population. Please select manually.
        </div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
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
                <option value="<?= $client['id'] ?>" 
                        data-name="<?= htmlspecialchars($client['name']) ?>"
                        <?= ($prePopulatedLoan && $prePopulatedLoan['client_id'] == $client['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($client['name']) ?> - <?= htmlspecialchars($client['phone_number'] ?? 'No phone') ?>
                </option>
              <?php endforeach; ?>
            </select>
            <!-- Locked client info display -->
            <div id="lockedClientInfo" class="mt-2" style="display: none;">
              <div class="alert alert-success py-2 px-3 mb-0">
                <i data-feather="lock" style="width: 14px; height: 14px;" class="me-1"></i>
                <strong>Selected Client:</strong> <span id="lockedClientName"></span>
                <small class="text-muted d-block">Fields are locked for this collection entry</small>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <label class="form-label">
              <i data-feather="file-text" style="width: 14px; height: 14px;"></i> Loan *
            </label>
            <select class="form-select" name="loan_id" id="loanSelect" required <?= $prePopulatedLoan ? '' : 'disabled' ?>>
              <?php if ($prePopulatedLoan): ?>
                <option value="<?= $prePopulatedLoan['id'] ?>" selected>
                  Loan #<?= $prePopulatedLoan['id'] ?> - ₱<?= number_format($prePopulatedLoan['principal'], 2) ?>
                </option>
              <?php else: ?>
                <option value="">-- Select client first --</option>
              <?php endif; ?>
            </select>
            <!-- Locked loan info display -->
            <div id="lockedLoanInfo" class="mt-2" style="display: none;">
              <div class="alert alert-info py-2 px-3 mb-0">
                <i data-feather="lock" style="width: 14px; height: 14px;" class="me-1"></i>
                <strong>Loan:</strong> <span id="lockedLoanDetails"></span>
                <small class="text-muted d-block">Weekly Payment: ₱<span id="lockedWeeklyAmount"></span></small>
              </div>
            </div>
            <small class="text-muted" id="loanSelectHelper">Active loans for selected client</small>
          </div>
          <div class="col-md-4">
            <label class="form-label">
              <i data-feather="dollar-sign" style="width: 14px; height: 14px;"></i> Payment Amount (₱) *
            </label>
            <input type="number" step="0.01" min="0.01" class="form-control" name="amount" id="amountInput"
                   placeholder="0.00" required
                   <?= $prePopulatedLoan ? 'value="' . number_format($prePopulatedLoan['total_loan_amount'] / 17, 2, '.', '') . '"' : '' ?>>
            <!-- Locked amount display -->
            <div id="lockedAmountInfo" class="mt-2" style="display: none;">
              <div class="alert alert-warning py-2 px-3 mb-0">
                <i data-feather="lock" style="width: 14px; height: 14px;" class="me-1"></i>
                <strong>Auto-calculated:</strong> ₱<span id="lockedAmountValue"></span>
                <small class="text-muted d-block">Based on weekly payment schedule</small>
              </div>
            </div>
            <?php if ($prePopulatedLoan): ?>
              <small class="text-muted" id="amountHelper">Weekly payment: ₱<?= number_format($prePopulatedLoan['total_loan_amount'] / 17, 2) ?></small>
            <?php else: ?>
              <small class="text-muted" id="amountHelper">Will be auto-filled when loan is selected</small>
            <?php endif; ?>
          </div>

          <div class="col-md-8">
            <label class="form-label">
              <i data-feather="message-circle" style="width: 14px; height: 14px;"></i> Notes
            </label>
            <input type="text" class="form-control" name="notes" id="notesInput" placeholder="Add any remarks or notes"
                   value="<?= $prePopulatedLoan ? 'Auto-populated from loan selection' : '' ?>">
            <!-- Auto-fill notes toggle -->
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" id="autoFillNotes" <?= $prePopulatedLoan ? 'checked' : '' ?>>
              <label class="form-check-label" for="autoFillNotes">
                Auto-fill collection notes
              </label>
            </div>
          </div>

          <div class="col-12">
            <button type="submit" class="btn btn-primary" id="addItemBtn">
              <i data-feather="plus" style="width: 14px; height: 14px;" class="me-1"></i> Add to Collection Sheet
            </button>
            <button type="reset" class="btn btn-outline-secondary" id="clearFormBtn">
              <i data-feather="x" style="width: 14px; height: 14px;" class="me-1"></i> Clear Form
            </button>
            <button type="button" class="btn btn-success" id="autoCollectBtn" style="display: none;">
              <i data-feather="zap" style="width: 14px; height: 14px;" class="me-1"></i> Auto-Collect Payment
            </button>
            <!-- Automation Controls -->
            <div class="mt-3">
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="lockAfterAdd" checked>
                <label class="form-check-label" for="lockAfterAdd">
                  Lock form after adding loan (prevents manual changes)
                </label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" id="autoSubmitEnabled">
                <label class="form-check-label" for="autoSubmitEnabled">
                  Auto-submit collection sheet when complete
                </label>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Items List -->
    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Items</strong>
        <div>
          <span class="me-3">Total: <strong>₱<?= number_format((float)$sheet['total_amount'], 2) ?></strong></span>
          <?php if ($sheet['status'] === 'draft' && in_array($userRole, ['super-admin', 'account_officer'])): ?>
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
<?php endif; ?>

<!-- JavaScript for automated collection sheet workflow -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const clientSelect = document.getElementById('clientSelect');
    const loanSelect = document.getElementById('loanSelect');
    const amountInput = document.getElementById('amountInput');
    const notesInput = document.getElementById('notesInput');
    const addItemBtn = document.getElementById('addItemBtn');
    const clearFormBtn = document.getElementById('clearFormBtn');
    const autoCollectBtn = document.getElementById('autoCollectBtn');
    
    // Locked info elements
    const lockedClientInfo = document.getElementById('lockedClientInfo');
    const lockedLoanInfo = document.getElementById('lockedLoanInfo');
    const lockedAmountInfo = document.getElementById('lockedAmountInfo');
    const lockedClientName = document.getElementById('lockedClientName');
    const lockedLoanDetails = document.getElementById('lockedLoanDetails');
    const lockedWeeklyAmount = document.getElementById('lockedWeeklyAmount');
    const lockedAmountValue = document.getElementById('lockedAmountValue');
    
    // Control checkboxes
    const lockAfterAdd = document.getElementById('lockAfterAdd');
    const autoSubmitEnabled = document.getElementById('autoSubmitEnabled');
    const autoFillNotes = document.getElementById('autoFillNotes');
    
    // Helper elements
    const loanSelectHelper = document.getElementById('loanSelectHelper');
    const amountHelper = document.getElementById('amountHelper');
    
    // State tracking
    let isFormLocked = false;
    let selectedLoanData = null;
    
    // Lock/unlock form elements
    function lockForm(lockState = true) {
        isFormLocked = lockState;
        
        clientSelect.disabled = lockState;
        loanSelect.disabled = lockState;
        amountInput.disabled = lockState;
        
        if (lockState && selectedLoanData) {
            // Show locked info displays
            lockedClientInfo.style.display = 'block';
            lockedLoanInfo.style.display = 'block';
            lockedAmountInfo.style.display = 'block';
            
            // Hide original selects
            clientSelect.style.display = 'none';
            loanSelect.style.display = 'none';
            amountInput.style.display = 'none';
            
            // Hide helpers
            if (loanSelectHelper) loanSelectHelper.style.display = 'none';
            if (amountHelper) amountHelper.style.display = 'none';
            
            // Fill locked display info
            lockedClientName.textContent = selectedLoanData.client_name;
            lockedLoanDetails.textContent = `#${selectedLoanData.loan_id} - ₱${parseFloat(selectedLoanData.principal).toFixed(2)}`;
            lockedWeeklyAmount.textContent = selectedLoanData.weekly_payment;
            lockedAmountValue.textContent = selectedLoanData.weekly_payment;
            
            // Show auto-collect button
            autoCollectBtn.style.display = 'inline-block';
            addItemBtn.textContent = 'Locked - Use Auto-Collect';
            addItemBtn.disabled = true;
            clearFormBtn.textContent = 'Unlock Form';
        } else {
            // Show original form elements
            lockedClientInfo.style.display = 'none';
            lockedLoanInfo.style.display = 'none';
            lockedAmountInfo.style.display = 'none';
            
            clientSelect.style.display = 'block';
            loanSelect.style.display = 'block';
            amountInput.style.display = 'block';
            
            // Show helpers
            if (loanSelectHelper) loanSelectHelper.style.display = 'block';
            if (amountHelper) amountHelper.style.display = 'block';
            
            // Hide auto-collect button
            autoCollectBtn.style.display = 'none';
            addItemBtn.textContent = 'Add to Collection Sheet';
            addItemBtn.disabled = false;
            clearFormBtn.textContent = 'Clear Form';
        }
    }
    
    if (clientSelect && loanSelect) {
        // Function to load loans for a client
        async function loadLoansForClient(clientId, preSelectedLoanId = null) {
            if (!clientId) {
                loanSelect.innerHTML = '<option value="">-- Select client first --</option>';
                loanSelect.disabled = true;
                return;
            }
            
            loanSelect.disabled = true;
            loanSelect.innerHTML = '<option value="">Loading...</option>';
            
            try {
                // Fetch active loans for the selected client
                const response = await fetch(`<?= APP_URL ?>/public/api/get_client_loans.php?client_id=${clientId}&status=active`);
                const data = await response.json();
                
                if (data.success && data.loans && data.loans.length > 0) {
                    loanSelect.innerHTML = '<option value="">-- Select Loan --</option>';
                    data.loans.forEach(loan => {
                        // Double-check that loan status is active (case-insensitive)
                        if (loan.status && loan.status.toLowerCase() === 'active') {
                            const weeklyPayment = (loan.total_loan_amount / loan.term_weeks).toFixed(2);
                            const option = document.createElement('option');
                            option.value = loan.id;
                            option.textContent = `Loan #${loan.id} - ₱${parseFloat(loan.principal).toFixed(2)} (Weekly: ₱${weeklyPayment})`;
                            option.dataset.weeklyPayment = weeklyPayment;
                            option.dataset.principal = loan.principal;
                            option.dataset.clientName = loan.client_name;
                            
                            // Pre-select if this is the intended loan
                            if (preSelectedLoanId && loan.id == preSelectedLoanId) {
                                option.selected = true;
                                // Store loan data
                                selectedLoanData = {
                                    loan_id: loan.id,
                                    client_id: clientId,
                                    client_name: loan.client_name,
                                    principal: loan.principal,
                                    weekly_payment: weeklyPayment
                                };
                                // Auto-fill amount
                                if (amountInput) {
                                    amountInput.value = weeklyPayment;
                                }
                            }
                            
                            loanSelect.appendChild(option);
                        }
                    });
                    loanSelect.disabled = false;
                } else {
                    loanSelect.innerHTML = '<option value="">No active loans for this client</option>';
                }
            } catch (error) {
                console.error('Error fetching loans:', error);
                loanSelect.innerHTML = '<option value="">Error loading loans</option>';
            }
        }
        
        // Event listener for client selection changes
        clientSelect.addEventListener('change', function() {
            if (!isFormLocked) {
                selectedLoanData = null;
                loadLoansForClient(this.value);
            }
        });
        
        // Auto-fill amount and prepare for locking when loan is selected
        loanSelect.addEventListener('change', function() {
            if (!isFormLocked) {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption && selectedOption.value && selectedOption.dataset.weeklyPayment) {
                    // Store selected loan data
                    selectedLoanData = {
                        loan_id: selectedOption.value,
                        client_id: clientSelect.value,
                        client_name: selectedOption.dataset.clientName,
                        principal: selectedOption.dataset.principal,
                        weekly_payment: selectedOption.dataset.weeklyPayment
                    };
                    
                    // Auto-fill amount
                    amountInput.value = selectedOption.dataset.weeklyPayment;
                    
                    // Auto-fill notes if enabled
                    if (autoFillNotes && autoFillNotes.checked) {
                        notesInput.value = `Weekly payment collection for Loan #${selectedOption.value}`;
                    }
                    
                    // Show notification about automation
                    showNotification('Loan selected! Form will auto-populate and lock after adding to collection sheet.', 'info');
                }
            }
        });
        
        // Auto-collect button functionality
        autoCollectBtn.addEventListener('click', async function() {
            if (selectedLoanData && isFormLocked) {
                // Use automated API instead of form submission
                try {
                    autoCollectBtn.disabled = true;
                    autoCollectBtn.innerHTML = '<i data-feather="loader" style="width: 14px; height: 14px;" class="me-1"></i> Auto-Collecting...';
                    
                    const formData = new FormData();
                    formData.append('action', 'add_loan_automated');
                    formData.append('sheet_id', <?= (int)$sheet['id'] ?>);
                    formData.append('loan_id', selectedLoanData.loan_id);
                    formData.append('auto_calculate', 'true');
                    formData.append('lock_form', 'true');
                    formData.append('auto_notes', 'true');
                    formData.append('csrf_token', '<?= htmlspecialchars($csrfToken) ?>');
                    
                    const response = await fetch('<?= APP_URL ?>/public/api/collection_automation.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showNotification('Payment auto-collected successfully!', 'success');
                        // Refresh the page to show updated collection sheet
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        throw new Error(result.error || 'Failed to auto-collect payment');
                    }
                    
                } catch (error) {
                    console.error('Auto-collect error:', error);
                    showNotification('Error during auto-collection: ' + error.message, 'error');
                    autoCollectBtn.disabled = false;
                    autoCollectBtn.innerHTML = '<i data-feather="zap" style="width: 14px; height: 14px;" class="me-1"></i> Auto-Collect Payment';
                }
            }
        });
        
        // Handle form submission
        document.getElementById('addItemForm').addEventListener('submit', function(e) {
            if (lockAfterAdd && lockAfterAdd.checked && selectedLoanData && !isFormLocked) {
                // Lock form after submission if enabled
                setTimeout(() => {
                    lockForm(true);
                    showNotification('Form locked! Use Auto-Collect for additional payments or unlock to change loan.', 'success');
                }, 100);
            }
        });
        
        // Clear/unlock form functionality
        clearFormBtn.addEventListener('click', function(e) {
            if (isFormLocked) {
                e.preventDefault();
                lockForm(false);
                selectedLoanData = null;
                // Reset form
                clientSelect.selectedIndex = 0;
                loanSelect.innerHTML = '<option value="">-- Select client first --</option>';
                loanSelect.disabled = true;
                amountInput.value = '';
                notesInput.value = '';
                showNotification('Form unlocked! You can now select a different loan.', 'info');
            }
        });
        
        // Auto-submit functionality
        if (autoSubmitEnabled) {
            autoSubmitEnabled.addEventListener('change', function() {
                if (this.checked) {
                    showNotification('Auto-submit enabled: Collection sheet will be submitted automatically when ready.', 'warning');
                }
            });
        }
        
        // If a client is pre-selected, load their loans automatically
        <?php if ($prePopulatedLoan && !$autoAdded): ?>
        const preSelectedClientId = <?= (int)$prePopulatedLoan['client_id'] ?>;
        const preSelectedLoanId = <?= (int)$prePopulatedLoan['id'] ?>;
        const autoProcess = <?= isset($_GET['auto_process']) && $_GET['auto_process'] === '1' ? 'true' : 'false' ?>;
        
        if (preSelectedClientId && preSelectedLoanId) {
            // Small delay to ensure DOM is fully loaded
            setTimeout(() => {
                loadLoansForClient(preSelectedClientId, preSelectedLoanId);
                
                // Check if this is auto-processing mode
                if (autoProcess) {
                    // Enhanced auto-processing mode - lock everything and prepare for instant payment
                    setTimeout(() => {
                        lockForm(true);
                        enhanceFormForAutoProcessing();
                        showNotification('⚡ Automatic Payment Mode Activated! Form locked for instant processing.', 'success');
                    }, 500);
                } else if (lockAfterAdd.checked) {
                    // Regular auto-lock for collection sheet addition
                    setTimeout(() => {
                        lockForm(true);
                        showNotification('Loan auto-selected from loan list! Form is locked for automated collection.', 'success');
                    }, 500);
                }
            }, 100);
        }
        <?php endif; ?>
        
        // Enhanced function for auto-processing mode
        function enhanceFormForAutoProcessing() {
            const addItemBtn = document.getElementById('addItemBtn');
            const clearFormBtn = document.getElementById('clearFormBtn');
            
            if (addItemBtn) {
                // Transform the button for auto-processing
                addItemBtn.innerHTML = '<i data-feather="zap" style="width: 16px; height: 16px;" class="me-2"></i> Process Payment Instantly';
                addItemBtn.className = 'btn btn-success btn-lg shadow-sm';
                
                // Add special styling
                addItemBtn.style.cssText = 'background: linear-gradient(45deg, #28a745, #20c997); border: none; font-weight: 600;';
                
                // Enhanced confirmation with payment details
                const originalClickHandler = addItemBtn.onclick;
                addItemBtn.onclick = function(e) {
                    e.preventDefault();
                    
                    const clientName = clientSelect.options[clientSelect.selectedIndex]?.text || 'Unknown Client';
                    const loanText = loanSelect.options[loanSelect.selectedIndex]?.text || 'Unknown Loan';
                    const amount = amountInput.value || '0.00';
                    
                    // Create enhanced confirmation dialog
                    const modalHTML = `
                        <div class="modal fade" id="confirmAutoProcess" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header bg-success text-white">
                                        <h5 class="modal-title">
                                            <i data-feather="zap" style="width: 18px; height: 18px;" class="me-2"></i>
                                            Confirm Automatic Payment Processing
                                        </h5>
                                    </div>
                                    <div class="modal-body">
                                        <div class="alert alert-info">
                                            <i data-feather="info" style="width: 16px; height: 16px;" class="me-2"></i>
                                            <strong>This payment will be processed instantly and cannot be undone.</strong>
                                        </div>
                                        
                                        <table class="table table-borderless">
                                            <tr><td><strong>Client:</strong></td><td>${clientName}</td></tr>
                                            <tr><td><strong>Loan:</strong></td><td>${loanText}</td></tr>
                                            <tr><td><strong>Amount:</strong></td><td class="text-success fw-bold">₱${parseFloat(amount).toFixed(2)}</td></tr>
                                            <tr><td><strong>Date:</strong></td><td>${new Date().toLocaleDateString()}</td></tr>
                                        </table>
                                        
                                        <div class="alert alert-warning">
                                            <i data-feather="alert-triangle" style="width: 16px; height: 16px;" class="me-2"></i>
                                            The payment will be automatically recorded and the loan balance updated immediately.
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="button" class="btn btn-success" onclick="proceedWithAutoProcess()">
                                            <i data-feather="zap" style="width: 14px; height: 14px;" class="me-1"></i> Process Payment Now
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Add modal to page and show it
                    document.body.insertAdjacentHTML('beforeend', modalHTML);
                    const modal = new bootstrap.Modal(document.getElementById('confirmAutoProcess'));
                    modal.show();
                    
                    // Refresh feather icons
                    feather.replace();
                };
            }
            
            // Hide clear button in auto-process mode
            if (clearFormBtn) {
                clearFormBtn.style.display = 'none';
            }
            
            // Add auto-process indicators
            document.querySelectorAll('.form-control, .form-select').forEach(el => {
                if (el.disabled || el.readOnly) {
                    el.style.borderColor = '#28a745';
                    el.style.backgroundColor = '#f8fff9';
                }
            });
        }
        
        // Function to proceed with auto-processing
        window.proceedWithAutoProcess = function() {
            // Hide the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('confirmAutoProcess'));
            modal.hide();
            
            // Show processing indicator
            const addItemBtn = document.getElementById('addItemBtn');
            if (addItemBtn) {
                addItemBtn.disabled = true;
                addItemBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing Payment...';
            }
            
            // Submit the form
            document.getElementById('addItemForm').submit();
        };
    }
    
    // Utility function to show notifications
    function showNotification(message, type = 'info') {
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'warning' ? 'alert-warning' : 
                          type === 'error' ? 'alert-danger' : 'alert-info';
        
        const notification = document.createElement('div');
        notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 1050; max-width: 400px;';
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i data-feather="info" style="width: 16px; height: 16px;" class="me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
        
        // Re-initialize feather icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    }
});
</script>

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>
