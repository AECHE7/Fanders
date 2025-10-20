<?php
/**
 * Table Structure Analysis Script
 * Analyzes the database schema for all tables, with special focus on transaction-related tables
 */

require_once 'app/config/config.php';
require_once 'app/core/Database.php';

try {
    $db = Database::getInstance();

    echo "=== DATABASE TABLE STRUCTURE ANALYSIS ===\n\n";

    // Get all table names
    $tables = $db->resultSet("SHOW TABLES");
    if (!$tables) {
        echo "No tables found or error retrieving tables.\n";
        echo "Trying alternative query...\n";

        // Try alternative query for PostgreSQL
        $tables = $db->resultSet("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
        if (!$tables) {
            echo "Still no tables found. Checking database connection...\n";
            // Test basic connection
            $test = $db->resultSet("SELECT 1 as test");
            if ($test) {
                echo "Database connection works, but no tables found.\n";
            } else {
                echo "Database connection failed.\n";
            }
            exit;
        }
    }

    foreach ($tables as $table) {
        $tableName = array_values($table)[0];

        echo "TABLE: {$tableName}\n";
        echo str_repeat("=", 50 + strlen($tableName)) . "\n";

        // Get table structure - PostgreSQL syntax
        $columns = $db->resultSet("
            SELECT
                column_name as Field,
                data_type as Type,
                CASE WHEN is_nullable = 'YES' THEN 'YES' ELSE 'NO' END as Null,
                CASE WHEN column_name IN (
                    SELECT kcu.column_name
                    FROM information_schema.table_constraints tc
                    JOIN information_schema.key_column_usage kcu
                    ON tc.constraint_name = kcu.constraint_name
                    WHERE tc.table_name = '{$tableName}'
                    AND tc.constraint_type = 'PRIMARY KEY'
                ) THEN 'PRI' ELSE '' END as Key,
                column_default as Default
            FROM information_schema.columns
            WHERE table_name = '{$tableName}'
            ORDER BY ordinal_position
        ");

        echo "COLUMNS:\n";
        printf("%-20s %-15s %-10s %-10s %-20s\n", "Field", "Type", "Null", "Key", "Default");
        echo str_repeat("-", 75) . "\n";

        foreach ($columns as $column) {
            printf("%-20s %-15s %-10s %-10s %-20s\n",
                $column['Field'],
                $column['Type'],
                $column['Null'],
                $column['Key'],
                $column['Default'] ?? 'NULL'
            );
        }

        // Get indexes
        $indexes = $db->resultSet("SHOW INDEX FROM {$tableName}");

        if (!empty($indexes)) {
            echo "\nINDEXES:\n";
            printf("%-20s %-15s %-10s %-10s\n", "Key_name", "Column_name", "Unique", "Seq_in_index");
            echo str_repeat("-", 55) . "\n";

            foreach ($indexes as $index) {
                printf("%-20s %-15s %-10s %-10s\n",
                    $index['Key_name'],
                    $index['Column_name'],
                    $index['Non_unique'] == 0 ? 'YES' : 'NO',
                    $index['Seq_in_index']
                );
            }
        }

        // Special focus on transaction-related tables
        if (strpos($tableName, 'transaction') !== false) {
            echo "\nTRANSACTION TABLE ANALYSIS:\n";

            // Count records
            $count = $db->single("SELECT COUNT(*) as count FROM {$tableName}")['count'];
            echo "Total records: {$count}\n";

            // Sample data
            $sample = $db->resultSet("SELECT * FROM {$tableName} LIMIT 5");
            if (!empty($sample)) {
                echo "\nSample records:\n";
                foreach ($sample as $row) {
                    echo json_encode($row, JSON_PRETTY_PRINT) . "\n---\n";
                }
            }
        }

        echo "\n" . str_repeat("=", 80) . "\n\n";
    }

    // Summary of transaction-related tables
    echo "=== TRANSACTION TABLES SUMMARY ===\n";
    $transactionTables = [];

    foreach ($tables as $table) {
        $tableName = array_values($table)[0];
        if (strpos($tableName, 'transaction') !== false) {
            $count = $db->single("SELECT COUNT(*) as count FROM {$tableName}")['count'];
            $transactionTables[$tableName] = $count;
        }
    }

    foreach ($transactionTables as $table => $count) {
        echo "{$table}: {$count} records\n";
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
