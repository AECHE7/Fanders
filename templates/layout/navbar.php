<?php
/**
 * Navbar template for the Library Management System
 */

// Get current user and role
$currentUser = $auth->getCurrentUser();
$userRole = $currentUser['role_id'];
$roleName = $currentUser['role_name'];
?>

<header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
    <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="<?= APP_URL ?>/public/dashboard.php"><?= APP_NAME ?></a>
    <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="navbar-nav">
        <div class="nav-item text-nowrap">
            <span class="nav-link px-3 text-white">Welcome, <?= htmlspecialchars($currentUser['first_name']) ?> (<?= $roleName ?>)</span>
        </div>
    </div>
</header>

<div class="container-fluid">
    <div class="row">
        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/public/dashboard.php">
                            <i data-feather="home"></i>
                            Dashboard
                        </a>
                    </li>
                    
                    <?php if ($userRole == ROLE_SUPER_ADMIN || $userRole == ROLE_ADMIN): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/public/books/index.php">
                            <i data-feather="book"></i>
                            Books
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($userRole == ROLE_BORROWER): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/public/books/index.php">
                            <i data-feather="book-open"></i>
                            Available Books
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($userRole == ROLE_SUPER_ADMIN || $userRole == ROLE_ADMIN): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/public/users/index.php">
                            <i data-feather="users"></i>
                            Users
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/public/transactions/index.php">
                            <i data-feather="repeat"></i>
                            <?= ($userRole == ROLE_BORROWER) ? 'My Loans' : 'Transactions' ?>
                        </a>
                    </li>
                    
                    <?php if ($userRole == ROLE_SUPER_ADMIN || $userRole == ROLE_ADMIN): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/public/reports/books.php">
                            <i data-feather="file-text"></i>
                            Reports
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Account</span>
                </h6>
                <ul class="nav flex-column mb-2">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= APP_URL ?>/public/logout.php">
                            <i data-feather="log-out"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
