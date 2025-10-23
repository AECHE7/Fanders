<?php
/**
 * Quick Database Setup - Create missing tables for Collection Sheets and Document Archive
 * Run this file directly to setup required database tables
 */

// Database configuration (modify as needed)
$host = getenv('PGHOST') ?: 'localhost';
$port = getenv('PGPORT') ?: '5432';
$dbname = getenv('PGDATABASE') ?: 'fanders';
$username = getenv('PGUSER') ?: 'postgres';
$password = getenv('PGPASSWORD') ?: '';

$dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✓ Connected to database successfully\n";

    // 1. Create collection_sheets table
    echo "Creating collection_sheets table...\n";
    $pdo->exec("
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
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_collection_sheets_officer 
                FOREIGN KEY (officer_id) REFERENCES users(id),
            CONSTRAINT fk_collection_sheets_approved_by 
                FOREIGN KEY (approved_by) REFERENCES users(id),
            CONSTRAINT fk_collection_sheets_posted_by 
                FOREIGN KEY (posted_by) REFERENCES users(id)
        );
    ");
    
    // 2. Create collection_sheet_items table
    echo "Creating collection_sheet_items table...\n";
    $pdo->exec("
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
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            
            CONSTRAINT fk_collection_sheet_items_sheet 
                FOREIGN KEY (sheet_id) REFERENCES collection_sheets(id) ON DELETE CASCADE,
            CONSTRAINT fk_collection_sheet_items_client 
                FOREIGN KEY (client_id) REFERENCES clients(id),
            CONSTRAINT fk_collection_sheet_items_loan 
                FOREIGN KEY (loan_id) REFERENCES loans(id),
            CONSTRAINT fk_collection_sheet_items_posted_by 
                FOREIGN KEY (posted_by) REFERENCES users(id)
        );
    ");

    // 3. Create document_archive table
    echo "Creating document_archive table...\n";
    $pdo->exec("
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
            notes TEXT NULL,
            
            CONSTRAINT fk_document_archive_loan 
                FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
            CONSTRAINT fk_document_archive_generated_by 
                FOREIGN KEY (generated_by) REFERENCES users(id),
            CONSTRAINT fk_document_archive_downloaded_by 
                FOREIGN KEY (last_downloaded_by) REFERENCES users(id),
                
            CONSTRAINT unique_document_loan UNIQUE (document_type, loan_id, document_number)
        );
    ");

    // 4. Create indexes
    echo "Creating indexes...\n";
    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_collection_sheets_officer_date ON collection_sheets(officer_id, sheet_date);
        CREATE INDEX IF NOT EXISTS idx_collection_sheets_status ON collection_sheets(status);
        CREATE INDEX IF NOT EXISTS idx_collection_sheet_items_sheet_id ON collection_sheet_items(sheet_id);
        CREATE INDEX IF NOT EXISTS idx_collection_sheet_items_loan_id ON collection_sheet_items(loan_id);
        CREATE INDEX IF NOT EXISTS idx_document_archive_loan_id ON document_archive(loan_id);
        CREATE INDEX IF NOT EXISTS idx_document_archive_type ON document_archive(document_type);
        CREATE INDEX IF NOT EXISTS idx_document_archive_generated_at ON document_archive(generated_at);
        CREATE INDEX IF NOT EXISTS idx_document_archive_status ON document_archive(status);
    ");

    echo "✅ All tables and indexes created successfully!\n";
    echo "✅ Database setup complete!\n";

} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>