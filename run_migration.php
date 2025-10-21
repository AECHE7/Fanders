<?php
/**
 * Run database migration for system_backups table
 */

require_once 'app/config/config.php';
require_once 'app/core/Database.php';

try {
    $db = Database::getInstance();

    // Read migration file
    $migrationSQL = file_get_contents('database/migrations/create_system_backups_table.sql');

    // Execute migration
    $pdo = $db->getConnection();
    $pdo->exec($migrationSQL);

    echo "Migration completed successfully. system_backups table created.\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
