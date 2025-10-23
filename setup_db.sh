#!/bin/bash

# Database Setup Script
echo "🚀 Setting up database tables for Collection Sheets and SLR..."

# Get database connection details from environment or use defaults
PGHOST=${PGHOST:-"localhost"}
PGPORT=${PGPORT:-"5432"}  
PGDATABASE=${PGDATABASE:-"fanders"}
PGUSER=${PGUSER:-"postgres"}

echo "📊 Database: $PGDATABASE on $PGHOST:$PGPORT"

# Check if psql is available
if ! command -v psql &> /dev/null; then
    echo "❌ psql not found. Please install PostgreSQL client."
    exit 1
fi

# Test database connection
echo "🔗 Testing database connection..."
if ! psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$PGDATABASE" -c "SELECT 1;" &> /dev/null; then
    echo "❌ Cannot connect to database. Please check your credentials."
    exit 1
fi

echo "✅ Database connection successful!"

# Create tables using psql
echo "📋 Creating collection_sheets table..."
psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$PGDATABASE" -c "
CREATE TABLE IF NOT EXISTS collection_sheets (
    id SERIAL PRIMARY KEY,
    officer_id INTEGER NOT NULL,
    sheet_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    submitted_at TIMESTAMP NULL,
    approved_at TIMESTAMP NULL,
    approved_by INTEGER NULL,
    posted_at TIMESTAMP NULL,
    posted_by INTEGER NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
"

echo "📋 Creating collection_sheet_items table..."
psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$PGDATABASE" -c "
CREATE TABLE IF NOT EXISTS collection_sheet_items (
    id SERIAL PRIMARY KEY,
    sheet_id INTEGER NOT NULL,
    client_id INTEGER NOT NULL,
    loan_id INTEGER NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    notes TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    posted_at TIMESTAMP NULL,
    posted_by INTEGER NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
"

echo "📋 Creating document_archive table..."
psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$PGDATABASE" -c "
CREATE TABLE IF NOT EXISTS document_archive (
    id SERIAL PRIMARY KEY,
    document_type VARCHAR(50) NOT NULL DEFAULT 'SLR',
    loan_id INTEGER NOT NULL,
    document_number VARCHAR(100) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INTEGER NOT NULL DEFAULT 0,
    generated_by INTEGER NOT NULL,
    generated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    download_count INTEGER NOT NULL DEFAULT 0,
    last_downloaded_at TIMESTAMP NULL,
    last_downloaded_by INTEGER NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    notes TEXT NULL
);
"

echo "🔗 Creating indexes..."
psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$PGDATABASE" -c "
CREATE INDEX IF NOT EXISTS idx_collection_sheets_officer_date ON collection_sheets(officer_id, sheet_date);
CREATE INDEX IF NOT EXISTS idx_collection_sheets_status ON collection_sheets(status);
CREATE INDEX IF NOT EXISTS idx_collection_sheet_items_sheet_id ON collection_sheet_items(sheet_id);
CREATE INDEX IF NOT EXISTS idx_collection_sheet_items_loan_id ON collection_sheet_items(loan_id);
CREATE INDEX IF NOT EXISTS idx_document_archive_loan_id ON document_archive(loan_id);
CREATE INDEX IF NOT EXISTS idx_document_archive_type ON document_archive(document_type);
"

echo "✅ Database setup completed successfully!"
echo "🎉 All tables are now ready for Collection Sheets and SLR operations!"