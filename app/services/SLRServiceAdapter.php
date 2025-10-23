<?php
/**
 * SLR Service Adapter - Backwards-compatible facade for refactored SLR service
 * 
 * This adapter maintains compatibility with existing code that uses the old
 * SLRService interface while delegating to the refactored implementation.
 * 
 * Usage: Replace old SLRService instantiation with this adapter
 * Old: $slrService = new SLRService();
 * New: $slrService = new SLRServiceAdapter(); // or just rename the class file
 */

require_once __DIR__ . '/../core/BaseService.php';
require_once __DIR__ . '/SLR/SLRServiceRefactored.php';
require_once __DIR__ . '/SLR/SLRResult.php';
require_once __DIR__ . '/../constants/SLRConstants.php';

use App\Services\SLR\SLRServiceRefactored;
use App\Services\SLR\SLRResult;
use App\Constants\SLRConstants;

class SLRServiceAdapter extends BaseService {
    private SLRServiceRefactored $refactoredService;
    
    public function __construct() {
        parent::__construct();
        $this->refactoredService = new SLRServiceRefactored();
    }
    
    /**
     * Generate SLR document for a loan
     * 
     * @param int $loanId
     * @param int $generatedBy User ID
     * @param string $trigger Generation trigger (manual, loan_approval, loan_disbursement)
     * @return array|false SLR document record or false on failure
     */
    public function generateSLR($loanId, $generatedBy, $trigger = 'manual') {
        // Map old trigger names to new constants
        $triggerMap = [
            'manual' => SLRConstants::TRIGGER_MANUAL,
            'manual_request' => SLRConstants::TRIGGER_MANUAL,
            'auto_approval' => SLRConstants::TRIGGER_LOAN_APPROVAL,
            'loan_approval' => SLRConstants::TRIGGER_LOAN_APPROVAL,
            'auto_disbursement' => SLRConstants::TRIGGER_LOAN_DISBURSEMENT,
            'loan_disbursement' => SLRConstants::TRIGGER_LOAN_DISBURSEMENT,
        ];
        
        $standardTrigger = $triggerMap[$trigger] ?? SLRConstants::TRIGGER_MANUAL;
        
        $result = $this->refactoredService->generateSLR($loanId, $generatedBy, $standardTrigger);
        
        if ($result->isSuccess()) {
            return $result->getData();
        } else {
            $this->setErrorMessage($result->getErrorMessage());
            error_log("SLR Generation failed: " . $result->getErrorMessage() . " (Code: " . $result->getErrorCode() . ")");
            return false;
        }
    }
    
    /**
     * Download SLR document
     * 
     * @param int $slrId
     * @param int $userId
     * @param string $reason
     * @return array|false File info or false on failure
     */
    public function downloadSLR($slrId, $userId, $reason = '') {
        $result = $this->refactoredService->downloadSLR($slrId, $userId, $reason);
        
        if ($result->isSuccess()) {
            return $result->getData();
        } else {
            $this->setErrorMessage($result->getErrorMessage());
            return false;
        }
    }
    
    /**
     * Get SLR by loan ID
     * 
     * @param int $loanId
     * @return array|null
     */
    public function getSLRByLoanId($loanId) {
        return $this->refactoredService->getSLRByLoanId($loanId);
    }
    
    /**
     * Get SLR by ID
     * 
     * @param int $slrId
     * @return array|null
     */
    public function getSLRById($slrId) {
        return $this->refactoredService->getSLRByLoanId($slrId);
    }
    
    /**
     * List all SLR documents with filters
     * 
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function listSLRDocuments($filters = [], $limit = 20, $offset = 0) {
        return $this->refactoredService->listSLRDocuments($filters, $limit, $offset);
    }
    
    /**
     * Archive an SLR document
     * 
     * @param int $slrId
     * @param int $userId
     * @param string $reason
     * @return bool
     */
    public function archiveSLR($slrId, $userId, $reason = '') {
        $result = $this->refactoredService->archiveSLR($slrId, $userId, $reason);
        
        if ($result->isSuccess()) {
            return true;
        } else {
            $this->setErrorMessage($result->getErrorMessage());
            return false;
        }
    }
    
    /**
     * Log SLR access (forwarded to refactored service)
     * 
     * @param int $slrId
     * @param string $accessType
     * @param int $userId
     * @param string $reason
     * @return void
     */
    public function logSLRAccess($slrId, $accessType, $userId, $reason = '') {
        // This is now handled internally by the refactored service
        // Keeping this method for backwards compatibility
        error_log("SLRServiceAdapter: logSLRAccess called externally (SLR: $slrId, Type: $accessType) - this is now handled internally");
    }
}
