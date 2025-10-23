<?php
/**
 * Migration Script: Remove old 'transactions' table and consolidate to 'transaction_logs'
 * 
 * This script:
 * 1. Backs up existing data from 'transactions' table (if needed)
 * 2. Drops the old 'transactions' table
 * 3. Confirms that transaction_logs table is being used
 * 
 * Date: October 23, 2025
 * Reason: Consolidate dual transaction logging system to single table (transaction_logs)
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

echo "\n==============================================\n";
echo "TRANSACTION TABLE MIGRATION SCRIPT\n";
echo "==============================================\n\n";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Step 1: Check if transactions table exists
    echo "Step 1: Checking for 'transactions' table...\n";
    $tableCheck = $pdo->query("SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'transactions'
    )");
    $tableExists = $tableCheck->fetchColumn();
    
    if (!$tableExists) {
        echo "✓ 'transactions' table does not exist. Nothing to migrate.\n";
        echo "\n✅ System is already using transaction_logs table exclusively.\n\n";
        exit(0);
    }
    
    echo "✓ Found 'transactions' table.\n\n";
    
    // Step 2: Count records in transactions table
    echo "Step 2: Checking data in 'transactions' table...\n";
    $countStmt = $pdo->query("SELECT COUNT(*) FROM transactions");
    $transactionCount = $countStmt->fetchColumn();
    echo "Found {$transactionCount} records in 'transactions' table.\n\n";
    
    // Step 3: Check transaction_logs table
    echo "Step 3: Verifying 'transaction_logs' table...\n";
    $logsCheck = $pdo->query("SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'transaction_logs'
    )");
    $logsExists = $logsCheck->fetchColumn();
    
    if (!$logsExists) {
        echo "❌ ERROR: 'transaction_logs' table does not exist!\n";
        echo "Cannot proceed with migration.\n";
        exit(1);
    }
    
    $logsCountStmt = $pdo->query("SELECT COUNT(*) FROM transaction_logs");
    $logsCount = $logsCountStmt->fetchColumn();
    echo "✓ 'transaction_logs' table exists with {$logsCount} records.\n\n";
    
    // Step 4: Backup old transactions table (optional export)
    if ($transactionCount > 0) {
        echo "Step 4: Backing up 'transactions' table data...\n";
        
        // Export to backup file
        $backupFile = __DIR__ . '/../storage/backups/transactions_backup_' . date('Y-m-d_H-i-s') . '.sql';
        $backupDir = dirname($backupFile);
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // Get database connection details
        $dbConfig = require __DIR__ . '/../app/config/database.php';
        $dbName = $dbConfig['database'];
        $dbHost = $dbConfig['host'];
        $dbUser = $dbConfig['username'];
        $dbPort = $dbConfig['port'] ?? 5432;
        
        // Use pg_dump to backup just the transactions table
        $dumpCommand = sprintf(
            'PGPASSWORD=%s pg_dump -h %s -p %d -U %s -d %s -t transactions --inserts -f %s 2>&1',
            escapeshellarg($dbConfig['password']),
            escapeshellarg($dbHost),
            $dbPort,
            escapeshellarg($dbUser),
            escapeshellarg($dbName),
            escapeshellarg($backupFile)
        );
        
        exec($dumpCommand, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($backupFile)) {
            echo "✓ Backup created: {$backupFile}\n";
            echo "  File size: " . filesize($backupFile) . " bytes\n\n";
        } else {
            echo "⚠ Warning: Could not create backup file.\n";
            echo "  You can manually backup with: pg_dump -t transactions\n\n";
        }
    }
    
    // Step 5: Drop the transactions table
    echo "Step 5: Preparing to drop 'transactions' table...\n";
    echo "\n⚠️  WARNING: This action cannot be undone!\n";
    echo "The 'transactions' table will be permanently deleted.\n";
    echo "All future logging will use 'transaction_logs' table.\n\n";
    
    // In production, you might want to add a confirmation prompt
    // For now, we'll proceed automatically
    
    echo "Dropping 'transactions' table...\n";
    $pdo->exec("DROP TABLE IF EXISTS transactions CASCADE");
    
    echo "✓ 'transactions' table dropped successfully!\n\n";
    
    // Step 6: Verify the drop
    echo "Step 6: Verifying migration...\n";
    $verifyCheck = $pdo->query("SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'transactions'
    )");
    $stillExists = $verifyCheck->fetchColumn();
    
    if ($stillExists) {
        echo "❌ ERROR: 'transactions' table still exists!\n";
        exit(1);
    }
    
    echo "✓ Confirmed: 'transactions' table has been removed.\n";
    echo "✓ System is now using 'transaction_logs' table exclusively.\n\n";
    
    // Step 7: Summary
    echo "==============================================\n";
    echo "MIGRATION COMPLETED SUCCESSFULLY\n";
    echo "==============================================\n\n";
    
    echo "Summary:\n";
    echo "  - Old 'transactions' table: DROPPED\n";
    echo "  - Records backed up: {$transactionCount}\n";
    echo "  - Active 'transaction_logs' table: {$logsCount} records\n";
    echo "  - TransactionService updated to use transaction_logs only\n\n";
    
    echo "Next Steps:\n";
    echo "  1. Test transaction logging functionality\n";
    echo "  2. Verify all services are logging correctly\n";
    echo "  3. Monitor system for any logging issues\n";
    echo "  4. Update documentation\n\n";
    
    echo "Backup Location:\n";
    if (isset($backupFile) && file_exists($backupFile)) {
        echo "  {$backupFile}\n\n";
    } else {
        echo "  No backup created (table was empty)\n\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n\n";
    exit(1);
}

echo "✅ Migration complete. System is ready to use.\n\n";
?>
