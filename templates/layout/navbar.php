<?php
/**
 * Navbar template for the Library Management System
 */

// Get current user and role
$currentUser = $auth->getCurrentUser();
$userRole = $currentUser['role_id'];
$roleName = $currentUser['role_name'];

// Determine active page for navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Define navigation items based on role
$navItems = [
    'dashboard' => [
        'icon' => 'home',
        'title' => 'Dashboard',
        'url' => APP_URL . '/public/dashboard.php',
        'roles' => [ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_BORROWER]
    ],
    'books' => [
        'icon' => 'book',
        'title' => ($userRole == ROLE_BORROWER) ? 'Available Books' : 'Books',
        'url' => APP_URL . '/public/books/index.php',
        'roles' => [ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_BORROWER]
    ],
    'bookshelf' => [
        'icon' => 'book-open',
        'title' => 'Bookshelf View',
        'url' => APP_URL . '/public/books/bookshelf.php',
        'roles' => [ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_BORROWER]
    ],
    'users' => [
        'icon' => 'users',
        'title' => 'Users',
        'url' => APP_URL . '/public/users/index.php',
        'roles' => [ROLE_SUPER_ADMIN, ROLE_ADMIN]
    ],
    'transactions' => [
        'icon' => 'repeat',
        'title' => ($userRole == ROLE_BORROWER) ? 'My Loans' : 'Transactions',
        'url' => APP_URL . '/public/transactions/index.php',
        'roles' => [ROLE_SUPER_ADMIN, ROLE_ADMIN, ROLE_BORROWER]
    ],
    'reports' => [
        'icon' => 'file-text',
        'title' => 'Reports',
        'url' => APP_URL . '/public/reports/books.php',
        'roles' => [ROLE_SUPER_ADMIN, ROLE_ADMIN]
    ]
];
?>

<header class="navbar sticky-top flex-md-nowrap p-0 border-bottom">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 d-flex align-items-center" href="<?= APP_URL ?>/public/dashboard.php">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-book me-2">
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
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
                        <span class="fw-bold"><?= substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1) ?></span>
                    </div>
                    <span class="d-none d-md-inline"><?= htmlspecialchars($currentUser['first_name']) ?></span>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li class="dropdown-header">Signed in as<br/><strong><?= htmlspecialchars($currentUser['username']) ?></strong></li>
                <li><hr class="dropdown-divider"></li>
                <li><span class="dropdown-item-text"><span class="badge bg-secondary"><?= $roleName ?></span></span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= APP_URL ?>/public/logout.php">Sign out</a></li>
            </ul>
        </div>
    </div>
</header>

<div class="container-fluid">
    <div class="row">
        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <?php foreach ($navItems as $id => $item): ?>
                        <?php if (in_array($userRole, $item['roles'])): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= (strpos($currentPage, $id) !== false) ? 'active' : '' ?>" href="<?= $item['url'] ?>">
                                    <i data-feather="<?= $item['icon'] ?>"></i>
                                    <?= $item['title'] ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                
                <div class="notion-divider my-3"></div>
                
                <!-- Quick Actions Section -->
                <div class="px-3 mb-3">
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center text-muted">
                        <span>Quick Actions</span>
                    </h6>
                    <div class="d-grid gap-2 mt-2">
                        <?php if ($userRole == ROLE_SUPER_ADMIN || $userRole == ROLE_ADMIN): ?>
                            <a href="<?= APP_URL ?>/public/books/add.php" class="btn btn-sm btn-outline-primary d-flex align-items-center">
                                <i data-feather="plus-circle" class="me-1" style="width: 16px; height: 16px;"></i> Add Book
                            </a>
                        <?php else: ?>
                            <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-sm btn-outline-primary d-flex align-items-center">
                                <i data-feather="search" class="me-1" style="width: 16px; height: 16px;"></i> Find Books
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Settings Section at Bottom -->
                <div class="position-absolute bottom-0 start-0 w-100 p-3 border-top">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-2">
                            <i data-feather="settings" style="width: 18px; height: 18px;"></i>
                        </div>
                        <div class="flex-grow-1 small text-muted d-flex justify-content-between align-items-center">
                            <span>Settings</span>
                            <span class="small text-muted"><?= date('Y') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
