<?php<?php

/**/**

 * SLR (Summary of Loan Release) Management Page * SLR (Summary of Loan Release) Management Page

 * Allows viewing, generating, and downloading SLR documents * Allows viewing, generating, and downloading SLR documents

 */ */



// Centralized initialization// Centralized initialization

require_once '../../public/init.php';require_once '../../public/init.php';



// Enforce role-based access control (Only cashiers, managers, admins)// Enforce role-based access control (Only cashiers, managers, admins)

$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier']);$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier']);



// Initialize services// Initialize services

$loanReleaseService = new LoanReleaseService();$loanReleaseService = new LoanReleaseService();



// Get list of eligible loans// Get list of eligible loans

$filters = [];$filters = [];

$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';



if ($statusFilter) {if ($statusFilter) {

    $filters['status'] = $statusFilter;    $filters['status'] = $statusFilter;

}}



$eligibleLoans = $loanReleaseService->getEligibleLoansForSLR($filters);$eligibleLoans = $loanReleaseService->getEligibleLoansForSLR($filters);



// Filter by search term if provided// Filter by search term if provided

if ($searchTerm && !empty($eligibleLoans)) {if ($searchTerm && !empty($eligibleLoans)) {

    $eligibleLoans = array_filter($eligibleLoans, function($loan) use ($searchTerm) {    $eligibleLoans = array_filter($eligibleLoans, function($loan) use ($searchTerm) {

        $clientName = strtolower($loan['client_name'] ?? $loan['name'] ?? '');        $clientName = strtolower($loan['client_name'] ?? $loan['name'] ?? '');

        $loanId = (string)$loan['id'];        $loanId = (string)$loan['id'];

        $search = strtolower($searchTerm);        $search = strtolower($searchTerm);

                

        return strpos($clientName, $search) !== false || strpos($loanId, $search) !== false;        return strpos($clientName, $search) !== false || strpos($loanId, $search) !== false;

    });    });

}}



$pageTitle = 'Summary of Loan Release (SLR)';    $pageTitle = 'Summary of Loan Release (SLR)';

include_once BASE_PATH . '/templates/layout/header.php';include_once BASE_PATH . '/templates/layout/header.php';

?>?>



