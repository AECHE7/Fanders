<?php
/**
 * API endpoint for book data
 * Used by the bookshelf visualization for fetching book details
 */

// Include configuration
require_once '../../app/config/config.php';

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

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit;
}

// Check for book ID parameter
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Book ID is required'
    ]);
    exit;
}

$bookId = (int)$_GET['id'];

// Initialize book service
$bookService = new BookService();

// Get book data with category
try {
    $book = $bookService->getBookWithCategory($bookId);
    
    if (!$book) {
        // Book not found
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Book not found'
        ]);
        exit;
    }
    
    // Return success response with book data
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'book' => $book
    ]);
} catch (Exception $e) {
    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
