<?php
/**
 * SLRDocumentService - Handles SLR (Statement of Loan Repayment) document generation for Fanders Microfinance.
 * This service implements Phase 2 SLR document generation functionality.
 */
require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/../models/LoanModel.php';
require_once __DIR__ . '/../models/PaymentModel.php';
require_once __DIR__ . '/../utilities/PDFGenerator.php';

class SLRDocumentService extends BaseService {
    private $loanModel;
    private $paymentModel;
    private $pdfGenerator;

    public function __construct() {
        parent::__construct();
        $this->loanModel = new LoanModel();
        $this->paymentModel = new PaymentModel();
        $this->pdfGenerator = new PDFGenerator();
    }

    /**
     * Generate SLR document for a specific loan.
     * @param int $loanId
     * @return string PDF content or false on failure
     */
    public function generateSLRDocument($loanId) {
        $loan = $this->loanModel->findById($loanId);
        if (!$loan) {
            $this->setErrorMessage('Loan not found.');
            return false;
        }

        // Get client information
        $client = $this->loanModel->getClientByLoanId($loanId);
        if (!$client) {
            $this->setErrorMessage('Client information not found.');
            return false;
        }

        // Get payment history
        $payments = $this->paymentModel->getPaymentsByLoan($loanId);

        // Calculate totals
        $totalPaid = $this->paymentModel->getTotalPaymentsForLoan($loanId);
        $remainingBalance = $loan['total_loan_amount'] - $totalPaid;

        // Generate PDF
        return $this->createSLRPDF($loan, $client, $payments, $totalPaid, $remainingBalance);
    }

    /**
     * Create the SLR PDF document.
     * @param array $loan
     * @param array $client
     * @param array $payments
     * @param float $totalPaid
     * @param float $remainingBalance
     * @return string PDF content
     */
    private function createSLRPDF($loan, $client, $payments, $totalPaid, $remainingBalance) {
        // Create a fresh PDF instance for each document to avoid "document closed" errors
        $pdf = new PDFGenerator();
        
        $pdf->setTitle('Statement of Loan Repayment - Loan #' . $loan['id']);
        $pdf->setAuthor('Fanders Microfinance');

        // Header
        $pdf->addHeaderRaw('FANDERS MICROFINANCE');
        $pdf->addSubHeader('STATEMENT OF LOAN REPAYMENT (SLR)');
        $pdf->addSpace();

        // Loan and Client Information
        $pdf->addSubHeader('Loan Information');
        $pdf->addLine('Loan ID: ' . $loan['id']);
        $pdf->addLine('Client Name: ' . $client['name']);
        $pdf->addLine('Client ID: ' . $client['id']);
        $pdf->addLine('Loan Amount: ₱' . number_format($loan['principal'], 2));
        $pdf->addLine('Total Loan Amount (with interest): ₱' . number_format($loan['total_loan_amount'], 2));
        $pdf->addLine('Weekly Payment: ₱' . number_format($loan['total_loan_amount'] / 17, 2));
        $pdf->addLine('Term: 17 weeks (4 months)');
        $pdf->addLine('Application Date: ' . date('F d, Y', strtotime($loan['application_date'])));
        $pdf->addLine('Disbursement Date: ' . ($loan['disbursement_date'] ? date('F d, Y', strtotime($loan['disbursement_date'])) : 'Pending'));
        $pdf->addLine('Status: ' . ucfirst($loan['status']));
        $pdf->addSpace();

        // Payment Summary
        $pdf->addSubHeader('Payment Summary');
        $pdf->addLine('Total Amount Paid: ₱' . number_format($totalPaid, 2));
        $pdf->addLine('Remaining Balance: ₱' . number_format($remainingBalance, 2));
        $pdf->addLine('Payments Made: ' . count($payments) . ' out of 17');
        $pdf->addSpace();

        // Payment History Table
        if (!empty($payments)) {
            $pdf->addSubHeader('Payment History');

            $columns = [
                ['header' => 'Payment #', 'width' => 25],
                ['header' => 'Amount', 'width' => 30],
                ['header' => 'Date', 'width' => 35],
                ['header' => 'Recorded By', 'width' => 50],
                ['header' => 'Notes', 'width' => 50]
            ];

            $data = [];
            foreach ($payments as $index => $payment) {
                $data[] = [
                    $index + 1,
                    '₱' . number_format($payment['amount'], 2),
                    date('M d, Y', strtotime($payment['payment_date'])),
                    $payment['recorded_by_name'] ?? 'N/A',
                    substr($payment['notes'] ?? '', 0, 30) . (strlen($payment['notes'] ?? '') > 30 ? '...' : '')
                ];
            }

            $pdf->addTable($columns, $data);
            $pdf->addSpace();
        }

        // Loan Breakdown
        $pdf->addSubHeader('Loan Breakdown');
        $interest = $loan['principal'] * 0.05 * 4; // 5% monthly for 4 months
        $insurance = 425.00;

        $pdf->addLine('Principal Amount: ₱' . number_format($loan['principal'], 2));
        $pdf->addLine('Interest (5% over 4 months): ₱' . number_format($interest, 2));
        $pdf->addLine('Insurance Fee: ₱' . number_format($insurance, 2));
        $pdf->addLine('Total Amount: ₱' . number_format($loan['total_loan_amount'], 2));
        $pdf->addSpace();

        // Footer
        $pdf->addLine('This document serves as an official statement of your loan repayment status.');
        $pdf->addLine('Generated on: ' . date('F d, Y H:i:s'));
        $pdf->addLine('Fanders Microfinance - Your Trusted Financial Partner');

        return $pdf->output('S'); // Return as string
    }

