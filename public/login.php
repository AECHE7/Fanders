<?php
/**
 * Login page for the Library Management System
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

// Initialize CSRF protection
$csrf = new CSRF();

// Initialize authentication service
$auth = new AuthService();

// Check if user is already logged in
if ($auth->isLoggedIn()) {
    // Redirect to dashboard
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Process login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$csrf->validateRequest()) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Get form data
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        // Validate form data
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            // Attempt to log in
            if ($auth->login($username, $password)) {
                // Login successful, redirect to dashboard
                header('Location: ' . APP_URL . '/public/dashboard.php');
                exit;
            } else {
                // Login failed
                $error = $auth->getErrorMessage();
            }
        }
    }
}

// Main content
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= APP_URL ?>/assets/favicon.ico" type="image/x-icon">
</head>
<body style="background-color: var(--notion-light-gray);">
    <div class="container">
        <div class="row justify-content-center vh-100 align-items-center">
            <div class="col-md-5 col-lg-4">
                <!-- Logo and brand at top -->
                <div class="text-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-book">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                    <h3 class="mt-3 mb-4"><?= APP_NAME ?></h3>
                </div>
                
                <!-- Login card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold mb-4">Sign in</h5>
                        
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
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?= $error ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post" class="notion-form needs-validation" novalidate>
                            <?= $csrf->getTokenField() ?>
                            
                            <div class="notion-form-group mb-4">
                                <label for="username" class="notion-form-label">Username</label>
                                <div class="d-flex align-items-center notion-form-control-wrapper">
                                    <div class="rounded d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; background-color: #edf2fc;">
                                        <i data-feather="user" style="width: 14px; height: 14px; color: #0b76ef;"></i>
                                    </div>
                                    <input type="text" class="notion-form-control" id="username" name="username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required autofocus placeholder="Enter your username">
                                </div>
                                <div class="invalid-feedback">Please enter your username.</div>
                            </div>
                            
                            <div class="notion-form-group mb-4">
                                <label for="password" class="notion-form-label">Password</label>
                                <div class="d-flex align-items-center position-relative notion-form-control-wrapper">
                                    <div class="rounded d-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; background-color: #f7ecff;">
                                        <i data-feather="lock" style="width: 14px; height: 14px; color: #9d71ea;"></i>
                                    </div>
                                    <input type="password" class="notion-form-control" id="password" name="password" required placeholder="Enter your password">
                                    <button type="button" class="btn btn-link password-toggle position-absolute end-0 me-2" style="z-index: 5; background: none; border: none; padding: 0;">
                                        <i data-feather="eye" style="width: 16px; height: 16px; color: #6b7280;"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">Please enter your password.</div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="notion-form-toggle">
                                    <input type="checkbox" value="1" id="rememberMe" name="remember_me">
                                    <span class="notion-form-toggle-slider"></span>
                                    <span class="ms-2 position-relative" style="top: -6px;">Remember me</span>
                                </label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary py-2 px-4 login-pulse-button">
                                    <i data-feather="log-in" class="me-2" style="width: 16px; height: 16px;"></i> Sign in
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Home link -->
                <div class="text-center mt-4">
                    <a href="<?= APP_URL ?>/public/index.php" class="text-decoration-none">
                        <i data-feather="arrow-left" style="width: 16px; height: 16px;"></i> Back to Home
                    </a>
                </div>
                
                <!-- Footer info -->
                <div class="text-center mt-5 small text-muted">
                    <p>Default login: admin / admin123</p>
                    <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="<?= APP_URL ?>/assets/js/main.js"></script>
    <style>
        /* Login-specific styles */
        .login-pulse-button {
            position: relative;
            overflow: hidden;
            transform: translate3d(0, 0, 0);
        }
        
        .login-pulse-button:after {
            content: "";
            display: block;
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            background-image: radial-gradient(circle, #fff 10%, transparent 10.01%);
            background-repeat: no-repeat;
            background-position: 50%;
            transform: scale(10, 10);
            opacity: 0;
            transition: transform .5s, opacity 1s;
        }
        
        .login-pulse-button:active:after {
            transform: scale(0, 0);
            opacity: .3;
            transition: 0s;
        }
        
        .notion-form-control-wrapper {
            position: relative;
            border: 1px solid var(--notion-border);
            border-radius: 4px;
            background-color: var(--notion-bg);
            transition: all 0.2s;
            padding: 0 8px;
        }
        
        .notion-form-control-wrapper:focus-within {
            border-color: var(--notion-blue);
            box-shadow: 0 0 0 2px rgba(11, 118, 239, 0.2);
        }
        
        .notion-form-control-wrapper .notion-form-control {
            border: none;
            box-shadow: none;
            background: transparent;
            flex: 1;
        }
        
        .notion-form-control-wrapper .notion-form-control:focus {
            outline: none;
            box-shadow: none;
        }
    </style>

    <script>
        // Initialize feather icons
        feather.replace();
        
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            const form = document.querySelector('.notion-form');
            if (form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            }
            
            // Interactive field focusing effects
            const notionFormControls = document.querySelectorAll('.notion-form-control');
            notionFormControls.forEach(control => {
                // Add focus class to parent
                control.addEventListener('focus', function() {
                    this.closest('.notion-form-group').classList.add('is-focused');
                });
                
                // Remove focus class on blur
                control.addEventListener('blur', function() {
                    this.closest('.notion-form-group').classList.remove('is-focused');
                });
            });
            
            // Password toggle functionality
            const passwordToggle = document.querySelector('.password-toggle');
            const passwordInput = document.querySelector('#password');
            
            if (passwordToggle && passwordInput) {
                let passwordVisible = false;
                
                passwordToggle.addEventListener('click', function() {
                    if (passwordVisible) {
                        passwordInput.type = 'password';
                        this.innerHTML = '<i data-feather="eye" style="width: 16px; height: 16px; color: #6b7280;"></i>';
                        this.title = 'Show password';
                    } else {
                        passwordInput.type = 'text';
                        this.innerHTML = '<i data-feather="eye-off" style="width: 16px; height: 16px; color: #6b7280;"></i>';
                        this.title = 'Hide password';
                    }
                    passwordVisible = !passwordVisible;
                    feather.replace();
                });
            }
        });
    </script>
</body>
</html>

<?php
// End output buffering and flush output
ob_end_flush();
?>
