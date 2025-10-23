<?php
/**
 * Enhanced SLR Service - Statement of Loan Receipt Management
 * 
 * This service handles the complete SLR lifecycle:
 * 1. Generation (manual/automatic)
 * 2. Storage and archiving
 * 3. Access control and logging
 * 4. Document integrity
 */

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/../models/LoanModel.php';
require_once __DIR__ . '/../models/ClientModel.php';
require_once __DIR__ . '/../utilities/PDFGenerator.php';
require_once __DIR__ . '/LoanCalculationService.php';

class SLRService extends BaseService {
    private $loanModel;
    private $clientModel;
    
    // Storage paths
    private $storageDir;
    private $slrDir;
    private $archiveDir;
    private $tempDir;

    public function __construct() {
        parent::__construct();
        $this->loanModel = new LoanModel();
        $this->clientModel = new ClientModel();
        
        // Initialize storage paths
        $this->storageDir = BASE_PATH . '/storage';
        $this->slrDir = $this->storageDir . '/slr';
        $this->archiveDir = $this->slrDir . '/archive';
        $this->tempDir = $this->slrDir . '/temp';
        
        $this->ensureStorageDirectories();
    }

    /**
     * Generate SLR document for a loan
     * @param int $loanId
     * @param int $generatedBy User ID
     * @param string $trigger Generation trigger (manual, auto_approval, auto_disbursement)
     * @return array|false SLR document record or false on failure
     */
    public function generateSLR($loanId, $generatedBy, $trigger = 'manual') {
        return $this->transaction(function() use ($loanId, $generatedBy, $trigger) {
            // Get loan details
            $loan = $this->loanModel->getLoanWithClient($loanId);
            if (!$loan) {
                $this->setErrorMessage('Loan not found.');
                return false;
            }

            // Validate generation eligibility
            if (!$this->canGenerateSLR($loan, $trigger)) {
                return false;
            }

            // Check if SLR already exists
            $existing = $this->getSLRByLoanId($loanId);
            if ($existing && $existing['status'] === 'active') {
                $this->setErrorMessage('Active SLR already exists for this loan. Archive the existing SLR first.');
                return false;
            }

            // Generate document number
            $documentNumber = $this->generateDocumentNumber($loanId);

            // Create PDF content
            $pdfContent = $this->createSLRPDF($loan);
            if (!$pdfContent) {
                return false;
            }

            // Save PDF file
            $fileName = "SLR_{$documentNumber}_" . date('Ymd') . '.pdf';
            $filePath = $this->slrDir . '/' . $fileName;
            
            if (!file_put_contents($filePath, $pdfContent)) {
                $this->setErrorMessage('Failed to save SLR document.');
                return false;
            }

            // Calculate file hash for integrity
            $contentHash = hash('sha256', $pdfContent);

            // Store in database
            $slrData = [
                'loan_id' => $loanId,
                'document_number' => $documentNumber,
                'generated_by' => $generatedBy,
                'generation_trigger' => $trigger,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => strlen($pdfContent),
                'content_hash' => $contentHash,
                'client_signature_required' => $this->requiresSignature($trigger),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $slrId = $this->createSLRRecord($slrData);
            if (!$slrId) {
                // Clean up file if database insert fails
                unlink($filePath);
                return false;
            }

            // Log the generation
            $this->logSLRAccess($slrId, 'generation', $generatedBy, "SLR generated via {$trigger}");

            // Get the created record
            return $this->getSLRById($slrId);
        });
    }

    /**
     * Download SLR document
     * @param int $slrId
     * @param int $userId
     * @param string $reason
     * @return array|false File info or false on failure
     */
    public function downloadSLR($slrId, $userId, $reason = '') {
        $slr = $this->getSLRById($slrId);
        if (!$slr) {
            $this->setErrorMessage('SLR document not found.');
            return false;
        }

        if ($slr['status'] !== 'active') {
            $this->setErrorMessage('SLR document is not active.');
            return false;
        }

        if (!file_exists($slr['file_path'])) {
            $this->setErrorMessage('SLR file not found on disk.');
            return false;
        }

        // Verify file integrity
        $fileContent = file_get_contents($slr['file_path']);
        $fileHash = hash('sha256', $fileContent);
        
        if ($slr['content_hash'] && $fileHash !== $slr['content_hash']) {
            $this->setErrorMessage('SLR file integrity check failed.');
            return false;
        }

        // Update download statistics
        $this->updateDownloadStats($slrId, $userId);

        // Log the access
        $this->logSLRAccess($slrId, 'download', $userId, $reason);

        return [
            'file_path' => $slr['file_path'],
            'file_name' => $slr['file_name'],
            'file_size' => $slr['file_size'],
            'content_type' => 'application/pdf'
        ];
    }

    /**
     * Get SLR by loan ID
     * @param int $loanId
     * @return array|null
     */
    public function getSLRByLoanId($loanId) {
        $sql = "SELECT s.*, l.client_id, l.principal, l.total_loan_amount,
                       c.name as client_name, u.name as generated_by_name
                FROM slr_documents s
                JOIN loans l ON s.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                JOIN users u ON s.generated_by = u.id
                WHERE s.loan_id = ? AND s.status = 'active'
                ORDER BY s.generated_at DESC
                LIMIT 1";
        
        return $this->db->single($sql, [$loanId]);
    }

    /**
     * Get SLR by ID
     * @param int $slrId
     * @return array|null
     */
    public function getSLRById($slrId) {
        $sql = "SELECT s.*, l.client_id, l.principal, l.total_loan_amount,
                       c.name as client_name, u.name as generated_by_name
                FROM slr_documents s
                JOIN loans l ON s.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                JOIN users u ON s.generated_by = u.id
                WHERE s.id = ?";
        
        return $this->db->single($sql, [$slrId]);
    }

    /**
     * List all SLR documents with filters
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function listSLRDocuments($filters = [], $limit = 20, $offset = 0) {
        $sql = "SELECT s.*, l.client_id, l.principal, l.total_loan_amount,
                       c.name as client_name, u.name as generated_by_name
                FROM slr_documents s
                JOIN loans l ON s.loan_id = l.id
                JOIN clients c ON l.client_id = c.id
                JOIN users u ON s.generated_by = u.id";
        
        $conditions = [];
        $params = [];
        
        if (!empty($filters['loan_id'])) {
            $conditions[] = 's.loan_id = ?';
            $params[] = $filters['loan_id'];
        }
        
        if (!empty($filters['status'])) {
            $conditions[] = 's.status = ?';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['client_id'])) {
            $conditions[] = 'l.client_id = ?';
            $params[] = $filters['client_id'];
        }
        
        if (!empty($filters['generated_by'])) {
            $conditions[] = 's.generated_by = ?';
            $params[] = $filters['generated_by'];
        }
        
        if (!empty($filters['date_from'])) {
            $conditions[] = 's.generated_at >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = 's.generated_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        
        $sql .= ' ORDER BY s.generated_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->resultSet($sql, $params);
    }

    /**
     * Archive an SLR document
     * @param int $slrId
     * @param int $userId
     * @param string $reason
     * @return bool
     */
    public function archiveSLR($slrId, $userId, $reason = '') {
        return $this->transaction(function() use ($slrId, $userId, $reason) {
            $slr = $this->getSLRById($slrId);
            if (!$slr) {
                $this->setErrorMessage('SLR document not found.');
                return false;
            }

            // Move file to archive directory
            $archivePath = $this->archiveDir . '/' . $slr['file_name'];
            if (file_exists($slr['file_path'])) {
                if (!rename($slr['file_path'], $archivePath)) {
                    $this->setErrorMessage('Failed to move SLR file to archive.');
                    return false;
                }
            }

            // Update database record
            $sql = "UPDATE slr_documents 
                    SET status = 'archived', 
                        file_path = ?, 
                        replacement_reason = ?, 
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            if (!$this->db->query($sql, [$archivePath, $reason, $slrId])) {
                // Rollback file move
                if (file_exists($archivePath)) {
                    rename($archivePath, $slr['file_path']);
                }
                $this->setErrorMessage('Failed to update SLR record.');
                return false;
            }

            // Log the archival
            $this->logSLRAccess($slrId, 'archive', $userId, $reason);

            return true;
        });
    }

    /**
     * Check if SLR can be generated for a loan
     * @param array $loan
     * @param string $trigger
     * @return bool
     */
    private function canGenerateSLR($loan, $trigger) {
        // Check loan status
        $validStatuses = ['approved', 'active', 'completed'];
        
        if (!in_array(strtolower($loan['status']), $validStatuses)) {
            $this->setErrorMessage('SLR can only be generated for approved, active, or completed loans.');
            return false;
        }

        // Check generation rules
        $rule = $this->getGenerationRule($trigger);
        if (!$rule || !$rule['is_active']) {
            $this->setErrorMessage('SLR generation not allowed for this trigger.');
            return false;
        }

        // Check principal amount limits if specified
        if ($rule['min_principal_amount'] && $loan['principal'] < $rule['min_principal_amount']) {
            $this->setErrorMessage('Loan principal is below minimum amount for SLR generation.');
            return false;
        }

        if ($rule['max_principal_amount'] && $loan['principal'] > $rule['max_principal_amount']) {
            $this->setErrorMessage('Loan principal exceeds maximum amount for SLR generation.');
            return false;
        }

        return true;
    }

    /**
     * Create SLR PDF content
     * @param array $loan
     * @return string|false
     */
    private function createSLRPDF($loan) {
        try {
            $pdf = new PDFGenerator();
            
            $pdf->setTitle('Statement of Loan Receipt - Loan #' . $loan['id']);
            $pdf->setAuthor('Fanders Microfinance Inc.');

            // Set up professional styling matching loan agreements
            $pdf->setFillColor(240, 248, 255); // Light blue background for headers
            $pdf->getPDF()->SetDrawColor(0, 123, 255); // Blue border color
            $pdf->setTextColor(33, 37, 41); // Dark text

            // Company Header with Professional Styling
            $pdf->setFont('Arial', 'B', 20);
            $pdf->setFillColor(0, 123, 255);
            $pdf->setTextColor(255, 255, 255);
            $pdf->addCell(0, 15, 'FANDERS MICROFINANCE', 0, 1, 'C', true);
            $pdf->setTextColor(33, 37, 41);

            // Subtitle
            $pdf->setFont('Arial', 'I', 12);
            $pdf->addCell(0, 8, 'Empowering Communities Through Financial Inclusion', 0, 1, 'C');
            $pdf->addLn(5);

            // Document Title
            $pdf->setFont('Arial', 'B', 18);
            $pdf->setFillColor(240, 248, 255);
            $pdf->addCell(0, 12, 'STATEMENT OF LOAN RECEIPT (SLR)', 1, 1, 'C', true);
            $pdf->addLn(3);

            // Document Information Box
            $pdf->setFont('Arial', '', 10);
            $pdf->setFillColor(248, 249, 250);
            $documentNumber = $this->generateDocumentNumber($loan['id']);
            $pdf->addCell(95, 8, 'SLR Number: ' . $documentNumber, 1, 0, 'L', true);
            $pdf->addCell(95, 8, 'Date Issued: ' . date('F d, Y'), 1, 1, 'L', true);
            $pdf->addLn(2);

            // Borrower Information Section
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

            // Loan Receipt Details Section
            $pdf->setFont('Arial', 'B', 14);
            $pdf->setFillColor(0, 123, 255);
            $pdf->setTextColor(255, 255, 255);
            $pdf->addCell(0, 10, 'LOAN RECEIPT DETAILS', 1, 1, 'L', true);
            $pdf->setTextColor(33, 37, 41);
            $pdf->setFont('Arial', '', 11);

            // Create detailed loan information table
            $pdf->setFillColor(248, 249, 250);
            $pdf->addCell(70, 8, 'Loan ID:', 1, 0, 'L', true);
            $pdf->addCell(0, 8, '#' . $loan['id'], 1, 1, 'L');

            $pdf->addCell(70, 8, 'Application Date:', 1, 0, 'L');
            $pdf->addCell(0, 8, date('F d, Y', strtotime($loan['application_date'])), 1, 1, 'L');

            $disbursementDate = $loan['disbursement_date'] ?? $loan['approval_date'] ?? date('Y-m-d');
            $pdf->addCell(70, 8, 'Receipt Date:', 1, 0, 'L', true);
            $pdf->addCell(0, 8, date('F d, Y', strtotime($disbursementDate)), 1, 1, 'L');

            $pdf->addCell(70, 8, 'Loan Term:', 1, 0, 'L');
            $pdf->addCell(0, 8, '17 weeks (4 months)', 1, 1, 'L');

            $pdf->addCell(70, 8, 'Payment Frequency:', 1, 0, 'L', true);
            $pdf->addCell(0, 8, 'Weekly', 1, 1, 'L');
            $pdf->addLn(3);

            // Amount Details Section
            $pdf->setFont('Arial', 'B', 14);
            $pdf->setFillColor(40, 167, 69);
            $pdf->setTextColor(255, 255, 255);
            $pdf->addCell(0, 10, 'LOAN AMOUNT RECEIVED', 1, 1, 'L', true);
            $pdf->setTextColor(33, 37, 41);
            $pdf->setFont('Arial', '', 11);

            $principal = $loan['principal'];
            $totalLoanAmount = $loan['total_loan_amount'];
            $weeklyPayment = $totalLoanAmount / 17;

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

            // Repayment Schedule Section
            $pdf->setFont('Arial', 'B', 14);
            $pdf->setFillColor(40, 167, 69);
            $pdf->setTextColor(255, 255, 255);
            $pdf->addCell(0, 10, 'REPAYMENT SCHEDULE', 1, 1, 'L', true);
            $pdf->setTextColor(33, 37, 41);
            $pdf->setFont('Arial', '', 10);

            // Schedule summary
            $pdf->setFillColor(248, 249, 250);
            $pdf->addCell(70, 6, 'Number of Payments:', 1, 0, 'L', true);
            $pdf->addCell(0, 6, '17 weekly payments', 1, 1, 'L');

            $pdf->addCell(70, 6, 'Weekly Amount:', 1, 0, 'L');
            $pdf->addCell(0, 6, '₱' . number_format($weeklyPayment, 2), 1, 1, 'L');

            $completionDate = date('F d, Y', strtotime($disbursementDate . ' +17 weeks'));
            $pdf->addCell(70, 6, 'Expected Completion:', 1, 0, 'L', true);
            $pdf->addCell(0, 6, $completionDate, 1, 1, 'L');
            $pdf->addLn(3);

            // Generate detailed payment schedule
            $loanCalculationService = new LoanCalculationService();
            $loanCalculation = $loanCalculationService->calculateLoan($principal, 17);
            
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

            // Acknowledgment Section
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
            $pdf->addCell(10, 6, '', 0, 0, 'C'); // Spacer
            $pdf->addCell(90, 6, 'Date: __________________', 0, 1, 'C');
            
            $pdf->setFont('Arial', 'B', 10);
            $pdf->addCell(90, 6, 'Borrower Signature', 0, 0, 'C');
            $pdf->addCell(10, 6, '', 0, 0, 'C'); // Spacer
            $pdf->addCell(90, 6, '', 0, 1, 'C');
            $pdf->addLn(8);

            $pdf->setFont('Arial', '', 10);
            $pdf->addCell(90, 6, '__________________________________', 0, 0, 'C');
            $pdf->addCell(10, 6, '', 0, 0, 'C'); // Spacer
            $pdf->addCell(90, 6, 'Date: __________________', 0, 1, 'C');
            
            $pdf->setFont('Arial', 'B', 10);
            $pdf->addCell(90, 6, 'Loan Officer Signature', 0, 0, 'C');
            $pdf->addCell(10, 6, '', 0, 0, 'C'); // Spacer
            $pdf->addCell(90, 6, '', 0, 1, 'C');
            $pdf->addLn(10);

            // Footer Section
            $pdf->getPDF()->SetDrawColor(0, 123, 255);
            $pdf->getPDF()->Line(10, $pdf->getY(), 200, $pdf->getY()); // Horizontal line
            $pdf->addLn(3);
            
            $pdf->setFont('Arial', 'I', 9);
            $pdf->setTextColor(108, 117, 125);
            $pdf->addCell(0, 5, 'This document serves as official receipt of loan disbursement.', 0, 1, 'C');
            $pdf->addCell(0, 5, 'Generated on: ' . date('F d, Y g:i A'), 0, 1, 'C');
            $pdf->addCell(0, 5, 'For inquiries, contact Fanders Microfinance Inc. - Centro East, Santiago City, Isabela', 0, 1, 'C');

            return $pdf->output();
            
        } catch (Exception $e) {
            $this->setErrorMessage('Failed to generate PDF: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate unique document number
     * @param int $loanId
     * @return string
     */
    private function generateDocumentNumber($loanId) {
        $year = date('Y');
        $month = date('m');
        $loanPadded = str_pad($loanId, 6, '0', STR_PAD_LEFT);
        
        return "SLR-{$year}{$month}-{$loanPadded}";
    }

    /**
     * Create SLR database record
     * @param array $data
     * @return int|false
     */
    private function createSLRRecord($data) {
        $sql = "INSERT INTO slr_documents (
                    loan_id, document_number, generated_by, generation_trigger,
                    file_path, file_name, file_size, content_hash,
                    client_signature_required, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['loan_id'],
            $data['document_number'],
            $data['generated_by'],
            $data['generation_trigger'],
            $data['file_path'],
            $data['file_name'],
            $data['file_size'],
            $data['content_hash'],
            $data['client_signature_required'] ? 1 : 0,
            $data['created_at'],
            $data['updated_at']
        ];
        
        if ($this->db->query($sql, $params)) {
            return $this->db->lastInsertId();
        }
        
        $this->setErrorMessage('Failed to create SLR record.');
        return false;
    }

    /**
     * Update download statistics
     * @param int $slrId
     * @param int $userId
     */
    private function updateDownloadStats($slrId, $userId) {
        $sql = "UPDATE slr_documents 
                SET download_count = download_count + 1,
                    last_downloaded_at = CURRENT_TIMESTAMP,
                    last_downloaded_by = ?
                WHERE id = ?";
        
        $this->db->query($sql, [$userId, $slrId]);
    }

    /**
     * Log SLR access
     * @param int $slrId
     * @param string $accessType
     * @param int $userId
     * @param string $reason
     */
    public function logSLRAccess($slrId, $accessType, $userId, $reason = '') {
        $sql = "INSERT INTO slr_access_log (
                    slr_document_id, access_type, accessed_by, 
                    access_reason, ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $this->db->query($sql, [
            $slrId, $accessType, $userId, $reason, $ipAddress, $userAgent
        ]);
    }

    /**
     * Get generation rule by trigger
     * @param string $trigger
     * @return array|null
     */
    private function getGenerationRule($trigger) {
        $sql = "SELECT * FROM slr_generation_rules 
                WHERE trigger_event = ? AND is_active = true 
                ORDER BY id DESC LIMIT 1";
        
        return $this->db->single($sql, [$trigger]);
    }

    /**
     * Check if signature is required for trigger
     * @param string $trigger
     * @return bool
     */
    private function requiresSignature($trigger) {
        $rule = $this->getGenerationRule($trigger);
        return $rule ? (bool)$rule['require_signatures'] : true;
    }

    /**
     * Ensure storage directories exist
     */
    private function ensureStorageDirectories() {
        $directories = [$this->storageDir, $this->slrDir, $this->archiveDir, $this->tempDir];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
}