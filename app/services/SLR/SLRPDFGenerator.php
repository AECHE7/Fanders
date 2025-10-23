<?php
/**
 * SLRPDFGenerator - Handles SLR PDF document generation
 */

namespace App\Services\SLR;

require_once __DIR__ . '/../../utilities/PDFGenerator.php';
require_once __DIR__ . '/../../constants/SLRConstants.php';
require_once __DIR__ . '/SLRResult.php';

use PDFGenerator;
use App\Constants\SLRConstants;

class SLRPDFGenerator {
    
    /**
     * Create SLR PDF content
     * 
     * @param array $loan Loan data with client info
     * @param object $calculationService LoanCalculationService instance
     * @return SLRResult
     */
    public function createSLRPDF(array $loan, $calculationService): SLRResult {
        try {
            $pdf = new PDFGenerator();
            
            $pdf->setTitle('Statement of Loan Receipt - Loan #' . $loan['id']);
            $pdf->setAuthor('Fanders Microfinance Inc.');

            // Company Header
            $this->addHeader($pdf);
            
            // Document Title and Number
            $this->addDocumentInfo($pdf, $loan);
            
            // Borrower Information
            $this->addBorrowerInfo($pdf, $loan);
            
            // Loan Receipt Details
            $this->addLoanDetails($pdf, $loan);
            
            // Amount Details
            $this->addAmountDetails($pdf, $loan);
            
            // Repayment Schedule
            $this->addRepaymentSchedule($pdf, $loan, $calculationService);
            
            // Acknowledgment and Signatures
            $this->addAcknowledgment($pdf);
            
            // Footer
            $this->addFooter($pdf);

            $pdfContent = $pdf->output();
            
            if (!$pdfContent) {
                return SLRResult::failure('PDF generation returned empty content', 'PDF_EMPTY');
            }
            
            return SLRResult::success($pdfContent);
            
        } catch (\Exception $e) {
            error_log("SLRPDFGenerator: Exception - " . $e->getMessage());
            return SLRResult::failure(
                'Failed to generate PDF: ' . $e->getMessage(),
                'PDF_GENERATION_ERROR'
            );
        }
    }
    
    /**
     * Add company header
     */
    private function addHeader($pdf): void {
        $pdf->setFillColor(240, 248, 255);
        $pdf->getPDF()->SetDrawColor(0, 123, 255);
        $pdf->setTextColor(33, 37, 41);

        $pdf->setFont('Arial', 'B', 20);
        $pdf->setFillColor(0, 123, 255);
        $pdf->setTextColor(255, 255, 255);
        $pdf->addCell(0, 15, 'FANDERS MICROFINANCE', 0, 1, 'C', true);
        $pdf->setTextColor(33, 37, 41);

        $pdf->setFont('Arial', 'I', 12);
        $pdf->addCell(0, 8, 'Empowering Communities Through Financial Inclusion', 0, 1, 'C');
        $pdf->addLn(5);
    }
    
    /**
     * Add document title and number
     */
    private function addDocumentInfo($pdf, $loan): void {
        $pdf->setFont('Arial', 'B', 18);
        $pdf->setFillColor(240, 248, 255);
        $pdf->addCell(0, 12, 'STATEMENT OF LOAN RECEIPT (SLR)', 1, 1, 'C', true);
        $pdf->addLn(3);

        $pdf->setFont('Arial', '', 10);
        $pdf->setFillColor(248, 249, 250);
        $documentNumber = $this->generateDocumentNumber($loan['id']);
        $pdf->addCell(95, 8, 'SLR Number: ' . $documentNumber, 1, 0, 'L', true);
        $pdf->addCell(95, 8, 'Date Issued: ' . date('F d, Y'), 1, 1, 'L', true);
        $pdf->addLn(2);
    }
    
