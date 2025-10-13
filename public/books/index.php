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
$userRole = $user['role'];

// Initialize book service
$bookService = new BookService();

// Initialize CSRF protection
$csrf = new CSRF();
$csrfToken = $csrf->generateToken();

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
    // Get all active books (not archived)
    $books = $bookService->getAllBooksWithCategories();
}

// Fetch archived books separately
$archivedBooks = $bookService->getArchivedBooks();

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
            <?php if ($userRole === 'super-admin' || $userRole === 'admin'): ?>
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
                    <option value="" <?= $categoryId == '' ? 'selected' : '' ?>>All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" <?= $categoryId == $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="1" <?= $categoryId == 'Fiction' ? 'selected' : '' ?>>Fiction</option>
                    <option value="2" <?= $categoryId == 'Non-Fiction' ? 'selected' : '' ?>>Non-Fiction</option>
                    <option value="3" <?= $categoryId == 'Science' ? 'selected' : '' ?>>Science</option>
                    <option value="4" <?= $categoryId == 'Technology' ? 'selected' : '' ?>>Technology</option>
                    <option value="5" <?= $categoryId == 'History' ? 'selected' : '' ?>>History</option>
                    <option value="6" <?= $categoryId == 'Philosophy' ? 'selected' : '' ?>>Philosophy</option>
                    <option value="7" <?= $categoryId == 'Art' ? 'selected' : '' ?>>Art</option>
                    <option value="8" <?= $categoryId == 'Reference' ? 'selected' : '' ?>>Reference</option>
                </select>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Books List -->
    <?php include_once BASE_PATH . '/templates/books/list.php'; ?>

    <?php if ($userRole === 'super-admin' || $userRole === 'admin'): ?>
    <!-- Archived Books List -->
    <?php include_once BASE_PATH . '/templates/books/archived.php'; ?>
    
    <?php endif; ?>

</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';


// // Debug - Add temporarily to check
// echo '<pre>';
// print_r($archivedBooks);
// echo '</pre>';

// End output buffering and flush output
ob_end_flush();
?>
