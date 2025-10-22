<?php
/**
 * Document Archive Migration
 * Creates table for tracking generated SLR documents
 */

// Connect to database
require_once __DIR__ . '/app/config/config.php';

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    echo "Creating document_archive table...\n";
    
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
        notes TEXT NULL,
        
        -- Foreign keys
        CONSTRAINT fk_document_archive_loan 
            FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
        CONSTRAINT fk_document_archive_generated_by 
            FOREIGN KEY (generated_by) REFERENCES users(id),
        CONSTRAINT fk_document_archive_downloaded_by 
            FOREIGN KEY (last_downloaded_by) REFERENCES users(id),
            
        -- Indexes
        CONSTRAINT unique_document_loan UNIQUE (document_type, loan_id, document_number)
    );
    
    CREATE INDEX IF NOT EXISTS idx_document_archive_loan_id ON document_archive(loan_id);
    CREATE INDEX IF NOT EXISTS idx_document_archive_type ON document_archive(document_type);
    CREATE INDEX IF NOT EXISTS idx_document_archive_generated_at ON document_archive(generated_at);
    CREATE INDEX IF NOT EXISTS idx_document_archive_status ON document_archive(status);
    ";
    
    $pdo->exec($sql);
    echo "✓ document_archive table created successfully\n";
    
    // Create storage directories
    $storageDir = __DIR__ . '/storage';
    $slrDir = $storageDir . '/slr';
    $archiveDir = $storageDir . '/archive';
    
    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0755, true);
        echo "✓ Created storage directory\n";
    }
    
    if (!is_dir($slrDir)) {
        mkdir($slrDir, 0755, true);
        echo "✓ Created SLR storage directory\n";
    }
    
    if (!is_dir($archiveDir)) {
        mkdir($archiveDir, 0755, true);
        echo "✓ Created archive directory\n";
    }
    
    // Create .gitkeep files
    file_put_contents($slrDir . '/.gitkeep', '');
    file_put_contents($archiveDir . '/.gitkeep', '');
    echo "✓ Created .gitkeep files\n";
    
    echo "\n=== Document Archive System Setup Complete ===\n";
    echo "Database table: document_archive\n";
    echo "Storage directories: storage/slr/, storage/archive/\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>