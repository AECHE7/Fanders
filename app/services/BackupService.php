<?php
/**
 * BackupService - Handles automated database backups for Fanders Microfinance LMS
 * Implements Phase 3 automated backup functionality with cloud storage integration
 */

require_once __DIR__ . '/../core/BaseService.php';
// Logger not available, using error_log for now

class BackupService extends BaseService {
    private $dbConfig;
    private $backupDir;
    private $cloudStorageDir;
    private $logger;

    public function __construct() {
        parent::__construct();
        $this->dbConfig = require __DIR__ . '/../config/database.php';
        $this->backupDir = BASE_PATH . '/storage/backups/database/';
        $this->cloudStorageDir = BASE_PATH . '/storage/backups/cloud/';
        // $this->logger = new Logger('backup_service'); // Logger not available

        // Ensure backup directories exist
        $this->ensureDirectoriesExist();
    }

    /**
     * Create a full database backup
     * @param string $backupType 'full', 'incremental', 'manual'
     * @return array Backup information or false on failure
     */
    public function createDatabaseBackup($backupType = 'full') {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "fanders_lms_backup_{$backupType}_{$timestamp}.sql";
            $filepath = $this->backupDir . $filename;

            // Create dump command based on database type
            if ($this->dbConfig['type'] === 'pgsql') {
                $command = $this->buildPgDumpCommand($filepath);
            } else {
                $command = $this->buildMysqldumpCommand($filepath);
            }

            // Execute backup
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                $this->setErrorMessage('Database backup failed: ' . implode("\n", $output));
                error_log('Database backup failed: ' . implode("\n", $output));
                return false;
            }

            // Verify backup file
            if (!file_exists($filepath) || filesize($filepath) === 0) {
                $this->setErrorMessage('Backup file was not created or is empty');
                return false;
            }

            // Compress the backup
            $compressedFile = $this->compressBackup($filepath);
            if ($compressedFile) {
                unlink($filepath); // Remove uncompressed file
                $filepath = $compressedFile;
                $filename = basename($compressedFile);
            }

            // Calculate file size
            $fileSize = filesize($filepath);

            // Store backup metadata
            $backupId = $this->storeBackupMetadata([
                'filename' => $filename,
                'filepath' => $filepath,
                'type' => $backupType,
                'size' => $fileSize,
                'status' => 'completed',
                'created_by' => 'system'
            ]);

            // Upload to cloud storage
            $cloudUrl = $this->uploadToCloudStorage($filepath, $filename);

            // Update metadata with cloud URL
            if ($cloudUrl) {
                $this->updateBackupMetadata($backupId, ['cloud_url' => $cloudUrl]);
            }

            // Log successful backup
            error_log("Database backup completed successfully: {$filename} ({$fileSize} bytes)");

            // Clean up old backups
            $this->cleanupOldBackups();

