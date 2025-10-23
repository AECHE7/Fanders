
<?php
/**
 * Refactored SLR Service - Statement of Loan Receipt Management
 * 
 * Improvements over legacy SLRService:
 * - Uses constants for trigger events and statuses
 * - Returns Result objects instead of false for better error handling
 * - Cleaner separation of concerns
 * - Comprehensive logging
 * - Better error messages with context
 */

namespace App\Services\SLR;

require_once __DIR__ . '/../../core/BaseService.php';
require_once __DIR__ . '/../../models/LoanModel.php';
require_once __DIR__ . '/../../models/ClientModel.php';
require_once __DIR__ . '/../../utilities/PDFGenerator.php';
require_once __DIR__ . '/../LoanCalculationService.php';
require_once __DIR__ . '/../TransactionService.php';
require_once __DIR__ . '/../../constants/SLRConstants.php';
require_once __DIR__ . '/SLRResult.php';
require_once __DIR__ . '/SLRValidator.php';
require_once __DIR__ . '/SLRPDFGenerator.php';
require_once __DIR__ . '/SLRRepository.php';

use BaseService;
use LoanModel;
use ClientModel;
use LoanCalculationService;
use TransactionService;
use App\Constants\SLRConstants;

class SLRServiceRefactored extends BaseService {
    private LoanModel $loanModel;
    private ClientModel $clientModel;
    private LoanCalculationService $calculationService;
    private TransactionService $transactionService;
    private SLRValidator $validator;
    private SLRPDFGenerator $pdfGenerator;
    private SLRRepository $repository;
    
    // Storage paths
    private string $storageDir;
    private string $slrDir;
    private string $archiveDir;
    private string $tempDir;

    public function __construct() {
        parent::__construct();
        $this->loanModel = new LoanModel();
        $this->clientModel = new ClientModel();
        $this->calculationService = new LoanCalculationService();
        $this->transactionService = new TransactionService();
        $this->validator = new SLRValidator($this->db);
        $this->pdfGenerator = new SLRPDFGenerator();
        $this->repository = new SLRRepository($this->db);
        
        // Initialize storage paths using constants
        $this->storageDir = BASE_PATH . '/storage';
        $this->slrDir = $this->storageDir . '/' . SLRConstants::STORAGE_DIR;
        $this->archiveDir = $this->storageDir . '/' . SLRConstants::ARCHIVE_DIR;
        $this->tempDir = $this->storageDir . '/' . SLRConstants::TEMP_DIR;
        
        $this->ensureStorageDirectories();
    }

