<?php
/**
 * Reports index page for the Library Management System
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

$reportService = new ReportService();

// Fetch sample data limited to 10 records for each report
$sampleBooks = $reportService->generateBooksReport(['limit' => 5]);
$sampleUsersRaw = $reportService->generateUsersReport(['limit' => 10]); // fetch more to filter out admins
$sampleUsers = ['users' => []];
if (!empty($sampleUsersRaw['users'])) {
    // Filter out users with role 'admin' or 'super-admin'
    $filteredUsers = array_filter($sampleUsersRaw['users'], function($user) {
        return !in_array($user['role'], ['admin', 'super-admin']);
    });
    // Limit to 5 records after filtering
    $sampleUsers['users'] = array_slice($filteredUsers, 0, 5);
}
$sampleTransactions = $reportService->generateTransactionsReport(['limit' => 5]);
// Penalties report method getPenaltiesForReports is undefined, so skip penalties sample
$samplePenalties = [];
$sampleBookBorrowingHistory = []; // No direct method to get list, skip for now

// Define available reports
$reports = [
    [
        'title' => 'Books Report',
        'description' => 'View detailed reports on books in the library.',
        'url' => APP_URL . '/public/reports/books.php',
        'sampleData' => $sampleBooks['books'] ?? []
    ],
    [
        'title' => 'Users Report',
        'description' => 'View detailed reports on users of the library system.',
        'url' => APP_URL . '/public/reports/users.php',
        'sampleData' => $sampleUsers['users'] ?? []
    ],
    [
        'title' => 'Transactions Report',
        'description' => 'View detailed reports on book transactions.',
        'url' => APP_URL . '/public/reports/transactions.php',
        'sampleData' => $sampleTransactions['transactions'] ?? []
    ],
    [
        'title' => 'Penalties Report',
        'description' => 'View detailed reports on penalties issued.',
        'url' => APP_URL . '/public/reports/penalties.php',
        'sampleData' => $samplePenalties
    ]
];

// Include header
include_once BASE_PATH . '/templates/layout/header.php';

// Include navbar
include_once BASE_PATH . '/templates/layout/navbar.php';

?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Reports</h1>
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

    <div class="row">
        <?php foreach ($reports as $report): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm d-flex flex-column">
                    <div class="card-body d-flex flex-column flex-grow-1">
                        <h5 class="card-title"><?= htmlspecialchars($report['title']) ?></h5>
                        <p class="card-text"><?= htmlspecialchars($report['description']) ?></p>
                        <a href="<?= $report['url'] ?>" class="btn btn-primary mt-auto mb-3">View Report</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="container mt-4" style="resize: both; overflow: auto; min-height: 200px; max-height: 600px; border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
        <div class="row">
            <?php foreach ($reports as $report): ?>
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm d-flex flex-column">
                        <div class="card-body d-flex flex-column flex-grow-1">
                            <h5 class="card-title"><?= htmlspecialchars($report['title']) ?> - Sample Records</h5>
                            <?php if (!empty($report['sampleData'])): ?>
                                <?php
                                // Render tables for each report type with appropriate columns
                                switch ($report['title']) {
                                    case 'Books Report':
                                        ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Title</th>
                                                        <th>Author</th>
                                                        <th>Category</th>
                                                        <th>Published Year</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($report['sampleData'] as $item): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($item['title'] ?? 'Unknown Title') ?></td>
                                                            <td><?= htmlspecialchars($item['author'] ?? 'Unknown Author') ?></td>
                                                            <td><?= htmlspecialchars($item['category_name'] ?? 'N/A') ?></td>
                                                            <td><?= htmlspecialchars($item['published_year'] ?? 'N/A') ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php
                                        break;
                                    case 'Users Report':
                                        ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Role</th>
                                                        <th>Registered On</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($report['sampleData'] as $item): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($item['name'] ?? 'Unknown User') ?></td>
                                                            <td><?= htmlspecialchars($item['email'] ?? 'N/A') ?></td>
                                                            <td><?= htmlspecialchars(ucfirst($item['role'] ?? 'N/A')) ?></td>
                                                            <td><?= htmlspecialchars(date('M d, Y', strtotime($item['created_at'] ?? ''))) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php
                                        break;
                                    case 'Transactions Report':
                                        ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Book Title</th>
                                                        <th>Borrower</th>
                                                        <th>Borrow Date</th>
                                                        <th>Return Date</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($report['sampleData'] as $item): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($item['book_title'] ?? 'Unknown Book') ?></td>
                                                            <td><?= htmlspecialchars($item['name'] ?? 'Unknown Borrower') ?></td>
                                                            <td><?= htmlspecialchars(date('M d, Y', strtotime($item['borrow_date'] ?? ''))) ?></td>
                                                            <td><?= $item['return_date'] ? htmlspecialchars(date('M d, Y', strtotime($item['return_date']))) : '-' ?></td>
                                                            <td><?= htmlspecialchars(ucfirst($item['status'] ?? 'N/A')) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <?php
                                        break;
                                    default:
                                        ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($report['sampleData'] as $item): ?>
                                                <li class="list-group-item">Record</li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php
                                }
                                ?>
                            <?php else: ?>
                                <p class="text-muted">No sample records available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';


// End output buffering and flush output
ob_end_flush();
?>
