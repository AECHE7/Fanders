<?php
/**
 * Main entry point for the Library Management System
 * Notion-inspired design
 */

// Include configuration
require_once '../app/config/config.php';

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

// Check for session timeout
if ($auth->checkSessionTimeout()) {
    // Session has timed out, redirect to login page with message
    $session->setFlash('error', 'Your session has expired. Please login again.');
    header('Location: ' . APP_URL . '/public/login.php');
    exit;
}

// Main content
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/favicon.ico" type="image/x-icon">
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg py-3 border-bottom">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= APP_URL ?>/public/index.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-book me-2">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
                <span class="fw-semibold"><?= APP_NAME ?></span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if ($auth->isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/public/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="d-inline-flex align-items-center">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                    <span class="fw-bold"><?= substr($session->get('user_first_name'), 0, 1) . substr($session->get('user_last_name'), 0, 1) ?></span>
                                </div>
                                <span><?= htmlspecialchars($session->get('user_first_name')) ?></span>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?= APP_URL ?>/public/dashboard.php">Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= APP_URL ?>/public/logout.php">Sign out</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/public/login.php">Sign in</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="py-5 text-center" style="background-color: var(--notion-light-gray);">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <?php if ($session->hasFlash('success')): ?>
                        <div class="alert alert-success mb-4">
                            <?= $session->getFlash('success') ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($session->hasFlash('error')): ?>
                        <div class="alert alert-danger mb-4">
                            <?= $session->getFlash('error') ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($auth->isLoggedIn()): ?>
                    <!-- User is logged in, show welcome message -->
                    <div class="notion-page-title">Welcome back, <?= htmlspecialchars($session->get('user_first_name')) ?>!</div>
                    <p class="lead mb-4">Continue managing your library resources and helping readers find their next great book.</p>
                    <a href="<?= APP_URL ?>/public/dashboard.php" class="btn btn-primary btn-lg px-4 me-2">Go to Dashboard</a>
                    <?php else: ?>
                    <!-- User is not logged in, show hero content -->
                    <div class="notion-page-title">Modern Library Management</div>
                    <p class="lead mb-4">A comprehensive solution for managing books, borrowers, and transactions with a clean, intuitive interface.</p>
                    <a href="<?= APP_URL ?>/public/login.php" class="btn btn-primary btn-lg px-4">Sign In</a>
                    <p class="small text-muted mt-3">Default login: admin / admin123</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="fw-bold text-center mb-5">Key Features</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle mb-3" style="width: 50px; height: 50px;">
                                <i data-feather="book" style="width: 24px; height: 24px;"></i>
                            </div>
                            <h5 class="card-title fw-bold">Book Management</h5>
                            <p class="card-text text-muted">Comprehensive catalog system to add, edit, categorize and track all books in your library collection.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle mb-3" style="width: 50px; height: 50px;">
                                <i data-feather="users" style="width: 24px; height: 24px;"></i>
                            </div>
                            <h5 class="card-title fw-bold">User Management</h5>
                            <p class="card-text text-muted">Multi-level user roles with Super Admin, Admin, and Borrower permissions for effective system administration.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle mb-3" style="width: 50px; height: 50px;">
                                <i data-feather="repeat" style="width: 24px; height: 24px;"></i>
                            </div>
                            <h5 class="card-title fw-bold">Borrowing System</h5>
                            <p class="card-text text-muted">Efficient book lending and return processing with automatic due date calculations and penalty management.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Dashboard Preview Section -->
    <section class="py-5" style="background-color: var(--notion-light-gray);">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="fw-bold mb-3">Intuitive Dashboard</h2>
                    <p class="mb-4">Access all key information at a glance with our Notion-inspired interface. Manage your library efficiently with clear statistics and quick action buttons for common tasks.</p>
                    <div class="notion-callout mb-4">
                        <div class="notion-callout-icon">
                            <i data-feather="info"></i>
                        </div>
                        <div>
                            <strong>Role-Based Access Control</strong><br>
                            Different user roles see tailored dashboards with appropriate access levels and functionality.
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= APP_URL ?>/public/login.php" class="btn btn-primary">Try It Out</a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card border-0 shadow">
                        <div class="card-body p-0">
                            <div class="rounded" style="background-color: var(--notion-bg); height: 300px; display: flex; align-items: center; justify-content: center;">
                                <div class="text-center p-4">
                                    <h4 class="mb-3">Dashboard Preview</h4>
                                    <div class="d-flex justify-content-center gap-3 mb-4">
                                        <div class="p-3 rounded" style="background-color: var(--notion-light-blue); width: 80px; text-align: center;">
                                            <div class="fw-bold">125</div>
                                            <div class="small">Books</div>
                                        </div>
                                        <div class="p-3 rounded" style="background-color: var(--notion-light-gray); width: 80px; text-align: center;">
                                            <div class="fw-bold">43</div>
                                            <div class="small">Users</div>
                                        </div>
                                        <div class="p-3 rounded" style="background-color: var(--notion-light-gray); width: 80px; text-align: center;">
                                            <div class="fw-bold">18</div>
                                            <div class="small">Loans</div>
                                        </div>
                                    </div>
                                    <div class="d-grid">
                                        <button class="btn btn-sm btn-outline-secondary" disabled>Interactive Demo</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 border-top">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">&copy; <?= date('Y') ?> <?= APP_NAME ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0 text-muted">Made with <i data-feather="heart" class="text-danger" style="width: 16px; height: 16px;"></i> for Libraries</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="<?= APP_URL ?>/assets/js/main.js"></script>
    <script>
        // Initialize feather icons
        feather.replace();
    </script>
</body>
</html>

<?php
// End output buffering and flush output
ob_end_flush();
?>
