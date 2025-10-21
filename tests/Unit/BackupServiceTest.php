<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\BackupService;
use Mockery;

class BackupServiceTest extends TestCase
{
    private $backupService;
    private $mockLogger;

    protected function setUp(): void
    {
        // Mock the logger since we're not testing logging functionality
        $this->mockLogger = Mockery::mock();

        // Create BackupService instance
        $this->backupService = new BackupService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testBuildPgDumpCommand()
    {
        $expectedCommand = 'pg_dump --host=localhost --port=5432 --username=testuser --dbname=testdb --no-password --format=custom --compress=9 --file=/path/to/backup.sql --verbose';

        $command = $this->backupService->buildPgDumpCommand(
            'localhost',
            '5432',
            'testuser',
            'testdb',
            '/path/to/backup.sql'
        );

        $this->assertStringContains('pg_dump', $command);
        $this->assertStringContains('--host=localhost', $command);
        $this->assertStringContains('--port=5432', $command);
        $this->assertStringContains('--username=testuser', $command);
        $this->assertStringContains('--dbname=testdb', $command);
        $this->assertStringContains('--file=/path/to/backup.sql', $command);
    }

    public function testBuildPgRestoreCommand()
    {
        $expectedCommand = 'pg_restore --host=localhost --port=5432 --username=testuser --dbname=testdb --no-password --verbose /path/to/backup.sql';

        $command = $this->backupService->buildPgRestoreCommand(
            'localhost',
            '5432',
            'testuser',
            'testdb',
            '/path/to/backup.sql'
        );

        $this->assertStringContains('pg_restore', $command);
        $this->assertStringContains('--host=localhost', $command);
        $this->assertStringContains('--port=5432', $command);
        $this->assertStringContains('--username=testuser', $command);
        $this->assertStringContains('--dbname=testdb', $command);
        $this->assertStringContains('/path/to/backup.sql', $command);
    }

    public function testGenerateBackupFilename()
    {
        $filename = $this->backupService->generateBackupFilename();

        $this->assertStringStartsWith('backup_', $filename);
        $this->assertStringEndsWith('.sql', $filename);
        $this->assertMatchesRegularExpression('/backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql/', $filename);
    }

    public function testValidateBackupFile()
    {
        // Test with valid file
        $validFile = sys_get_temp_dir() . '/test_backup.sql';
        file_put_contents($validFile, 'test backup content');

        $isValid = $this->backupService->validateBackupFile($validFile);

        $this->assertTrue($isValid);

        unlink($validFile);

        // Test with invalid file
        $isValid = $this->backupService->validateBackupFile('/nonexistent/file.sql');

        $this->assertFalse($isValid);
    }

    public function testGetBackupFileSize()
    {
        $testFile = sys_get_temp_dir() . '/test_size.sql';
        $testContent = str_repeat('a', 1024); // 1KB content
        file_put_contents($testFile, $testContent);

        $size = $this->backupService->getBackupFileSize($testFile);

        $this->assertEquals(1024, $size);

        unlink($testFile);
    }

    public function testFormatFileSize()
    {
        $this->assertEquals('1.00 B', $this->backupService->formatFileSize(1));
        $this->assertEquals('1.00 KB', $this->backupService->formatFileSize(1024));
        $this->assertEquals('1.00 MB', $this->backupService->formatFileSize(1024 * 1024));
        $this->assertEquals('1.00 GB', $this->backupService->formatFileSize(1024 * 1024 * 1024));
    }

    public function testIsValidBackupSchedule()
    {
        $this->assertTrue($this->backupService->isValidBackupSchedule('daily'));
        $this->assertTrue($this->backupService->isValidBackupSchedule('weekly'));
        $this->assertTrue($this->backupService->isValidBackupSchedule('monthly'));
        $this->assertFalse($this->backupService->isValidBackupSchedule('hourly'));
        $this->assertFalse($this->backupService->isValidBackupSchedule(''));
    }

    public function testCalculateNextBackupDate()
    {
        $baseDate = '2025-01-15 10:00:00';

        $nextDaily = $this->backupService->calculateNextBackupDate('daily', $baseDate);
        $this->assertEquals('2025-01-16 10:00:00', $nextDaily);

        $nextWeekly = $this->backupService->calculateNextBackupDate('weekly', $baseDate);
        $this->assertEquals('2025-01-22 10:00:00', $nextWeekly);

        $nextMonthly = $this->backupService->calculateNextBackupDate('monthly', $baseDate);
        $this->assertEquals('2025-02-15 10:00:00', $nextMonthly);
    }

    public function testGetBackupStatusText()
    {
        $this->assertEquals('Pending', $this->backupService->getBackupStatusText('pending'));
        $this->assertEquals('In Progress', $this->backupService->getBackupStatusText('in_progress'));
        $this->assertEquals('Completed', $this->backupService->getBackupStatusText('completed'));
        $this->assertEquals('Failed', $this->backupService->getBackupStatusText('failed'));
        $this->assertEquals('Unknown', $this->backupService->getBackupStatusText('invalid_status'));
    }

    public function testShouldRunBackup()
    {
        // Test daily backup - should run if last backup was yesterday
        $lastBackup = date('Y-m-d H:i:s', strtotime('-25 hours'));
        $this->assertTrue($this->backupService->shouldRunBackup('daily', $lastBackup));

        // Test daily backup - should not run if last backup was today
        $lastBackup = date('Y-m-d H:i:s', strtotime('-2 hours'));
        $this->assertFalse($this->backupService->shouldRunBackup('daily', $lastBackup));

        // Test weekly backup - should run if last backup was 8 days ago
        $lastBackup = date('Y-m-d H:i:s', strtotime('-8 days'));
        $this->assertTrue($this->backupService->shouldRunBackup('weekly', $lastBackup));

        // Test weekly backup - should not run if last backup was 3 days ago
        $lastBackup = date('Y-m-d H:i:s', strtotime('-3 days'));
        $this->assertFalse($this->backupService->shouldRunBackup('weekly', $lastBackup));
    }

    public function testValidateDatabaseConnection()
    {
        // This would normally test actual database connection
        // For unit testing, we'll mock the behavior
        $this->assertTrue($this->backupService->validateDatabaseConnection());
    }

    public function testGetBackupDirectory()
    {
        $directory = $this->backupService->getBackupDirectory();

        $this->assertIsString($directory);
        $this->assertStringContains('backups', $directory);
    }

    public function testEnsureBackupDirectoryExists()
    {
        $result = $this->backupService->ensureBackupDirectoryExists();

        $this->assertTrue($result);

        // Verify directory exists
        $directory = $this->backupService->getBackupDirectory();
        $this->assertDirectoryExists($directory);
    }

    public function testCleanupOldBackups()
    {
        // Create some test backup files
        $backupDir = $this->backupService->getBackupDirectory();
        $oldFile = $backupDir . '/old_backup.sql';
        $newFile = $backupDir . '/new_backup.sql';

        file_put_contents($oldFile, 'old backup');
        file_put_contents($newFile, 'new backup');

        // Set old file modification time to more than 30 days ago
        touch($oldFile, strtotime('-40 days'));

        $result = $this->backupService->cleanupOldBackups(30);

        $this->assertTrue($result);

        // Old file should be deleted, new file should remain
        $this->assertFileDoesNotExist($oldFile);
        $this->assertFileExists($newFile);

        // Clean up
        unlink($newFile);
    }
}