    /**
     * Add borrower information section
     */
    private function addBorrowerInfo($pdf, $loan): void {
        $pdf->setFont('Arial', 'B', 14);
        $pdf->setFillColor(0, 123, 255);
        $pdf->setTextColor(255, 255, 255);
        $pdf->addCell(0, 10, 'BORROWER INFORMATION', 1, 1, 'L', true);
        $pdf->setTextColor(33, 37, 41);
        $pdf->setFont('Arial', '', 11);

        $pdf->addCell(40, 8, 'Full Name:', 1, 0, 'L');
        $pdf->addCell(0, 8, strtoupper($loan['client_name'] ?? $loan['name']), 1, 1, 'L');

        $pdf->addCell(40, 8, 'Client ID:', 1, 0, 'L');
        $pdf->addCell(0, 8, str_pad($loan['client_id'], 6, '0', STR_PAD_LEFT), 1, 1, 'L');

        $pdf->addCell(40, 8, 'Address:', 1, 0, 'L');
        $pdf->addCell(0, 8, $loan['client_address'] ?? $loan['address'] ?? 'N/A', 1, 1, 'L');

        $pdf->addCell(40, 8, 'Contact:', 1, 0, 'L');
        $pdf->addCell(0, 8, $loan['client_phone'] ?? $loan['phone_number'] ?? 'N/A', 1, 1, 'L');
        $pdf->addLn(3);
    }
    
    /**
     * Add loan receipt details
     */
    private function addLoanDetails($pdf, $loan): void {
        $pdf->setFont('Arial', 'B', 14);
        $pdf->setFillColor(0, 123, 255);
        $pdf->setTextColor(255, 255, 255);
        $pdf->addCell(0, 10, 'LOAN RECEIPT DETAILS', 1, 1, 'L', true);
        $pdf->setTextColor(33, 37, 41);
        $pdf->setFont('Arial', '', 11);

        $pdf->setFillColor(248, 249, 250);
        $pdf->addCell(70, 8, 'Loan ID:', 1, 0, 'L', true);
        $pdf->addCell(0, 8, '#' . $loan['id'], 1, 1, 'L');

        $pdf->addCell(70, 8, 'Application Date:', 1, 0, 'L');
        $pdf->addCell(0, 8, date('F d, Y', strtotime($loan['application_date'])), 1, 1, 'L');

        $disbursementDate = $loan['disbursement_date'] ?? $loan['approval_date'] ?? date('Y-m-d');
        $pdf->addCell(70, 8, 'Receipt Date:', 1, 0, 'L', true);
        $pdf->addCell(0, 8, date('F d, Y', strtotime($disbursementDate)), 1, 1, 'L');

        $termWeeks = $loan['term_weeks'] ?? SLRConstants::DEFAULT_TERM_WEEKS;
        $pdf->addCell(70, 8, 'Loan Term:', 1, 0, 'L');
        $pdf->addCell(0, 8, "$termWeeks weeks", 1, 1, 'L');

        $pdf->addCell(70, 8, 'Payment Frequency:', 1, 0, 'L', true);
        $pdf->addCell(0, 8, 'Weekly', 1, 1, 'L');
        $pdf->addLn(3);
    }
    
    /**
     * Add amount details section
     */
    private function addAmountDetails($pdf, $loan): void {
        $pdf->setFont('Arial', 'B', 14);
        $pdf->setFillColor(40, 167, 69);
        $pdf->setTextColor(255, 255, 255);
        $pdf->addCell(0, 10, 'LOAN AMOUNT RECEIVED', 1, 1, 'L', true);
        $pdf->setTextColor(33, 37, 41);
        $pdf->setFont('Arial', '', 11);

        $principal = $loan['principal'];
        $totalLoanAmount = $loan['total_loan_amount'];
        $termWeeks = $loan['term_weeks'] ?? SLRConstants::DEFAULT_TERM_WEEKS;
        $weeklyPayment = $totalLoanAmount / $termWeeks;

        // Highlighted principal amount
        $pdf->setFont('Arial', 'B', 14);
        $pdf->setFillColor(255, 193, 7);
        $pdf->setTextColor(0, 0, 0);
        $pdf->addCell(70, 12, 'PRINCIPAL RECEIVED:', 1, 0, 'L', true);
        $pdf->addCell(0, 12, '₱' . number_format($principal, 2), 1, 1, 'R');

        $pdf->setFont('Arial', '', 11);
        $pdf->setFillColor(248, 249, 250);
        $pdf->setTextColor(33, 37, 41);
        
        $pdf->addCell(70, 8, 'Total Repayment Amount:', 1, 0, 'L', true);
        $pdf->addCell(0, 8, '₱' . number_format($totalLoanAmount, 2), 1, 1, 'R');

        $pdf->addCell(70, 8, 'Weekly Payment Amount:', 1, 0, 'L');
        $pdf->addCell(0, 8, '₱' . number_format($weeklyPayment, 2), 1, 1, 'R');
        $pdf->addLn(3);
    }
    
