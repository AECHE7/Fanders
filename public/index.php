<?php
/**
 * Main entry point for the Library Management System
 * Notion-inspired design
 */

// Start output buffering immediately to prevent headers sent issues
ob_start();

// Include configuration
require_once '../app/config/config.php';

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
    header('Location: ' . APP_URL . '/public/auth/login.php');
    exit;
}

// Get current user and role (safe defaulting)
$currentUser = $auth->getCurrentUser();

// Use 'role' string directly
$userRole = isset($currentUser['role']) ? $currentUser['role'] : '';
// For display, format role if needed
$roleName = isset($currentUser['role_display']) ? $currentUser['role_display'] : ucfirst(str_replace('-', ' ', $userRole));

// Parse first and last name from 'name'
$fullName = isset($currentUser['name']) ? $currentUser['name'] : '';
$nameParts = explode(' ', $fullName, 2);
$firstName = $nameParts[0] ?? '';
$lastName = $nameParts[1] ?? '';
$initials = ($firstName ? substr($firstName, 0, 1) : '') . ($lastName ? substr($lastName, 0, 1) : '');

// For username, prefer 'username', fallback to 'email'
$usernameDisplay = $currentUser['username'] ?? ($currentUser['email'] ?? '');

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
    <link rel="icon" href="../public/assets/favicon.png" type="image/png">
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="navbar navbar-expand-lg py-3 border-bottom">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= APP_URL ?>/public/index.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-dollar-sign me-2">
                    <path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <span class="fw-semibold"><?= APP_NAME ?></span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if ($auth->isLoggedIn()): ?>
                    <!-- User is logged in -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle px-3 d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                    <span class="fw-bold"><?= htmlspecialchars($initials) ?></span>
                                </div>
                                <span class="d-none d-md-inline"><?= htmlspecialchars($firstName) ?></span>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown" style="z-index: 1050; position: absolute;">
                            <li class="dropdown-header">
                            <i data-feather="user" class="me-2" style="width: 16px; height: 16px;"></i>Signed in as<br/><strong><?= htmlspecialchars($usernameDisplay) ?></strong></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><span class="dropdown-item-text"><span class="badge bg-secondary">
                            <i data-feather="user-check" class="me-2" style="width: 16px; height: 16px;"></i><?= $roleName ?></span></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= APP_URL ?>/public/users/view.php?id=<?= $currentUser['id'] ?>">
                                <i data-feather="settings" class="me-2" style="width: 16px; height: 16px;"></i> Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= APP_URL ?>/public/auth/logout.php">
                            <i data-feather="log-out" class="me-2" style="width: 16px; height: 16px;"></i>Sign out</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/public/auth/login.php">Sign in</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Enhanced Onboarding -->
    <section class="hero-section py-5 text-center position-relative overflow-hidden" style="background: linear-gradient(135deg, var(--notion-light-gray) 0%, var(--notion-bg) 100%);">
        <!-- Background Pattern -->
        <div class="hero-pattern position-absolute top-0 start-0 w-100 h-100 opacity-10">
            <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="heroPattern" x="0" y="0" width="60" height="60" patternUnits="userSpaceOnUse">
                        <circle cx="30" cy="30" r="1.5" fill="var(--notion-blue)"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#heroPattern)"/>
            </svg>
        </div>

        <div class="container py-5 position-relative">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <?php if ($session->hasFlash('success')): ?>
                        <div class="alert alert-success mb-4 shadow-sm">
                            <i data-feather="check-circle" class="me-2"></i>
                            <?= $session->getFlash('success') ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($session->hasFlash('error')): ?>
                        <div class="alert alert-danger mb-4 shadow-sm">
                            <i data-feather="alert-circle" class="me-2"></i>
                            <?= $session->getFlash('error') ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($auth->isLoggedIn()): ?>
                    <!-- Enhanced Welcome Back for Logged-in Users -->
                    <div class="welcome-back-section">
                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <div class="welcome-avatar me-3">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                    <?= htmlspecialchars(substr($firstName, 0, 1) . substr($lastName, 0, 1)) ?>
                                </div>
                            </div>
                            <div class="text-start">
                                <h1 class="notion-page-title mb-1">Welcome back, <?= htmlspecialchars($firstName) ?>!</h1>
                                <p class="text-muted mb-0">Ready to continue managing your loans?</p>
                            </div>
                        </div>

                        <!-- Quick Stats Overview -->
                        <div class="quick-stats row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="stat-card card border-0 shadow-sm h-100">
                                    <div class="card-body text-center p-3">
                                        <div class="stat-icon mb-2">
                                            <i data-feather="dollar-sign" class="text-primary" style="width: 24px; height: 24px;"></i>
                                        </div>
                                        <div class="stat-number fw-bold text-primary">125</div>
                                        <div class="stat-label small text-muted">Active Loans</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card card border-0 shadow-sm h-100">
                                    <div class="card-body text-center p-3">
                                        <div class="stat-icon mb-2">
                                            <i data-feather="users" class="text-success" style="width: 24px; height: 24px;"></i>
                                        </div>
                                        <div class="stat-number fw-bold text-success">43</div>
                                        <div class="stat-label small text-muted">Total Clients</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card card border-0 shadow-sm h-100">
                                    <div class="card-body text-center p-3">
                                        <div class="stat-icon mb-2">
                                            <i data-feather="trending-up" class="text-info" style="width: 24px; height: 24px;"></i>
                                        </div>
                                        <div class="stat-number fw-bold text-info">98.5%</div>
                                        <div class="stat-label small text-muted">On-Time Payments</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card card border-0 shadow-sm h-100">
                                    <div class="card-body text-center p-3">
                                        <div class="stat-icon mb-2">
                                            <i data-feather="calendar" class="text-warning" style="width: 24px; height: 24px;"></i>
                                        </div>
                                        <div class="stat-number fw-bold text-warning">7</div>
                                        <div class="stat-label small text-muted">Due This Week</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="quick-actions-section mb-4">
                            <h5 class="text-center mb-3">Quick Actions</h5>
                            <div class="row g-2 justify-content-center">
                                <div class="col-auto">
                                    <a href="<?= APP_URL ?>/public/loans/add.php" class="btn btn-outline-primary btn-sm px-3">
                                        <i data-feather="plus-circle" class="me-1" style="width: 16px; height: 16px;"></i>
                                        New Loan
                                    </a>
                                </div>
                                <div class="col-auto">
                                    <a href="<?= APP_URL ?>/public/clients/add.php" class="btn btn-outline-success btn-sm px-3">
                                        <i data-feather="user-plus" class="me-1" style="width: 16px; height: 16px;"></i>
                                        Add Client
                                    </a>
                                </div>
                                <div class="col-auto">
                                    <a href="<?= APP_URL ?>/public/payments/add.php" class="btn btn-outline-info btn-sm px-3">
                                        <i data-feather="credit-card" class="me-1" style="width: 16px; height: 16px;"></i>
                                        Record Payment
                                    </a>
                                </div>
                                <div class="col-auto">
                                    <a href="<?= APP_URL ?>/public/reports/index.php" class="btn btn-outline-secondary btn-sm px-3">
                                        <i data-feather="bar-chart-2" class="me-1" style="width: 16px; height: 16px;"></i>
                                        View Reports
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <a href="<?= APP_URL ?>/public/dashboard/index.php" class="btn btn-primary btn-lg px-4 me-2">
                                <i data-feather="layout" class="me-2" style="width: 20px; height: 20px;"></i>
                                Go to Dashboard
                            </a>
                            <button class="btn btn-outline-primary btn-lg px-4" onclick="startOnboardingTour()">
                                <i data-feather="help-circle" class="me-2" style="width: 20px; height: 20px;"></i>
                                Take a Quick Tour
                            </button>
                        </div>
                    </div>

                    <?php else: ?>
                    <!-- Enhanced Onboarding for New/Guest Users -->
                    <div class="guest-onboarding">
                        <!-- Onboarding Progress Indicator -->
                        <div class="onboarding-progress mb-4 d-none" id="onboardingProgress">
                            <div class="progress mb-2" style="height: 4px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 0%" id="progressBar"></div>
                            </div>
                            <small class="text-muted" id="progressText">Step 1 of 4: Getting Started</small>
                        </div>

                        <div class="notion-page-title">
                            <h1>Welcome to Fanders</h1>
                            <p class="text-muted">Your Complete Loan Management Solution</p>
                        </div>

                        <p class="lead mb-4">
                            Streamline your loan operations with our intuitive platform designed for modern microfinance institutions.
                            Manage clients, track payments, and generate reports with ease.
                        </p>

                        <!-- Onboarding Steps -->
                        <div class="onboarding-steps row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="step-card card border-0 shadow-sm h-100 text-center p-3" data-step="1">
                                    <div class="step-number bg-primary text-white rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold;">1</div>
                                    <h6 class="mb-2">Sign Up</h6>
                                    <p class="small text-muted mb-0">Create your account and set up your profile</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="step-card card border-0 shadow-sm h-100 text-center p-3" data-step="2">
                                    <div class="step-number bg-primary text-white rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold;">2</div>
                                    <h6 class="mb-2">Add Clients</h6>
                                    <p class="small text-muted mb-0">Import or manually add your client database</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="step-card card border-0 shadow-sm h-100 text-center p-3" data-step="3">
                                    <div class="step-number bg-primary text-white rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold;">3</div>
                                    <h6 class="mb-2">Create Loans</h6>
                                    <p class="small text-muted mb-0">Set up loan products and start lending</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="step-card card border-0 shadow-sm h-100 text-center p-3" data-step="4">
                                    <div class="step-number bg-primary text-white rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold;">4</div>
                                    <h6 class="mb-2">Track & Report</h6>
                                    <p class="small text-muted mb-0">Monitor performance and generate insights</p>
                                </div>
                            </div>
                        </div>

                        <!-- Feature Highlights -->
                        <div class="feature-highlights mb-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="feature-card card border-0 shadow-sm h-100">
                                        <div class="card-body text-center p-3">
                                            <i data-feather="shield" class="text-success mb-2" style="width: 32px; height: 32px;"></i>
                                            <h6>Secure & Compliant</h6>
                                            <p class="small text-muted">Bank-level security with full regulatory compliance</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="feature-card card border-0 shadow-sm h-100">
                                        <div class="card-body text-center p-3">
                                            <i data-feather="zap" class="text-warning mb-2" style="width: 32px; height: 32px;"></i>
                                            <h6>Lightning Fast</h6>
                                            <p class="small text-muted">Optimized performance for large-scale operations</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="feature-card card border-0 shadow-sm h-100">
                                        <div class="card-body text-center p-3">
                                            <i data-feather="headphones" class="text-info mb-2" style="width: 32px; height: 32px;"></i>
                                            <h6>24/7 Support</h6>
                                            <p class="small text-muted">Dedicated support team available around the clock</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="cta-section">
                            <a href="<?= APP_URL ?>/public/auth/login.php" class="btn btn-primary btn-lg px-4 me-3">
                                <i data-feather="log-in" class="me-2" style="width: 20px; height: 20px;"></i>
                                Sign In
                            </a>
                            <button class="btn btn-outline-primary btn-lg px-4" onclick="startGuestTour()">
                                <i data-feather="play-circle" class="me-2" style="width: 20px; height: 20px;"></i>
                                Take a Tour
                            </button>
                        </div>

                        <!-- Trust Indicators -->
                        <div class="trust-indicators mt-4 pt-4 border-top">
                            <small class="text-muted d-block mb-2">Trusted by microfinance institutions worldwide</small>
                            <div class="d-flex justify-content-center align-items-center gap-4">
                                <div class="trust-stat">
                                    <span class="fw-bold text-primary">500+</span>
                                    <small class="text-muted ms-1">Institutions</small>
                                </div>
                                <div class="trust-stat">
                                    <span class="fw-bold text-success">2M+</span>
                                    <small class="text-muted ms-1">Loans Managed</small>
                                </div>
                                <div class="trust-stat">
                                    <span class="fw-bold text-info">99.9%</span>
                                    <small class="text-muted ms-1">Uptime</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced Features Section with Interactive Elements -->
    <section class="features-section py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold mb-3">Powerful Features for Modern Microfinance</h2>
                <p class="lead text-muted">Everything you need to manage loans efficiently and scale your operations</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card card h-100 border-0 shadow-sm hover-lift">
                        <div class="card-body p-4 text-center">
                            <div class="feature-icon-wrapper mb-4">
                                <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                                    <i data-feather="dollar-sign" class="text-primary" style="width: 32px; height: 32px;"></i>
                                </div>
                            </div>
                            <h5 class="card-title fw-bold mb-3">Advanced Loan Management</h5>
                            <p class="card-text text-muted mb-3">Complete loan lifecycle management with automated calculations, flexible terms, and comprehensive tracking.</p>
                            <div class="feature-benefits">
                                <small class="text-muted d-block mb-1">✓ Automated interest calculations</small>
                                <small class="text-muted d-block mb-1">✓ Flexible repayment schedules</small>
                                <small class="text-muted d-block">✓ Real-time loan status updates</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card card h-100 border-0 shadow-sm hover-lift">
                        <div class="card-body p-4 text-center">
                            <div class="feature-icon-wrapper mb-4">
                                <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                                    <i data-feather="users" class="text-success" style="width: 32px; height: 32px;"></i>
                                </div>
                            </div>
                            <h5 class="card-title fw-bold mb-3">Smart Client Management</h5>
                            <p class="card-text text-muted mb-3">Comprehensive client profiles with credit scoring, document management, and relationship tracking.</p>
                            <div class="feature-benefits">
                                <small class="text-muted d-block mb-1">✓ Automated credit scoring</small>
                                <small class="text-muted d-block mb-1">✓ Document verification</small>
                                <small class="text-muted d-block">✓ Client communication history</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card card h-100 border-0 shadow-sm hover-lift">
                        <div class="card-body p-4 text-center">
                            <div class="feature-icon-wrapper mb-4">
                                <div class="d-inline-flex align-items-center justify-content-center bg-info bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                                    <i data-feather="bar-chart-3" class="text-info" style="width: 32px; height: 32px;"></i>
                                </div>
                            </div>
                            <h5 class="card-title fw-bold mb-3">Analytics & Reporting</h5>
                            <p class="card-text text-muted mb-3">Powerful insights with customizable dashboards, automated reports, and performance metrics.</p>
                            <div class="feature-benefits">
                                <small class="text-muted d-block mb-1">✓ Real-time dashboards</small>
                                <small class="text-muted d-block mb-1">✓ Automated report generation</small>
                                <small class="text-muted d-block">✓ Performance analytics</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card card h-100 border-0 shadow-sm hover-lift">
                        <div class="card-body p-4 text-center">
                            <div class="feature-icon-wrapper mb-4">
                                <div class="d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                                    <i data-feather="smartphone" class="text-warning" style="width: 32px; height: 32px;"></i>
                                </div>
                            </div>
                            <h5 class="card-title fw-bold mb-3">Mobile-First Design</h5>
                            <p class="card-text text-muted mb-3">Responsive interface that works perfectly on all devices with offline capabilities.</p>
                            <div class="feature-benefits">
                                <small class="text-muted d-block mb-1">✓ Fully responsive design</small>
                                <small class="text-muted d-block mb-1">✓ Mobile app integration</small>
                                <small class="text-muted d-block">✓ Offline functionality</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card card h-100 border-0 shadow-sm hover-lift">
                        <div class="card-body p-4 text-center">
                            <div class="feature-icon-wrapper mb-4">
                                <div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                                    <i data-feather="shield" class="text-danger" style="width: 32px; height: 32px;"></i>
                                </div>
                            </div>
                            <h5 class="card-title fw-bold mb-3">Enterprise Security</h5>
                            <p class="card-text text-muted mb-3">Bank-level security with end-to-end encryption, audit trails, and compliance features.</p>
                            <div class="feature-benefits">
                                <small class="text-muted d-block mb-1">✓ End-to-end encryption</small>
                                <small class="text-muted d-block mb-1">✓ Complete audit trails</small>
                                <small class="text-muted d-block">✓ Regulatory compliance</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="feature-card card h-100 border-0 shadow-sm hover-lift">
                        <div class="card-body p-4 text-center">
                            <div class="feature-icon-wrapper mb-4">
                                <div class="d-inline-flex align-items-center justify-content-center bg-secondary bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                                    <i data-feather="zap" class="text-secondary" style="width: 32px; height: 32px;"></i>
                                </div>
                            </div>
                            <h5 class="card-title fw-bold mb-3">Automation Engine</h5>
                            <p class="card-text text-muted mb-3">Streamline operations with automated workflows, notifications, and smart reminders.</p>
                            <div class="feature-benefits">
                                <small class="text-muted d-block mb-1">✓ Automated notifications</small>
                                <small class="text-muted d-block mb-1">✓ Smart payment reminders</small>
                                <small class="text-muted d-block">✓ Workflow automation</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Interactive Demo CTA -->
            <div class="text-center mt-5">
                <div class="demo-cta bg-white rounded-3 shadow-sm p-4 mx-auto" style="max-width: 600px;">
                    <h4 class="mb-3">Ready to see it in action?</h4>
                    <p class="text-muted mb-4">Experience our platform with a personalized demo tailored to your needs.</p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="<?= APP_URL ?>/public/auth/register.php" class="btn btn-primary px-4">
                            <i data-feather="user-plus" class="me-2" style="width: 18px; height: 18px;"></i>
                            Start Free Trial
                        </a>
                        <button class="btn btn-outline-primary px-4" onclick="startInteractiveDemo()">
                            <i data-feather="play-circle" class="me-2" style="width: 18px; height: 18px;"></i>
                            Watch Demo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced Dashboard Preview with Recent Activity -->
    <section class="dashboard-preview-section py-5" style="background: linear-gradient(135deg, var(--notion-light-gray) 0%, var(--notion-bg) 100%);">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="dashboard-info">
                        <h2 class="fw-bold mb-3">Powerful Dashboard Experience</h2>
                        <p class="lead mb-4">Get complete visibility into your loan portfolio with real-time insights, automated alerts, and intelligent recommendations.</p>

                        <div class="dashboard-features mb-4">
                            <div class="feature-item d-flex align-items-center mb-3">
                                <div class="feature-icon bg-primary bg-opacity-10 rounded-circle p-2 me-3">
                                    <i data-feather="activity" class="text-primary" style="width: 20px; height: 20px;"></i>
                                </div>
                                <div>
                                    <strong>Real-time Analytics</strong>
                                    <br><small class="text-muted">Live updates on portfolio performance</small>
                                </div>
                            </div>

                            <div class="feature-item d-flex align-items-center mb-3">
                                <div class="feature-icon bg-success bg-opacity-10 rounded-circle p-2 me-3">
                                    <i data-feather="bell" class="text-success" style="width: 20px; height: 20px;"></i>
                                </div>
                                <div>
                                    <strong>Smart Notifications</strong>
                                    <br><small class="text-muted">Automated alerts for important events</small>
                                </div>
                            </div>

                            <div class="feature-item d-flex align-items-center mb-3">
                                <div class="feature-icon bg-info bg-opacity-10 rounded-circle p-2 me-3">
                                    <i data-feather="trending-up" class="text-info" style="width: 20px; height: 20px;"></i>
                                </div>
                                <div>
                                    <strong>Performance Insights</strong>
                                    <br><small class="text-muted">AI-powered recommendations</small>
                                </div>
                            </div>
                        </div>

                        <div class="notion-callout mb-4">
                            <div class="notion-callout-icon">
                                <i data-feather="users" class="text-primary"></i>
                            </div>
                            <div>
                                <strong>Role-Based Dashboards</strong><br>
                                Each user role gets a customized experience with relevant metrics and actions.
                            </div>
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <a href="<?= APP_URL ?>/public/auth/login.php" class="btn btn-primary px-4">
                                <i data-feather="log-in" class="me-2" style="width: 18px; height: 18px;"></i>
                                Try It Out
                            </a>
                            <button class="btn btn-outline-primary px-4" onclick="showDashboardTour()">
                                <i data-feather="eye" class="me-2" style="width: 18px; height: 18px;"></i>
                                Preview Dashboard
                            </button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="dashboard-mockup position-relative">
                        <!-- Browser Window Frame -->
                        <div class="browser-frame bg-white rounded-3 shadow-lg overflow-hidden">
                            <div class="browser-header bg-light d-flex align-items-center px-3 py-2 border-bottom">
                                <div class="d-flex gap-2 me-3">
                                    <div class="browser-dot bg-danger rounded-circle" style="width: 12px; height: 12px;"></div>
                                    <div class="browser-dot bg-warning rounded-circle" style="width: 12px; height: 12px;"></div>
                                    <div class="browser-dot bg-success rounded-circle" style="width: 12px; height: 12px;"></div>
                                </div>
                                <div class="browser-url flex-grow-1">
                                    <div class="bg-white border rounded-pill px-3 py-1 text-center">
                                        <small class="text-muted">fanders.app/dashboard</small>
                                    </div>
                                </div>
                            </div>

                            <div class="dashboard-content p-4" style="background-color: var(--notion-bg); min-height: 400px;">
                                <!-- Dashboard Header -->
                                <div class="dashboard-header d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <h5 class="mb-1">Portfolio Overview</h5>
                                        <small class="text-muted">Last updated: 2 minutes ago</small>
                                    </div>
                                    <div class="dashboard-actions">
                                        <button class="btn btn-sm btn-outline-primary me-2">
                                            <i data-feather="download" style="width: 14px; height: 14px;"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i data-feather="settings" style="width: 14px; height: 14px;"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Key Metrics -->
                                <div class="metrics-grid row g-3 mb-4">
                                    <div class="col-6">
                                        <div class="metric-card bg-primary bg-opacity-10 rounded-3 p-3 text-center">
                                            <div class="metric-value fw-bold text-primary mb-1">₱2.4M</div>
                                            <div class="metric-label small text-muted">Total Portfolio</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="metric-card bg-success bg-opacity-10 rounded-3 p-3 text-center">
                                            <div class="metric-value fw-bold text-success mb-1">98.5%</div>
                                            <div class="metric-label small text-muted">On-Time Rate</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="metric-card bg-info bg-opacity-10 rounded-3 p-3 text-center">
                                            <div class="metric-value fw-bold text-info mb-1">156</div>
                                            <div class="metric-label small text-muted">Active Loans</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="metric-card bg-warning bg-opacity-10 rounded-3 p-3 text-center">
                                            <div class="metric-value fw-bold text-warning mb-1">12</div>
                                            <div class="metric-label small text-muted">Due Today</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Recent Activity -->
                                <div class="recent-activity">
                                    <h6 class="mb-3">Recent Activity</h6>
                                    <div class="activity-list">
                                        <div class="activity-item d-flex align-items-center mb-2 p-2 rounded-2" style="background-color: var(--notion-light-gray);">
                                            <div class="activity-icon bg-success bg-opacity-20 rounded-circle p-1 me-3">
                                                <i data-feather="check-circle" class="text-success" style="width: 14px; height: 14px;"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="fw-medium">Payment received from Juan Dela Cruz</small>
                                                <br><small class="text-muted">₱5,000 • 2 minutes ago</small>
                                            </div>
                                        </div>

                                        <div class="activity-item d-flex align-items-center mb-2 p-2 rounded-2" style="background-color: var(--notion-light-gray);">
                                            <div class="activity-icon bg-primary bg-opacity-20 rounded-circle p-1 me-3">
                                                <i data-feather="plus-circle" class="text-primary" style="width: 14px; height: 14px;"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="fw-medium">New loan application approved</small>
                                                <br><small class="text-muted">Maria Santos • ₱25,000 • 15 minutes ago</small>
                                            </div>
                                        </div>

                                        <div class="activity-item d-flex align-items-center mb-2 p-2 rounded-2" style="background-color: var(--notion-light-gray);">
                                            <div class="activity-icon bg-warning bg-opacity-20 rounded-circle p-1 me-3">
                                                <i data-feather="clock" class="text-warning" style="width: 14px; height: 14px;"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="fw-medium">Payment reminder sent</small>
                                                <br><small class="text-muted">Pedro Garcia • Due tomorrow</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Actions -->
                                <div class="quick-actions mt-4 pt-3 border-top">
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button class="btn btn-sm btn-outline-primary">
                                            <i data-feather="plus" style="width: 14px; height: 14px;"></i>
                                            New Loan
                                        </button>
                                        <button class="btn btn-sm btn-outline-success">
                                            <i data-feather="user-plus" style="width: 14px; height: 14px;"></i>
                                            Add Client
                                        </button>
                                        <button class="btn btn-sm btn-outline-info">
                                            <i data-feather="file-text" style="width: 14px; height: 14px;"></i>
                                            Generate Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Floating Notification -->
                        <div class="floating-notification position-absolute bg-success text-white rounded-3 shadow-lg p-3" style="top: 20px; right: -20px; min-width: 200px; z-index: 10;">
                            <div class="d-flex align-items-center">
                                <i data-feather="check-circle" class="me-2" style="width: 16px; height: 16px;"></i>
                                <small class="fw-medium">Payment Received!</small>
                            </div>
                            <small class="opacity-75">₱5,000 from Juan Dela Cruz</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php
// Include footer
include_once BASE_PATH . '/templates/layout/footer.php';
?>
</body>
</html>

<?php
// End output buffering and flush output
ob_end_flush();
?>
