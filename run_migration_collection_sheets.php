<?php
/**
 * Run database migration for Collection Sheets tables
 */

require_once 'app/config/config.php';
require_once 'app/core/Database.php';

try {
    $db = Database::getInstance();

    $migrationSQL = file_get_contents('database/migrations/create_collection_sheets_tables.sql');

    $pdo = $db->getConnection();
    $pdo->exec($migrationSQL);

    echo "Migration completed successfully. collection_sheets and collection_sheet_items tables created.\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