    /**
     * Add repayment schedule
     */
    private function addRepaymentSchedule($pdf, $loan, $calculationService): void {
        $pdf->setFont('Arial', 'B', 14);
        $pdf->setFillColor(40, 167, 69);
        $pdf->setTextColor(255, 255, 255);
        $pdf->addCell(0, 10, 'REPAYMENT SCHEDULE', 1, 1, 'L', true);
        $pdf->setTextColor(33, 37, 41);
        $pdf->setFont('Arial', '', 10);

        $principal = $loan['principal'];
        $termWeeks = $loan['term_weeks'] ?? SLRConstants::DEFAULT_TERM_WEEKS;
        $totalLoanAmount = $loan['total_loan_amount'];
        $weeklyPayment = $totalLoanAmount / $termWeeks;
        $disbursementDate = $loan['disbursement_date'] ?? $loan['approval_date'] ?? date('Y-m-d');

        // Schedule summary
        $pdf->setFillColor(248, 249, 250);
        $pdf->addCell(70, 6, 'Number of Payments:', 1, 0, 'L', true);
        $pdf->addCell(0, 6, "$termWeeks weekly payments", 1, 1, 'L');

        $pdf->addCell(70, 6, 'Weekly Amount:', 1, 0, 'L');
        $pdf->addCell(0, 6, '₱' . number_format($weeklyPayment, 2), 1, 1, 'L');

        $completionDate = date('F d, Y', strtotime($disbursementDate . ' +' . $termWeeks . ' weeks'));
        $pdf->addCell(70, 6, 'Expected Completion:', 1, 0, 'L', true);
        $pdf->addCell(0, 6, $completionDate, 1, 1, 'L');
        $pdf->addLn(3);

        // Generate detailed payment schedule
        $loanCalculation = $calculationService->calculateLoan($principal, $termWeeks);
        
        if ($loanCalculation && isset($loanCalculation['payment_schedule'])) {
            // Payment schedule table header
            $pdf->setFont('Arial', 'B', 9);
            $pdf->setFillColor(40, 167, 69);
            $pdf->setTextColor(255, 255, 255);
            
            $pdf->addCell(15, 8, 'Week', 1, 0, 'C', true);
            $pdf->addCell(25, 8, 'Due Date', 1, 0, 'C', true);
            $pdf->addCell(30, 8, 'Payment', 1, 0, 'C', true);
            $pdf->addCell(25, 8, 'Principal', 1, 0, 'C', true);
            $pdf->addCell(25, 8, 'Interest', 1, 0, 'C', true);
            $pdf->addCell(25, 8, 'Insurance', 1, 0, 'C', true);
            $pdf->addCell(30, 8, 'Balance', 1, 1, 'C', true);
            
            // Payment schedule data
            $pdf->setFont('Arial', '', 8);
            $pdf->setTextColor(33, 37, 41);
            $runningBalance = $totalLoanAmount;
            
            foreach ($loanCalculation['payment_schedule'] as $payment) {
                $dueDate = date('M d', strtotime($disbursementDate . ' +' . ($payment['week'] - 1) . ' weeks'));
                $runningBalance -= $payment['expected_payment'];
                
                // Alternate row colors
                $fillColor = ($payment['week'] % 2 == 0) ? [248, 249, 250] : [255, 255, 255];
                $pdf->setFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
                
                $pdf->addCell(15, 6, $payment['week'], 1, 0, 'C', true);
                $pdf->addCell(25, 6, $dueDate, 1, 0, 'C', true);
                $pdf->addCell(30, 6, '₱' . number_format($payment['expected_payment'], 2), 1, 0, 'R', true);
                $pdf->addCell(25, 6, '₱' . number_format($payment['principal_payment'], 2), 1, 0, 'R', true);
                $pdf->addCell(25, 6, '₱' . number_format($payment['interest_payment'], 2), 1, 0, 'R', true);
                $pdf->addCell(25, 6, '₱' . number_format($payment['insurance_payment'], 2), 1, 0, 'R', true);
                $pdf->addCell(30, 6, '₱' . number_format(max(0, $runningBalance), 2), 1, 1, 'R', true);
            }
            
            // Payment instructions
            $pdf->addLn(2);
            $pdf->setFont('Arial', 'I', 9);
            $pdf->setFillColor(255, 248, 220);
            $pdf->addCell(0, 6, 'NOTE: Payments are due every week starting from disbursement date. Please keep this schedule for reference.', 1, 1, 'L', true);
        }
        $pdf->addLn(3);
    }
    
