<?php
/**
 * LoanReleaseService - Handles Summary of Loan Release (SLR) document generation
 * This service generates the official SLR document when loans are disbursed to clients
 * 
 * Purpose: FR-007, FR-008, UR-007 - Loan Release Documentation
 */

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/../models/LoanModel.php';
require_once __DIR__ . '/../models/ClientModel.php';
require_once __DIR__ . '/../utilities/PDFGenerator.php';
require_once __DIR__ . '/DocumentArchiveService.php';

class LoanReleaseService extends BaseService {
    private $loanModel;
    private $clientModel;
    private $documentArchive;

    public function __construct() {
        parent::__construct();
        $this->loanModel = new LoanModel();
        $this->clientModel = new ClientModel();
        $this->documentArchive = new DocumentArchiveService();
    }

    /**
     * Generate SLR (Summary of Loan Release) document for a disbursed loan
     * @param int $loanId
     * @return string|false PDF content or false on failure
     */
    public function generateSLRDocument($loanId) {
        // Get loan details
        $loan = $this->loanModel->getLoanWithClient($loanId);
        if (!$loan) {
            $this->setErrorMessage('Loan not found.');
            return false;
        }

        // Validate loan can have SLR generated
        if (!$this->canGenerateSLR($loan)) {
            return false;
        }

        // Generate PDF
        return $this->createSLRPDF($loan);
    }

    /**
     * Check if SLR can be generated for this loan
     * @param array $loan
     * @return bool
     */
    private function canGenerateSLR($loan) {
        // SLR is only for approved/active/completed loans (must be disbursed)
        $validStatuses = ['approved', 'active', 'completed'];
        
        if (!in_array(strtolower($loan['status']), $validStatuses)) {
            $this->setErrorMessage('SLR can only be generated for approved or disbursed loans.');
            return false;
        }

        return true;
    }

