<?php
/**
 * Bulk SLR Generation Page
 * Allows generating multiple SLR documents at once
 */

// Centralized initialization
require_once '../../public/init.php';

// Enforce role-based access control
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier']);

// Initialize services
$loanReleaseService = new LoanReleaseService();

// Handle bulk generation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_generate') {
    if (!$csrf->validateRequest()) {
        $_SESSION['error'] = 'Invalid security token.';
        header('Location: bulk.php');
        exit;
    }
    
    $selectedLoans = $_POST['selected_loans'] ?? [];
    if (empty($selectedLoans)) {
        $_SESSION['error'] = 'Please select at least one loan for SLR generation.';
        header('Location: bulk.php');
        exit;
    }
    
    $results = $loanReleaseService->generateBulkSLR($selectedLoans, null, $_SESSION['user_id']);
    
    if ($results['success']) {
        $_SESSION['success'] = "Successfully generated {$results['count']} SLR documents. " . 
                               ($results['errors'] ? "Failed: {$results['errors']}" : "");
        header('Location: bulk.php');
    } else {
        $_SESSION['error'] = 'Failed to generate SLR documents: ' . $loanReleaseService->getErrorMessage();
        header('Location: bulk.php');
    }
    exit;
}

// Get filters
$filters = [];
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

if ($statusFilter) {
    $filters['status'] = $statusFilter;
}
if ($dateFrom) {
    $filters['date_from'] = $dateFrom;
}
if ($dateTo) {
    $filters['date_to'] = $dateTo;
}

$eligibleLoans = $loanReleaseService->getEligibleLoansForSLR($filters);

// Filter by search term if provided
if ($searchTerm && !empty($eligibleLoans)) {
    $eligibleLoans = array_filter($eligibleLoans, function($loan) use ($searchTerm) {
        $clientName = strtolower($loan['client_name'] ?? $loan['name'] ?? '');
        $loanId = (string)$loan['id'];
        $search = strtolower($searchTerm);
        
        return strpos($clientName, $search) !== false || strpos($loanId, $search) !== false;
    });
}

$pageTitle = 'Bulk SLR Generation';
include_once BASE_PATH . '/templates/layout/header.php';
?>

