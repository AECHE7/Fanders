<?php
/**
 * API endpoint for book data
 * Used by the bookshelf visualization for fetching book details
 */

// Include configuration
require_once '../../app/config/config.php';

// Include required classes
require_once '../../app/core/Session.php';
require_once '../../app/services/AuthService.php';
require_once '../../app/utilities/PasswordHash.php';
require_once '../../app/core/Database.php';
require_once '../../app/core/BaseModel.php';
require_once '../../app/core/BaseService.php';
require_once '../../app/models/UserModel.php';
require_once '../../app/models/BookModel.php';
require_once '../../app/models/CategoryModel.php';
require_once '../../app/services/BookService.php';

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
