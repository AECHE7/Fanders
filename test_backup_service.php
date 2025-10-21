<?php
/**
 * Test script for BackupService functionality
 */

require_once 'app/config/config.php';
require_once 'app/core/Database.php';
require_once 'app/services/BackupService.php';

echo "Testing BackupService...\n\n";

try {
    $backupService = new BackupService();

    // Test 1: Get backup statistics
    echo "1. Testing backup statistics...\n";
    $stats = $backupService->getBackupStats();
    echo "   Current backup stats: " . json_encode($stats, JSON_PRETTY_PRINT) . "\n\n";

    // Test 2: Create a manual backup
    echo "2. Creating manual backup...\n";
    $result = $backupService->createDatabaseBackup('manual');
    if ($result) {
        echo "   ✓ Manual backup created successfully\n";
        echo "   - ID: {$result['id']}\n";
        echo "   - Filename: {$result['filename']}\n";
        echo "   - Size: " . number_format($result['size']) . " bytes\n";
        echo "   - Cloud URL: " . ($result['cloud_url'] ?: 'Not uploaded') . "\n\n";

        $backupId = $result['id'];
    } else {
        echo "   ✗ Manual backup failed: " . $backupService->getErrorMessage() . "\n\n";
        exit(1);
    }

    // Test 3: Get backups list
    echo "3. Testing backups list retrieval...\n";
    $backups = $backupService->getBackups();
    echo "   Found " . count($backups) . " backups\n";
    if (!empty($backups)) {
        $latest = $backups[0];
        echo "   Latest backup: {$latest['filename']} ({$latest['type']}) - " . date('Y-m-d H:i:s', strtotime($latest['created_at'])) . "\n";
    }
    echo "\n";

    // Test 4: Test scheduled backup (should skip if already done today)
    echo "4. Testing scheduled backup logic...\n";
    $scheduledResult = $backupService->performScheduledBackup();
    if ($scheduledResult) {
        echo "   ✓ Scheduled backup logic executed successfully\n";
    } else {
        echo "   ✗ Scheduled backup failed: " . $backupService->getErrorMessage() . "\n";
    }
    echo "\n";

    // Test 5: Get updated statistics
    echo "5. Getting updated backup statistics...\n";
    $updatedStats = $backupService->getBackupStats();
    echo "   Updated stats: " . json_encode($updatedStats, JSON_PRETTY_PRINT) . "\n\n";

    echo "All BackupService tests completed successfully!\n";

} catch (Exception $e) {
    echo "Test failed with exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
