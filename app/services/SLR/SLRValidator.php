<?php
/**
 * SLRValidator - Validates SLR generation eligibility
 */

namespace App\Services\SLR;

require_once __DIR__ . '/../../constants/SLRConstants.php';
require_once __DIR__ . '/SLRResult.php';

use App\Constants\SLRConstants;

class SLRValidator {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Check if SLR can be generated for a loan
     * 
     * @param array $loan Loan data with client info
     * @param string $trigger Generation trigger
     * @return SLRResult
     */
    public function canGenerateSLR(array $loan, string $trigger): SLRResult {
        // Check loan status
        $validStatuses = ['approved', 'active', 'completed'];
        
        if (!in_array(strtolower($loan['status']), $validStatuses)) {
            return SLRResult::failure(
                'SLR can only be generated for approved, active, or completed loans. Current status: ' . $loan['status'],
                'INVALID_LOAN_STATUS'
            );
        }

        // Check generation rules
        $rule = $this->getGenerationRule($trigger);
        if (!$rule) {
            return SLRResult::failure(
                'No generation rule found for trigger: ' . $trigger,
                'NO_GENERATION_RULE'
            );
        }
        
        if (!$rule['is_active']) {
            return SLRResult::failure(
                'SLR generation is disabled for ' . SLRConstants::getTriggerLabel($trigger),
                'RULE_NOT_ACTIVE'
            );
        }

        // Check principal amount limits if specified
        if ($rule['min_principal_amount'] && $loan['principal'] < $rule['min_principal_amount']) {
            return SLRResult::failure(
                sprintf(
                    'Loan principal (₱%s) is below minimum amount (₱%s) for SLR generation.',
                    number_format($loan['principal'], 2),
                    number_format($rule['min_principal_amount'], 2)
                ),
                'PRINCIPAL_TOO_LOW'
            );
        }

        if ($rule['max_principal_amount'] && $loan['principal'] > $rule['max_principal_amount']) {
            return SLRResult::failure(
                sprintf(
                    'Loan principal (₱%s) exceeds maximum amount (₱%s) for SLR generation.',
                    number_format($loan['principal'], 2),
                    number_format($rule['max_principal_amount'], 2)
                ),
                'PRINCIPAL_TOO_HIGH'
            );
        }

        return SLRResult::success();
    }
    
    /**
     * Get generation rule by trigger
     * 
     * @param string $trigger
     * @return array|null
     */
    public function getGenerationRule(string $trigger): ?array {
        $sql = "SELECT * FROM slr_generation_rules 
                WHERE trigger_event = ? AND is_active = true 
                ORDER BY id DESC LIMIT 1";

        // Primary lookup using provided trigger
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$trigger]);
        $rule = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

        // Fallbacks for legacy/alias trigger names
        if (!$rule) {
            // Handle historical alias: 'manual_request' vs 'manual'
            if ($trigger === 'manual_request') {
                $stmt->execute(['manual']);
                $rule = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            } elseif ($trigger === 'manual') {
                $stmt->execute(['manual_request']);
                $rule = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            }

            // Handle older auto_* aliases just in case
            if (!$rule && $trigger === 'auto_approval') {
                $stmt->execute(['loan_approval']);
                $rule = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            } elseif (!$rule && $trigger === 'auto_disbursement') {
                $stmt->execute(['loan_disbursement']);
                $rule = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            }
        }

        return $rule;
    }
    
    /**
     * Check if signature is required for trigger
     * 
     * @param string $trigger
     * @return bool
     */
    public function requiresSignature(string $trigger): bool {
        $rule = $this->getGenerationRule($trigger);
        return $rule ? (bool)$rule['require_signatures'] : true;
    }
    
    /**
     * Check if client notification is required
     * 
     * @param string $trigger
     * @return bool
     */
    public function requiresClientNotification(string $trigger): bool {
        $rule = $this->getGenerationRule($trigger);
        return $rule ? (bool)$rule['notify_client'] : false;
    }
    
    /**
     * Check if officer notification is required
     * 
     * @param string $trigger
     * @return bool
     */
    public function requiresOfficerNotification(string $trigger): bool {
        $rule = $this->getGenerationRule($trigger);
        return $rule ? (bool)$rule['notify_officers'] : false;
    }
}