<main class="main-content">
  <div class="content-wrapper">
    <div class="container-fluid mt-4">
      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header bg-primary text-white">
              <h4 class="mb-0">
                <i data-feather="layers"></i> Bulk SLR Generation
              </h4>
              <small>Generate multiple Summary of Loan Release documents at once</small>
            </div>
            <div class="card-body">
              <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                  <?php 
                  echo htmlspecialchars($_SESSION['error']);
                  unset($_SESSION['error']);
                  ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              <?php endif; ?>
              
              <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                  <?php 
                  echo htmlspecialchars($_SESSION['success']);
                  unset($_SESSION['success']);
                  ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
              <?php endif; ?>
              
              <!-- Filters -->
              <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                  <label class="form-label">Search</label>
                  <input type="text" name="search" class="form-control" 
                         placeholder="Client name or Loan ID"
                         value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Status</label>
                  <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">From Date</label>
                  <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($dateFrom); ?>">
                </div>
                <div class="col-md-2">
                  <label class="form-label">To Date</label>
                  <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($dateTo); ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                  <button type="submit" class="btn btn-primary">
                    <i data-feather="search"></i> Filter
                  </button>
                  <a href="bulk.php" class="btn btn-secondary">
                    <i data-feather="rotate-cw"></i> Reset
                  </a>
                </div>
              </form>
              
              <form method="POST" id="bulkForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="bulk_generate">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div>
                    <button type="button" id="selectAll" class="btn btn-sm btn-outline-primary">
                      <i data-feather="check-square"></i> Select All
                    </button>
                    <button type="button" id="selectNone" class="btn btn-sm btn-outline-secondary">
                      <i data-feather="square"></i> Select None
                    </button>
                    <span id="selectedCount" class="text-muted ms-3">0 loans selected</span>
                  </div>
                  <div class="d-flex gap-2">
                    <button type="submit" id="bulkGenerate" class="btn btn-success" disabled>
                      <i data-feather="layers"></i> Generate & Save SLRs
                    </button>
                    <button type="button" id="downloadZip" class="btn btn-info" disabled>
                      <i data-feather="download"></i> Download as ZIP
                    </button>
                  </div>
                </div>
                
                <div class="table-responsive">
                  <table class="table table-striped table-hover">
                    <thead class="table-dark">
                      <tr>
                        <th width="40">
                          <input type="checkbox" id="masterCheck" class="form-check-input">
                        </th>
                        <th>Loan ID</th>
                        <th>Client Name</th>
                        <th>Principal</th>
                        <th>Total Amount</th>
                        <th>Disbursement Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($eligibleLoans)): ?>
                        <?php foreach ($eligibleLoans as $loan): ?>
                          <tr>
                            <td>
                              <input type="checkbox" name="selected_loans[]" 
                                     value="<?php echo $loan['id']; ?>" 
                                     class="form-check-input loan-checkbox">
                            </td>
                            <td><?php echo $loan['id']; ?></td>
                            <td><?php echo htmlspecialchars($loan['client_name'] ?? $loan['name']); ?></td>
                            <td>₱<?php echo number_format($loan['principal'], 2); ?></td>
                            <td>₱<?php echo number_format($loan['total_loan_amount'], 2); ?></td>
                            <td>
                              <?php 
                              if (!empty($loan['disbursement_date'])) {
                                  echo date('M d, Y', strtotime($loan['disbursement_date']));
                              } elseif (!empty($loan['start_date'])) {
                                  echo date('M d, Y', strtotime($loan['start_date']));
                              } else {
                                  echo 'N/A';
                              }
                              ?>
                            </td>
                            <td>
                              <span class="badge bg-<?php 
                                  echo $loan['status'] === 'active' ? 'success' : 
                                      ($loan['status'] === 'approved' ? 'warning' : 'info'); 
                              ?>">
                                  <?php echo ucfirst($loan['status']); ?>
                              </span>
                            </td>
                            <td>
                              <a href="view.php?loan_id=<?php echo $loan['id']; ?>" 
                                 class="btn btn-sm btn-outline-info" title="View Details">
                                <i data-feather="eye"></i>
                              </a>
                              <a href="generate.php?loan_id=<?php echo $loan['id']; ?>" 
                                 class="btn btn-sm btn-outline-success" title="Generate Individual SLR">
                                <i data-feather="download"></i>
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="8" class="text-center text-muted py-4">
                            <i data-feather="inbox" class="mb-2"></i>
                            <p>No eligible loans found for SLR generation.</p>
                          </td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </form>
              
              <div class="mt-3">
                <p class="text-muted">
                  <strong>Total Loans:</strong> <?php echo count($eligibleLoans); ?>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const masterCheck = document.getElementById('masterCheck');
    const loanCheckboxes = document.querySelectorAll('.loan-checkbox');
    const selectedCount = document.getElementById('selectedCount');
    const bulkGenerate = document.getElementById('bulkGenerate');
    const selectAll = document.getElementById('selectAll');
    const selectNone = document.getElementById('selectNone');
    
    function updateUI() {
        const checked = document.querySelectorAll('.loan-checkbox:checked');
        const count = checked.length;
        
        selectedCount.textContent = `${count} loan${count !== 1 ? 's' : ''} selected`;
        bulkGenerate.disabled = count === 0;
        document.getElementById('downloadZip').disabled = count === 0;
        
        if (count === 0) {
            masterCheck.indeterminate = false;
            masterCheck.checked = false;
        } else if (count === loanCheckboxes.length) {
            masterCheck.indeterminate = false;
            masterCheck.checked = true;
        } else {
            masterCheck.indeterminate = true;
        }
    }
    
    masterCheck.addEventListener('change', function() {
        loanCheckboxes.forEach(cb => cb.checked = this.checked);
        updateUI();
    });
    
    loanCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateUI);
    });
    
    selectAll.addEventListener('click', function() {
        loanCheckboxes.forEach(cb => cb.checked = true);
        updateUI();
    });
    
    selectNone.addEventListener('click', function() {
        loanCheckboxes.forEach(cb => cb.checked = false);
        updateUI();
    });
    
    document.getElementById('bulkForm').addEventListener('submit', function(e) {
        const checked = document.querySelectorAll('.loan-checkbox:checked');
        if (checked.length === 0) {
            e.preventDefault();
            alert('Please select at least one loan for SLR generation.');
            return;
        }
        
        if (!confirm(`Generate SLR documents for ${checked.length} selected loans?`)) {
            e.preventDefault();
        }
    });
    
    // ZIP Download functionality
    document.getElementById('downloadZip').addEventListener('click', function() {
        const checked = document.querySelectorAll('.loan-checkbox:checked');
        if (checked.length === 0) {
            alert('Please select at least one loan for ZIP download.');
            return;
        }
        
        if (!confirm(`Download SLR documents for ${checked.length} selected loans as ZIP file?`)) {
            return;
        }
        
        // Create form for ZIP download
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'download-bulk.php';
        form.style.display = 'none';
        
        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = document.querySelector('input[name="csrf_token"]').value;
        form.appendChild(csrfInput);
        
        // Add selected loan IDs
        checked.forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_loans[]';
            input.value = checkbox.value;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    });
    
    updateUI();
});
</script>

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>