    /**
     * Generate SLR document for a loan
     * 
     * @param int $loanId Loan ID
     * @param int $generatedBy User ID who triggered generation
     * @param string $trigger Generation trigger (use SLRConstants::TRIGGER_*)
     * @return SLRResult
     */
    public function generateSLR(int $loanId, int $generatedBy, string $trigger = SLRConstants::TRIGGER_MANUAL): SLRResult {
        try {
            return $this->transaction(function() use ($loanId, $generatedBy, $trigger) {
                // Validate trigger
                if (!in_array($trigger, SLRConstants::getValidTriggers())) {
                    error_log("SLR: Invalid trigger '$trigger' for loan $loanId");
                    return SLRResult::failure(
                        "Invalid generation trigger: $trigger",
                        'INVALID_TRIGGER'
                    );
                }

                // Get loan details
                $loan = $this->loanModel->getLoanWithClient($loanId);
                if (!$loan) {
                    error_log("SLR: Loan $loanId not found");
                    return SLRResult::failure(
                        'Loan not found.',
                        'LOAN_NOT_FOUND'
                    );
                }

                // Validate generation eligibility
                $validationResult = $this->validator->canGenerateSLR($loan, $trigger);
                if ($validationResult->isFailure()) {
                    error_log("SLR: Validation failed for loan $loanId: " . $validationResult->getErrorMessage());
                    return $validationResult;
                }

                // Check for existing active SLR
                $existing = $this->repository->getSLRByLoanId($loanId);
                if ($existing && $existing['status'] === SLRConstants::STATUS_ACTIVE) {
                    error_log("SLR: Active SLR already exists for loan $loanId");
                    return SLRResult::failure(
                        'Active SLR already exists for this loan. Archive the existing SLR first.',
                        'ACTIVE_SLR_EXISTS'
                    );
                }

                // Generate document number
                $documentNumber = $this->generateDocumentNumber($loanId);

                // Create PDF content
                $pdfResult = $this->pdfGenerator->createSLRPDF($loan, $this->calculationService);
                if ($pdfResult->isFailure()) {
                    error_log("SLR: PDF generation failed for loan $loanId: " . $pdfResult->getErrorMessage());
                    return $pdfResult;
                }
                
                $pdfContent = $pdfResult->getData();

                // Save PDF file
                $fileName = "SLR_{$documentNumber}_" . date('Ymd') . SLRConstants::FILE_EXTENSION;
                $filePath = $this->slrDir . '/' . $fileName;
                
                if (!file_put_contents($filePath, $pdfContent)) {
                    error_log("SLR: Failed to save PDF file for loan $loanId at $filePath");
                    return SLRResult::failure(
                        'Failed to save SLR document to disk.',
                        'FILE_SAVE_ERROR'
                    );
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
                    'client_signature_required' => $this->validator->requiresSignature($trigger)
                ];

                $slrId = $this->repository->createSLR($slrData);
                if (!$slrId) {
                    // Clean up file if database insert fails
                    unlink($filePath);
                    error_log("SLR: Database insert failed for loan $loanId");
                    return SLRResult::failure(
                        'Failed to save SLR record to database.',
                        'DB_INSERT_ERROR'
                    );
                }

                // Log the generation
                $this->logSLRAccess(
                    $slrId,
                    SLRConstants::ACCESS_GENERATION,
                    $generatedBy,
                    "SLR generated via " . SLRConstants::getTriggerLabel($trigger)
                );

                // Get the created record
                $slrRecord = $this->repository->getSLRById($slrId);
                
                error_log("SLR: Successfully generated SLR $documentNumber for loan $loanId (trigger: $trigger)");
                
                return SLRResult::success($slrRecord);
            });
        } catch (\Exception $e) {
            error_log("SLR: Exception during generation for loan $loanId: " . $e->getMessage());
            error_log($e->getTraceAsString());
            return SLRResult::failure(
                'An unexpected error occurred while generating SLR: ' . $e->getMessage(),
                'EXCEPTION'
            );
        }
    }

    /**
     * Download SLR document with integrity checks
     * 
     * @param int $slrId SLR document ID
     * @param int $userId User requesting download
     * @param string $reason Optional reason for access
     * @return SLRResult
     */
    public function downloadSLR(int $slrId, int $userId, string $reason = ''): SLRResult {
        try {
            $slr = $this->repository->getSLRById($slrId);
            if (!$slr) {
                return SLRResult::failure('SLR document not found.', 'SLR_NOT_FOUND');
            }

            if ($slr['status'] !== SLRConstants::STATUS_ACTIVE) {
                return SLRResult::failure(
                    'SLR document is not active (status: ' . $slr['status'] . ').',
                    'SLR_NOT_ACTIVE'
                );
            }

            if (!file_exists($slr['file_path'])) {
                error_log("SLR: File not found on disk for SLR $slrId: " . $slr['file_path']);
                return SLRResult::failure('SLR file not found on disk.', 'FILE_NOT_FOUND');
            }

            // Verify file integrity
            $fileContent = file_get_contents($slr['file_path']);
            $fileHash = hash('sha256', $fileContent);
            
            if ($slr['content_hash'] && $fileHash !== $slr['content_hash']) {
                error_log("SLR: Integrity check failed for SLR $slrId. Expected: {$slr['content_hash']}, Got: $fileHash");
                return SLRResult::failure(
                    'SLR file integrity check failed. Document may be corrupted.',
                    'INTEGRITY_CHECK_FAILED'
                );
            }

            // Update download statistics
            $this->repository->updateDownloadStats($slrId, $userId);

            // Log the access
            $this->logSLRAccess($slrId, SLRConstants::ACCESS_DOWNLOAD, $userId, $reason);

            return SLRResult::success([
                'file_path' => $slr['file_path'],
                'file_name' => $slr['file_name'],
                'file_size' => $slr['file_size'],
                'content_type' => SLRConstants::CONTENT_TYPE,
                'content' => $fileContent
            ]);
        } catch (\Exception $e) {
            error_log("SLR: Exception during download for SLR $slrId: " . $e->getMessage());
            return SLRResult::failure(
                'An unexpected error occurred while downloading SLR: ' . $e->getMessage(),
                'EXCEPTION'
            );
        }
    }

