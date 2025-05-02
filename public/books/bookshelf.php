<?php
/**
 * Bookshelf View - Visual browsing interface
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

// Initialize authentication
$session = new Session();
$auth = new AuthService();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    // Redirect to login page
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

// Get current user and role
$currentUser = $auth->getCurrentUser();

// Initialize book service
$bookService = new BookService();

// Get all books with categories
$books = $bookService->getAllBooksWithCategories();

// Get all categories for filter
$categories = $bookService->getAllCategoriesWithBookCount();

// Page title
$pageTitle = 'Bookshelf View';

// Include header
include_once '../../templates/layout/header.php';

// Include navbar
include_once '../../templates/layout/navbar.php';

// Include bookshelf template
include_once '../../templates/books/bookshelf.php';

// Include footer
include_once '../../templates/layout/footer.php';
