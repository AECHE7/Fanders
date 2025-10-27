<?php
/**
 * Improved Sidebar template for the Fanders Microfinance Loan Management System
 * Reorganized with logical groupings and better UX
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

// Define navigation groups with improved organization
$navGroups = [
    'core_operations' => [
        'title' => 'Core Operations',
        'show_separator' => false,
        'items' => [
            'dashboard' => [
                'icon' => 'home',
                'title' => 'Dashboard',
                'url' => APP_URL . '/public/dashboard/index.php',
                'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier', 'client'],
                'active_pages' => ['dashboard', 'index', 'view', 'edit', 'add', 'list']
            ],
            'loans' => [
                'icon' => 'file-text',
                'title' => 'Loan Management',
                'url' => APP_URL . '/public/loans/index.php',
                'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier', 'client'],
                'active_pages' => ['loans', 'approvals'],
                'priority' => true // Mark as high priority
            ],
            'clients' => [
                'icon' => 'users',
                'title' => 'Client Management',
                'url' => APP_URL . '/public/clients/index.php',
                'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier'],
                'active_pages' => ['clients']
            ]
        ]
    ],
    'financial_operations' => [
        'title' => 'Financial Operations',
        'show_separator' => true,
        'items' => [
            'payments' => [
                'icon' => 'credit-card',
                'title' => 'Payments & Collections',
                'url' => APP_URL . '/public/payments/index.php',
                'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier', 'client'],
                'active_pages' => ['payments', 'request', 'collection-sheets']
            ],
            'cash-blotter' => [
                'icon' => 'book-open',
                'title' => 'Cash Management',
                'url' => APP_URL . '/public/cash-blotter/index.php',
                'roles' => ['super-admin', 'admin', 'manager', 'cashier'],
                'active_pages' => ['cash_blotter', 'cash-blotter']
            ],
            'slr-documents' => [
                'icon' => 'file',
                'title' => 'SLR Documents',
                'url' => APP_URL . '/public/slr/index.php',
                'roles' => ['super-admin', 'admin', 'manager', 'cashier'],
                'active_pages' => ['slr-documents', 'slr']
            ]
        ]
    ],
    'management_reporting' => [
        'title' => 'Management & Reporting',
        'show_separator' => true,
        'items' => [
            'reports' => [
                'icon' => 'bar-chart-2',
                'title' => 'Reports & Analytics',
                'url' => APP_URL . '/public/reports/index.php',
                'roles' => ['super-admin', 'admin', 'manager'],
                'active_pages' => ['reports']
            ],
            'transactions' => [
                'icon' => 'activity',
                'title' => 'Audit & Transactions',
                'url' => APP_URL . '/public/transactions/index.php',
                'roles' => ['super-admin', 'admin', 'manager'],
                'active_pages' => ['transactions']
            ]
        ]
    ],
    'administration' => [
        'title' => 'System Administration',
        'show_separator' => true,
        'items' => [
            'users' => [
                'icon' => 'user-check',
                'title' => 'Staff Management',
                'url' => APP_URL . '/public/users/index.php',
                'roles' => ['super-admin', 'admin'],
                'active_pages' => ['users']
            ]
        ]
    ]
];

// Get pending loan approvals count for badge
$pendingCount = 0;
if (Permissions::canViewLoanApprovals($userRole)) {
    try {
        $loanService = new \LoanService();
        $pendingLoans = $loanService->getLoansByStatus('pending_approval');
        $pendingCount = is_array($pendingLoans) ? count($pendingLoans) : 0;
    } catch (Exception $e) {
        // Silently handle error
        $pendingCount = 0;
    }
}
?>

<div class="sidebar-wrapper">
    <div class="sidebar sidebar-expanded collapse d-md-block bg-light border-end" id="sidebarMenu" style="width: 280px; transition: all 0.3s ease;">
        <nav class="position-sticky pt-3" style="height: calc(100vh - 56px); overflow-y: auto;">
            
            <?php foreach ($navGroups as $groupId => $group): ?>
                <?php 
                // Check if user has access to any item in this group
                $hasGroupAccess = false;
                foreach ($group['items'] as $item) {
                    if (Permissions::isAllowed($userRole, $item['roles'])) {
                        $hasGroupAccess = true;
                        break;
                    }
                }
                ?>
                
                <?php if ($hasGroupAccess): ?>
                    <?php if ($group['show_separator'] && $groupId !== 'core_operations'): ?>
                        <!-- Group Separator -->
                        <hr class="my-3 mx-3 opacity-50">
                        <div class="px-3 mb-2">
                            <small class="text-muted fw-semibold text-uppercase tracking-wider group-title" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <?= $group['title'] ?>
                            </small>
                        </div>
                    <?php endif; ?>
                    
                    <ul class="nav flex-column px-2 mb-2">
                        <?php foreach ($group['items'] as $id => $item): ?>
                            <?php if (Permissions::isAllowed($userRole, $item['roles'])): ?>
                                <?php
                                $isActive = isset($item['active_pages']) && in_array($currentPage, $item['active_pages']);
                                $isPriority = isset($item['priority']) && $item['priority'];
                                ?>
                                <li class="nav-item mb-1">
                                    <a class="nav-link <?= $isActive ? 'active bg-primary text-white' : 'text-dark' ?> <?= $isPriority ? 'priority-item' : '' ?> d-flex align-items-center py-2 px-3 rounded nav-item-link" 
                                       href="<?= $item['url'] ?>" 
                                       data-title="<?= $item['title'] ?>">
                                        <i data-feather="<?= $item['icon'] ?>" class="me-2 nav-icon" style="width: 18px; height: 18px;"></i>
                                        <span class="fw-medium nav-text"><?= $item['title'] ?></span>
                                        
                                        <?php if ($id === 'loans' && $pendingCount > 0): ?>
                                            <span class="badge bg-danger ms-auto nav-badge" data-count="<?= $pendingCount ?>"><?= $pendingCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- Special Loan Approvals Item (if user has access and there are pending items) -->
            <?php if (Permissions::canViewLoanApprovals($userRole) && $pendingCount > 0): ?>
                <div class="px-3 mb-2">
                    <small class="text-muted fw-semibold text-uppercase tracking-wider" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        Urgent Actions
                    </small>
                </div>
                <ul class="nav flex-column px-2 mb-3">
                    <li class="nav-item mb-1">
                        <a class="nav-link <?= ($currentPage === 'approvals') ? 'active bg-warning text-dark' : 'text-dark bg-warning-subtle' ?> d-flex align-items-center justify-content-between py-2 px-3 rounded nav-item-link urgent-item" 
                           href="<?= APP_URL ?>/public/loans/approvals.php" 
                           data-title="Loan Approvals">
                            <div class="d-flex align-items-center">
                                <i data-feather="alert-circle" class="me-2 nav-icon" style="width: 18px; height: 18px;"></i>
                                <span class="fw-medium nav-text">Pending Approvals</span>
                            </div>
                            <span class="badge bg-danger ms-2 nav-badge" data-count="<?= $pendingCount ?>"><?= $pendingCount ?></span>
                        </a>
                    </li>
                </ul>
            <?php endif; ?>

            <hr class="my-3 mx-2">

            <!-- Quick Actions Section -->
            <div class="px-3 mb-3 quick-actions">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center text-muted mb-3">
                    <span class="fw-semibold quick-actions-title text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Quick Actions</span>
                </h6>
                <div class="d-grid gap-2">
                    <?php if (Permissions::canCreateLoan($userRole)): ?>
                        <a href="<?= APP_URL ?>/public/loans/add.php" class="btn btn-sm btn-primary d-flex align-items-center justify-content-center py-2 quick-action-btn" data-title="New Loan">
                            <i data-feather="plus-circle" class="me-2 quick-action-icon" style="width: 16px; height: 16px;"></i>
                            <span class="quick-action-text">New Loan Application</span>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (in_array($userRole, ['super-admin', 'admin', 'manager', 'account-officer'])): ?>
                        <a href="<?= APP_URL ?>/public/collection-sheets/add.php" class="btn btn-sm btn-success d-flex align-items-center justify-content-center py-2 quick-action-btn" data-title="Collection Sheet">
                            <i data-feather="clipboard" class="me-2 quick-action-icon" style="width: 16px; height: 16px;"></i>
                            <span class="quick-action-text">Create Collection</span>
                        </a>
                    <?php elseif (Permissions::canRecordPayment($userRole)): ?>
                        <a href="<?= APP_URL ?>/public/payments/list.php" class="btn btn-sm btn-success d-flex align-items-center justify-content-center py-2 quick-action-btn" data-title="Record Payment">
                            <i data-feather="dollar-sign" class="me-2 quick-action-icon" style="width: 16px; height: 16px;"></i>
                            <span class="quick-action-text">Record Payment</span>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (in_array($userRole, ['super-admin', 'admin', 'manager'])): ?>
                        <a href="<?= APP_URL ?>/public/reports/index.php" class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center py-2 quick-action-btn" data-title="Generate Report">
                            <i data-feather="bar-chart-2" class="me-2 quick-action-icon" style="width: 16px; height: 16px;"></i>
                            <span class="quick-action-text">Generate Report</span>
                        </a>
                    <?php elseif (in_array($userRole, ['cashier'])): ?>
                        <a href="<?= APP_URL ?>/public/slr/index.php" class="btn btn-sm btn-outline-secondary d-flex align-items-center justify-content-center py-2 quick-action-btn" data-title="SLR Documents">
                            <i data-feather="file-text" class="me-2 quick-action-icon" style="width: 16px; height: 16px;"></i>
                            <span class="quick-action-text">SLR Documents</span>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (in_array($userRole, ['client'])): ?>
                        <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-sm btn-primary d-flex align-items-center justify-content-center py-2 quick-action-btn" data-title="My Loans">
                            <i data-feather="file-text" class="me-2 quick-action-icon" style="width: 16px; height: 16px;"></i>
                            <span class="quick-action-text">My Loans</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </nav>
    </div>
</div>