    /**
     * Create the SLR PDF document
     * @param array $loan Loan data with client information
     * @return string|false PDF content or false on failure
     */
    private function createSLRPDF($loan) {
        try {
            $pdf = new PDFGenerator();
            
            $pdf->setTitle('Summary of Loan Release - Loan #' . $loan['id']);
            $pdf->setAuthor('Fanders Microfinance Inc.');

            // Company Header
            $pdf->addHeaderRaw('FANDERS MICROFINANCE INC.');
            $pdf->addLine('Centro East, Santiago City, Isabela', true);
            $pdf->addSpace();
            
            $pdf->addSubHeader('SUMMARY OF LOAN RELEASE (SLR)');
            $pdf->addSpace();

            // Document Information
            $pdf->addLine('SLR Number: SLR-' . str_pad($loan['id'], 6, '0', STR_PAD_LEFT), false);
            $pdf->addLine('Date Issued: ' . date('F d, Y'), false);
            $pdf->addSpace();

            // Client Information
            $pdf->addSubHeader('BORROWER INFORMATION');
            $pdf->addLine('Client Name: ' . strtoupper($loan['client_name'] ?? $loan['name']), false);
            $pdf->addLine('Client ID: ' . str_pad($loan['client_id'], 6, '0', STR_PAD_LEFT), false);
            $pdf->addLine('Address: ' . ($loan['client_address'] ?? $loan['address'] ?? 'N/A'), false);
            $pdf->addLine('Contact Number: ' . ($loan['client_phone'] ?? $loan['phone_number'] ?? 'N/A'), false);
            $pdf->addSpace();

            // Loan Release Details
            $pdf->addSubHeader('LOAN RELEASE DETAILS');
            $pdf->addLine('Loan ID: ' . $loan['id'], false);
            $pdf->addLine('Application Date: ' . date('F d, Y', strtotime($loan['application_date'])), false);
            
            $disbursementDate = $loan['disbursement_date'] ?? $loan['start_date'] ?? date('Y-m-d');
            $pdf->addLine('Disbursement Date: ' . date('F d, Y', strtotime($disbursementDate)), false);
            
            $pdf->addLine('Loan Term: 17 weeks (4 months)', false);
            $pdf->addLine('Payment Frequency: Weekly', false);
            $pdf->addSpace();

            // Loan Breakdown
            $pdf->addSubHeader('LOAN AMOUNT BREAKDOWN');
            
            $principal = $loan['principal'];
            $interestRate = 0.05; // 5% monthly
            $totalInterest = $principal * $interestRate * 4; // 4 months
            $insuranceFee = 425.00; // Fixed insurance fee
            $totalLoanAmount = $loan['total_loan_amount'];
            $weeklyPayment = $totalLoanAmount / 17;

            $pdf->addLine('Principal Amount: ₱' . number_format($principal, 2), false);
            $pdf->addLine('Interest (5% monthly for 4 months): ₱' . number_format($totalInterest, 2), false);
            $pdf->addLine('Insurance Fee: ₱' . number_format($insuranceFee, 2), false);
            $pdf->addLine(str_repeat('-', 60), false);
            $pdf->addLine('TOTAL LOAN AMOUNT: ₱' . number_format($totalLoanAmount, 2), true);
            $pdf->addLine('Weekly Payment Amount: ₱' . number_format($weeklyPayment, 2), true);
            $pdf->addSpace();

            // Payment Schedule Summary
            $pdf->addSubHeader('PAYMENT SCHEDULE');
            $pdf->addLine('Number of Payments: 17 weekly payments', false);
            $pdf->addLine('Weekly Amount: ₱' . number_format($weeklyPayment, 2), false);
            $pdf->addLine('Expected Completion Date: ' . date('F d, Y', strtotime($disbursementDate . ' +17 weeks')), false);
            $pdf->addSpace();

            // Amount Released
            $pdf->addSubHeader('AMOUNT RELEASED TO BORROWER');
            $pdf->addLine('Cash Released: ₱' . number_format($principal, 2), true);
            $pdf->addLine('Release Method: Cash', false);
            $pdf->addLine('Released By: Cashier', false);
            $pdf->addSpace();
            $pdf->addSpace();

            // Signatures
            $pdf->addSubHeader('ACKNOWLEDGMENT');
            $pdf->addSpace();
            $pdf->addSpace();
            
            // Borrower signature
            $pdf->addLine('_______________________________', false);
            $pdf->addLine('Borrower Signature over Printed Name', false);
            $pdf->addLine('Date: ________________', false);
            $pdf->addSpace();

            // Cashier signature  
            $pdf->addLine('_______________________________', false);
            $pdf->addLine('Cashier Signature over Printed Name', false);
            $pdf->addLine('Date: ________________', false);
            $pdf->addSpace();

            // Witness signature
            $pdf->addLine('_______________________________', false);
            $pdf->addLine('Witness Signature over Printed Name', false);
            $pdf->addLine('Date: ________________', false);
            $pdf->addSpace();
            $pdf->addSpace();

            // Terms and Conditions
            $pdf->addSubHeader('TERMS AND CONDITIONS');
            $pdf->addLine('1. The borrower acknowledges receipt of the principal amount stated above.', false, 8);
            $pdf->addLine('2. The borrower agrees to make weekly payments as per the schedule.', false, 8);
            $pdf->addLine('3. Payments must be made on time to avoid penalties.', false, 8);
            $pdf->addLine('4. This document serves as official proof of loan disbursement.', false, 8);
            $pdf->addLine('5. The borrower has read and understood all terms of this loan agreement.', false, 8);
            $pdf->addSpace();

            // Footer
            $pdf->addLine(str_repeat('-', 80), false, 8);
            $pdf->addLine('This is a computer-generated document. Generated on ' . date('F d, Y h:i A'), true, 8);
            $pdf->addLine('Fanders Microfinance Inc. - Your Trusted Financial Partner', true, 8);

            return $pdf->output('S'); // Return as string
            
        } catch (Exception $e) {
            error_log('SLR PDF Generation Error: ' . $e->getMessage());
            $this->setErrorMessage('Failed to generate SLR PDF: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate and save SLR document to file system
     * @param int $loanId
     * @param string $outputPath Directory to save PDF (optional)
     * @param int $userId User ID generating the document
     * @return string|false File path or false on failure
     */
    public function generateAndSaveSLR($loanId, $outputPath = null, $userId = null) {
        $pdfContent = $this->generateSLRDocument($loanId);
        
        if ($pdfContent === false) {
            return false;
        }

        // Use archive service to generate storage path
        if ($outputPath === null) {
            $filepath = $this->documentArchive->generateStoragePath('SLR', $loanId);
        } else {
            // Create directory if it doesn't exist
            if (!is_dir($outputPath)) {
                mkdir($outputPath, 0755, true);
            }
            
            $filename = 'SLR_' . str_pad($loanId, 6, '0', STR_PAD_LEFT) . '_' . date('Ymd_His') . '.pdf';
            $filepath = $outputPath . $filename;
        }

        // Save PDF
        if (file_put_contents($filepath, $pdfContent) === false) {
            $this->setErrorMessage('Failed to save SLR document to file.');
            return false;
        }

        // Archive the document if user ID is provided
        if ($userId !== null) {
            $documentData = [
                'document_type' => 'SLR',
                'loan_id' => $loanId,
                'document_number' => 'SLR-' . str_pad($loanId, 6, '0', STR_PAD_LEFT),
                'file_name' => basename($filepath),
                'file_path' => $filepath,
                'generated_by' => $userId,
                'notes' => 'Auto-generated SLR document'
            ];
            
            $archiveId = $this->documentArchive->archiveDocument($documentData);
            if ($archiveId === false) {
                // Log error but don't fail the generation
                error_log('Failed to archive SLR document: ' . $this->documentArchive->getErrorMessage());
            }
        }

        return $filepath;
    }

    /**
     * Get SLR metadata for display
     * @param int $loanId
     * @return array|false
     */
    public function getSLRMetadata($loanId) {
        $loan = $this->loanModel->getLoanWithClient($loanId);
        
        if (!$loan) {
            return false;
        }

        return [
            'loan_id' => $loanId,
            'slr_number' => 'SLR-' . str_pad($loanId, 6, '0', STR_PAD_LEFT),
            'client_name' => $loan['client_name'] ?? $loan['name'],
            'principal_amount' => $loan['principal'],
            'total_loan_amount' => $loan['total_loan_amount'],
            'disbursement_date' => $loan['disbursement_date'] ?? $loan['start_date'],
            'status' => $loan['status'],
            'can_generate' => $this->canGenerateSLR($loan)
        ];
    }

    /**
     * Get list of all loans that can have SLR generated
     * @param array $filters
     * @return array
     */
    public function getEligibleLoansForSLR($filters = []) {
        // Get loans that are approved, active, or completed
        $validStatuses = ['approved', 'active', 'completed'];
        $filters['status'] = $validStatuses;

        return $this->loanModel->getAllLoansWithClients($filters);
    }

    /**
     * Generate SLR documents for multiple loans
     * @param array $loanIds Array of loan IDs
     * @param string $outputPath Directory to save PDFs (optional)
     * @param int $userId User ID generating the documents
     * @return array Results with success count and errors
     */
    public function generateBulkSLR($loanIds, $outputPath = null, $userId = null) {
        if (empty($loanIds)) {
            $this->setErrorMessage('No loan IDs provided for bulk generation.');
            return ['success' => false, 'count' => 0, 'errors' => 0, 'files' => []];
        }

        $successCount = 0;
        $errorCount = 0;
        $generatedFiles = [];
        $errors = [];

        foreach ($loanIds as $loanId) {
            try {
                $filePath = $this->generateAndSaveSLR($loanId, $outputPath, $userId);
                
                if ($filePath !== false) {
                    $successCount++;
                    $generatedFiles[] = [
                        'loan_id' => $loanId,
                        'file_path' => $filePath,
                        'filename' => basename($filePath)
                    ];
                } else {
                    $errorCount++;
                    $errors[] = "Loan ID {$loanId}: " . $this->getErrorMessage();
                }
            } catch (Exception $e) {
                $errorCount++;
                $errors[] = "Loan ID {$loanId}: " . $e->getMessage();
                error_log("Bulk SLR Generation Error for Loan {$loanId}: " . $e->getMessage());
            }
        }

        return [
            'success' => $successCount > 0,
            'count' => $successCount,
            'errors' => $errorCount,
            'files' => $generatedFiles,
            'error_details' => $errors
        ];
    }

    /**
     * Generate bulk SLR as ZIP file for download
     * @param array $loanIds Array of loan IDs
     * @param int $userId User ID generating the documents
     * @return string|false ZIP file path or false on failure
     */
    public function generateBulkSLRZip($loanIds, $userId = null) {
        if (empty($loanIds)) {
            $this->setErrorMessage('No loan IDs provided for bulk generation.');
            return false;
        }

        // Create temporary directory for PDFs
        $tempDir = sys_get_temp_dir() . '/slr_bulk_' . uniqid();
        if (!mkdir($tempDir, 0755, true)) {
            $this->setErrorMessage('Failed to create temporary directory.');
            return false;
        }

        // Generate PDFs
        $results = $this->generateBulkSLR($loanIds, $tempDir . '/', $userId);
        
        if ($results['count'] === 0) {
            $this->setErrorMessage('No SLR documents were generated successfully.');
            return false;
        }

        // Create ZIP file
        $zipPath = sys_get_temp_dir() . '/SLR_Bulk_' . date('Ymd_His') . '.zip';
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            $this->setErrorMessage('Failed to create ZIP file.');
            return false;
        }

        // Add PDFs to ZIP
        foreach ($results['files'] as $file) {
            $zip->addFile($file['file_path'], $file['filename']);
        }

        // Add summary file
        $summary = $this->createBulkSummary($results);
        $zip->addFromString('BULK_GENERATION_SUMMARY.txt', $summary);
        
        $zip->close();

        // Cleanup temporary files
        foreach ($results['files'] as $file) {
            unlink($file['file_path']);
        }
        rmdir($tempDir);

        return $zipPath;
    }

    /**
     * Create summary text for bulk generation
     * @param array $results Generation results
     * @return string Summary text
     */
    private function createBulkSummary($results) {
        $summary = "BULK SLR GENERATION SUMMARY\n";
        $summary .= str_repeat("=", 50) . "\n\n";
        $summary .= "Generation Date: " . date('F d, Y h:i A') . "\n";
        $summary .= "Total Requested: " . (count($results['files']) + $results['errors']) . "\n";
        $summary .= "Successfully Generated: " . $results['count'] . "\n";
        $summary .= "Failed: " . $results['errors'] . "\n\n";

        if (!empty($results['files'])) {
            $summary .= "GENERATED FILES:\n";
            $summary .= str_repeat("-", 30) . "\n";
            foreach ($results['files'] as $file) {
                $summary .= "Loan ID {$file['loan_id']}: {$file['filename']}\n";
            }
            $summary .= "\n";
        }

        if (!empty($results['error_details'])) {
            $summary .= "ERRORS:\n";
            $summary .= str_repeat("-", 30) . "\n";
            foreach ($results['error_details'] as $error) {
                $summary .= $error . "\n";
            }
        }

        $summary .= "\n" . str_repeat("=", 50) . "\n";
        $summary .= "Generated by Fanders Microfinance Inc.\n";

        return $summary;
    }
}
