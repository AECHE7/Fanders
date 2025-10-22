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
$loanService = new LoanService();

// Get action
$action = $_GET['action'] ?? 'list';
$loanId = isset($_GET['loan_id']) ? (int)$_GET['loan_id'] : null;

// Handle SLR generation and download
if ($action === 'generate' && $loanId) {
    $pdfContent = $loanReleaseService->generateSLRDocument($loanId);
    
    if ($pdfContent) {
        // Set headers for PDF download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="SLR_' . str_pad($loanId, 6, '0', STR_PAD_LEFT) . '_' . date('Ymd') . '.pdf"');
        header('Content-Length: ' . strlen($pdfContent));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        echo $pdfContent;
        exit;
    } else {
        $_SESSION['error'] = 'Failed to generate SLR: ' . $loanReleaseService->getErrorMessage();
        header('Location: /public/loans/slr.php');
        exit;
    }
}

// Handle viewing single SLR metadata
if ($action === 'view' && $loanId) {
    $slrMetadata = $loanReleaseService->getSLRMetadata($loanId);
    $loan = $loanService->getLoanWithClient($loanId);
    
    if (!$slrMetadata || !$loan) {
        $_SESSION['error'] = 'Loan not found.';
        header('Location: /public/loans/slr.php');
        exit;
    }
}

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

$pageTitle = $action === 'view' ? 'View SLR Document' : 'Summary of Loan Release (SLR)';
include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            
            <?php if ($action === 'list'): ?>
                <!-- List View -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-file-contract"></i> Summary of Loan Release (SLR) Documents
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
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                    </div>
                                    <div class="col-md-2">
                                        <a href="/public/loans/slr.php" class="btn btn-secondary w-100">
                                            <i class="fas fa-redo"></i> Reset
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>About SLR:</strong> Summary of Loan Release documents are generated for approved and disbursed loans. 
                            Click "Generate SLR" to create and download the official loan release document.
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead-dark">
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
                                                <td><?php echo $loan['disbursement_date'] ? date('M d, Y', strtotime($loan['disbursement_date'])) : ($loan['start_date'] ? date('M d, Y', strtotime($loan['start_date'])) : 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $loan['status'] === 'active' ? 'success' : 
                                                            ($loan['status'] === 'approved' ? 'warning' : 'info'); 
                                                    ?>">
                                                        <?php echo ucfirst($loan['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="?action=view&loan_id=<?php echo $loan['id']; ?>" 
                                                           class="btn btn-info"
                                                           title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="?action=generate&loan_id=<?php echo $loan['id']; ?>" 
                                                           class="btn btn-success"
                                                           title="Generate & Download SLR">
                                                            <i class="fas fa-file-pdf"></i> SLR
                                                        </a>
                                                        <a href="/public/loans/view.php?id=<?php echo $loan['id']; ?>" 
                                                           class="btn btn-secondary"
                                                           title="View Loan">
                                                            <i class="fas fa-external-link-alt"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">
                                                No eligible loans found for SLR generation. 
                                                <?php if ($searchTerm || $statusFilter): ?>
                                                    <a href="/public/loans/slr.php">Clear filters</a>
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
                
            <?php elseif ($action === 'view' && isset($loan)): ?>
                <!-- Detail View -->
                <div class="card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-file-contract"></i> SLR Details - <?php echo $slrMetadata['slr_number']; ?>
                        </h4>
                        <a href="/public/loans/slr.php" class="btn btn-light btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Document Information</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">SLR Number</th>
                                        <td><code><?php echo $slrMetadata['slr_number']; ?></code></td>
                                    </tr>
                                    <tr>
                                        <th>Loan ID</th>
                                        <td><?php echo $loan['id']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $loan['status'] === 'active' ? 'success' : 
                                                    ($loan['status'] === 'approved' ? 'warning' : 'info'); 
                                            ?>">
                                                <?php echo ucfirst($loan['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Disbursement Date</th>
                                        <td><?php echo $slrMetadata['disbursement_date'] ? date('F d, Y', strtotime($slrMetadata['disbursement_date'])) : 'Not disbursed'; ?></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="col-md-6">
                                <h5>Client Information</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="40%">Client Name</th>
                                        <td><?php echo htmlspecialchars($slrMetadata['client_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Client ID</th>
                                        <td><?php echo $loan['client_id']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Address</th>
                                        <td><?php echo htmlspecialchars($loan['client_address'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Contact</th>
                                        <td><?php echo htmlspecialchars($loan['client_phone'] ?? 'N/A'); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <h5>Loan Amount Breakdown</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <th width="30%">Principal Amount</th>
                                        <td class="text-end">₱<?php echo number_format($slrMetadata['principal_amount'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Interest (5% monthly × 4 months)</th>
                                        <td class="text-end">₱<?php echo number_format($slrMetadata['principal_amount'] * 0.05 * 4, 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Insurance Fee</th>
                                        <td class="text-end">₱425.00</td>
                                    </tr>
                                    <tr class="table-success">
                                        <th>TOTAL LOAN AMOUNT</th>
                                        <th class="text-end">₱<?php echo number_format($slrMetadata['total_loan_amount'], 2); ?></th>
                                    </tr>
                                    <tr>
                                        <th>Weekly Payment (17 weeks)</th>
                                        <td class="text-end">₱<?php echo number_format($slrMetadata['total_loan_amount'] / 17, 2); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="d-grid gap-2">
                                    <a href="?action=generate&loan_id=<?php echo $loan['id']; ?>" 
                                       class="btn btn-success btn-lg">
                                        <i class="fas fa-file-pdf"></i> Generate & Download SLR Document
                                    </a>
                                    <a href="/public/loans/view.php?id=<?php echo $loan['id']; ?>" 
                                       class="btn btn-secondary">
                                        <i class="fas fa-eye"></i> View Full Loan Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
