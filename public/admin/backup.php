<?php
/**
 * Database Backup Page
 * Allows admin to download database backup as Excel/CSV files
 */

session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: /public/login.php');
    exit;
}

// Get action
$action = $_GET['action'] ?? '';

// Handle backup download
if ($action === 'download') {
    $format = $_GET['format'] ?? 'excel';
    
    try {
        $db = getDB();
        
        // Get all tables
        $stmt = $db->query("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_type = 'BASE TABLE'
            ORDER BY table_name
        ");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if ($format === 'csv_zip') {
            // Create ZIP file with CSV for each table
            $timestamp = date('Y-m-d_His');
            $zipFilename = "fanders_backup_$timestamp.zip";
            $tempDir = sys_get_temp_dir() . "/backup_$timestamp";
            mkdir($tempDir, 0755, true);
            
            foreach ($tables as $table) {
                $csvFile = "$tempDir/{$table}.csv";
                $fp = fopen($csvFile, 'w');
                
                $stmt = $db->query("SELECT * FROM \"$table\"");
                $firstRow = true;
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if ($firstRow) {
                        fputcsv($fp, array_keys($row));
                        $firstRow = false;
                    }
                    fputcsv($fp, $row);
                }
                fclose($fp);
            }
            
            // Create ZIP
            $zipPath = sys_get_temp_dir() . "/$zipFilename";
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                foreach (glob("$tempDir/*.csv") as $csvFile) {
                    $zip->addFile($csvFile, basename($csvFile));
                }
                $zip->close();
            }
            
            // Clean up temp files
            array_map('unlink', glob("$tempDir/*.csv"));
            rmdir($tempDir);
            
            // Download ZIP
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);
            unlink($zipPath);
            exit;
            
        } else {
            // Generate HTML table that can be opened in Excel
            $timestamp = date('Y-m-d_His');
            $filename = "fanders_backup_$timestamp.xls";
            
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
            echo '<head><meta charset="UTF-8"></head>';
            echo '<body>';
            
            foreach ($tables as $table) {
                echo "<h2>Table: $table</h2>";
                echo '<table border="1">';
                
                $stmt = $db->query("SELECT * FROM \"$table\"");
                $firstRow = true;
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if ($firstRow) {
                        echo '<tr>';
                        foreach (array_keys($row) as $header) {
                            echo '<th>' . htmlspecialchars($header) . '</th>';
                        }
                        echo '</tr>';
                        $firstRow = false;
                    }
                    
                    echo '<tr>';
                    foreach ($row as $value) {
                        echo '<td>' . htmlspecialchars($value ?? '') . '</td>';
                    }
                    echo '</tr>';
                }
                
                echo '</table><br><br>';
            }
            
            echo '</body></html>';
            exit;
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Backup failed: ' . $e->getMessage();
        header('Location: /public/admin/backup.php');
        exit;
    }
}

// Get database statistics
try {
    $db = getDB();
    
    $stmt = $db->query("
        SELECT 
            schemaname,
            tablename,
            pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) AS size,
            (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = tablename) as columns
        FROM pg_tables
        WHERE schemaname = 'public'
        ORDER BY tablename
    ");
    $tableStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count rows for each table
    foreach ($tableStats as &$stat) {
        $countStmt = $db->query("SELECT COUNT(*) FROM \"{$stat['tablename']}\"");
        $stat['rows'] = $countStmt->fetchColumn();
    }
    
} catch (Exception $e) {
    $tableStats = [];
    $error = $e->getMessage();
}

$pageTitle = 'Database Backup';
include __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-database"></i> Database Backup & Export</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?php 
                            echo htmlspecialchars($_SESSION['error']); 
                            unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?php 
                            echo htmlspecialchars($_SESSION['success']); 
                            unset($_SESSION['success']);
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5>Export Database</h5>
                            <p class="text-muted">Download a complete backup of all database tables</p>
                            
                            <div class="btn-group" role="group">
                                <a href="?action=download&format=excel" class="btn btn-success">
                                    <i class="fas fa-file-excel"></i> Download as Excel (.xls)
                                </a>
                                <a href="?action=download&format=csv_zip" class="btn btn-info">
                                    <i class="fas fa-file-archive"></i> Download as CSV ZIP
                                </a>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Excel Format:</strong> Single file with all tables (can be opened in Excel/LibreOffice)<br>
                                <strong>CSV ZIP:</strong> Individual CSV files for each table in a ZIP archive
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h5>Database Tables</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Table Name</th>
                                    <th>Rows</th>
                                    <th>Columns</th>
                                    <th>Size</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($tableStats)): ?>
                                    <?php foreach ($tableStats as $stat): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($stat['tablename']); ?></code></td>
                                            <td><?php echo number_format($stat['rows']); ?></td>
                                            <td><?php echo $stat['columns']; ?></td>
                                            <td><?php echo htmlspecialchars($stat['size']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr class="font-weight-bold">
                                        <td>TOTAL</td>
                                        <td><?php echo number_format(array_sum(array_column($tableStats, 'rows'))); ?></td>
                                        <td colspan="2"><?php echo count($tableStats); ?> tables</td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">
                                            <?php echo isset($error) ? 'Error: ' . htmlspecialchars($error) : 'No tables found'; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>Important:</strong> 
                        <ul class="mb-0">
                            <li>Regular backups help protect against data loss</li>
                            <li>Store backup files in a secure location</li>
                            <li>Test backup restoration periodically</li>
                            <li>Consider automating backups for production systems</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../templates/footer.php'; ?>
