<?php
/**
 * CSV Backup Endpoint
 * Exports all database tables to CSV files
 * 
 * Security: Protected by secret token
 * Usage: /public/cron/backup_csv.php?token=YOUR_SECRET_TOKEN
 */

// Security: Check for secret token
$token = $_GET['token'] ?? '';
$expectedToken = getenv('CRON_SECRET_TOKEN') ?: 'abc123xyz789';

if ($token !== $expectedToken) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Forbidden: Invalid token',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Set headers
header('Content-Type: application/json');

// Database connection
try {
    // Get database credentials from environment
    $host = getenv('PGHOST') ?: 'localhost';
    $port = getenv('PGPORT') ?: '5432';
    $dbname = getenv('PGDATABASE') ?: 'railway';
    $user = getenv('PGUSER') ?: 'postgres';
    $password = getenv('PGPASSWORD') ?: '';
    
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Create backup directory
    $backupDir = '/app/backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    // Create timestamped backup folder
    $timestamp = date('Y-m-d_His');
    $backupFolder = "$backupDir/backup_$timestamp";
    mkdir($backupFolder, 0755, true);
    
    $exportedTables = [];
    $errors = [];
    
    // Get list of all tables
    $stmt = $pdo->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_type = 'BASE TABLE'
        ORDER BY table_name
    ");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Export each table to CSV
    foreach ($tables as $table) {
        try {
            $csvFile = "$backupFolder/{$table}.csv";
            $fp = fopen($csvFile, 'w');
            
            if (!$fp) {
                $errors[] = "Failed to create CSV file for table: $table";
                continue;
            }
            
            // Get table data
            $stmt = $pdo->query("SELECT * FROM \"$table\"");
            
            // Write headers (column names)
            $firstRow = true;
            $rowCount = 0;
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($firstRow) {
                    fputcsv($fp, array_keys($row));
                    $firstRow = false;
                }
                fputcsv($fp, $row);
                $rowCount++;
            }
            
            fclose($fp);
            
            $fileSize = filesize($csvFile);
            $exportedTables[] = [
                'table' => $table,
                'rows' => $rowCount,
                'size' => round($fileSize / 1024, 2) . ' KB'
            ];
            
        } catch (Exception $e) {
            $errors[] = "Error exporting table $table: " . $e->getMessage();
        }
    }
    
    // Create a ZIP archive of all CSV files
    $zipFile = "$backupDir/backup_$timestamp.zip";
    $zip = new ZipArchive();
    
    if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
        // Add all CSV files to ZIP
        foreach (glob("$backupFolder/*.csv") as $csvFile) {
            $zip->addFile($csvFile, basename($csvFile));
        }
        $zip->close();
        
        // Remove individual CSV files (keep only ZIP)
        array_map('unlink', glob("$backupFolder/*.csv"));
        rmdir($backupFolder);
        
        $zipSize = round(filesize($zipFile) / 1024, 2);
    } else {
        $errors[] = "Failed to create ZIP archive";
        $zipFile = null;
        $zipSize = 0;
    }
    
    // Clean up old backups (keep only last 30 days)
    $retentionDays = (int)(getenv('BACKUP_RETENTION_DAYS') ?: 30);
    $oldBackups = glob("$backupDir/backup_*.zip");
    foreach ($oldBackups as $oldBackup) {
        if (filemtime($oldBackup) < strtotime("-$retentionDays days")) {
            unlink($oldBackup);
        }
    }
    
    // Prepare response
    $response = [
        'success' => count($errors) === 0,
        'message' => count($errors) === 0 ? 'Backup completed successfully' : 'Backup completed with errors',
        'timestamp' => date('Y-m-d H:i:s'),
        'database' => [
            'host' => $host,
            'database' => $dbname
        ],
        'backup' => [
            'folder' => basename($backupFolder),
            'zip_file' => basename($zipFile ?? 'N/A'),
            'zip_size' => $zipSize . ' KB',
            'tables_exported' => count($exportedTables),
            'retention_days' => $retentionDays
        ],
        'tables' => $exportedTables,
        'errors' => $errors
    ];
    
    http_response_code(200);
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Backup failed',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
