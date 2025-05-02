<!-- Sidebar -->
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="text-center mb-3 d-none d-md-block">
            <h5><?= APP_NAME ?></h5>
        </div>
        
        <div class="text-center mb-3">
            <div class="user-profile">
                <?php if (isset($user)): ?>
                    <div class="mb-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user-circle"><path d="M5.52 19c.64-2.2 1.84-3 3.22-3h6.52c1.38 0 2.58.8 3.22 3"/><circle cx="12" cy="10" r="3"/><circle cx="12" cy="12" r="10"/></svg>
                    </div>
                    <h6 class="mb-0"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h6>
                    <small class="text-muted"><?= htmlspecialchars($user['role_name']) ?></small>
                <?php endif; ?>
            </div>
        </div>
        
        <hr>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="<?= APP_URL ?>/public/dashboard.php">
                    <span data-feather="home"></span>
                    Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/books/') !== false ? 'active' : '' ?>" href="<?= APP_URL ?>/public/books/index.php">
                    <span data-feather="book"></span>
                    Books
                </a>
            </li>
            
            <?php if ($userRole == ROLE_SUPER_ADMIN): ?>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/admins/') !== false ? 'active' : '' ?>" href="<?= APP_URL ?>/public/admins/index.php">
                        <span data-feather="users"></span>
                        Administrators
                    </a>
                </li>
            <?php endif; ?>
            
            <?php if ($userRole == ROLE_SUPER_ADMIN || $userRole == ROLE_ADMIN): ?>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/users/') !== false ? 'active' : '' ?>" href="<?= APP_URL ?>/public/users/index.php">
                        <span data-feather="user"></span>
                        <?= $userRole == ROLE_ADMIN ? 'Borrowers' : 'Users' ?>
                    </a>
                </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/transactions/') !== false ? 'active' : '' ?>" href="<?= APP_URL ?>/public/transactions/index.php">
                    <span data-feather="repeat"></span>
                    <?= $userRole == ROLE_BORROWER ? 'My Loans' : 'Transactions' ?>
                </a>
            </li>
            
            <?php if ($userRole == ROLE_SUPER_ADMIN || $userRole == ROLE_ADMIN): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= strpos($_SERVER['PHP_SELF'], '/reports/') !== false ? 'active' : '' ?>" href="#" id="reportsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span data-feather="file-text"></span>
                        Reports
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="reportsDropdown">
                        <li><a class="dropdown-item" href="<?= APP_URL ?>/public/reports/books.php">Books Report</a></li>
                        <li><a class="dropdown-item" href="<?= APP_URL ?>/public/reports/users.php">Users Report</a></li>
                        <li><a class="dropdown-item" href="<?= APP_URL ?>/public/reports/transactions.php">Transactions Report</a></li>
                    </ul>
                </li>
            <?php endif; ?>
        </ul>
        
        <hr>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="<?= APP_URL ?>/public/logout.php">
                    <span data-feather="log-out"></span>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</nav>
