<?php
/**
 * Add new book page for the Library Management System
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

// Check if user has permission to add books (Super Admin or Admin)
if (!$auth->hasRole([ROLE_SUPER_ADMIN, ROLE_ADMIN])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Get current user data
$user = $auth->getCurrentUser();

// Initialize book service
$bookService = new BookService();

// Get all categories for the dropdown
$categories = $bookService->getAllCategories();

// Process form submission
$book = [
    'title' => '',
    'author' => '',
    'isbn' => '',
    'description' => '',
    'publication_year' => '',
    'publisher' => '',
    'category_id' => '',
    'total_copies' => 1,
    'available_copies' => 1
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrf->validateRequest()) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Get form data
        $book = [
            'title' => isset($_POST['title']) ? trim($_POST['title']) : '',
            'author' => isset($_POST['author']) ? trim($_POST['author']) : '',
            'isbn' => isset($_POST['isbn']) ? trim($_POST['isbn']) : '',
            'description' => isset($_POST['description']) ? trim($_POST['description']) : '',
            'publication_year' => isset($_POST['publication_year']) ? trim($_POST['publication_year']) : '',
            'publisher' => isset($_POST['publisher']) ? trim($_POST['publisher']) : '',
            'category_id' => isset($_POST['category_id']) ? (int)$_POST['category_id'] : '',
            'total_copies' => isset($_POST['total_copies']) ? (int)$_POST['total_copies'] : 1,
            'available_copies' => isset($_POST['available_copies']) ? (int)$_POST['available_copies'] : 1
        ];
        
        // Add the book
        $bookId = $bookService->addBook($book, $user['id']);
        
        if ($bookId) {
            // Book added successfully
            $session->setFlash('success', 'Book added successfully.');
            header('Location: ' . APP_URL . '/public/books/index.php');
            exit;
        } else {
            // Failed to add book
            $error = $bookService->getErrorMessage();
        }
    }
}

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Add New Book</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-sm btn-outline-secondary">
                <i data-feather="arrow-left"></i> Back to Books
            </a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <!-- Book Form -->
    <div class="card">
        <div class="card-body">
            <?php include_once BASE_PATH . '/templates/books/form.php'; ?>
        </div>
    </div>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
