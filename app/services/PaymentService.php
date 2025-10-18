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

            // 3. Log transaction for audit trail
            if (class_exists('TransactionService')) {
                $transactionService = new TransactionService();
                $transactionService->logPaymentTransaction($newPaymentId, $recordedBy, [
                    'loan_id' => $loanId,
                    'amount' => $amount,
                    'payment_date' => date('Y-m-d H:i:s'),
                    'remaining_balance_before' => $remainingBalance
                ]);
            }

            // 4. Update cash blotter for the payment date
            if (class_exists('CashBlotterService')) {
                $cashBlotterService = new CashBlotterService();
                $cashBlotterService->updateBlotterForDate(date('Y-m-d'));
            }

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

    /**
     * Get overdue payments (loans with missed payments)
     * @return array
     */
    public function getOverduePayments() {
        // Get active loans that are overdue
        $sql = "SELECT l.*, c.name as client_name, c.phone_number
                FROM loans l
                JOIN clients c ON l.client_id = c.id
                WHERE l.status = 'active'
                AND l.due_date < CURDATE()
                ORDER BY l.due_date ASC";

        return $this->db->resultSet($sql);
    }

    /**
     * Get recent payments
     * @param int $limit
     * @return array
     */
    public function getRecentPayments($limit = 10) {
        $sql = "SELECT p.*, l.total_loan_amount, c.name as client_name,
                (SELECT COUNT(*) FROM payments p2 WHERE p2.loan_id = p.loan_id AND p2.payment_date <= p.payment_date) as week_number
                FROM payments p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                ORDER BY p.payment_date DESC
                LIMIT ?";

        return $this->db->resultSet($sql, [$limit]);
    }

    /**
     * Get next payment week for a loan
     * @param int $loanId
     * @return int|null
     */
    public function getNextPaymentWeek($loanId) {
        $loan = $this->loanModel->findById($loanId);
        if (!$loan || $loan['status'] !== 'active') {
            return null;
        }

        $payments = $this->paymentModel->getPaymentsByLoan($loanId);
        $nextWeek = count($payments) + 1;

        return $nextWeek;
    }

    /**
     * Get payment summary for a loan (total paid and payment count)
     * @param int $loanId
     * @return array
     */
    public function getPaymentSummaryByLoan($loanId) {
        $totalPaid = $this->paymentModel->getTotalPaymentsForLoan($loanId);
        $payments = $this->paymentModel->getPaymentsByLoan($loanId);

        return [
            'total_paid' => $totalPaid,
            'payment_count' => count($payments)
        ];
    }

    /**
     * Get all payments with filtering support
     * @param array $filters Array of filters (date_from, date_to, client_id, loan_id, recorded_by, search)
     * @param int $limit Optional limit for pagination
     * @param int $offset Optional offset for pagination
     * @return array
     */
    public function getAllPayments(array $filters = [], $limit = null, $offset = 0) {
        $sql = "SELECT p.*,
                l.principal, l.total_loan_amount, l.status as loan_status,
                c.name AS client_name, c.phone_number,
                u.name AS recorded_by_name
                FROM {$this->paymentModel->getTable()} p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                JOIN users u ON p.user_id = u.id";

        $conditions = [];
        $params = [];

        // Apply filters
        if (!empty($filters['search'])) {
            $conditions[] = "(c.name LIKE ? OR c.phone_number LIKE ? OR l.id = ?)";
            $searchParam = '%' . $filters['search'] . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $filters['search'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "p.payment_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "p.payment_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['client_id'])) {
            $conditions[] = "l.client_id = ?";
            $params[] = $filters['client_id'];
        }

        if (!empty($filters['loan_id'])) {
            $conditions[] = "p.loan_id = ?";
            $params[] = $filters['loan_id'];
        }

        if (!empty($filters['recorded_by'])) {
            $conditions[] = "p.user_id = ?";
            $params[] = $filters['recorded_by'];
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= " ORDER BY p.payment_date DESC, p.id DESC";

        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = $limit;

            if ($offset > 0) {
                $sql .= " OFFSET ?";
                $params[] = $offset;
            }
        }

        return $this->db->resultSet($sql, $params);
    }

    /**
     * Get total count of payments with filters
     * @param array $filters Array of filters
     * @return int
     */
    public function getTotalPaymentsCount(array $filters = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->paymentModel->getTable()} p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON l.client_id = c.id";

        $conditions = [];
        $params = [];

        // Apply same filters as getAllPayments
        if (!empty($filters['search'])) {
            $conditions[] = "(c.name LIKE ? OR c.phone_number LIKE ? OR l.id = ?)";
            $searchParam = '%' . $filters['search'] . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $filters['search'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "p.payment_date >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "p.payment_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['client_id'])) {
            $conditions[] = "l.client_id = ?";
            $params[] = $filters['client_id'];
        }

        if (!empty($filters['loan_id'])) {
            $conditions[] = "p.loan_id = ?";
            $params[] = $filters['loan_id'];
        }

        if (!empty($filters['recorded_by'])) {
            $conditions[] = "p.user_id = ?";
            $params[] = $filters['recorded_by'];
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $result = $this->db->single($sql, $params);
        return (int)($result ? $result['total'] : 0);
    }
}
