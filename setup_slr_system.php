<?php
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';

echo "🚀 Setting up SLR (Statement of Loan Receipt) System...\n";

try {
    // Use singleton accessor since Database::__construct is private
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    if (!$connection) {
        throw new Exception("Failed to connect to database");
    }
    
    echo "✅ Connected to database: " . DB_NAME . " on " . DB_HOST . "\n";
    
    echo "\n=== SLR System Database Migration ===\n\n";
    
    // 1. Create SLR Documents table (enhanced version)
    echo "📋 Creating slr_documents table...\n";
    
    $sql = "
    CREATE TABLE IF NOT EXISTS slr_documents (
        id SERIAL PRIMARY KEY,
        loan_id INTEGER NOT NULL,
        document_number VARCHAR(50) NOT NULL UNIQUE,
        
        -- Document generation info
        generated_by INTEGER NOT NULL,
        generated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        generation_trigger VARCHAR(50) NOT NULL DEFAULT 'manual', -- 'manual', 'auto_approval', 'auto_disbursement'
        
        -- Document content tracking
        file_path VARCHAR(500) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_size INTEGER NOT NULL DEFAULT 0,
        content_hash VARCHAR(64) NULL, -- For integrity checking
        
        -- Access tracking
        download_count INTEGER NOT NULL DEFAULT 0,
        last_downloaded_at TIMESTAMP NULL,
        last_downloaded_by INTEGER NULL,
        
        -- Document status
        status VARCHAR(20) NOT NULL DEFAULT 'active', -- 'active', 'archived', 'replaced', 'invalid'
        replacement_reason TEXT NULL,
        replaced_by INTEGER NULL, -- References another slr_documents.id
        
        -- Metadata
        client_signature_required BOOLEAN NOT NULL DEFAULT TRUE,
        client_signed_at TIMESTAMP NULL,
        client_signed_by INTEGER NULL, -- User who verified signature
        
        notes TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        
        -- Foreign keys
        CONSTRAINT fk_slr_loan 
            FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
        CONSTRAINT fk_slr_generated_by 
            FOREIGN KEY (generated_by) REFERENCES users(id),
        CONSTRAINT fk_slr_downloaded_by 
            FOREIGN KEY (last_downloaded_by) REFERENCES users(id),
        CONSTRAINT fk_slr_signed_by 
            FOREIGN KEY (client_signed_by) REFERENCES users(id),
        CONSTRAINT fk_slr_replaced_by 
            FOREIGN KEY (replaced_by) REFERENCES slr_documents(id)
    );
    ";
    
    $connection->exec($sql);
    echo "✅ slr_documents table created successfully!\n\n";
    
    // 2. Create SLR Generation Rules table
    echo "📋 Creating slr_generation_rules table...\n";
    
    $sql = "
    CREATE TABLE IF NOT EXISTS slr_generation_rules (
        id SERIAL PRIMARY KEY,
        rule_name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT NOT NULL,
        
        -- Trigger conditions
        trigger_event VARCHAR(50) NOT NULL, -- 'loan_approval', 'loan_disbursement', 'manual_request'
        auto_generate BOOLEAN NOT NULL DEFAULT FALSE,
        
        -- Applicability
        applies_to_loan_types VARCHAR(100) DEFAULT 'all', -- JSON array or 'all'
        min_principal_amount DECIMAL(12,2) DEFAULT 0,
        max_principal_amount DECIMAL(12,2) DEFAULT NULL,
        
        -- Generation settings
        require_signatures BOOLEAN NOT NULL DEFAULT TRUE,
        notify_client BOOLEAN NOT NULL DEFAULT FALSE,
        notify_officers BOOLEAN NOT NULL DEFAULT TRUE,
        
        -- Status
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        created_by INTEGER NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        
        CONSTRAINT fk_slr_rules_created_by 
            FOREIGN KEY (created_by) REFERENCES users(id)
    );
    ";
    
    $connection->exec($sql);
    echo "✅ slr_generation_rules table created successfully!\n\n";
    
    // 3. Create SLR Access Log table
    echo "📋 Creating slr_access_log table...\n";
    
    $sql = "
    CREATE TABLE IF NOT EXISTS slr_access_log (
        id SERIAL PRIMARY KEY,
        slr_document_id INTEGER NOT NULL,
        
        -- Access details
        access_type VARCHAR(30) NOT NULL, -- 'view', 'download', 'print', 'email'
        accessed_by INTEGER NOT NULL,
        accessed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        
        -- Request details
        ip_address INET NULL,
        user_agent TEXT NULL,
        access_reason TEXT NULL,
        
        -- Result
        success BOOLEAN NOT NULL DEFAULT TRUE,
        error_message TEXT NULL,
        
        CONSTRAINT fk_slr_access_document 
            FOREIGN KEY (slr_document_id) REFERENCES slr_documents(id) ON DELETE CASCADE,
        CONSTRAINT fk_slr_access_user 
            FOREIGN KEY (accessed_by) REFERENCES users(id)
    );
    ";
    
    $connection->exec($sql);
    echo "✅ slr_access_log table created successfully!\n\n";
    
    // 4. Create indexes for performance
    echo "📋 Creating database indexes...\n";
    
    $indexes = [
        // SLR Documents indexes
        "CREATE INDEX IF NOT EXISTS idx_slr_documents_loan_id ON slr_documents(loan_id);",
        "CREATE INDEX IF NOT EXISTS idx_slr_documents_status ON slr_documents(status);",
        "CREATE INDEX IF NOT EXISTS idx_slr_documents_generated_at ON slr_documents(generated_at);",
        "CREATE INDEX IF NOT EXISTS idx_slr_documents_number ON slr_documents(document_number);",
        
        // Generation Rules indexes
        "CREATE INDEX IF NOT EXISTS idx_slr_rules_trigger ON slr_generation_rules(trigger_event);",
        "CREATE INDEX IF NOT EXISTS idx_slr_rules_active ON slr_generation_rules(is_active);",
        
        // Access Log indexes
        "CREATE INDEX IF NOT EXISTS idx_slr_access_document_id ON slr_access_log(slr_document_id);",
        "CREATE INDEX IF NOT EXISTS idx_slr_access_user ON slr_access_log(accessed_by);",
        "CREATE INDEX IF NOT EXISTS idx_slr_access_type ON slr_access_log(access_type);",
        "CREATE INDEX IF NOT EXISTS idx_slr_access_date ON slr_access_log(accessed_at);"
    ];
    
    foreach ($indexes as $index) {
        $connection->exec($index);
    }
    echo "✅ All indexes created successfully!\n\n";
    
    // 5. Insert default generation rules
    echo "📋 Inserting default SLR generation rules...\n";
    
    $defaultRules = [
        [
            'rule_name' => 'Auto-generate on Approval',
            'description' => 'Automatically generate SLR when loan is approved',
            'trigger_event' => 'loan_approval',
            'auto_generate' => true,
            'require_signatures' => true,
            'notify_client' => false,
            'notify_officers' => true
        ],
        [
            'rule_name' => 'Manual Generation Only',
            'description' => 'SLR must be generated manually by authorized personnel',
            'trigger_event' => 'manual_request',
            'auto_generate' => false,
            'require_signatures' => true,
            'notify_client' => false,
            'notify_officers' => false
        ],
        [
            'rule_name' => 'Generate on Disbursement',
            'description' => 'Generate SLR when loan funds are disbursed to client',
            'trigger_event' => 'loan_disbursement',
            'auto_generate' => false,
            'require_signatures' => true,
            'notify_client' => true,
            'notify_officers' => true
        ]
    ];
    
    $insertRule = "INSERT INTO slr_generation_rules 
        (rule_name, description, trigger_event, auto_generate, require_signatures, notify_client, notify_officers, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
    
    $stmt = $connection->prepare($insertRule);
    
    foreach ($defaultRules as $rule) {
        $stmt->execute([
            $rule['rule_name'],
            $rule['description'],
            $rule['trigger_event'],
            $rule['auto_generate'] ? 1 : 0,
            $rule['require_signatures'] ? 1 : 0,
            $rule['notify_client'] ? 1 : 0,
            $rule['notify_officers'] ? 1 : 0
        ]);
    }
    
    echo "✅ Default generation rules inserted successfully!\n\n";
    
    // 6. Create storage directories
    echo "📁 Setting up storage directories...\n";
    
    $storageDir = __DIR__ . '/storage';
    $slrDir = $storageDir . '/slr';
    $archiveDir = $storageDir . '/slr/archive';
    $tempDir = $storageDir . '/slr/temp';
    
    $directories = [$storageDir, $slrDir, $archiveDir, $tempDir];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "✅ Created directory: $dir\n";
        }
    }
    
    // Create .gitkeep files
    $gitkeepFiles = [
        $slrDir . '/.gitkeep',
        $archiveDir . '/.gitkeep',
        $tempDir . '/.gitkeep'
    ];
    
    foreach ($gitkeepFiles as $file) {
        file_put_contents($file, '# SLR Storage Directory');
    }
    
    echo "✅ Created .gitkeep files\n\n";
    
    // 7. Verify tables exist
    echo "🔍 Verifying table creation...\n";
    
    $tables = ['slr_documents', 'slr_generation_rules', 'slr_access_log'];
    
    foreach ($tables as $table) {
        $result = $connection->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_name = '$table'");
        $exists = $result->fetchColumn() > 0;
        echo ($exists ? "✅" : "❌") . " Table '$table': " . ($exists ? "EXISTS" : "NOT FOUND") . "\n";
    }
    
    echo "\n=== SLR System Migration Complete ===\n";
    echo "📋 Tables created: slr_documents, slr_generation_rules, slr_access_log\n";
    echo "📁 Storage directories: storage/slr/, storage/slr/archive/, storage/slr/temp/\n";
    echo "⚙️  Default generation rules installed\n";
    echo "🔒 Indexes created for optimal performance\n\n";
    
    echo "Next Steps:\n";
    echo "1. Configure SLR generation rules in admin panel\n";
    echo "2. Test SLR generation with approved loans\n";
    echo "3. Set up automated backup for SLR documents\n";
    echo "4. Train staff on SLR workflow\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Check database connection and try again.\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}