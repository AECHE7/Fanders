<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

echo "🚀 Setting up database tables for Collection Sheets and SLR...\n";

try {
    // Use singleton accessor since Database::__construct is private
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    if (!$connection) {
        throw new Exception("Failed to connect to database");
    }
    
    echo "✅ Connected to database: " . DB_NAME . " on " . DB_HOST . "\n";
    
    // Create collection_sheets table
    echo "📋 Creating collection_sheets table...\n";
    $sql = "
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
    );";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    echo "✅ collection_sheets table created successfully!\n";
    
    // Create collection_sheet_items table
    echo "📋 Creating collection_sheet_items table...\n";
    $sql = "
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
    );";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    echo "✅ collection_sheet_items table created successfully!\n";
    
    // Create document_archive table
    echo "📋 Creating document_archive table...\n";
    $sql = "
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
    );";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    echo "✅ document_archive table created successfully!\n";
    
    // Create indexes
    echo "🔗 Creating indexes...\n";
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_collection_sheets_officer_date ON collection_sheets(officer_id, sheet_date);",
        "CREATE INDEX IF NOT EXISTS idx_collection_sheets_status ON collection_sheets(status);",
        "CREATE INDEX IF NOT EXISTS idx_collection_sheet_items_sheet_id ON collection_sheet_items(sheet_id);",
        "CREATE INDEX IF NOT EXISTS idx_collection_sheet_items_loan_id ON collection_sheet_items(loan_id);",
        "CREATE INDEX IF NOT EXISTS idx_document_archive_loan_id ON document_archive(loan_id);",
        "CREATE INDEX IF NOT EXISTS idx_document_archive_type ON document_archive(document_type);"
    ];
    
    foreach ($indexes as $indexSql) {
        $stmt = $connection->prepare($indexSql);
        $stmt->execute();
    }
    echo "✅ All indexes created successfully!\n";
    
    // Verify tables exist
    echo "🔍 Verifying table creation...\n";
    $tables = ['collection_sheets', 'collection_sheet_items', 'document_archive'];
    
    foreach ($tables as $table) {
        $sql = "SELECT COUNT(*) FROM information_schema.tables WHERE table_name = ?";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$table]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' NOT found\n";
        }
    }
    
    echo "\n🎉 Database setup completed successfully!\n";
    echo "🎉 All tables are now ready for Collection Sheets and SLR operations!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}