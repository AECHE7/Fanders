<?php
/**
 * Add user page for the Library Management System
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
$userRole = $user['role_id'];

// Check if user has permission to add users (Super Admin can add any user, Admin can only add borrowers)
if (!$auth->hasRole([ROLE_SUPER_ADMIN, ROLE_ADMIN])) {
    // Redirect to dashboard with error message
    $session->setFlash('error', 'You do not have permission to access this page.');
    header('Location: ' . APP_URL . '/public/dashboard.php');
    exit;
}

// Initialize user service
$userService = new UserService();

// Initialize password hash utility for generating random password
$passwordHash = new PasswordHash();

// Process form submission
$newUser = [
    'username' => '',
    'email' => '',
    'password' => '',
    'first_name' => '',
    'last_name' => '',
    'role_id' => $userRole == ROLE_SUPER_ADMIN ? '' : ROLE_BORROWER,
];

$error = '';
$formSubmitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formSubmitted = true;
    
    // Validate CSRF token
    if (!$csrf->validateRequest()) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        // Get form data
        $newUser = [
            'username' => isset($_POST['username']) ? trim($_POST['username']) : '',
            'email' => isset($_POST['email']) ? trim($_POST['email']) : '',
            'password' => isset($_POST['password']) ? $_POST['password'] : $passwordHash->generateRandomPassword(),
            'first_name' => isset($_POST['first_name']) ? trim($_POST['first_name']) : '',
            'last_name' => isset($_POST['last_name']) ? trim($_POST['last_name']) : '',
            'role_id' => $userRole == ROLE_SUPER_ADMIN && isset($_POST['role_id']) ? (int)$_POST['role_id'] : ROLE_BORROWER,
        ];
        
        // Admin can only add borrowers
        if ($userRole == ROLE_ADMIN && $newUser['role_id'] != ROLE_BORROWER) {
            $newUser['role_id'] = ROLE_BORROWER;
        }
        
        // Add the user
        $userId = $userService->addUser($newUser);
        
        if ($userId) {
            // User added successfully
            $session->setFlash('success', 'User added successfully.');
            header('Location: ' . APP_URL . '/public/users/index.php');
            exit;
        } else {
            // Failed to add user
            $error = $userService->getErrorMessage();
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
        <h1 class="h2">Add User</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= APP_URL ?>/public/users/index.php" class="btn btn-sm btn-outline-secondary">
                <i data-feather="arrow-left"></i> Back to Users
            </a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= $error ?>
        </div>
    <?php endif; ?>
    
    <?php if ($formSubmitted && !$error): ?>
        <div class="alert alert-success">
            User added successfully.
        </div>
    <?php endif; ?>
    
    <!-- User Form -->
    <div class="card">
        <div class="card-body">
            <?php include_once BASE_PATH . '/templates/users/form.php'; ?>
        </div>
    </div>
</main>

<?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';

// End output buffering and flush output
ob_end_flush();
?>
