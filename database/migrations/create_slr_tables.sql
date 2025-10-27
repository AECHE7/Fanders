-- SLR (Statement of Loan Receipt) Tables Migration
-- This file creates the necessary tables for SLR document management

-- SLR Documents Table
CREATE TABLE IF NOT EXISTS `slr_documents` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `loan_id` INT(11) NOT NULL,
    `document_number` VARCHAR(50) NOT NULL UNIQUE,
    `generated_by` INT(11) NOT NULL,
    `generation_trigger` VARCHAR(50) NOT NULL DEFAULT 'manual',
    `file_path` VARCHAR(500) NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_size` INT(11) NOT NULL,
    `content_hash` VARCHAR(128) NOT NULL,
    `client_signature_required` TINYINT(1) NOT NULL DEFAULT 1,
    `status` VARCHAR(20) NOT NULL DEFAULT 'active',
    `replacement_reason` TEXT NULL,
    `download_count` INT(11) NOT NULL DEFAULT 0,
    `last_downloaded_at` DATETIME NULL,
    `last_downloaded_by` INT(11) NULL,
    `generated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    INDEX `idx_loan_id` (`loan_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_generation_trigger` (`generation_trigger`),
    INDEX `idx_generated_by` (`generated_by`),
    INDEX `idx_document_number` (`document_number`),

    CONSTRAINT `fk_slr_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_slr_generated_by` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_slr_last_downloaded_by` FOREIGN KEY (`last_downloaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SLR Generation Rules Table
CREATE TABLE IF NOT EXISTS `slr_generation_rules` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `trigger_event` VARCHAR(50) NOT NULL UNIQUE,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `auto_generate` TINYINT(1) NOT NULL DEFAULT 0,
    `min_principal_amount` DECIMAL(10,2) NULL DEFAULT NULL,
    `max_principal_amount` DECIMAL(10,2) NULL DEFAULT NULL,
    `require_signatures` TINYINT(1) NOT NULL DEFAULT 1,
    `notify_client` TINYINT(1) NOT NULL DEFAULT 0,
    `notify_officers` TINYINT(1) NOT NULL DEFAULT 0,
    `rule_name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    INDEX `idx_trigger_event` (`trigger_event`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SLR Access Log Table
CREATE TABLE IF NOT EXISTS `slr_access_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `slr_document_id` INT(11) NOT NULL,
    `access_type` VARCHAR(20) NOT NULL,
    `accessed_by` INT(11) NOT NULL,
    `access_reason` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `accessed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    INDEX `idx_slr_document_id` (`slr_document_id`),
    INDEX `idx_access_type` (`access_type`),
    INDEX `idx_accessed_by` (`accessed_by`),
    INDEX `idx_accessed_at` (`accessed_at`),

    CONSTRAINT `fk_slr_access_document` FOREIGN KEY (`slr_document_id`) REFERENCES `slr_documents` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_slr_access_user` FOREIGN KEY (`accessed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default SLR generation rules
INSERT IGNORE INTO `slr_generation_rules` (`trigger_event`, `is_active`, `auto_generate`, `require_signatures`, `notify_client`, `notify_officers`, `rule_name`, `description`) VALUES
('manual', 1, 0, 1, 0, 0, 'Manual Generation', 'SLR documents generated manually by staff'),
('loan_approval', 1, 1, 1, 1, 1, 'Automatic on Loan Approval', 'SLR generated automatically when loan is approved'),
('loan_disbursement', 1, 1, 1, 1, 0, 'Automatic on Loan Disbursement', 'SLR generated automatically when loan funds are disbursed');

-- Create storage directories (this would be handled by PHP in production)
-- The SLRServiceRefactored ensures directories exist in ensureStorageDirectories()
