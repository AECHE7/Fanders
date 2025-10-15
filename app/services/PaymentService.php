<?php
/**
 * PaymentService - Handles loan payment operations
 */
class PaymentService extends BaseService {
    private $paymentModel;
    private $loanModel;
    private $clientModel;

    public function __construct() {
        parent::__construct();
        $this->paymentModel = new PaymentModel();
        $this->loanModel = new LoanModel();
        $this->clientModel = new ClientModel();
        $this->setModel($this->paymentModel);
    }

    public function recordPayment($loanId, $paymentAmount, $weekNumber, $recordedBy, $collectedBy = null, $paymentMethod = 'cash', $notes = null) {
        // Get loan details
        $loan = $this->loanModel->findById($loanId);
        if (!$loan) {
            $this->setErrorMessage('Loan not found.');
            return false;
        }

        // Check if loan is active
        if ($loan['status'] !== 'active') {
            $this->setErrorMessage('Cannot record payment for inactive loan.');
            return false;
        }

        // Check if payment for this week already exists
        $existingPayments = $this->paymentModel->getPaymentsForWeek($loanId, $weekNumber);
        if (!empty($existingPayments)) {
            $this->setErrorMessage('Payment for this week already exists.');
            return false;
        }

        // Calculate payment breakdown using LoanCalculationService
        $loanCalculationService = new LoanCalculationService();
        $breakdown = $loanCalculationService->calculatePaymentBreakdown($loan, $paymentAmount);

        $data = [
            'loan_id' => $loanId,
            'client_id' => $loan['client_id'],
            'payment_amount' => $paymentAmount,
            'payment_date' => date('Y-m-d H:i:s'),
            'payment_method' => $paymentMethod,
            'collected_by' => $collectedBy,
            'recorded_by' => $recordedBy,
            'week_number' => $weekNumber,
            'principal_paid' => $breakdown['principal_paid'],
            'interest_paid' => $breakdown['interest_paid'],
            'insurance_paid' => $breakdown['insurance_paid'],
            'savings_paid' => $breakdown['savings_paid'],
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $paymentId = $this->paymentModel->create($data);

        if ($paymentId) {
            // Check if loan is now complete
            $totalPaid = $this->paymentModel->getTotalPaymentsForLoan($loanId);
            if ($totalPaid >= $loan['total_amount']) {
                $this->loanModel->completeLoan($loanId);
            }
        }

        return $paymentId;
    }

    public function getPaymentsByLoan($loanId) {
        return $this->paymentModel->getPaymentsByLoan($loanId);
    }

    public function getPaymentsByClient($clientId) {
        return $this->paymentModel->getPaymentsByClient($clientId);
    }

    public function getPaymentsByDateRange($startDate, $endDate) {
        return $this->paymentModel->getPaymentsByDateRange($startDate, $endDate);
    }

    public function getPaymentsByCollector($collectorId, $startDate = null, $endDate = null) {
        return $this->paymentModel->getPaymentsByCollector($collectorId, $startDate, $endDate);
    }

    public function getRecentPayments($limit = 10) {
        return $this->paymentModel->getRecentPayments($limit);
    }

    public function getOverduePayments() {
        return $this->paymentModel->getOverduePayments();
    }

    public function getPaymentSummary($startDate = null, $endDate = null) {
        return $this->paymentModel->getPaymentSummary($startDate, $endDate);
    }

    public function getPaymentStats() {
        return $this->paymentModel->getPaymentStats();
    }

    public function searchPayments($term) {
        return $this->paymentModel->searchPayments($term);
    }

    public function getPaymentWithDetails($paymentId) {
        return $this->paymentModel->getPaymentWithDetails($paymentId);
    }

    public function getNextPaymentWeek($loanId) {
        return $this->paymentModel->getNextPaymentWeek($loanId);
    }

    public function getTotalPaymentsForLoan($loanId) {
        return $this->paymentModel->getTotalPaymentsForLoan($loanId);
    }

    public function getPaymentsForWeek($loanId, $weekNumber) {
        return $this->paymentModel->getPaymentsForWeek($loanId, $weekNumber);
    }

    public function updatePayment($paymentId, $data) {
        return $this->paymentModel->update($paymentId, $data);
    }

    public function deletePayment($paymentId) {
        return $this->paymentModel->delete($paymentId);
    }

    public function getPaymentsByPaymentMethod($startDate = null, $endDate = null) {
        return $this->paymentModel->getPaymentsByPaymentMethod($startDate, $endDate);
    }

    public function getAllPaymentsWithDetails() {
        $sql = "SELECT p.*,
                l.loan_amount, l.total_amount, l.weekly_payment, l.status as loan_status,
                c.name as client_name, c.phone_number,
                u1.name as collected_by_name,
                u2.name as recorded_by_name
                FROM payments p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON p.client_id = c.id
                LEFT JOIN users u1 ON p.collected_by = u1.id
                LEFT JOIN users u2 ON p.recorded_by = u2.id
                ORDER BY p.payment_date DESC, p.created_at DESC";

        return $this->db->resultSet($sql);
    }

    public function getPaymentsForReports($startDate = null, $endDate = null, $clientId = null, $loanId = null) {
        $sql = "SELECT p.*,
                l.loan_amount, l.total_amount, l.weekly_payment, l.status as loan_status,
                c.name as client_name, c.phone_number,
                u1.name as collected_by_name,
                u2.name as recorded_by_name
                FROM payments p
                JOIN loans l ON p.loan_id = l.id
                JOIN clients c ON p.client_id = c.id
                LEFT JOIN users u1 ON p.collected_by = u1.id
                LEFT JOIN users u2 ON p.recorded_by = u2.id
                WHERE 1=1";

        $params = [];

        if ($startDate) {
            $sql .= " AND DATE(p.payment_date) >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND DATE(p.payment_date) <= ?";
            $params[] = $endDate;
        }

        if ($clientId) {
            $sql .= " AND p.client_id = ?";
            $params[] = $clientId;
        }

        if ($loanId) {
            $sql .= " AND p.loan_id = ?";
            $params[] = $loanId;
        }

        $sql .= " ORDER BY p.payment_date DESC";

        return $this->db->resultSet($sql, $params);
    }

    public function exportPaymentsToPDF($filters = []) {
        // Get payments based on filters
        $payments = $this->getPaymentsForReports(
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null,
            $filters['client_id'] ?? null,
            $filters['loan_id'] ?? null
        );

        if (!$payments) {
            $this->setErrorMessage('No payments found to export.');
            return false;
        }

        // Create PDF using FPDF
        require_once BASE_PATH . '/vendor/setasign/fpdf/fpdf.php';

        $pdf = new FPDF('P', 'mm', 'A4');

        // Set document information
        $pdf->SetCreator('Fanders Microfinance System');
        $pdf->SetAuthor('Fanders Microfinance System');
        $pdf->SetTitle('Payment Report');

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 10);

        // Add a page
        $pdf->AddPage();

        // Create the table
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Payment Report', 0, 1, 'C');
        $pdf->Ln(5);

        // Table header
        $pdf->SetFont('Arial', 'B', 10);
        $headers = ['ID', 'Client', 'Amount', 'Date', 'Week', 'Method', 'Collected By'];
        $widths = [15, 40, 25, 25, 15, 25, 35];

        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C');
        }
        $pdf->Ln();

        // Table data
        $pdf->SetFont('Arial', '', 9);
        foreach ($payments as $payment) {
            $pdf->Cell($widths[0], 6, $payment['id'], 1);
            $pdf->Cell($widths[1], 6, substr($payment['client_name'], 0, 20), 1);
            $pdf->Cell($widths[2], 6, '₱' . number_format($payment['payment_amount'], 2), 1);
            $pdf->Cell($widths[3], 6, date('M d, Y', strtotime($payment['payment_date'])), 1);
            $pdf->Cell($widths[4], 6, $payment['week_number'], 1);
            $pdf->Cell($widths[5], 6, ucfirst($payment['payment_method']), 1);
            $pdf->Cell($widths[6], 6, substr($payment['collected_by_name'] ?? 'N/A', 0, 15), 1);
            $pdf->Ln();
        }

        // Generate file path
        $filePath = BASE_PATH . '/storage/reports/payments_' . date('Y-m-d_His') . '.pdf';

        // Create directory if it doesn't exist
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Save PDF
        $pdf->Output($filePath, 'F');

        return $filePath;
    }
}
