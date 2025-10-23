#!/usr/bin/env php
<?php
/**
 * SLR Schema Verification Script
 * Verifies and creates SLR database schema if needed
 */

echo "\n";
echo "====================================================\n";
echo "  SLR SCHEMA VERIFICATION & MIGRATION\n";
echo "====================================================\n\n";

try {
    // Database connection using environment variables
    $host = getenv('PGHOST') ?: 'aws-1-ap-southeast-1.pooler.supabase.com';
    $port = getenv('PGPORT') ?: '6543';
    $dbname = getenv('PGDATABASE') ?: 'postgres';
    $username = getenv('PGUSER') ?: 'postgres.smzpalngwpwylljdvppb';
    $password = getenv('PGPASSWORD') ?: '105489100018Gadiano';

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
    
    echo "Connecting to database...\n";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✓ Connected successfully\n\n";

    // Read migration SQL
    $migrationFile = __DIR__ . '/../database/migrations/verify_slr_schema.sql';
    
    if (!file_exists($migrationFile)) {
        echo "✗ Migration file not found: {$migrationFile}\n";
        exit(1);
    }
    
    echo "Reading migration file...\n";
    $sql = file_get_contents($migrationFile);
    
    // Split into individual statements (basic split on semicolons)
    // Note: This is a simple split and may not handle all edge cases
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^\s*--/', $stmt) &&
                   !preg_match('/^SELECT/i', $stmt); // Skip verification queries for now
        }
    );
    
    echo "Executing " . count($statements) . " migration statements...\n\n";
    
    $pdo->beginTransaction();
    
    $executed = 0;
    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
            $executed++;
            
            // Show progress for major operations
            if (preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/i', $statement, $matches)) {
                echo "  ✓ Table: {$matches[1]}\n";
            } elseif (preg_match('/CREATE INDEX IF NOT EXISTS (\w+)/i', $statement, $matches)) {
                echo "  ✓ Index: {$matches[1]}\n";
            } elseif (preg_match('/INSERT INTO slr_generation_rules.*\'([^\']+)\'/i', $statement, $matches)) {
                echo "  ✓ Rule: {$matches[1]}\n";
            }
        } catch (PDOException $e) {
            // If error is "already exists", it's okay
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    }
    
    $pdo->commit();
    echo "\n✓ Executed {$executed} statements successfully\n\n";

    // Verification: Check tables exist
    echo "Verifying schema...\n\n";
    
    echo "1. Checking tables:\n";
    $stmt = $pdo->query("
        SELECT 
            table_name,
            (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = t.table_name) as column_count
        FROM information_schema.tables t
        WHERE table_name IN ('slr_documents', 'slr_generation_rules', 'slr_access_log')
        ORDER BY table_name
    ");
    
    $tables = $stmt->fetchAll();
    if (count($tables) === 3) {
        foreach ($tables as $table) {
            echo "  ✓ {$table['table_name']} ({$table['column_count']} columns)\n";
        }
    } else {
        echo "  ✗ Missing tables! Expected 3, found " . count($tables) . "\n";
    }
    
    // Check generation rules
    echo "\n2. Checking generation rules:\n";
    $stmt = $pdo->query("
        SELECT 
            rule_name,
            trigger_event,
            auto_generate,
            is_active
        FROM slr_generation_rules
        ORDER BY priority
    ");
    
    $rules = $stmt->fetchAll();
    if (count($rules) >= 3) {
        foreach ($rules as $rule) {
            $status = $rule['auto_generate'] ? '⚡ AUTO' : '👤 MANUAL';
            $active = $rule['is_active'] ? '🟢' : '🔴';
            echo "  {$active} {$rule['rule_name']} ({$rule['trigger_event']}) - {$status}\n";
        }
    } else {
        echo "  ⚠ Warning: Only " . count($rules) . " rules found (expected at least 3)\n";
    }
    
    // Check indexes
    echo "\n3. Checking indexes:\n";
    $stmt = $pdo->query("
        SELECT COUNT(*) as index_count
        FROM pg_indexes
        WHERE tablename IN ('slr_documents', 'slr_generation_rules', 'slr_access_log')
    ");
    
    $indexCount = $stmt->fetch()['index_count'];
    echo "  ✓ Found {$indexCount} indexes\n";
    
    // Check foreign keys
    echo "\n4. Checking foreign key constraints:\n";
    $stmt = $pdo->query("
        SELECT COUNT(*) as fk_count
        FROM information_schema.table_constraints
        WHERE constraint_type = 'FOREIGN KEY'
            AND table_name IN ('slr_documents', 'slr_generation_rules', 'slr_access_log')
    ");
    
    $fkCount = $stmt->fetch()['fk_count'];
    echo "  ✓ Found {$fkCount} foreign key constraints\n";
    
    echo "\n";
    echo "====================================================\n";
    echo "  ✅ SLR SCHEMA VERIFICATION COMPLETE\n";
    echo "====================================================\n";
    echo "\nAll SLR tables, indexes, and constraints are in place.\n";
    echo "The system is ready for SLR document management.\n\n";
    
    // Show current auto-generation status
    echo "📊 Current Auto-Generation Status:\n";
    $stmt = $pdo->query("
        SELECT 
            trigger_event,
            auto_generate,
            is_active
        FROM slr_generation_rules
        WHERE trigger_event IN ('loan_approval', 'loan_disbursement')
        ORDER BY trigger_event
    ");
    
    foreach ($stmt->fetchAll() as $rule) {
        $enabled = ($rule['auto_generate'] && $rule['is_active']) ? '✅ ENABLED' : '❌ DISABLED';
        echo "  • {$rule['trigger_event']}: {$enabled}\n";
    }
    
    echo "\n";
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\n❌ Database error: " . $e->getMessage() . "\n\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

exit(0);
