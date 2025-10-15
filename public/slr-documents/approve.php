<?php
/**
 * Approve SLR Document - Fanders Microfinance System
 */

// Include configuration
require_once '../../app/config/config.php';

// Start output buffering
ob_start();

// Include all required files
function autoload($className) {
    // Define the directories to look in
    $directories = [
        'app/core/',
        'app/models/',
        'app/services/',
        'app/utilities/'
    ];

    // Try to find the class file
    foreach ($directories as $directory) {
        $file = BASE_PATH . '/' . $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}

// Register autoloader
spl_autoload_register('autoload');

// Initialize session management
$session = new Session();

// Initialize authentication service
$auth = new AuthService();

// Initialize CSRF protection
$csrf = new CSRF();

// Set content type to JSON for AJAX response
header('Content-Type: application/json');

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to access this page.']);
    exit;
}

// Check for session timeout
if ($auth->checkSessionTimeout()) {
    echo json_encode(['success' => false, 'message' => 'Your session has expired. Please login again.']);
    exit;
}

// Get current user data
$user = $auth->getCurrentUser();
$userRole = $user['role'];

// Check if user has permission to approve SLR documents
if (!$auth->hasRole(['administrator', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to approve SLR documents.']);
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrf->validateToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please try again.']);
        exit;
    }

    $slrId = (int)($_POST['slr_id'] ?? 0);

    if (!$slrId) {
        echo json_encode(['success' => false, 'message' => 'Invalid SLR document ID.']);
        exit;
    }

    // Initialize SLR document service
    $slrDocumentService = new SlrDocumentService();

    // Get SLR document details
    $slrDetails = $slrDocumentService->getSlrDetails($slrId);
    if (!$slrDetails) {
        echo json_encode(['success' => false, 'message' => 'SLR document not found.']);
        exit;
    }

    $slr = $slrDetails['slr'];

    if ($slr['status'] !== 'draft') {
        echo json_encode(['success' => false, 'message' => 'Only draft SLR documents can be approved.']);
        exit;
    }

    // Approve the SLR document
    $success = $slrDocumentService->updateSlrDocument($slrId, [
        'status' => 'approved',
        'approved_by' => $user['id'],
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'SLR document approved successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve SLR document: ' . $slrDocumentService->getLastError()]);
    }
    exit;
} else {
    // Invalid request method
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}
?>
