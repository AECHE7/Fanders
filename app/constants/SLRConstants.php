<?php
/**
 * SLR (Statement of Loan Receipt) Constants
 * 
 * Defines standardized constants for SLR triggers, statuses, and access types
 * to ensure consistency across the system.
 */

namespace App\Constants;

class SLRConstants {
    // Generation Trigger Events
    const TRIGGER_MANUAL = 'manual';
    const TRIGGER_LOAN_APPROVAL = 'loan_approval';
    const TRIGGER_LOAN_DISBURSEMENT = 'loan_disbursement';
    
    // SLR Document Status
    const STATUS_ACTIVE = 'active';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_REPLACED = 'replaced';
    const STATUS_VOID = 'void';
    
    // Access Types for Logging
    const ACCESS_GENERATION = 'generation';
    const ACCESS_VIEW = 'view';
    const ACCESS_DOWNLOAD = 'download';
    const ACCESS_ARCHIVE = 'archive';
    const ACCESS_RESTORE = 'restore';
    const ACCESS_VOID = 'void';
    
    // File Storage Paths (relative to BASE_PATH/storage)
    const STORAGE_DIR = 'slr';
    const ARCHIVE_DIR = 'slr/archive';
    const TEMP_DIR = 'slr/temp';
    
    // Document Settings
    const DEFAULT_TERM_WEEKS = 17;
    const FILE_EXTENSION = '.pdf';
    const CONTENT_TYPE = 'application/pdf';
    
    /**
     * Get all valid trigger events
     * @return array
     */
    public static function getValidTriggers(): array {
        return [
            self::TRIGGER_MANUAL,
            self::TRIGGER_LOAN_APPROVAL,
            self::TRIGGER_LOAN_DISBURSEMENT,
        ];
    }
    
    /**
     * Get all valid document statuses
     * @return array
     */
    public static function getValidStatuses(): array {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_ARCHIVED,
            self::STATUS_REPLACED,
            self::STATUS_VOID,
        ];
    }
    
    /**
     * Get all valid access types
     * @return array
     */
    public static function getValidAccessTypes(): array {
        return [
            self::ACCESS_GENERATION,
            self::ACCESS_VIEW,
            self::ACCESS_DOWNLOAD,
            self::ACCESS_ARCHIVE,
            self::ACCESS_RESTORE,
            self::ACCESS_VOID,
        ];
    }
    
    /**
     * Get human-readable trigger name
     * @param string $trigger
     * @return string
     */
    public static function getTriggerLabel(string $trigger): string {
        return match($trigger) {
            self::TRIGGER_MANUAL => 'Manual Generation',
            self::TRIGGER_LOAN_APPROVAL => 'Automatic on Approval',
            self::TRIGGER_LOAN_DISBURSEMENT => 'Automatic on Disbursement',
            default => ucfirst(str_replace('_', ' ', $trigger)),
        };
    }
    
    /**
     * Get human-readable status name
     * @param string $status
     * @return string
     */
    public static function getStatusLabel(string $status): string {
        return match($status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_ARCHIVED => 'Archived',
            self::STATUS_REPLACED => 'Replaced',
            self::STATUS_VOID => 'Void',
            default => ucfirst($status),
        };
    }
    
    /**
     * Get badge color class for status
     * @param string $status
     * @return string
     */
    public static function getStatusBadgeClass(string $status): string {
        return match($status) {
            self::STATUS_ACTIVE => 'bg-success',
            self::STATUS_ARCHIVED => 'bg-secondary',
            self::STATUS_REPLACED => 'bg-warning',
            self::STATUS_VOID => 'bg-danger',
            default => 'bg-secondary',
        };
    }
}