    /**
     * Add acknowledgment and signature section
     */
    private function addAcknowledgment($pdf): void {
        $pdf->setFont('Arial', 'B', 14);
        $pdf->setFillColor(108, 117, 125);
        $pdf->setTextColor(255, 255, 255);
        $pdf->addCell(0, 10, 'BORROWER ACKNOWLEDGMENT', 1, 1, 'L', true);
        $pdf->setTextColor(33, 37, 41);
        $pdf->setFont('Arial', '', 10);

        $pdf->addLn(2);
        $pdf->addCell(0, 6, 'I acknowledge receipt of the loan amount stated above and agree to the', 0, 1, 'L');
        $pdf->addCell(0, 6, 'repayment terms as outlined in the loan agreement.', 0, 1, 'L');
        $pdf->addLn(10);

        // Signature Section
        $pdf->setFont('Arial', '', 10);
        $pdf->addCell(90, 6, '__________________________________', 0, 0, 'C');
        $pdf->addCell(10, 6, '', 0, 0, 'C');
        $pdf->addCell(90, 6, 'Date: __________________', 0, 1, 'C');
        
        $pdf->setFont('Arial', 'B', 10);
        $pdf->addCell(90, 6, 'Borrower Signature', 0, 0, 'C');
        $pdf->addCell(10, 6, '', 0, 0, 'C');
        $pdf->addCell(90, 6, '', 0, 1, 'C');
        $pdf->addLn(8);

        $pdf->setFont('Arial', '', 10);
        $pdf->addCell(90, 6, '__________________________________', 0, 0, 'C');
        $pdf->addCell(10, 6, '', 0, 0, 'C');
        $pdf->addCell(90, 6, 'Date: __________________', 0, 1, 'C');
        
        $pdf->setFont('Arial', 'B', 10);
        $pdf->addCell(90, 6, 'Loan Officer Signature', 0, 0, 'C');
        $pdf->addCell(10, 6, '', 0, 0, 'C');
        $pdf->addCell(90, 6, '', 0, 1, 'C');
        $pdf->addLn(10);
    }
    
    /**
     * Add footer
     */
    private function addFooter($pdf): void {
        $pdf->getPDF()->SetDrawColor(0, 123, 255);
        $pdf->getPDF()->Line(10, $pdf->getY(), 200, $pdf->getY());
        $pdf->addLn(3);
        
        $pdf->setFont('Arial', 'I', 9);
        $pdf->setTextColor(108, 117, 125);
        $pdf->addCell(0, 5, 'This document serves as official receipt of loan disbursement.', 0, 1, 'C');
        $pdf->addCell(0, 5, 'Generated on: ' . date('F d, Y g:i A'), 0, 1, 'C');
        $pdf->addCell(0, 5, 'For inquiries, contact Fanders Microfinance Inc. - Centro East, Santiago City, Isabela', 0, 1, 'C');
    }
    
    /**
     * Generate unique document number
     */
    private function generateDocumentNumber(int $loanId): string {
        $year = date('Y');
        $month = date('m');
        $loanPadded = str_pad($loanId, 6, '0', STR_PAD_LEFT);
        
        return "SLR-{$year}{$month}-{$loanPadded}";
    }
}
