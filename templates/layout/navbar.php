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

// Extract the specific page from the URL - improved logic
// First try to match directory/file pattern
if (preg_match('/\/public\/([^\/]+)\/([^\/\?]+)\.php/', $requestUri, $matches)) {
    // For URLs like /public/loans/approvals.php or /public/payments/overdue_payments.php
    $directory = $matches[1];
    $filename = $matches[2];
    
    // Use the filename as the page identifier for better specificity
    $currentPage = $filename;
} elseif (preg_match('/\/public\/([^\/]+)/', $requestUri, $matches)) {
    // For URLs like /public/dashboard/ or /public/loans/
    $currentPage = $matches[1];
}

// Handle specific cases
if (strpos($currentPage, '.php') !== false) {
    $currentPage = basename($currentPage, '.php');
}

// Enhanced page detection logic - check both directory and filename
$currentDirectory = '';
if (preg_match('/\/public\/([^\/]+)\//', $requestUri, $matches)) {
    $currentDirectory = $matches[1];
}

// Map specific pages to their parent sections only when they don't have their own navigation items
$pageMappings = [
    'request' => 'payments',
];

// Only apply mapping if the page doesn't have its own dedicated navigation item
if (isset($pageMappings[$currentPage])) {
    $currentPage = $pageMappings[$currentPage];
}

// Enhanced active detection: use directory as fallback if current page doesn't match
$activePageOptions = [$currentPage];
if (!empty($currentDirectory) && $currentDirectory !== $currentPage) {
    $activePageOptions[] = $currentDirectory;
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
                // Only match the dashboard section explicitly to avoid collisions with generic page names
                'active_pages' => ['dashboard']
            ],
            'loans' => [
                'icon' => 'file-text',
                'title' => 'Loan Management',
                'url' => APP_URL . '/public/loans/index.php',
                'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier', 'client'],
                // Match by module/directory; avoid generic names like index/view/edit
                'active_pages' => ['loans'],
                'priority' => true // Mark as high priority
            ],
            'loan-approvals' => [
                'icon' => 'check-circle',
                'title' => 'Loan Approvals',
                'url' => APP_URL . '/public/loans/approvals.php',
                'roles' => ['super-admin', 'admin', 'manager'],
                'active_pages' => ['approvals'],
                'show_badge' => true // This will show the pending count badge
            ],
            'clients' => [
                'icon' => 'users',
                'title' => 'Client Management',
                'url' => APP_URL . '/public/clients/index.php',
                'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier'],
                'active_pages' => ['clients', 'client']
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
                'active_pages' => ['payments', 'request']
            ],
            'overdue-payments' => [
                'icon' => 'alert-triangle',
                'title' => 'Overdue Payments',
                'url' => APP_URL . '/public/payments/overdue_payments.php',
                'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier'],
                'active_pages' => ['overdue_payments'],
                'show_badge' => true,
                'badge_color' => 'danger'
            ],
            'collection-sheets' => [
                'icon' => 'clipboard',
                'title' => 'Collection Sheets',
                'url' => APP_URL . '/public/collection-sheets/index.php',
                'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier'],
                'active_pages' => ['collection-sheets']
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

<style>
/* Enhanced Sidebar Navigation Styles */
.sidebar .nav-link {
    transition: all 0.3s ease;
    border-radius: 0.5rem !important;
    margin-bottom: 0.25rem;
    border: 2px solid transparent;
}

.sidebar .nav-link:hover {
    background-color: #e9ecef !important;
    color: #495057 !important;
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.sidebar .nav-link.active {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%) !important;
    color: white !important;
    border-color: #0a58ca !important;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3) !important;
    transform: translateX(6px);
}

.sidebar .nav-link.active:hover {
    background: linear-gradient(135deg, #0b5ed7 0%, #0a58ca 100%) !important;
    color: white !important;
    transform: translateX(6px);
    box-shadow: 0 6px 16px rgba(13, 110, 253, 0.4) !important;
}

.sidebar .nav-link .nav-icon {
    transition: all 0.3s ease;
}

.sidebar .nav-link.active .nav-icon {
    filter: drop-shadow(0 0 2px rgba(255,255,255,0.3));
}

.sidebar .urgent-item {
    animation: pulse-warning 2s infinite;
}

.sidebar .urgent-item.active {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%) !important;
    color: #000 !important;
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4) !important;
}

