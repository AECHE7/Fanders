<?php
/**
 * Cache Maintenance Script
 * Cleans up corrupted cache entries that may be causing unserialize errors
 * 
 * Run this script once to clean up existing cache issues:
 * php cache_maintenance.php
 */

require_once 'app/config/config.php';

function autoload($className) {
    $directories = [
        'app/core/',
        'app/models/',
        'app/services/',
        'app/utilities/'
    ];

    foreach ($directories as $directory) {
        $file = BASE_PATH . '/' . $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}

spl_autoload_register('autoload');

echo "=== Cache Maintenance Tool ===\n\n";

$cache = new CacheUtility();

// Get stats before cleanup
echo "Before cleanup:\n";
$statsBefore = $cache->getStats();
echo "  - Total files: " . $statsBefore['total_files'] . "\n";
echo "  - Valid entries: " . $statsBefore['valid_entries'] . "\n";
echo "  - Expired entries: " . $statsBefore['expired_entries'] . "\n";
echo "  - Corrupted entries: " . $statsBefore['corrupted_entries'] . "\n";
echo "  - Total size: " . formatBytes($statsBefore['total_size']) . "\n\n";

// Clear corrupted entries
echo "Cleaning corrupted cache entries...\n";
$clearedCount = $cache->clearCorrupted();
echo "Removed {$clearedCount} corrupted cache file(s)\n\n";

// Get stats after cleanup
echo "After cleanup:\n";
$statsAfter = $cache->getStats();
echo "  - Total files: " . $statsAfter['total_files'] . "\n";
echo "  - Valid entries: " . $statsAfter['valid_entries'] . "\n";
echo "  - Expired entries: " . $statsAfter['expired_entries'] . "\n";
echo "  - Corrupted entries: " . $statsAfter['corrupted_entries'] . "\n";
echo "  - Total size: " . formatBytes($statsAfter['total_size']) . "\n\n";

echo "✅ Cache maintenance complete!\n";

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>