<?php
/**
 * Backup Scheduler Script
 * This script should be run daily via cron job or scheduled task
 * Example cron job: 0 2 * * * /usr/bin/php /path/to/fanders/scripts/backup_scheduler.php
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/services/BackupService.php';

try {
    echo "Starting scheduled backup process...\n";

    $backupService = new BackupService();

    // Perform scheduled backup
    $result = $backupService->performScheduledBackup();

    if ($result) {
        echo "Scheduled backup completed successfully.\n";

        // Get backup statistics
        $stats = $backupService->getBackupStats();
        echo "Backup Statistics:\n";
        echo "- Total backups: " . ($stats['total_backups'] ?? 0) . "\n";
        echo "- Scheduled backups: " . ($stats['scheduled_backups'] ?? 0) . "\n";
        echo "- Manual backups: " . ($stats['manual_backups'] ?? 0) . "\n";
        echo "- Total size: " . number_format(($stats['total_size'] ?? 0) / 1024 / 1024, 2) . " MB\n";
        echo "- Last backup: " . ($stats['last_backup_date'] ?? 'Never') . "\n";

    } else {
        echo "Scheduled backup failed: " . $backupService->getErrorMessage() . "\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "Backup scheduler error: " . $e->getMessage() . "\n";
    error_log("Backup scheduler exception: " . $e->getMessage());
    exit(1);
}

echo "Backup scheduler completed.\n";
