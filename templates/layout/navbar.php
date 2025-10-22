<?php
/**
 * Sidebar template for the Fanders Microfinance Loan Management System
 */

// Centralized permissions
require_once BASE_PATH . '/app/utilities/Permissions.php';

// Determine active page for navigation highlighting
$requestUri = $_SERVER['REQUEST_URI'];
$currentPage = 'dashboard'; // default

// Extract the main section from the URL
if (preg_match('/\/public\/([^\/]+)/', $requestUri, $matches)) {
    $currentPage = $matches[1];
}

// Handle specific cases
if (strpos($currentPage, '.php') !== false) {
    $currentPage = basename($currentPage, '.php');
}

// Map specific pages to their parent sections
$pageMappings = [
    'index' => 'dashboard',
    'view' => 'dashboard',
    'edit' => 'dashboard',
    'add' => 'dashboard',
    'list' => 'dashboard',
    'approvals' => 'loans',
    'request' => 'payments',
];

if (isset($pageMappings[$currentPage])) {
    $currentPage = $pageMappings[$currentPage];
}

// Define navigation items (visibility is determined via Permissions)
$navItems = [
    'dashboard' => [
        'icon' => 'grid',
        'title' => 'Dashboard',
        'url' => APP_URL . '/public/dashboard.php',
        'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier', 'client'],
        'active_pages' => ['dashboard', 'index', 'view', 'edit', 'add', 'list']
    ],
    'loans' => [
        'icon' => 'file-text',
        'title' => 'Loans',
        'url' => APP_URL . '/public/loans/index.php',
        'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier', 'client'],
        'active_pages' => ['loans', 'approvals']
    ],
    'clients' => [
        'icon' => 'users',
        'title' => 'Clients',
        'url' => APP_URL . '/public/clients/index.php',
        'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier'],
        'active_pages' => ['clients']
    ],
    'payments' => [
        'icon' => 'dollar-sign',
        'title' => 'Payments',
        'url' => APP_URL . '/public/payments/index.php',
        'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier', 'client'],
        'active_pages' => ['payments', 'request']
    ],
    'collection-sheets' => [
        'icon' => 'clipboard',
        'title' => 'Collection Sheets',
        // Point to the new Collection Sheets landing (placeholder until full implementation)
        'url' => APP_URL . '/public/collection-sheets/index.php',
        'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier'],
        'active_pages' => ['collection-sheets']
    ],
    'cash-blotter' => [
        'icon' => 'book-open',
        'title' => 'Cash Blotter',
        'url' => APP_URL . '/public/cash_blotter/index.php',
        'roles' => ['super-admin', 'admin', 'manager', 'cashier'],
        'active_pages' => ['cash_blotter', 'cash-blotter']
    ],
    'slr-documents' => [
        'icon' => 'file-contract',
        'title' => 'SLR Documents',
        // Link to the new SLR management page
            'url' => APP_URL . '/public/slr/index.php',
        'roles' => ['super-admin', 'admin', 'manager', 'cashier'],
        'active_pages' => ['slr-documents', 'slr']
    ],
    'reports' => [
        'icon' => 'bar-chart-2',
        'title' => 'Reports',
        'url' => APP_URL . '/public/reports/index.php',
        'roles' => ['super-admin', 'admin', 'manager'],
        'active_pages' => ['reports']
    ],
    'transactions' => [
        'icon' => 'activity',
        'title' => 'Transactions',
        'url' => APP_URL . '/public/transactions/index.php',
        'roles' => ['super-admin', 'admin', 'manager'],
        'active_pages' => ['transactions']
    ],
    'users' => [
        'icon' => 'user-check',
        'title' => 'Staff Management',
        'url' => APP_URL . '/public/users/index.php',
        'roles' => ['super-admin', 'admin'],
        'active_pages' => ['users']
    ]
];
?>

<div class="sidebar-wrapper">
    <div class="sidebar sidebar-expanded d-none d-md-block bg-light border-end" id="sidebarMenu" style="width: 280px; transition: all 0.3s ease;">
        <nav class="position-sticky pt-3" style="height: calc(100vh - 56px); overflow-y: auto;">
            <ul class="nav flex-column px-2">
                <?php foreach ($navItems as $id => $item): ?>
                        <?php if (Permissions::isAllowed($userRole, $item['roles'])): ?>
                            <?php
                            $isActive = isset($item['active_pages']) && in_array($currentPage, $item['active_pages']);
                            ?>
                            <li class="nav-item mb-1">
                                <a class="nav-link <?= $isActive ? 'active bg-primary text-white' : 'text-dark' ?> d-flex align-items-center py-2 px-3 rounded nav-item-link" href="<?= $item['url'] ?>" data-title="<?= $item['title'] ?>">
                                    <i data-feather="<?= $item['icon'] ?>" class="me-2 nav-icon" style="width: 18px; height: 18px;"></i>
                                    <span class="fw-medium nav-text"><?= $item['title'] ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (Permissions::canViewLoanApprovals($userRole)): ?>
                        <?php
                            // Load LoanService to get pending approvals count
                            $loanService = new \LoanService();
                            $pendingLoans = $loanService->getLoansByStatus('pending_approval');
                            $pendingCount = is_array($pendingLoans) ? count($pendingLoans) : 0;
                        ?>
                        <li class="nav-item mb-1">
                            <a class="nav-link <?= ($currentPage === 'approvals') ? 'active bg-primary text-white' : 'text-dark' ?> d-flex align-items-center justify-content-between py-2 px-3 rounded nav-item-link" href="<?= APP_URL ?>/public/loans/approvals.php" data-title="Loan Approvals">
                                <div class="d-flex align-items-center">
                                    <i data-feather="check-circle" class="me-2 nav-icon" style="width: 18px; height: 18px;"></i>
                                    <span class="fw-medium nav-text">Loan Approvals</span>
                                </div>
                                <?php if ($pendingCount > 0): ?>
                                    <span class="badge bg-danger ms-2 nav-badge" data-count="<?= $pendingCount ?>"><?= $pendingCount ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <hr class="my-3 mx-2">

                <!-- Quick Actions Section -->
                <div class="px-3 mb-3 quick-actions">
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center text-muted mb-3">
                        <span class="fw-semibold quick-actions-title">Quick Actions</span>
                    </h6>
                    <div class="d-grid gap-2">
                        <?php if (Permissions::canCreateLoan($userRole)): ?>
                            <a href="<?= APP_URL ?>/public/loans/add.php" class="btn btn-sm btn-primary d-flex align-items-center justify-content-center py-2 quick-action-btn" data-title="New Loan">
                                <i data-feather="plus-circle" class="me-2 quick-action-icon" style="width: 16px; height: 16px;"></i>
                                <span class="quick-action-text">New Loan</span>
                            </a>
                        <?php elseif (Permissions::canRecordPayment($userRole)): ?>
                            <a href="<?= APP_URL ?>/public/payments/add.php" class="btn btn-sm btn-primary d-flex align-items-center justify-content-center py-2 quick-action-btn" data-title="Record Payment">
                                <i data-feather="dollar-sign" class="me-2 quick-action-icon" style="width: 16px; height: 16px;"></i>
                                <span class="quick-action-text">Record Payment</span>
                            </a>
                        <?php else: ?>
                            <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-sm btn-primary d-flex align-items-center justify-content-center py-2 quick-action-btn" data-title="My Loans">
                                <i data-feather="file-text" class="me-2 quick-action-icon" style="width: 16px; height: 16px;"></i>
                                <span class="quick-action-text">My Loans</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </nav>
    </div>
</div>