    /**
     * Archive an SLR document
     * 
     * @param int $slrId SLR document ID
     * @param int $userId User performing archival
     * @param string $reason Reason for archiving
     * @return SLRResult
     */
    public function archiveSLR(int $slrId, int $userId, string $reason = ''): SLRResult {
        try {
            return $this->transaction(function() use ($slrId, $userId, $reason) {
                $slr = $this->repository->getSLRById($slrId);
                if (!$slr) {
                    return SLRResult::failure('SLR document not found.', 'SLR_NOT_FOUND');
                }

                if ($slr['status'] !== SLRConstants::STATUS_ACTIVE) {
                    return SLRResult::failure(
                        'Only active SLR documents can be archived.',
                        'INVALID_STATUS'
                    );
                }

                // Move file to archive directory
                $archivePath = $this->archiveDir . '/' . $slr['file_name'];
                if (file_exists($slr['file_path'])) {
                    if (!rename($slr['file_path'], $archivePath)) {
                        error_log("SLR: Failed to move file to archive for SLR $slrId");
                        return SLRResult::failure(
                            'Failed to move SLR file to archive directory.',
                            'FILE_MOVE_ERROR'
                        );
                    }
                }

                // Update database record
                if (!$this->repository->updateSLRStatus($slrId, SLRConstants::STATUS_ARCHIVED, $archivePath, $reason)) {
                    // Rollback file move
                    if (file_exists($archivePath)) {
                        rename($archivePath, $slr['file_path']);
                    }
                    error_log("SLR: Failed to update database for archival of SLR $slrId");
                    return SLRResult::failure(
                        'Failed to update SLR record in database.',
                        'DB_UPDATE_ERROR'
                    );
                }

                // Log the archival
                $this->logSLRAccess($slrId, SLRConstants::ACCESS_ARCHIVE, $userId, $reason);

                error_log("SLR: Successfully archived SLR $slrId by user $userId");
                return SLRResult::success(['slr_id' => $slrId, 'status' => SLRConstants::STATUS_ARCHIVED]);
            });
        } catch (\Exception $e) {
            error_log("SLR: Exception during archival for SLR $slrId: " . $e->getMessage());
            return SLRResult::failure(
                'An unexpected error occurred while archiving SLR: ' . $e->getMessage(),
                'EXCEPTION'
            );
        }
    }

    /**
     * Get SLR document by loan ID
     * 
     * @param int $loanId
     * @return array|null
     */
    public function getSLRByLoanId(int $loanId): ?array {
        return $this->repository->getSLRByLoanId($loanId);
    }

    /**
     * List SLR documents with filters
     * 
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function listSLRDocuments(array $filters = [], int $limit = 20, int $offset = 0): array {
        return $this->repository->listSLRDocuments($filters, $limit, $offset);
    }

    /**
     * Log SLR access to both slr_access_log and transaction_logs
     * 
     * @param int $slrId
     * @param string $accessType
     * @param int $userId
     * @param string $reason
     * @return void
     */
    private function logSLRAccess(int $slrId, string $accessType, int $userId, string $reason = ''): void {
        try {
            // Log to slr_access_log table
            $this->repository->logAccess($slrId, $accessType, $userId, $reason);

            // Also log to transaction_logs for overall audit trail
            $this->transactionService->logGeneric(
                'slr_' . $accessType,
                $userId,
                $slrId,
                [
                    'access_type' => $accessType,
                    'reason' => $reason,
                    'slr_id' => $slrId,
                    'label' => 'SLR ' . ucfirst($accessType)
                ]
            );
        } catch (\Exception $e) {
            // Log the error but don't fail the operation
            error_log("SLR: Failed to log access (type: $accessType, SLR: $slrId, User: $userId): " . $e->getMessage());
        }
    }

    /**
     * Generate unique document number
     * 
     * @param int $loanId
     * @return string
     */
    private function generateDocumentNumber(int $loanId): string {
        $year = date('Y');
        $month = date('m');
        $loanPadded = str_pad($loanId, 6, '0', STR_PAD_LEFT);
        
        return "SLR-{$year}{$month}-{$loanPadded}";
    }

    /**
     * Ensure storage directories exist
     * 
     * @return void
     */
    private function ensureStorageDirectories(): void {
        $directories = [$this->storageDir, $this->slrDir, $this->archiveDir, $this->tempDir];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    error_log("SLR: Failed to create directory: $dir");
                }
            }
        }
    }
}
