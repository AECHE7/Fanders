<?php
/**
 * Automated Backup Cron Endpoint
 * This endpoint triggers the database backup script when called
 * 
 * Security: Protected by secret token
 * Usage: /cron/backup.php?token=YOUR_SECRET_TOKEN
 */

// Security: Check for secret token
$token = $_GET['token'] ?? '';
$expectedToken = getenv('CRON_SECRET_TOKEN') ?: 'change-this-secret-token';

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

// Execute the backup script
$backupScript = '/app/scripts/backup_database.sh';
$output = [];
$returnVar = 0;

// Run the backup script
exec("bash $backupScript 2>&1", $output, $returnVar);

// Prepare response
$response = [
    'success' => $returnVar === 0,
    'message' => $returnVar === 0 ? 'Backup completed successfully' : 'Backup failed',
    'timestamp' => date('Y-m-d H:i:s'),
    'output' => implode("\n", $output),
    'exit_code' => $returnVar
];

// Return JSON response
http_response_code($returnVar === 0 ? 200 : 500);
echo json_encode($response, JSON_PRETTY_PRINT);
