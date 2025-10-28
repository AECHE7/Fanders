<?php
/**
 * NavigationConfig - Centralized navigation configuration for Fanders system
 * This provides a robust, maintainable approach to navigation management
 */

class NavigationConfig {
    
    /**
     * Core navigation structure definition
     * Organized by logical groups with comprehensive configuration
     */
    public static function getNavigationStructure() {
        return [
            'dashboard' => [
                'group' => 'core_operations',
                'title' => 'Dashboard',
                'icon' => 'home',
                'url' => '/public/dashboard/index.php',
                'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier', 'client'],
                'active_patterns' => [
                    '/dashboard',
                    '/public/dashboard',
                    '/index.php',
                    '/'
                ],
                'priority' => 1,
                'show_always' => true
            ],
            
            'loans' => [
                'group' => 'core_operations',
                'title' => 'Loan Management',
                'icon' => 'file-text',
                'url' => '/public/loans/index.php',
                'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier', 'client'],
                'active_patterns' => [
                    '/loans',
                    '/public/loans'
                ],
                'priority' => 2,
                'highlight_important' => true
            ],
            
            'loan_approvals' => [
                'group' => 'core_operations',
                'title' => 'Loan Approvals',
                'icon' => 'check-circle',
                'url' => '/public/loans/approvals.php',
                'roles' => ['super-admin', 'admin', 'manager'],
                'active_patterns' => [
                    '/approvals',
                    '/public/loans/approvals'
                ],
                'priority' => 3,
                'show_badge' => true,
                'badge_type' => 'pending_loans'
            ],
            
