<?php
/**
 * Document Download from Archive
 * Secure download of archived documents
 */

// Centralized initialization
require_once '../../public/init.php';

// Enforce role-based access control
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier']);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die('Invalid document ID');
}

$archiveId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

// Initialize service
$documentArchive = new DocumentArchiveService();

// Get document for download
$documentInfo = $documentArchive->getDocumentForDownload($archiveId, $userId);

if ($documentInfo === false) {
    http_response_code(404);
    die('Document not found or not accessible: ' . $documentArchive->getErrorMessage());
}

// Verify file exists
if (!file_exists($documentInfo['file_path'])) {
    http_response_code(404);
    die('Document file not found in storage');
}

// Determine content type
$contentType = 'application/octet-stream';
$extension = strtolower(pathinfo($documentInfo['file_name'], PATHINFO_EXTENSION));

switch ($extension) {
    case 'pdf':
        $contentType = 'application/pdf';
        break;
    case 'doc':
        $contentType = 'application/msword';
        break;
    case 'docx':
        $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        break;
    case 'xls':
        $contentType = 'application/vnd.ms-excel';
        break;
    case 'xlsx':
        $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        break;
}

// Set headers for download
header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . $documentInfo['file_name'] . '"');
header('Content-Length: ' . $documentInfo['file_size']);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Output file
readfile($documentInfo['file_path']);
exit;
?>