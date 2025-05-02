<?php
/**
 * Main entry point for the Library Management System
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
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Main content -->
            <main class="col-md-12 ms-sm-auto px-md-4 py-4">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <h1 class="mb-4"><?= APP_NAME ?></h1>
                            
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
                            
                            <?php if ($auth->isLoggedIn()): ?>
                                <!-- User is logged in, show dashboard link -->
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Welcome, <?= htmlspecialchars($session->get('user_first_name')) ?>!</h5>
                                        <p class="card-text">You are logged in to the Library Management System.</p>
                                        <a href="<?= APP_URL ?>/public/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- User is not logged in, show login/register options -->
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Welcome to the Library Management System</h5>
                                        <p class="card-text">Please login to access the system.</p>
                                        <a href="<?= APP_URL ?>/public/login.php" class="btn btn-primary">Login</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-4">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">Book Management</h5>
                                                <p class="card-text">Add, edit, and manage library books.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">User Management</h5>
                                                <p class="card-text">Manage library staff and borrowers.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card">
                                            <div class="card-body">
                                                <h5 class="card-title">Borrowing System</h5>
                                                <p class="card-text">Track book loans and returns.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

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
