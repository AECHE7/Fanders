<?php
/**
 * Books list page for the Library Management System
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

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    // Redirect to login page
    $session->setFlash('error', 'Please login to access this page.');
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

// Check for session timeout
if ($auth->checkSessionTimeout()) {
    // Session has timed out, redirect to login page with message
    $session->setFlash('error', 'Your session has expired. Please login again.');
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

// Get current user data
$user = $auth->getCurrentUser();
$userRole = $user['role_id'];

// Initialize book service
$bookService = new BookService();

// Process search if submitted
$searchTerm = '';
$categoryId = '';
$books = [];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
    $books = $bookService->searchBooks($searchTerm);
} elseif (isset($_GET['category_id']) && !empty($_GET['category_id'])) {
    $categoryId = (int)$_GET['category_id'];
    $books = $bookService->getBooksByCategory($categoryId);
} else {
    // Get all books
    $books = $bookService->getAllBooksWithCategories();
}

// Get all categories for the filter dropdown
$categories = $bookService->getAllCategories();

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Books</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <?php if ($userRole == ROLE_SUPER_ADMIN || $userRole == ROLE_ADMIN): ?>
                <div class="btn-group me-2">
                    <a href="<?= APP_URL ?>/public/books/add.php" class="btn btn-sm btn-outline-primary">
                        <i data-feather="plus"></i> Add New Book
                    </a>
                    <a href="<?= APP_URL ?>/public/reports/books.php" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="file-text"></i> Generate Report
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($session->hasFlash('success')): ?>
        <div class="alert alert-success">
            <?= $session->getFlash('success') ?>
        </div>
    <?php endif; ?>
    
    <?php if ($session->hasFlash('error')): ?>
        <div class="alert alert-danger">
            <?= $session->getFlash('error') ?>
        </div>
    <?php endif; ?>
    
    <!-- Search and Filter -->
    <div class="row mb-3">
        <div class="col-md-6">
            <form action="<?= $_SERVER['PHP_SELF'] ?>" method="get" class="d-flex">
                <input type="text" name="search" class="form-control me-2" placeholder="Search books..." value="<?= htmlspecialchars($searchTerm) ?>">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
        <div class="col-md-6">
            <form action="<?= $_SERVER['PHP_SELF'] ?>" method="get" class="d-flex">
                <select name="category_id" class="form-select me-2">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= $categoryId == $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-secondary">Filter</button>
            </form>
        </div>
    </div>
    
    <!-- Books List -->
    <?php include_once BASE_PATH . '/templates/books/list.php'; ?>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
