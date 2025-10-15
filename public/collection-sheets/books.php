<?php
/**
 * Books report page for the Library Management System
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
$userRole = $user['role'];

// Check if user has permission to generate reports (Super Admin or Admin)
if (!$auth->hasRole(['super-admin', 'admin'])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Initialize services
$bookService = new BookService();
$reportService = new ReportService();

// Get all categories for the filter dropdown
$categories = $bookService->getAllCategories();

// Process filter form
$filters = [];
$generatePdf = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $csrf->validateRequest()) {
    // Get filters
    $filters['category_id'] = isset($_POST['category_id']) && !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $filters['availability'] = isset($_POST['availability']) && $_POST['availability'] !== '' ? (int)$_POST['availability'] : null;
    
    // Check if PDF generation is requested
    $generatePdf = isset($_POST['generate_pdf']) && $_POST['generate_pdf'] == 1;
    
    if ($generatePdf) {
        // Generate PDF
        $reportService->generateBooksReport($filters, true);
        exit; // PDF is output directly
    }
}

// Generate the report with applied filters
$reportData = $reportService->generateBooksReport($filters);

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Books Report</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/reports/index.php" class="btn btn-sm btn-outline-primary me-2">
                <i data-feather="list"></i> Back to Reports
            </a>
            <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-sm btn-outline-secondary">
                <i data-feather="arrow-left"></i> Back to Books
            </a>
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
    
    <!-- Report Filter Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Report Filters</h5>
        </div>
        <div class="card-body">
            <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
                <?= $csrf->getTokenField() ?>
                
                <div class="row mb-3">
                    <!-- <div class="col-md-4">
                        <label for="category_id" class="form-label">Category</label>
                        <select name="category_id" id="category_id" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= isset($filters['category_id']) && $filters['category_id'] == $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div> -->
                    
                    <div class="col-md-4">
                        <label for="availability" class="form-label">Availability</label>
                        <select name="availability" id="availability" class="form-select">
                            <option value="">All Books</option>
                            <option value="1" <?= isset($filters['availability']) && $filters['availability'] == 1 ? 'selected' : '' ?>>Available</option>
                            <option value="0" <?= isset($filters['availability']) && $filters['availability'] == 0 ? 'selected' : '' ?>>Not Available</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" name="apply_filters" class="btn btn-primary me-2">Apply Filters</button>
                        <button type="submit" name="generate_pdf" value="1" class="btn btn-success">
                            <i data-feather="file-text"></i> Generate PDF
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Report Results -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Books Report</h5>
            <small class="text-muted">Generated on: <?= $reportData['generated_date'] ?></small>
        </div>
        <div class="card-body">
            <?php if (empty($reportData['books'])): ?>
                <div class="alert alert-info">
                    No books found matching the selected criteria.
                </div>
            <?php else: ?>
                <div class="mb-3">
                    <strong>Total Books:</strong> <?= count($reportData['books']) ?>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Copies</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            ?>
                            <?php foreach ($reportData['books'] as $book): ?>
                                <tr>
                                    <td><?= $i ?></td>
                                    <td><?= htmlspecialchars($book['title'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($book['author'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($book['category_name'] ?? '') ?></td>
                                    <td><?= ($book['available_copies'] ?? '0') ?>/<?= ($book['total_copies'] ?? '0') ?></td>
                                    <td>
                                        <?php if (isset($book['is_available']) && $book['is_available']): ?>
                                            <span class="badge bg-success">Available</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Not Available</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                                $i++;
                                ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
