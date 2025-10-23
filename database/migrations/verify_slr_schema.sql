-- SLR Schema Verification and Migration
-- Ensures all SLR-related tables exist with proper structure, indexes, and constraints
-- Run this to verify or create the complete SLR database schema

-- =====================================================
-- 1. SLR Documents Table
-- =====================================================
CREATE TABLE IF NOT EXISTS slr_documents (
    id SERIAL PRIMARY KEY,
    loan_id INTEGER NOT NULL,
    document_number VARCHAR(50) NOT NULL UNIQUE,
    generated_by INTEGER NOT NULL,
    generation_trigger VARCHAR(50) NOT NULL DEFAULT 'manual',
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INTEGER NOT NULL,
    content_hash VARCHAR(64),
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    replacement_reason TEXT,
    client_signature_required BOOLEAN DEFAULT true,
    client_signed_at TIMESTAMP,
    officer_signed_at TIMESTAMP,
    download_count INTEGER DEFAULT 0,
    last_downloaded_at TIMESTAMP,
    last_downloaded_by INTEGER,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    CONSTRAINT fk_slr_loan FOREIGN KEY (loan_id) 
        REFERENCES loans(id) ON DELETE CASCADE,
    CONSTRAINT fk_slr_generated_by FOREIGN KEY (generated_by) 
        REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_slr_downloaded_by FOREIGN KEY (last_downloaded_by) 
        REFERENCES users(id) ON DELETE SET NULL,
    
    -- Constraints
    CONSTRAINT chk_slr_status CHECK (status IN ('active', 'archived', 'replaced', 'void')),
    CONSTRAINT chk_slr_trigger CHECK (generation_trigger IN ('manual', 'loan_approval', 'loan_disbursement')),
    CONSTRAINT chk_slr_file_size CHECK (file_size > 0)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_slr_loan_id ON slr_documents(loan_id);
CREATE INDEX IF NOT EXISTS idx_slr_status ON slr_documents(status);
CREATE INDEX IF NOT EXISTS idx_slr_generated_at ON slr_documents(generated_at DESC);
CREATE INDEX IF NOT EXISTS idx_slr_trigger ON slr_documents(generation_trigger);
CREATE INDEX IF NOT EXISTS idx_slr_document_number ON slr_documents(document_number);

-- =====================================================
-- 2. SLR Generation Rules Table
-- =====================================================
CREATE TABLE IF NOT EXISTS slr_generation_rules (
    id SERIAL PRIMARY KEY,
    rule_name VARCHAR(100) NOT NULL,
    description TEXT,
    trigger_event VARCHAR(50) NOT NULL,
    auto_generate BOOLEAN DEFAULT false,
    require_signatures BOOLEAN DEFAULT true,
    notify_client BOOLEAN DEFAULT false,
    notify_officers BOOLEAN DEFAULT false,
    min_principal_amount DECIMAL(12,2),
    max_principal_amount DECIMAL(12,2),
    is_active BOOLEAN DEFAULT true,
    priority INTEGER DEFAULT 0,
    created_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    CONSTRAINT fk_rule_created_by FOREIGN KEY (created_by) 
        REFERENCES users(id) ON DELETE SET NULL,
    
    -- Constraints
    CONSTRAINT chk_rule_trigger CHECK (trigger_event IN ('manual', 'loan_approval', 'loan_disbursement')),
    CONSTRAINT chk_rule_principal CHECK (
        min_principal_amount IS NULL OR 
        max_principal_amount IS NULL OR 
        min_principal_amount <= max_principal_amount
    ),
    CONSTRAINT uq_rule_trigger_priority UNIQUE (trigger_event, priority)
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_rule_trigger ON slr_generation_rules(trigger_event);
CREATE INDEX IF NOT EXISTS idx_rule_active ON slr_generation_rules(is_active);
CREATE INDEX IF NOT EXISTS idx_rule_priority ON slr_generation_rules(priority DESC);

-- =====================================================
-- 3. SLR Access Log Table
-- =====================================================
CREATE TABLE IF NOT EXISTS slr_access_log (
    id SERIAL PRIMARY KEY,
    slr_document_id INTEGER NOT NULL,
    access_type VARCHAR(50) NOT NULL,
    accessed_by INTEGER NOT NULL,
    access_reason TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    access_result VARCHAR(20) DEFAULT 'success',
    error_message TEXT,
    accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign keys
    CONSTRAINT fk_access_slr FOREIGN KEY (slr_document_id) 
        REFERENCES slr_documents(id) ON DELETE CASCADE,
    CONSTRAINT fk_access_user FOREIGN KEY (accessed_by) 
        REFERENCES users(id) ON DELETE SET NULL,
    
    -- Constraints
    CONSTRAINT chk_access_type CHECK (access_type IN ('generation', 'view', 'download', 'archive', 'restore', 'void')),
    CONSTRAINT chk_access_result CHECK (access_result IN ('success', 'failure', 'denied'))
);

-- Indexes
CREATE INDEX IF NOT EXISTS idx_access_slr ON slr_access_log(slr_document_id);
CREATE INDEX IF NOT EXISTS idx_access_user ON slr_access_log(accessed_by);
CREATE INDEX IF NOT EXISTS idx_access_type ON slr_access_log(access_type);
CREATE INDEX IF NOT EXISTS idx_access_time ON slr_access_log(accessed_at DESC);

-- =====================================================
-- 4. Insert Default Generation Rules (if not exists)
-- =====================================================
INSERT INTO slr_generation_rules (
    rule_name, 
    description, 
    trigger_event, 
    auto_generate, 
    require_signatures,
    notify_client,
    notify_officers,
    is_active,
    priority,
    created_by
)
SELECT 
    'Manual SLR Request',
    'SLR can be manually generated on request by authorized staff',
    'manual',
    true,
    true,
    false,
    false,
    true,
    0,
    1
WHERE NOT EXISTS (
    SELECT 1 FROM slr_generation_rules WHERE trigger_event = 'manual'
);

INSERT INTO slr_generation_rules (
    rule_name, 
    description, 
    trigger_event, 
    auto_generate, 
    require_signatures,
    notify_client,
    notify_officers,
    is_active,
    priority,
    created_by
)
SELECT 
    'Auto SLR on Loan Approval',
    'Automatically generate SLR when loan is approved',
    'loan_approval',
    false,
    true,
    true,
    true,
    true,
    1,
    1
WHERE NOT EXISTS (
    SELECT 1 FROM slr_generation_rules WHERE trigger_event = 'loan_approval'
);

INSERT INTO slr_generation_rules (
    rule_name, 
    description, 
    trigger_event, 
    auto_generate, 
    require_signatures,
    notify_client,
    notify_officers,
    is_active,
    priority,
    created_by
)
SELECT 
    'Auto SLR on Loan Disbursement',
    'Automatically generate SLR when loan funds are disbursed to client',
    'loan_disbursement',
    true,
    true,
    true,
    true,
    true,
    2,
    1
WHERE NOT EXISTS (
    SELECT 1 FROM slr_generation_rules WHERE trigger_event = 'loan_disbursement'
);

-- =====================================================
-- 5. Update Trigger to maintain updated_at timestamps
-- =====================================================
CREATE OR REPLACE FUNCTION update_slr_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trigger_slr_documents_updated_at ON slr_documents;
CREATE TRIGGER trigger_slr_documents_updated_at
    BEFORE UPDATE ON slr_documents
    FOR EACH ROW
    EXECUTE FUNCTION update_slr_updated_at();

DROP TRIGGER IF EXISTS trigger_slr_rules_updated_at ON slr_generation_rules;
CREATE TRIGGER trigger_slr_rules_updated_at
    BEFORE UPDATE ON slr_generation_rules
    FOR EACH ROW
    EXECUTE FUNCTION update_slr_updated_at();

-- =====================================================
-- 6. Grant permissions (adjust as needed)
-- =====================================================
-- GRANT SELECT, INSERT, UPDATE ON slr_documents TO your_app_user;
-- GRANT SELECT, INSERT, UPDATE ON slr_generation_rules TO your_app_user;
-- GRANT SELECT, INSERT ON slr_access_log TO your_app_user;
-- GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO your_app_user;

-- =====================================================
-- Verification Queries
-- =====================================================
-- Run these to verify the schema:

-- Check tables exist
SELECT 
    table_name,
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = t.table_name) as column_count
FROM information_schema.tables t
WHERE table_name IN ('slr_documents', 'slr_generation_rules', 'slr_access_log')
ORDER BY table_name;

-- Check indexes
SELECT 
    tablename,
    indexname,
    indexdef
FROM pg_indexes
WHERE tablename IN ('slr_documents', 'slr_generation_rules', 'slr_access_log')
ORDER BY tablename, indexname;

-- Check generation rules
SELECT 
    id,
    rule_name,
    trigger_event,
    auto_generate,
    is_active,
    priority
FROM slr_generation_rules
ORDER BY priority;

-- Check foreign key constraints
SELECT
    tc.constraint_name,
    tc.table_name,
    kcu.column_name,
    ccu.table_name AS foreign_table_name,
    ccu.column_name AS foreign_column_name
FROM information_schema.table_constraints AS tc
JOIN information_schema.key_column_usage AS kcu
    ON tc.constraint_name = kcu.constraint_name
    AND tc.table_schema = kcu.table_schema
JOIN information_schema.constraint_column_usage AS ccu
    ON ccu.constraint_name = tc.constraint_name
    AND ccu.table_schema = tc.table_schema
WHERE tc.constraint_type = 'FOREIGN KEY'
    AND tc.table_name IN ('slr_documents', 'slr_generation_rules', 'slr_access_log')
ORDER BY tc.table_name, tc.constraint_name;
