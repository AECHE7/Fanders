<?php
/**
 * Loan Detail View Template (templates/loans/view.php)
 * Displays detailed information about a single loan and its payment history.
 * @var array $viewData Contains: loanData, paymentSummary, paymentHistory, loanCalculation, remainingBalance, isComplete
 */

extract($viewData);

// Helper function to get loan status badge class (copied from index.php for consistency)
if (!function_exists('getLoanStatusBadgeClass')) {
    function getLoanStatusBadgeClass($status) {
        switch(strtolower($status)) {
            case 'active': return 'primary';
            case 'application': return 'warning';
            case 'approved': return 'info';
            case 'completed': return 'success';
            case 'defaulted': return 'danger';
            default: return 'secondary';
        }
    }
}
?>

<div class="row">
    <!-- Loan Summary (Left Column) -->
    <div class="col-md-5">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Financial Summary</h5>
                <span class="badge text-bg-light text-dark fw-bold p-2">
                    Status: <?= htmlspecialchars(ucfirst($loanData['status'])) ?>
                </span>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-6">Client Name:</dt>
                    <dd class="col-sm-6 fw-bold">
                        <a href="<?= APP_URL ?>/public/clients/view.php?id=<?= $loanData['client_id'] ?>" class="text-decoration-none">
                            <?= htmlspecialchars($loanData['client_name']) ?>
                        </a>
                    </dd>
                    <dt class="col-sm-6">Principal Amount:</dt>
                    <dd class="col-sm-6">₱<?= number_format($loanData['principal'], 2) ?></dd>
                    
                    <dt class="col-sm-6">Total Due (P+I+F):</dt>
                    <dd class="col-sm-6">₱<?= number_format($loanData['total_loan_amount'], 2) ?></dd>

                    <dt class="col-sm-6">Weekly Payment:</dt>
                    <dd class="col-sm-6 fw-bold text-success">₱<?= number_format($loanData['weekly_payment'], 2) ?></dd>
                    
                    <dt class="col-sm-6 text-muted">Term:</dt>
                    <dd class="col-sm-6 text-muted"><?= $loanData['term_weeks'] ?> Weeks (4 Mos)</dd>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Payment Metrics</h5>
            </div>
            <div class="card-body">
                 <dl class="row mb-0">
                    <dt class="col-sm-6">Total Paid To Date:</dt>
                    <dd class="col-sm-6 fw-bold text-success">₱<?= number_format($paymentSummary['total_paid'] ?? 0, 2) ?></dd>
                    
                    <dt class="col-sm-6">Payments Count:</dt>
                    <dd class="col-sm-6"><?= $paymentSummary['payment_count'] ?? 0 ?> / <?= $loanCalculation['weeks_total'] ?></dd>
                    
                    <hr class="my-2">
                    
                    <dt class="col-sm-6 fs-5">Remaining Balance:</dt>
                    <dd class="col-sm-6 fs-5 fw-bold text-danger">
                        ₱<?= number_format(max(0, $remainingBalance), 2) ?>
                    </dd>
                    
                    <?php if ($isComplete): ?>
                        <div class="col-12 mt-3 alert alert-success text-center fw-bold">
                            Loan is Fully Paid! (<?= date('M d, Y', strtotime($loanData['completion_date'])) ?>)
                        </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>

    <!-- Loan Timeline & History (Right Column) -->
    <div class="col-md-7">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Loan History & Timeline</h5>
            </div>
            <div class="card-body">
                <p class="small text-muted">Key dates for this loan:</p>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between">
                        <strong>Application Date:</strong> 
                        <span><?= date('M d, Y H:i A', strtotime($loanData['application_date'])) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <strong>Approval Date:</strong> 
                        <span><?= $loanData['approval_date'] ? date('M d, Y', strtotime($loanData['approval_date'])) : 'Pending' ?></span>
                    </li>
                     <li class="list-group-item d-flex justify-content-between <?= $loanData['disbursement_date'] ? 'text-primary fw-medium' : '' ?>">
                        <strong>Disbursement Date (Start):</strong> 
                        <span><?= $loanData['disbursement_date'] ? date('M d, Y', strtotime($loanData['disbursement_date'])) : 'Not Disbursed' ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between <?= $isComplete ? 'text-success fw-medium' : '' ?>">
                        <strong>Completion Date:</strong> 
                        <span><?= $loanData['completion_date'] ? date('M d, Y', strtotime($loanData['completion_date'])) : 'Outstanding' ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Payment History Table (Full Width) -->
<div class="card shadow-sm mt-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0">Detailed Payment History</h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($paymentHistory)): ?>
            <div class="alert alert-info m-4" role="alert">
                No payments have been recorded for this loan yet.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;">ID</th>
                            <th style="width: 15%;">Amount Paid</th>
                            <th style="width: 20%;">Date Recorded</th>
                            <th style="width: 25%;">Recorded By</th>
                            <th style="width: 35%;">Action/Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paymentHistory as $payment): ?>
                            <tr>
                                <td><?= htmlspecialchars($payment['id']) ?></td>
                                <td class="fw-medium text-success">₱<?= number_format($payment['amount'], 2) ?></td>
                                <td><?= date('M d, Y H:i A', strtotime($payment['payment_date'])) ?></td>
                                <td>
                                    <?= htmlspecialchars($payment['recorded_by_name'] ?? 'N/A') ?>
                                    <small class="d-block text-muted">Staff ID: <?= $payment['user_id'] ?></small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($payment['notes'] ?? 'Payment recorded.') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
