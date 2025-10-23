-- Migration: Create collection sheets tables for FR-006 / UR-006 / FR-007 (PostgreSQL)

CREATE TABLE IF NOT EXISTS collection_sheets (
    id SERIAL PRIMARY KEY,
    officer_id INTEGER NOT NULL,
    sheet_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft', -- draft|submitted|posted
    total_amount NUMERIC(12,2) DEFAULT 0.00,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_collection_sheets_officer_date ON collection_sheets(officer_id, sheet_date);
CREATE INDEX IF NOT EXISTS idx_collection_sheets_status_date ON collection_sheets(status, sheet_date);

CREATE TABLE IF NOT EXISTS collection_sheet_items (
    id SERIAL PRIMARY KEY,
    sheet_id INTEGER NOT NULL REFERENCES collection_sheets(id) ON DELETE CASCADE,
    client_id INTEGER NOT NULL,
    loan_id INTEGER NOT NULL,
    amount NUMERIC(12,2) NOT NULL,
    notes VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft', -- draft|submitted|posted|rejected
    posted_at TIMESTAMP WITHOUT TIME ZONE NULL,
    posted_by INTEGER NULL,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_collection_sheet_items_sheet_id ON collection_sheet_items(sheet_id);
CREATE INDEX IF NOT EXISTS idx_collection_sheet_items_client_loan ON collection_sheet_items(client_id, loan_id);

-- document_archive table for SLR documents
CREATE TABLE IF NOT EXISTS document_archive (
    id SERIAL PRIMARY KEY,
    document_type VARCHAR(50) NOT NULL DEFAULT 'SLR',
    loan_id INTEGER NOT NULL,
    document_number VARCHAR(100) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INTEGER NOT NULL DEFAULT 0,
    generated_by INTEGER NOT NULL,
    generated_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    download_count INTEGER NOT NULL DEFAULT 0,
    last_downloaded_at TIMESTAMP WITHOUT TIME ZONE NULL,
    last_downloaded_by INTEGER NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    notes TEXT NULL
);

CREATE INDEX IF NOT EXISTS idx_document_archive_loan_id ON document_archive(loan_id);
CREATE INDEX IF NOT EXISTS idx_document_archive_type ON document_archive(document_type);
