<?php
/**
 * Payment View Controller
 * Displays detailed information about a specific payment.
 * Integrates: PaymentService, LoanService, ClientService, UserService
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../init.php';

// Enforce role-based access control (Staff roles can view payments)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier', 'account-officer']);

// Initialize services
$paymentService = new PaymentService();
$loanService = new LoanService();
$clientService = new ClientService();
$userService = new UserService();

// Get Payment ID from GET parameter
$paymentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($paymentId <= 0) {
    $session->setFlash('error', 'Payment ID is required.');
    header('Location: ' . APP_URL . '/public/payments/index.php');
    exit;
}

// Fetch payment data with related information
$payment = $paymentService->getPaymentWithDetails($paymentId);

if (!$payment) {
    $session->setFlash('error', 'Payment not found.');
    header('Location: ' . APP_URL . '/public/payments/index.php');
    exit;
}

// Get loan details
$loan = $loanService->getLoanWithClient($payment['loan_id']);

if (!$loan) {
    $session->setFlash('error', 'Associated loan not found.');
    header('Location: ' . APP_URL . '/public/payments/index.php');
    exit;
}

// Get payment summary for the loan
$paymentSummary = $paymentService->getPaymentSummaryByLoan($payment['loan_id']);

// Get recorded by user details
$recordedByUser = $userService->getUserWithRoleName($payment['user_id'] ?? null);

$pageTitle = "Payment Details - #" . $paymentId;
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Payment Details</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/payments/index.php" class="btn btn-sm btn-outline-secondary me-2">
                <i data-feather="arrow-left"></i> Back to Payments
            </a>
            <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $payment['loan_id'] ?>" class="btn btn-sm btn-outline-primary">
                <i data-feather="eye"></i> View Loan
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if ($session->hasFlash('success')): ?>
        <div class="alert alert-success">
            <?= $session->getFlash('success') ?>
        </div>
    <?php endif; ?>
    <?php if ($session->hasFlash('error')): ?>
        <div class="alert alert-danger">
            <?= $session->getFlash('error') ?>
        </div>
    <?php endif; ?>

    <!-- Payment Information Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Payment Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Payment ID:</dt>
                        <dd class="col-sm-7 fw-bold">#<?= $paymentId ?></dd>

                        <dt class="col-sm-5">Amount:</dt>
                        <dd class="col-sm-7 fw-bold text-success">₱<?= number_format($payment['amount'], 2) ?></dd>

                        <dt class="col-sm-5">Payment Date:</dt>
                        <dd class="col-sm-7"><?= date('M d, Y', strtotime($payment['payment_date'])) ?></dd>

                        <dt class="col-sm-5">Recorded By:</dt>
                        <dd class="col-sm-7">
                            <?php if ($recordedByUser): ?>
                                <?= htmlspecialchars($recordedByUser['name']) ?>
                                <small class="text-muted">(<?= htmlspecialchars($recordedByUser['role_name'] ?? $recordedByUser['role']) ?>)</small>
                            <?php else: ?>
                                <span class="text-muted">Unknown</span>
                            <?php endif; ?>
                        </dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Recorded On:</dt>
                        <dd class="col-sm-7"><?= date('M d, Y H:i', strtotime($payment['created_at'])) ?></dd>

                        <dt class="col-sm-5">Last Updated:</dt>
                        <dd class="col-sm-7">
                            <?php if (isset($payment['updated_at']) && $payment['updated_at'] && $payment['updated_at'] !== $payment['created_at']): ?>
                                <?= date('M d, Y H:i', strtotime($payment['updated_at'])) ?>
                            <?php else: ?>
                                <span class="text-muted">Never</span>
                            <?php endif; ?>
                        </dd>

                        <?php if (!empty($payment['notes'])): ?>
                            <dt class="col-sm-5">Notes:</dt>
                            <dd class="col-sm-7">
                                <div class="border rounded p-2 bg-light">
                                    <?= nl2br(htmlspecialchars($payment['notes'])) ?>
                                </div>
                            </dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Associated Loan Information -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Associated Loan Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Loan ID:</dt>
                        <dd class="col-sm-7 fw-bold">
                            <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $loan['id'] ?>" class="text-decoration-none">
                                #<?= $loan['id'] ?>
                            </a>
                        </dd>

                        <dt class="col-sm-5">Client:</dt>
                        <dd class="col-sm-7">
                            <a href="<?= APP_URL ?>/public/clients/view.php?id=<?= $loan['client_id'] ?>" class="text-decoration-none fw-medium">
                                <?= htmlspecialchars($loan['client_name']) ?>
                            </a>
                            <br><small class="text-muted"><?= htmlspecialchars($loan['phone_number']) ?></small>
                        </dd>

                        <dt class="col-sm-5">Loan Amount:</dt>
                        <dd class="col-sm-7">₱<?= number_format($loan['total_loan_amount'], 2) ?></dd>

                        <dt class="col-sm-5">Status:</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-<?= $loan['status'] === LoanModel::STATUS_ACTIVE ? 'success' :
                                ($loan['status'] === LoanModel::STATUS_COMPLETED ? 'primary' :
                                ($loan['status'] === LoanModel::STATUS_DEFAULTED ? 'danger' : 'secondary')) ?>">
                                <?= htmlspecialchars(ucfirst($loan['status'])) ?>
                            </span>
                        </dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Disbursement Date:</dt>
                        <dd class="col-sm-7"><?= date('M d, Y', strtotime($loan['disbursement_date'])) ?></dd>

                        <dt class="col-sm-5">Total Paid:</dt>
                        <dd class="col-sm-7 text-success fw-bold">₱<?= number_format($paymentSummary['total_paid'] ?? 0, 2) ?></dd>

                        <dt class="col-sm-5">Remaining Balance:</dt>
                        <dd class="col-sm-7 text-danger fw-bold">₱<?= number_format($loan['total_loan_amount'] - ($paymentSummary['total_paid'] ?? 0), 2) ?></dd>

                        <dt class="col-sm-5">Payment Progress:</dt>
                        <dd class="col-sm-7">
                            <div class="progress" style="height: 20px;">
                                <?php
                                $progress = $loan['total_loan_amount'] > 0 ? (($paymentSummary['total_paid'] ?? 0) / $loan['total_loan_amount']) * 100 : 0;
                                $progressClass = $progress >= 100 ? 'bg-success' : ($progress >= 50 ? 'bg-info' : 'bg-warning');
                                ?>
                                <div class="progress-bar <?= $progressClass ?>" role="progressbar"
                                     style="width: <?= min($progress, 100) ?>%"
                                     aria-valuenow="<?= round($progress, 1) ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?= round($progress, 1) ?>%
                                </div>
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment History for this Loan -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0">Payment History for this Loan</h5>
        </div>
        <div class="card-body">
            <?php
            $loanPayments = $paymentService->getPaymentsByLoan($payment['loan_id'], 1, 10); // Get recent 10 payments
            if (!empty($loanPayments['payments'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Recorded By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($loanPayments['payments'] as $loanPayment): ?>
                                <tr class="<?= $loanPayment['id'] == $paymentId ? 'table-primary' : '' ?>">
                                    <td><?= date('M d, Y', strtotime($loanPayment['payment_date'])) ?></td>
                                    <td class="fw-bold text-success">₱<?= number_format($loanPayment['amount'], 2) ?></td>
                                    <td>
                                        <?php
                                        $paymentUser = $userService->getUserWithRoleName($loanPayment['user_id'] ?? null);
                                        echo $paymentUser ? htmlspecialchars($paymentUser['name']) : 'Unknown';
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($loanPayment['id'] != $paymentId): ?>
                                            <a href="?id=<?= $loanPayment['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i data-feather="eye"></i> View
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Current</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($loanPayments['total'] > 10): ?>
                    <div class="text-center mt-3">
                        <a href="<?= APP_URL ?>/public/loans/view.php?id=<?= $payment['loan_id'] ?>" class="btn btn-sm btn-outline-secondary">
                            View All Payments for this Loan
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-4">
                    <i data-feather="credit-card" class="text-muted" style="width: 3rem; height: 3rem;"></i>
                    <h6 class="text-muted mt-2">No payment history found</h6>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
</main>

<?php
include_once BASE_PATH . '/templates/layout/footer.php';
?>