@keyframes pulse-warning {
    0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
    70% { box-shadow: 0 0 0 8px rgba(255, 193, 7, 0); }
    100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
}

.sidebar .quick-action-btn {
    transition: all 0.3s ease;
    border-radius: 0.4rem;
}

.sidebar .quick-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.sidebar .group-title {
    opacity: 0.7;
    font-weight: 600;
}
</style>

<div class="sidebar-wrapper">
    <div class="sidebar sidebar-expanded d-md-block bg-light border-end" id="sidebarMenu" style="width: 280px; transition: all 0.3s ease;">
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
                                // Enhanced active detection - check both current page and directory
                                $isActive = false;
                                if (isset($item['active_pages'])) {
                                    foreach ($activePageOptions as $pageOption) {
                                        if (in_array($pageOption, $item['active_pages'])) {
                                            $isActive = true;
                                            break;
                                        }
                                    }
                                }
                                $isPriority = isset($item['priority']) && $item['priority'];
                                ?>
                                <li class="nav-item mb-1">
                                    <a class="nav-link <?= $isActive ? 'active' : 'text-dark' ?> <?= $isPriority ? 'priority-item' : '' ?> d-flex align-items-center py-2 px-3 nav-item-link" 
                                       href="<?= $item['url'] ?>" 
                                       data-title="<?= $item['title'] ?>">
                                        <i data-feather="<?= $item['icon'] ?>" class="me-2 nav-icon" style="width: 18px; height: 18px;"></i>
                                        <span class="fw-medium nav-text"><?= $item['title'] ?></span>
                                        
                                        <?php if ($id === 'loan-approvals' && $pendingCount > 0): ?>
                                            <span class="badge bg-danger ms-auto nav-badge" data-count="<?= $pendingCount ?>"><?= $pendingCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- Urgent Actions Section (only when there are many pending approvals) -->
            <?php if (Permissions::canViewLoanApprovals($userRole) && $pendingCount > 5): ?>
                <div class="px-3 mb-2">
                    <small class="text-muted fw-semibold text-uppercase tracking-wider" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        ⚠️ Urgent Actions
                    </small>
                </div>
                <ul class="nav flex-column px-2 mb-3">
                    <li class="nav-item mb-1">
                        <a class="nav-link <?= ($currentPage === 'approvals') ? 'active' : 'text-dark bg-warning-subtle' ?> d-flex align-items-center justify-content-between py-2 px-3 nav-item-link urgent-item" 
                           href="<?= APP_URL ?>/public/loans/approvals.php" 
                           data-title="High Priority Approvals">
                            <div class="d-flex align-items-center">
                                <i data-feather="alert-triangle" class="me-2 nav-icon" style="width: 18px; height: 18px;"></i>
                                <span class="fw-medium nav-text">High Priority!</span>
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
                        <a href="<?= APP_URL ?>/public/payments/add.php" class="btn btn-sm btn-success d-flex align-items-center justify-content-center py-2 quick-action-btn" data-title="Record Payment">
                            <i data-feather="dollar-sign" class="me-2 quick-action-icon" style="width: 16px; height: 16px;"></i>
                            <span class="quick-action-text">Record Payment</span>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (Permissions::canViewLoanApprovals($userRole) && $pendingCount > 0): ?>
                        <a href="<?= APP_URL ?>/public/loans/approvals.php" class="btn btn-sm btn-warning d-flex align-items-center justify-content-center py-2 quick-action-btn" data-title="Review Approvals">
                            <i data-feather="check-circle" class="me-2 quick-action-icon" style="width: 16px; height: 16px;"></i>
                            <span class="quick-action-text">Review Approvals (<?= $pendingCount ?>)</span>
                        </a>
                    <?php elseif (in_array($userRole, ['super-admin', 'admin', 'manager'])): ?>
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
