<?php
/**
 * PaymentService - Handles loan payment operations, ensuring transactional integrity.
 * This is the core service for processing collections and updating loan status.
 */
require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/../models/PaymentModel.php';
require_once __DIR__ . '/../models/LoanModel.php';
require_once __DIR__ . '/LoanCalculationService.php';

class PaymentService extends BaseService {
    private $paymentModel;
    private $loanModel;
    private $loanCalculationService;

    public function __construct() {
        parent::__construct();
        $this->paymentModel = new PaymentModel();
        $this->loanModel = new LoanModel();
        $this->loanCalculationService = new LoanCalculationService();
        $this->setModel($this->paymentModel);
    }

    /**
     * Records a payment against an active loan, performs status checks, and uses a database transaction.
     * @param int $loanId The ID of the loan being paid.
     * @param float $amount Amount paid by the client.
     * @param int $recordedBy The user ID (Cashier/AO) recording the payment.
     * @return int|false New Payment ID on success, false on failure.
     */
    public function recordPayment($loanId, $amount, $recordedBy) {
        // Basic validation
        if (!$this->validate(['loan_id' => $loanId, 'amount' => $amount, 'recorded_by' => $recordedBy], [
            'loan_id' => 'required|numeric|positive',
            'amount' => 'required|numeric|positive',
            'recorded_by' => 'required|numeric|positive'
        ])) {
            return false;
        }

        // Fetch the loan, ensuring it exists and is active
        $loan = $this->loanModel->findById($loanId);
        if (!$loan) {
            $this->setErrorMessage('Loan not found.');
            return false;
        }

        if ($loan['status'] !== LoanModel::STATUS_ACTIVE) {
            $this->setErrorMessage('Cannot record payment: Loan is not currently active.');
            return false;
        }
        
        $totalPaid = $this->paymentModel->getTotalPaymentsForLoan($loanId);
        $remainingBalance = $loan['total_loan_amount'] - $totalPaid;

        // Ensure payment does not exceed the remaining balance
        if ($amount > $remainingBalance) {
            $amount = $remainingBalance;
            // Optionally set a message indicating the amount was capped
            // $this->setErrorMessage("Payment amount capped to final balance of ₱" . number_format($amount, 2));
        }
        
        // This MUST be an atomic transaction to ensure data integrity
        $paymentId = $this->transaction(function() use ($loanId, $amount, $recordedBy, $loan) {
            $data = [
                'loan_id' => $loanId,
                'user_id' => $recordedBy, // The user who recorded the payment
                'amount' => $amount,
                'payment_date' => date('Y-m-d H:i:s'),
            ];

            // 1. Record the Payment
            $newPaymentId = $this->paymentModel->create($data);

            if (!$newPaymentId) {
                throw new Exception("Failed to create payment record.");
            }

            // 2. Check for Loan Completion
            $newTotalPaid = $this->paymentModel->getTotalPaymentsForLoan($loanId);
            if (round($newTotalPaid, 2) >= round($loan['total_loan_amount'], 2)) {
                // If paid amount meets or exceeds the total loan amount, complete the loan
                $completionSuccess = $this->loanModel->completeLoan($loanId);
                if (!$completionSuccess) {
                    throw new Exception("Failed to update loan status to completed.");
                }
            }

            // 3. Log Transaction (Phase 2/3 - Placeholder for Audit Trail)
            // $transactionService = new TransactionService();
            // $transactionService->log('PAYMENT_RECORDED', $newPaymentId, $recordedBy);

            return $newPaymentId;
        });

        if (!$paymentId) {
            // Error message is set by the transaction wrapper if rollback occurs
            return false;
        }

        return $paymentId;
    }

    /**
     * Calculates the current status and financial summary of a loan.
     * @param int $loanId
     * @return array|false Loan details combined with payment totals.
     */
    public function getLoanSummaryWithStatus($loanId) {
        $loan = $this->loanModel->getLoanWithClient($loanId);
        if (!$loan) return false;

        $totalPaid = $this->paymentModel->getTotalPaymentsForLoan($loanId);
        $remainingBalance = $loan['total_loan_amount'] - $totalPaid;
        $payments = $this->paymentModel->getPaymentsByLoan($loanId);
        $nextWeekNumber = count($payments) + 1;
        
        $summary = [
            'loan' => $loan,
            'total_paid' => $totalPaid,
            'remaining_balance' => max(0, $remainingBalance),
            'payments_made' => count($payments),
            'next_payment_week' => $nextWeekNumber,
            'is_complete' => round($remainingBalance, 2) <= 0.01,
        ];
        
        // Determine overdue status
        if ($loan['status'] === LoanModel::STATUS_ACTIVE) {
             // Logic to determine expected due date based on disbursement date (Phase 2 logic)
             // For Phase 1, we rely on basic status update.
             // Final overdue check will be implemented in Phase 2 using the LoanCalculationService schedule.
        }

        return $summary;
    }

    // --- Utility Methods ---

    public function getPaymentsByLoan($loanId) {
        return $this->paymentModel->getPaymentsByLoan($loanId);
    }

    public function getPaymentWithDetails($paymentId) {
        return $this->paymentModel->getPaymentWithDetails($paymentId);
    }
}
