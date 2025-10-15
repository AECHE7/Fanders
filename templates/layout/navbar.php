<?php
/**
 * Navbar template for the Fanders Microfinance Loan Management System
 */

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

// Determine active page for navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Define navigation items based on role for microfinance system
$navItems = [
    'dashboard' => [
        'icon' => 'grid',
        'title' => 'Dashboard',
        'url' => APP_URL . '/public/dashboard.php',
        'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier', 'client']
    ],
    'loans' => [
        'icon' => 'file-text',
        'title' => 'Loans',
        'url' => APP_URL . '/public/loans/index.php',
        'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier', 'client']
    ],
    'clients' => [
        'icon' => 'users',
        'title' => 'Clients',
        'url' => APP_URL . '/public/clients/index.php',
        'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier']
    ],
    'payments' => [
        'icon' => 'dollar-sign',
        'title' => 'Payments',
        'url' => APP_URL . '/public/payments/index.php',
        'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier', 'client']
    ],
    'collection-sheets' => [
        'icon' => 'clipboard',
        'title' => 'Collection Sheets',
        'url' => APP_URL . '/public/collection-sheets/index.php',
        'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier']
    ],
    'cash-blotter' => [
        'icon' => 'book-open',
        'title' => 'Cash Blotter',
        'url' => APP_URL . '/public/cash-blotter/index.php',
        'roles' => ['super-admin', 'admin', 'manager', 'cashier']
    ],
    'slr-documents' => [
        'icon' => 'file-check',
        'title' => 'SLR Documents',
        'url' => APP_URL . '/public/slr-documents/index.php',
        'roles' => ['super-admin', 'admin', 'manager', 'account_officer']
    ],
    'reports' => [
        'icon' => 'bar-chart-2',
        'title' => 'Reports',
        'url' => APP_URL . '/public/reports/index.php',
        'roles' => ['super-admin', 'admin', 'manager']
    ],
    'users' => [
        'icon' => 'user-check',
        'title' => 'Staff Management',
        'url' => APP_URL . '/public/users/index.php',
        'roles' => ['super-admin', 'admin']
    ]
];
?>

<header class="navbar sticky-top flex-md-nowrap p-0 border-bottom">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 d-flex align-items-center" href="<?= APP_URL ?>/public/dashboard.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-dollar-sign me-2">
                    <path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
        <span><?= APP_NAME ?></span>
    </a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="navbar-nav flex-row me-auto ms-4 d-none d-md-flex">
        <div class="nav-item">
            <form class="d-flex position-relative" role="search">
                <input class="form-control" type="search" placeholder="Quick search..." aria-label="Search" style="width: 250px;">
                <div class="position-absolute top-50 end-0 translate-middle-y me-2">
                    <i data-feather="search" class="text-muted" style="width: 18px; height: 18px;"></i>
                </div>
            </form>
        </div>
    </div>
    <div class="navbar-nav">
        <div class="nav-item dropdown">
            <a class="nav-link dropdown-toggle px-3 d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                        <span class="fw-bold"><?= htmlspecialchars($initials) ?></span>
                    </div>
                    <span class="d-none d-md-inline"><?= htmlspecialchars($firstName) ?></span>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="navbarDropdown" style="z-index: 1050; position: absolute;">
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
                <li><a class="dropdown-item" href="<?= APP_URL ?>/public/logout.php">
                <i data-feather="log-out" class="me-2" style="width: 16px; height: 16px;"></i>Sign out</a></li>
            </ul>
        </div>
    </div>
</header>

<div class="container-fluid">
    <div class="row g-0">
        <div class="collapse d-md-block col-md-3 col-lg-2 sidebar" id="sidebarMenu">
            <nav class="position-sticky pt-3">
                <ul class="nav flex-column">
                <?php foreach ($navItems as $id => $item): ?>
                        <?php if (in_array($userRole, $item['roles'])): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= ($currentPage === $id) ? 'active' : '' ?>" href="<?= $item['url'] ?>">
                                    <i data-feather="<?= $item['icon'] ?>"></i>
                                    <?= $item['title'] ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (in_array($userRole, ['super-admin', 'admin', 'manager'])): ?>
                        <?php
                            // Load LoanService to get pending approvals count
                            $loanService = new \LoanService();
                            $pendingLoans = $loanService->getLoansByStatus('pending_approval');
                            $pendingCount = is_array($pendingLoans) ? count($pendingLoans) : 0;
                        ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($currentPage === 'approvals') ? 'active' : '' ?>" href="<?= APP_URL ?>/public/loans/approvals.php">
                                <i data-feather="check-circle"></i>
                                Loan Approvals
                                <?php if ($pendingCount > 0): ?>
                                    <span class="badge bg-danger ms-1"><?= $pendingCount ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <div class="notion-divider my-3"></div>
                
                <!-- Quick Actions Section -->
                <div class="px-3 mb-3">
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center text-muted">
                        <span>Quick Actions</span>
                    </h6>
                    <div class="d-grid gap-2 mt-2">
                        <?php if (in_array($userRole, ['super-admin', 'admin', 'manager', 'account_officer'])): ?>
                            <a href="<?= APP_URL ?>/public/loans/add.php" class="btn btn-sm btn-outline-primary d-flex align-items-center">
                                <i data-feather="plus-circle" class="me-1" style="width: 16px; height: 16px;"></i> New Loan
                            </a>
                        <?php elseif ($userRole === 'cashier'): ?>
                            <a href="<?= APP_URL ?>/public/payments/add.php" class="btn btn-sm btn-outline-primary d-flex align-items-center">
                                <i data-feather="dollar-sign" class="me-1" style="width: 16px; height: 16px;"></i> Record Payment
                            </a>
                        <?php else: ?>
                            <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-sm btn-outline-primary d-flex align-items-center">
                                <i data-feather="file-text" class="me-1" style="width: 16px; height: 16px;"></i> My Loans
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
        </nav>
    
