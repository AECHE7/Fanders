<?php
/**
 * SLR Detail View Page
 * Shows detailed information about a specific SLR document
 */

// Centralized initialization
require_once '../../public/init.php';

// Enforce role-based access control
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier']);

// Initialize services
$loanReleaseService = new LoanReleaseService();
$loanService = new LoanService();

// Get loan ID
$loanId = isset($_GET['loan_id']) ? (int)$_GET['loan_id'] : null;

if (!$loanId) {
    $_SESSION['error'] = 'Invalid loan ID.';
    header('Location: index.php');
    exit;
}

// Get SLR metadata and loan details
$slrMetadata = $loanReleaseService->getSLRMetadata($loanId);
$loan = $loanService->getLoanWithClient($loanId);

if (!$slrMetadata || !$loan) {
    $_SESSION['error'] = 'Loan or SLR metadata not found.';
    header('Location: index.php');
    exit;
}

$pageTitle = 'View SLR Document';
include_once BASE_PATH . '/templates/layout/header.php';
?>

<main class="main-content">
  <div class="content-wrapper">
    <div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i data-feather="file-text"></i> SLR Details - <?php echo htmlspecialchars($slrMetadata['slr_number']); ?>
                    </h4>
                    <a href="index.php" class="btn btn-light btn-sm">
                        <i data-feather="arrow-left"></i> Back to List
                    </a>
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
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3">Document Information</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%" class="bg-light">SLR Number</th>
                                    <td><code><?php echo htmlspecialchars($slrMetadata['slr_number']); ?></code></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Loan ID</th>
                                    <td><?php echo $loan['id']; ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Status</th>
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
                                    <th class="bg-light">Disbursement Date</th>
                                    <td>
                                        <?php 
                                        if (!empty($slrMetadata['disbursement_date'])) {
                                            echo date('F d, Y', strtotime($slrMetadata['disbursement_date']));
                                        } else {
                                            echo '<span class="text-muted">Not disbursed yet</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <h5 class="mb-3">Client Information</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%" class="bg-light">Client Name</th>
                                    <td><?php echo htmlspecialchars($slrMetadata['client_name']); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Client ID</th>
                                    <td><?php echo $loan['client_id']; ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Address</th>
                                    <td><?php echo htmlspecialchars($loan['client_address'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Contact</th>
                                    <td><?php echo htmlspecialchars($loan['client_phone'] ?? 'N/A'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5 class="mb-3">Loan Amount Breakdown</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="50%" class="bg-light">Principal Amount</th>
                                    <td class="text-end">₱<?php echo number_format($slrMetadata['principal_amount'], 2); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Interest (5% monthly × 4 months)</th>
                                    <td class="text-end">₱<?php echo number_format($slrMetadata['principal_amount'] * 0.05 * 4, 2); ?></td>
                                </tr>
                                <tr>
                                    <th class="bg-light">Insurance Fee</th>
                                    <td class="text-end">₱425.00</td>
                                </tr>
                                <tr class="table-success">
                                    <th><strong>TOTAL LOAN AMOUNT</strong></th>
                                    <th class="text-end"><strong>₱<?php echo number_format($slrMetadata['total_loan_amount'], 2); ?></strong></th>
                                </tr>
                                <tr class="table-info">
                                    <th class="bg-light">Weekly Payment (17 weeks)</th>
                                    <td class="text-end"><strong>₱<?php echo number_format($slrMetadata['total_loan_amount'] / 17, 2); ?></strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="d-grid gap-2">
                                <a href="generate.php?loan_id=<?php echo $loan['id']; ?>" 
                                   class="btn btn-success btn-lg">
                                    <i data-feather="download"></i> Generate & Download SLR Document
                                </a>
                                <a href="<?php echo APP_URL; ?>/public/loans/view.php?id=<?php echo $loan['id']; ?>" 
                                   class="btn btn-secondary">
                                    <i data-feather="eye"></i> View Full Loan Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
  </div>
</main>

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>
