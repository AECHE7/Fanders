<?php
/**
 * Bulk SLR ZIP Download
 * Generates multiple SLR documents and returns as ZIP file
 */

// Centralized initialization
require_once '../../public/init.php';

// Enforce role-based access control
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

if (!$csrf->validateRequest()) {
    http_response_code(403);
    die('Invalid security token');
}

$selectedLoans = $_POST['selected_loans'] ?? [];
if (empty($selectedLoans)) {
    http_response_code(400);
    die('No loans selected');
}

// Initialize service
$loanReleaseService = new LoanReleaseService();

// Generate bulk SLR as ZIP
$zipPath = $loanReleaseService->generateBulkSLRZip($selectedLoans, $_SESSION['user_id']);

if ($zipPath === false) {
    http_response_code(500);
    die('Failed to generate SLR documents: ' . $loanReleaseService->getErrorMessage());
}

// Send ZIP file for download
$filename = 'SLR_Bulk_' . count($selectedLoans) . '_loans_' . date('Ymd_His') . '.zip';

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($zipPath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Output file
readfile($zipPath);

// Clean up temporary file
unlink($zipPath);
exit;
?>