    /**
     * Generate bulk SLR documents for multiple loans.
     * @param array $loanIds Array of loan IDs
     * @return array Array of PDF contents keyed by loan ID
     */
    public function generateBulkSLRDocuments($loanIds) {
        $documents = [];

        foreach ($loanIds as $loanId) {
            $pdfContent = $this->generateSLRDocument($loanId);
            if ($pdfContent !== false) {
                $documents[$loanId] = $pdfContent;
            }
        }

        return $documents;
    }

    /**
     * Generate SLR for all active loans of a client.
     * @param int $clientId
     * @return array Array of PDF contents keyed by loan ID
     */
    public function generateClientSLRDocuments($clientId) {
        $loans = $this->loanModel->getLoansByClient($clientId);
        $loanIds = array_column($loans, 'id');

        return $this->generateBulkSLRDocuments($loanIds);
    }

    /**
     * Get SLR document metadata for a loan.
     * @param int $loanId
     * @return array|false
     */
    public function getSLRMetadata($loanId) {
        $loan = $this->loanModel->findById($loanId);
        if (!$loan) {
            return false;
        }

        $payments = $this->paymentModel->getPaymentsByLoan($loanId);
        $totalPaid = $this->paymentModel->getTotalPaymentsForLoan($loanId);
        $remainingBalance = $loan['total_loan_amount'] - $totalPaid;

        return [
            'loan_id' => $loanId,
            'client_name' => $loan['client_name'] ?? 'N/A',
            'loan_amount' => $loan['principal'],
            'total_loan_amount' => $loan['total_loan_amount'],
            'payments_made' => count($payments),
            'total_paid' => $totalPaid,
            'remaining_balance' => $remainingBalance,
            'loan_status' => $loan['status'],
            'generated_date' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Validate if SLR can be generated for a loan.
     * @param int $loanId
     * @return bool
     */
    public function canGenerateSLR($loanId) {
        $loan = $this->loanModel->findById($loanId);
        if (!$loan) {
            $this->setErrorMessage('Loan not found.');
            return false;
        }

        // SLR can be generated for loans that have been disbursed or have payments
        if ($loan['status'] === LoanModel::STATUS_APPLICATION) {
            $this->setErrorMessage('SLR cannot be generated for loan applications that have not been disbursed.');
            return false;
        }

        return true;
    }
}