            'clients' => [
                'group' => 'core_operations',
                'title' => 'Client Management',
                'icon' => 'users',
                'url' => '/public/clients/index.php',
                'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier'],
                'active_patterns' => [
                    '/clients',
                    '/public/clients'
                ],
                'priority' => 4
            ],
            
            'payments' => [
                'group' => 'financial_operations',
                'title' => 'Payments & Collections',
                'icon' => 'credit-card',
                'url' => '/public/payments/index.php',
                'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier', 'client'],
                'active_patterns' => [
                    '/payments',
                    '/public/payments'
                ],
                'priority' => 5
            ],
            
            'overdue_payments' => [
                'group' => 'financial_operations',
                'title' => 'Overdue Payments',
                'icon' => 'alert-triangle',
                'url' => '/public/payments/overdue_payments.php',
                'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier'],
                'active_patterns' => [
                    '/overdue_payments',
                    '/public/payments/overdue'
                ],
                'priority' => 6,
                'show_badge' => true,
                'badge_type' => 'overdue_count',
                'urgent' => true
            ],
            
            'collection_sheets' => [
                'group' => 'financial_operations',
                'title' => 'Collection Sheets',
                'icon' => 'clipboard',
                'url' => '/public/collection-sheets/index.php',
                'roles' => ['super-admin', 'admin', 'manager', 'account_officer', 'cashier'],
                'active_patterns' => [
                    '/collection-sheets',
                    '/public/collection-sheets'
                ],
                'priority' => 7
            ],
            
            'cash_blotter' => [
                'group' => 'financial_operations',
                'title' => 'Cash Management',
                'icon' => 'book-open',
                'url' => '/public/cash-blotter/index.php',
                'roles' => ['super-admin', 'admin', 'manager', 'cashier'],
                'active_patterns' => [
                    '/cash-blotter',
                    '/public/cash-blotter'
                ],
                'priority' => 8
            ],
            
            'slr_documents' => [
                'group' => 'financial_operations',
                'title' => 'SLR Documents',
                'icon' => 'file',
                'url' => '/public/slr/index.php',
                'roles' => ['super-admin', 'admin', 'manager', 'cashier'],
                'active_patterns' => [
                    '/slr',
                    '/public/slr'
                ],
                'priority' => 9
            ],
            
            'reports' => [
                'group' => 'management_reporting',
                'title' => 'Reports & Analytics',
                'icon' => 'bar-chart-2',
                'url' => '/public/reports/index.php',
                'roles' => ['super-admin', 'admin', 'manager'],
                'active_patterns' => [
                    '/reports',
                    '/public/reports'
                ],
                'priority' => 10
            ],
            
            'transactions' => [
                'group' => 'management_reporting',
                'title' => 'Audit & Transactions',
                'icon' => 'activity',
                'url' => '/public/transactions/index.php',
                'roles' => ['super-admin', 'admin', 'manager'],
                'active_patterns' => [
                    '/transactions',
                    '/public/transactions'
                ],
                'priority' => 11
            ],
            
            'users' => [
                'group' => 'administration',
                'title' => 'Staff Management',
                'icon' => 'user-check',
                'url' => '/public/users/index.php',
                'roles' => ['super-admin', 'admin'],
                'active_patterns' => [
                    '/users',
                    '/public/users'
                ],
                'priority' => 12
            ]
        ];
    }
    
    /**
     * Define navigation groups with metadata
     */
    public static function getNavigationGroups() {
        return [
            'core_operations' => [
                'title' => 'Core Operations',
                'show_separator' => false,
                'icon' => 'activity',
                'priority' => 1
            ],
            'financial_operations' => [
                'title' => 'Financial Operations',
                'show_separator' => true,
                'icon' => 'dollar-sign',
                'priority' => 2
            ],
            'management_reporting' => [
                'title' => 'Management & Reporting',
                'show_separator' => true,
                'icon' => 'trending-up',
                'priority' => 3
            ],
            'administration' => [
                'title' => 'System Administration',
                'show_separator' => true,
                'icon' => 'settings',
                'priority' => 4
            ]
        ];
    }
    
    /**
     * Define quick actions based on user roles
     */
    public static function getQuickActions() {
        return [
            'new_loan' => [
                'title' => 'New Loan Application',
                'icon' => 'plus-circle',
                'url' => '/public/loans/add.php',
                'roles' => ['super-admin', 'admin', 'manager', 'account_officer'],
                'class' => 'btn-primary',
                'priority' => 1
            ],
            'collection_sheet' => [
                'title' => 'Create Collection Sheet',
                'icon' => 'clipboard',
                'url' => '/public/collection-sheets/add.php',
                'roles' => ['super-admin', 'admin', 'manager', 'account_officer'],
                'class' => 'btn-success',
                'priority' => 2
            ],
            'record_payment' => [
                'title' => 'Record Payment',
                'icon' => 'dollar-sign',
                'url' => '/public/payments/add.php',
                'roles' => ['cashier'],
                'class' => 'btn-success',
                'priority' => 2
            ],
            'review_approvals' => [
                'title' => 'Review Approvals',
                'icon' => 'check-circle',
                'url' => '/public/loans/approvals.php',
                'roles' => ['super-admin', 'admin', 'manager'],
                'class' => 'btn-warning',
                'priority' => 3,
                'show_if_pending' => true
            ],
            'generate_report' => [
                'title' => 'Generate Report',
                'icon' => 'bar-chart-2',
                'url' => '/public/reports/index.php',
                'roles' => ['super-admin', 'admin', 'manager'],
                'class' => 'btn-outline-secondary',
                'priority' => 4
            ],
            'slr_documents' => [
                'title' => 'SLR Documents',
                'icon' => 'file-text',
                'url' => '/public/slr/index.php',
                'roles' => ['cashier'],
                'class' => 'btn-outline-secondary',
                'priority' => 4
            ],
            'my_loans' => [
                'title' => 'My Loans',
                'icon' => 'file-text',
                'url' => '/public/loans/index.php',
                'roles' => ['client'],
                'class' => 'btn-primary',
                'priority' => 1
            ]
        ];
    }
    
    /**
     * Badge configurations for dynamic counters
     */
    public static function getBadgeConfig() {
        return [
            'pending_loans' => [
                'service' => 'LoanService',
                'method' => 'getPendingApprovalsCount',
                'color' => 'danger',
                'threshold' => 1
            ],
            'overdue_count' => [
                'service' => 'PaymentService',
                'method' => 'getOverdueCount',
                'color' => 'warning',
                'threshold' => 1
            ]
        ];
    }
}