<div class="container-fluid mt-4"><div class="container-fluid mt-4">

    <div class="row">    <div class="row">

        <div class="col-md-12">        <div class="col-md-12">

            <div class="card">                    <div class="card">

                <div class="card-header bg-success text-white">                    <div class="card-header bg-success text-white">

                    <h4 class="mb-0">                        <h4 class="mb-0">

                        <i data-feather="file-text"></i> Summary of Loan Release (SLR) Documents                            <i class="fas fa-file-contract"></i> Summary of Loan Release (SLR) Documents

                    </h4>                        </h4>

                </div>                    </div>

                <div class="card-body">                    <div class="card-body">

                    <?php if (isset($_SESSION['error'])): ?>                        <?php if (isset($_SESSION['error'])): ?>

                        <div class="alert alert-danger alert-dismissible fade show">                            <div class="alert alert-danger alert-dismissible fade show">

                            <?php                                 <?php 

                            echo htmlspecialchars($_SESSION['error']);                                echo htmlspecialchars($_SESSION['error']);

                            unset($_SESSION['error']);                                unset($_SESSION['error']);

                            ?>                                ?>

                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>

                        </div>                            </div>

                    <?php endif; ?>                        <?php endif; ?>

                                            

                    <?php if (isset($_SESSION['success'])): ?>                        <?php if (isset($_SESSION['success'])): ?>

                        <div class="alert alert-success alert-dismissible fade show">                            <div class="alert alert-success alert-dismissible fade show">

                            <?php                                 <?php 

                            echo htmlspecialchars($_SESSION['success']);                                echo htmlspecialchars($_SESSION['success']);

                            unset($_SESSION['success']);                                unset($_SESSION['success']);

                            ?>                                ?>

                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>

                        </div>                            </div>

                    <?php endif; ?>                        <?php endif; ?>

                                            

                    <!-- Search and Filter -->                        <!-- Search and Filter -->

                    <div class="row mb-3">                        <div class="row mb-3">

                        <div class="col-md-12">                            <div class="col-md-12">

                            <form method="GET" class="row g-3">                                <form method="GET" class="row g-3">

                                <div class="col-md-4">                                    <div class="col-md-4">

                                    <input type="text"                                         <input type="text" 

                                           name="search"                                                name="search" 

                                           class="form-control"                                                class="form-control" 

                                           placeholder="Search by Client Name or Loan ID..."                                               placeholder="Search by Client Name or Loan ID..."

                                           value="<?php echo htmlspecialchars($searchTerm); ?>">                                               value="<?php echo htmlspecialchars($searchTerm); ?>">

                                </div>                                    </div>

                                <div class="col-md-3">                                    <div class="col-md-3">

                                    <select name="status" class="form-select">                                        <select name="status" class="form-select">

                                        <option value="">All Statuses</option>                                            <option value="">All Statuses</option>

                                        <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>                                            <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>

                                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>                                            <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>

                                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>                                            <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>

                                    </select>                                        </select>

                                </div>                                    </div>

                                <div class="col-md-2">                                    <div class="col-md-2">

                                    <button type="submit" class="btn btn-primary w-100">                                        <button type="submit" class="btn btn-primary w-100">

                                        <i data-feather="search"></i> Search                                            <i class="fas fa-search"></i> Search

                                    </button>                                        </button>

                                </div>                                    </div>

                                <div class="col-md-2">                                    <div class="col-md-2">

                                    <a href="index.php" class="btn btn-secondary w-100">                                            <a href="index.php" class="btn btn-secondary w-100">

                                        <i data-feather="rotate-cw"></i> Reset                                            <i class="fas fa-redo"></i> Reset

                                    </a>                                        </a>

                                </div>                                    </div>

                            </form>                                </form>

                        </div>                            </div>

                    </div>                        </div>

                                            

                    <div class="alert alert-info">                        <div class="alert alert-info">

                        <i data-feather="info"></i>                             <i class="fas fa-info-circle"></i> 

                        <strong>About SLR:</strong> Summary of Loan Release documents are generated for approved and disbursed loans.                             <strong>About SLR:</strong> Summary of Loan Release documents are generated for approved and disbursed loans. 

                        Click "Generate SLR" to create and download the official loan release document.                            Click "Generate SLR" to create and download the official loan release document.

                    </div>                        </div>

                                            

                    <div class="table-responsive">                        <div class="table-responsive">

                        <table class="table table-striped table-hover">                            <table class="table table-striped table-hover">

                            <thead class="table-dark">                                <thead class="thead-dark">

                                <tr>                                    <tr>

                                    <th>SLR No.</th>                                        <th>SLR No.</th>

                                    <th>Loan ID</th>                                        <th>Loan ID</th>

                                    <th>Client Name</th>                                        <th>Client Name</th>

                                    <th>Principal</th>                                        <th>Principal</th>

                                    <th>Total Amount</th>                                        <th>Total Amount</th>

                                    <th>Disbursement Date</th>                                        <th>Disbursement Date</th>

                                    <th>Status</th>                                        <th>Status</th>

                                    <th>Actions</th>                                        <th>Actions</th>

                                </tr>                                    </tr>

                            </thead>                                </thead>

                            <tbody>                                <tbody>

                                <?php if (!empty($eligibleLoans)): ?>                                    <?php if (!empty($eligibleLoans)): ?>

                                    <?php foreach ($eligibleLoans as $loan): ?>                                        <?php foreach ($eligibleLoans as $loan): ?>

                                        <tr>                                            <tr>

                                            <td><code>SLR-<?php echo str_pad($loan['id'], 6, '0', STR_PAD_LEFT); ?></code></td>                                                <td><code>SLR-<?php echo str_pad($loan['id'], 6, '0', STR_PAD_LEFT); ?></code></td>

                                            <td><?php echo $loan['id']; ?></td>                                                <td><?php echo $loan['id']; ?></td>

                                            <td><?php echo htmlspecialchars($loan['client_name'] ?? $loan['name']); ?></td>                                                <td><?php echo htmlspecialchars($loan['client_name'] ?? $loan['name']); ?></td>

                                            <td>₱<?php echo number_format($loan['principal'], 2); ?></td>                                                <td>₱<?php echo number_format($loan['principal'], 2); ?></td>

                                            <td>₱<?php echo number_format($loan['total_loan_amount'], 2); ?></td>                                                <td>₱<?php echo number_format($loan['total_loan_amount'], 2); ?></td>

                                            <td>                                                <td><?php echo $loan['disbursement_date'] ? date('M d, Y', strtotime($loan['disbursement_date'])) : ($loan['start_date'] ? date('M d, Y', strtotime($loan['start_date'])) : 'N/A'); ?></td>

                                                <?php                                                 <td>

                                                if (!empty($loan['disbursement_date'])) {                                                    <span class="badge bg-<?php 

                                                    echo date('M d, Y', strtotime($loan['disbursement_date']));                                                        echo $loan['status'] === 'active' ? 'success' : 

                                                } elseif (!empty($loan['start_date'])) {                                                            ($loan['status'] === 'approved' ? 'warning' : 'info'); 

                                                    echo date('M d, Y', strtotime($loan['start_date']));                                                    ?>">

                                                } else {                                                        <?php echo ucfirst($loan['status']); ?>

                                                    echo 'N/A';                                                    </span>

                                                }                                                </td>

                                                ?>                                                <td>

                                            </td>                                                    <div class="btn-group btn-group-sm">

                                            <td>                                                                                                                    <a href="view.php?loan_id=<?php echo $loan['id']; ?>" 

                                                <span class="badge bg-<?php                                                            class="btn btn-info"

                                                    echo $loan['status'] === 'active' ? 'success' :                                                            title="View Details">

                                                        ($loan['status'] === 'approved' ? 'warning' : 'info');                                                                                                                             <i data-feather="eye"></i>

                                                ?>">                                                        </a>

                                                    <?php echo ucfirst($loan['status']); ?>                                                                                                                    <a href="generate.php?loan_id=<?php echo $loan['id']; ?>" 

                                                </span>                                                           class="btn btn-success"

                                            </td>                                                           title="Generate & Download SLR">

                                            <td>                                                                                                                            <i data-feather="download"></i> SLR

                                                <div class="btn-group btn-group-sm" role="group">                                                        </a>

                                                    <a href="view.php?loan_id=<?php echo $loan['id']; ?>"                                                                                                                 <a href="<?php echo APP_URL; ?>/public/loans/view.php?id=<?php echo $loan['id']; ?>" 

                                                       class="btn btn-outline-info"                                                           class="btn btn-secondary"

                                                       title="View Details">                                                           title="View Loan">

                                                        <i data-feather="eye"></i>                                                                                                                            <i data-feather="external-link"></i>

                                                    </a>                                                        </a>

                                                    <a href="generate.php?loan_id=<?php echo $loan['id']; ?>"                                                     </div>

                                                       class="btn btn-outline-success"                                                </td>

                                                       title="Generate & Download SLR">                                            </tr>

                                                        <i data-feather="download"></i>                                        <?php endforeach; ?>

                                                    </a>                                    <?php else: ?>

                                                    <a href="<?php echo APP_URL; ?>/public/loans/view.php?id=<?php echo $loan['id']; ?>"                                         <tr>

                                                       class="btn btn-outline-secondary"                                            <td colspan="8" class="text-center text-muted">

                                                       title="View Loan">                                                No eligible loans found for SLR generation. 

                                                        <i data-feather="external-link"></i>                                                <?php if ($searchTerm || $statusFilter): ?>

                                                    </a>                                                        <a href="index.php">Clear filters</a>

                                                </div>                                                <?php endif; ?>

                                            </td>                                            </td>

                                        </tr>                                        </tr>

                                    <?php endforeach; ?>                                    <?php endif; ?>

                                <?php else: ?>                                </tbody>

                                    <tr>                            </table>

                                        <td colspan="8" class="text-center text-muted py-4">                        </div>

                                            <i data-feather="inbox" class="mb-2"></i>                        

                                            <p>No eligible loans found for SLR generation.</p>                        <div class="mt-3">

                                            <?php if ($searchTerm || $statusFilter): ?>                            <p class="text-muted">

                                                <a href="index.php" class="btn btn-sm btn-primary">Clear filters</a>                                <strong>Total Loans:</strong> <?php echo count($eligibleLoans); ?>

                                            <?php endif; ?>                            </p>

                                        </td>                        </div>

                                    </tr>                    </div>

                                <?php endif; ?>                </div>

                            </tbody>        

                        </table>        </div>

                    </div>    </div>

                    </div>

                    <div class="mt-3">

                        <p class="text-muted"><?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>

                            <strong>Total Loans:</strong> <?php echo count($eligibleLoans); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>
