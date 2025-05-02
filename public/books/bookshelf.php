<?php
/**
 * Animated bookshelf visualization page
 * Displays books in a skeuomorphic bookshelf view
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

// Initialize book service
$bookService = new BookService();

// Get all books with category information
$books = $bookService->getAllBooksWithCategories();

// Get all categories for filtering
$categories = $bookService->getAllCategories();

// Define page title
$pageTitle = 'Bookshelf View';

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <!-- Page Header -->
    <div class="notion-page-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f7ecff;">
                        <i data-feather="book" style="width: 24px; height: 24px; color: #9d71ea;"></i>
                    </div>
                </div>
                <h1 class="notion-page-title mb-0">Library Bookshelf</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-sm btn-outline-secondary px-3">
                    <i data-feather="list" class="me-1" style="width: 14px; height: 14px;"></i> List View
                </a>
                <?php if ($auth->hasRole([ROLE_SUPER_ADMIN, ROLE_ADMIN])): ?>
                <a href="<?= APP_URL ?>/public/books/add.php" class="btn btn-sm btn-primary px-3">
                    <i data-feather="plus" class="me-1" style="width: 14px; height: 14px;"></i> Add Book
                </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="notion-divider my-3"></div>
    </div>
    
    <!-- Session Flash Messages -->
    <?php if ($session->hasFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $session->getFlash('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($session->hasFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $session->getFlash('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Bookshelf Visualization -->
    <?php include_once BASE_PATH . '/templates/books/bookshelf.php'; ?>
    
    <!-- Info Card at the bottom -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                    <i data-feather="info" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <h5 class="card-title mb-1">About the Bookshelf View</h5>
                    <p class="card-text text-muted mb-0">Browse our library in this interactive bookshelf visualization. Click on any book to see its details, or use the filters above to find specific books.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Add the bookshelf CSS -->
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/bookshelf.css">

<!-- Add JavaScript for APP_URL global variable -->
<script>
    const APP_URL = '<?= APP_URL ?>';
</script>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>