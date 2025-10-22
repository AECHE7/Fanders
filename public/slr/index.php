<?php
/**
 * SLR (Summary of Loan Release) Management Page
 * Allows viewing, generating, and downloading SLR documents
 */

// Centralized initialization
require_once '../../public/init.php';

// Enforce role-based access control (Only cashiers, managers, admins)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier']);

// Initialize services
$loanReleaseService = new LoanReleaseService();

// Get list of eligible loans
$filters = [];
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

if ($statusFilter) {
    $filters['status'] = $statusFilter;
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

$pageTitle = 'Summary of Loan Release (SLR)';
include_once BASE_PATH . '/templates/layout/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i data-feather="file-text"></i> Summary of Loan Release (SLR) Documents
                    </h4>
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
                    
                    <!-- Search and Filter -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <input type="text" 
                                           name="search" 
                                           class="form-control" 
                                           placeholder="Search by Client Name or Loan ID..."
                                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                                </div>
                                <div class="col-md-3">
                                    <select name="status" class="form-select">
                                        <option value="">All Statuses</option>
                                        <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i data-feather="search"></i> Search
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <a href="index.php" class="btn btn-secondary w-100">
                                        <i data-feather="rotate-cw"></i> Reset
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i data-feather="info"></i> 
                        <strong>About SLR:</strong> Summary of Loan Release documents are generated for approved and disbursed loans. 
                        Click "Generate SLR" to create and download the official loan release document.
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>SLR No.</th>
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
                                            <td><code>SLR-<?php echo str_pad($loan['id'], 6, '0', STR_PAD_LEFT); ?></code></td>
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
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="view.php?loan_id=<?php echo $loan['id']; ?>" 
                                                       class="btn btn-outline-info"
                                                       title="View Details">
                                                        <i data-feather="eye"></i>
                                                    </a>
                                                    <a href="generate.php?loan_id=<?php echo $loan['id']; ?>" 
                                                       class="btn btn-outline-success"
                                                       title="Generate & Download SLR">
                                                        <i data-feather="download"></i>
                                                    </a>
                                                    <a href="<?php echo APP_URL; ?>/public/loans/view.php?id=<?php echo $loan['id']; ?>" 
                                                       class="btn btn-outline-secondary"
                                                       title="View Loan">
                                                        <i data-feather="external-link"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i data-feather="inbox" class="mb-2"></i>
                                            <p>No eligible loans found for SLR generation.</p>
                                            <?php if ($searchTerm || $statusFilter): ?>
                                                <a href="index.php" class="btn btn-sm btn-primary">Clear filters</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
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

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>