            return [
                'id' => $backupId,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => $fileSize,
                'cloud_url' => $cloudUrl,
                'type' => $backupType,
                'created_at' => date('Y-m-d H:i:s')
            ];

        } catch (Exception $e) {
            $this->setErrorMessage('Backup creation failed: ' . $e->getMessage());
            error_log('Backup creation exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Schedule automated daily backups
     * This should be called by a cron job or scheduled task
     */
    public function performScheduledBackup() {
        error_log('Starting scheduled daily backup');

        // Check if backup was already performed today
        if ($this->hasBackupToday()) {
            error_log('Daily backup already exists, skipping');
            return true;
        }

        $result = $this->createDatabaseBackup('scheduled');

        if ($result) {
            error_log('Scheduled backup completed successfully');
            return true;
        } else {
            error_log('Scheduled backup failed: ' . $this->getErrorMessage());
            return false;
        }
    }

    /**
     * Restore database from backup
     * @param int $backupId
     * @return bool
     */
    public function restoreFromBackup($backupId) {
        try {
            // Get backup metadata
            $backup = $this->getBackupById($backupId);
            if (!$backup) {
                $this->setErrorMessage('Backup not found');
                return false;
            }

            // Download from cloud if needed
            $localFile = $backup['filepath'];
            if (!file_exists($localFile) && !empty($backup['cloud_url'])) {
                $localFile = $this->downloadFromCloudStorage($backup['cloud_url'], $backup['filename']);
                if (!$localFile) {
                    $this->setErrorMessage('Failed to download backup from cloud storage');
                    return false;
                }
            }

            // Decompress if needed
            if (pathinfo($localFile, PATHINFO_EXTENSION) === 'gz') {
                $localFile = $this->decompressBackup($localFile);
                if (!$localFile) {
                    $this->setErrorMessage('Failed to decompress backup file');
                    return false;
                }
            }

            // Execute restore command based on database type
            if ($this->dbConfig['type'] === 'pgsql') {
                $command = $this->buildPgRestoreCommand($localFile);
            } else {
                $command = $this->buildMysqlRestoreCommand($localFile);
            }

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                $this->setErrorMessage('Database restore failed: ' . implode("\n", $output));
                error_log('Database restore failed: ' . implode("\n", $output));
                return false;
            }

            // Log successful restore
            error_log('Database restore completed successfully: ' . $backup['filename']);

            // Update backup metadata
            $this->updateBackupMetadata($backupId, [
                'last_restored_at' => date('Y-m-d H:i:s'),
                'restore_count' => ($backup['restore_count'] ?? 0) + 1
            ]);

            return true;

        } catch (Exception $e) {
            $this->setErrorMessage('Restore failed: ' . $e->getMessage());
            error_log('Restore exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get list of available backups
     * @param array $filters
     * @return array
     */
    public function getBackups($filters = []) {
        try {
            $sql = "SELECT * FROM system_backups WHERE 1=1";
            $params = [];

            if (!empty($filters['type'])) {
                $sql .= " AND type = ?";
                $params[] = $filters['type'];
            }

            if (!empty($filters['status'])) {
                $sql .= " AND status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['date_from'])) {
                $sql .= " AND created_at::date >= ?";
                $params[] = $filters['date_from'];
            }

            if (!empty($filters['date_to'])) {
                $sql .= " AND created_at::date <= ?";
                $params[] = $filters['date_to'];
            }

            $sql .= " ORDER BY created_at DESC";

            if (!empty($filters['limit'])) {
                $sql .= " LIMIT ?";
                $params[] = (int)$filters['limit'];
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Failed to get backups: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete old backups (keep last 30 days + last 10 of each month)
     */
    private function cleanupOldBackups() {
        try {
            // Keep backups from last 30 days
            $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));

            // Keep last 10 backups of each month for the past year
            $oneYearAgo = date('Y-m-d', strtotime('-1 year'));

            $sql = "
                DELETE FROM system_backups
                WHERE created_at < ?
                AND id NOT IN (
                    SELECT id FROM (
                        SELECT id,
                               ROW_NUMBER() OVER (PARTITION BY EXTRACT(YEAR FROM created_at), EXTRACT(MONTH FROM created_at) ORDER BY created_at DESC) as rn
                        FROM system_backups
                        WHERE created_at >= ?
                    ) ranked WHERE rn <= 10
                )
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$thirtyDaysAgo, $oneYearAgo]);

            $deletedCount = $stmt->rowCount();

            if ($deletedCount > 0) {
                error_log('Cleaned up old backups', ['deleted_count' => $deletedCount]);
            }

        } catch (Exception $e) {
            error_log('Backup cleanup failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if a backup was already performed today
     */
    private function hasBackupToday() {
        try {
            $today = date('Y-m-d');
            $sql = "SELECT COUNT(*) as count FROM system_backups WHERE created_at::date = ? AND type = 'scheduled'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$today]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['count'] > 0;

        } catch (Exception $e) {
            error_log('Failed to check daily backup status: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Build mysqldump command for MySQL/MariaDB
     */
    private function buildMysqldumpCommand($filepath) {
        $host = $this->dbConfig['host'];
        $username = $this->dbConfig['username'];
        $password = $this->dbConfig['password'];
        $database = $this->dbConfig['database'];
        $port = $this->dbConfig['port'] ?? 3306;

        // Escape password for shell
        $escapedPassword = escapeshellarg($this->dbConfig['password']);

        // Build mysqldump command with compression
        $command = "mysqldump --host={$host} --port={$port} --user={$username} --password={$escapedPassword} {$database} --single-transaction --routines --triggers --compress > \"{$filepath}\"";

        return $command;
    }

    /**
     * Build mysql command for restore
     */
    private function buildMysqlRestoreCommand($filepath) {
        $host = $this->dbConfig['host'];
        $username = $this->dbConfig['username'];
        $password = $this->dbConfig['password'];
        $database = $this->dbConfig['database'];
        $port = $this->dbConfig['port'] ?? 3306;

        // Escape password for shell
        $escapedPassword = escapeshellarg($this->dbConfig['password']);

        $command = "mysql --host={$host} --port={$port} --user={$username} --password={$escapedPassword} {$database} < \"{$filepath}\"";

        return $command;
    }

    /**
     * Build pg_dump command for PostgreSQL
     */
    private function buildPgDumpCommand($filepath) {
        $host = $this->dbConfig['host'];
        $username = $this->dbConfig['username'];
        $password = $this->dbConfig['password'];
        $database = $this->dbConfig['database'];
        $port = $this->dbConfig['port'] ?? 5432;

        // Set PGPASSWORD environment variable for authentication
        $envPassword = "PGPASSWORD={$password}";

        // Use pg_dump with compression and custom format
        $command = "{$envPassword} pg_dump --host={$host} --port={$port} --username={$username} --dbname={$database} --no-password --format=custom --compress=9 --file=\"{$filepath}\"";

        return $command;
    }

    /**
     * Build pg_restore command for PostgreSQL
     */
    private function buildPgRestoreCommand($filepath) {
        $host = $this->dbConfig['host'];
        $username = $this->dbConfig['username'];
        $password = $this->dbConfig['password'];
        $database = $this->dbConfig['database'];
        $port = $this->dbConfig['port'] ?? 5432;

        // Set PGPASSWORD environment variable for authentication
        $envPassword = "PGPASSWORD={$password}";

        $command = "{$envPassword} pg_restore --host={$host} --port={$port} --username={$username} --dbname={$database} --no-password --clean --if-exists --create \"{$filepath}\"";

        return $command;
    }

    /**
     * Compress backup file using gzip
     */
    private function compressBackup($filepath) {
        $compressedFile = $filepath . '.gz';

        try {
            $command = "gzip -c \"{$filepath}\" > \"{$compressedFile}\"";
            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($compressedFile)) {
                return $compressedFile;
            }
        } catch (Exception $e) {
            // Fallback to PHP compression if gzip not available
            $content = file_get_contents($filepath);
            if ($content !== false) {
                $compressed = gzencode($content, 9);
                if (file_put_contents($compressedFile, $compressed) !== false) {
                    return $compressedFile;
                }
            }
        }

        return false;
    }

    /**
     * Decompress backup file
     */
    private function decompressBackup($compressedFile) {
        $decompressedFile = str_replace('.gz', '', $compressedFile);

        try {
            $command = "gzip -d -c \"{$compressedFile}\" > \"{$decompressedFile}\"";
            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($decompressedFile)) {
                return $decompressedFile;
            }
        } catch (Exception $e) {
            // Fallback to PHP decompression
            $content = file_get_contents($compressedFile);
            if ($content !== false) {
                $decompressed = gzdecode($content);
                if ($decompressed !== false && file_put_contents($decompressedFile, $decompressed) !== false) {
                    return $decompressedFile;
                }
            }
        }

        return false;
    }

    /**
     * Upload backup to cloud storage (placeholder for cloud integration)
     */
    private function uploadToCloudStorage($filepath, $filename) {
        // Placeholder for cloud storage integration
        // In a real implementation, this would upload to AWS S3, Google Cloud Storage, etc.

        try {
            // For now, just copy to cloud storage directory
            $cloudPath = $this->cloudStorageDir . $filename;
            if (copy($filepath, $cloudPath)) {
                // Return a mock cloud URL
                return "cloud://backups/{$filename}";
            }
        } catch (Exception $e) {
            error_log('Cloud upload failed: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Download backup from cloud storage
     */
    private function downloadFromCloudStorage($cloudUrl, $filename) {
        // Placeholder for cloud download
        $localPath = $this->backupDir . 'temp_' . $filename;

        try {
            $cloudPath = $this->cloudStorageDir . basename($cloudUrl);
            if (file_exists($cloudPath) && copy($cloudPath, $localPath)) {
                return $localPath;
            }
        } catch (Exception $e) {
            error_log('Cloud download failed: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Store backup metadata in database
     */
    private function storeBackupMetadata($data) {
        try {
            $sql = "INSERT INTO system_backups (filename, filepath, type, size, status, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['filename'],
                $data['filepath'],
                $data['type'],
                $data['size'],
                $data['status'],
                $data['created_by']
            ]);

            return $this->db->lastInsertId();

        } catch (Exception $e) {
            error_log('Failed to store backup metadata: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update backup metadata
     */
    private function updateBackupMetadata($backupId, $data) {
        try {
            $sets = [];
            $params = [];

            foreach ($data as $key => $value) {
                $sets[] = "{$key} = ?";
                $params[] = $value;
            }

            $params[] = $backupId;

            $sql = "UPDATE system_backups SET " . implode(', ', $sets) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

        } catch (Exception $e) {
            error_log('Failed to update backup metadata: ' . $e->getMessage());
        }
    }

    /**
     * Get backup by ID
     */
    private function getBackupById($backupId) {
        try {
            $sql = "SELECT * FROM system_backups WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$backupId]);

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Failed to get backup by ID: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ensure backup directories exist
     */
    private function ensureDirectoriesExist() {
        $dirs = [$this->backupDir, $this->cloudStorageDir];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Get backup statistics
     */
    public function getBackupStats() {
        try {
            $sql = "
                SELECT
                    COUNT(*) as total_backups,
                    SUM(CASE WHEN type = 'scheduled' THEN 1 ELSE 0 END) as scheduled_backups,
                    SUM(CASE WHEN type = 'manual' THEN 1 ELSE 0 END) as manual_backups,
                    SUM(size) as total_size,
                    MAX(created_at) as last_backup_date
                FROM system_backups
                WHERE status = 'completed'
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log('Failed to get backup stats: ' . $e->getMessage());
            return [];
        }
    }
}
