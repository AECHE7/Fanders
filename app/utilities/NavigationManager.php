<?php
/**
 * NavigationManager - Enhanced navigation system for Fanders
 * Handles active state detection, URL parsing, and navigation rendering
 */

require_once BASE_PATH . '/app/utilities/NavigationConfig.php';
require_once BASE_PATH . '/app/utilities/Permissions.php';

class NavigationManager {
    
    private $currentUri;
    private $currentPage;
    private $currentDirectory;
    private $userRole;
    private $navStructure;
    private $navGroups;
    private $quickActions;
    
    public function __construct($userRole = 'client') {
        $this->userRole = $userRole;
        $this->navStructure = NavigationConfig::getNavigationStructure();
        $this->navGroups = NavigationConfig::getNavigationGroups();
        $this->quickActions = NavigationConfig::getQuickActions();
        
        $this->parseCurrentUrl();
    }
    
    /**
     * Parse current URL to determine page and directory context
     */
    private function parseCurrentUrl() {
        $this->currentUri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Clean the URI - remove query parameters
        $cleanUri = strtok($this->currentUri, '?');
        $cleanUri = rtrim($cleanUri, '/');
        
        // Initialize defaults
        $this->currentPage = 'dashboard';
        $this->currentDirectory = '';
        
        // Enhanced URL parsing patterns
        $patterns = [
            // /public/directory/file.php
            '#/public/([^/]+)/([^/]+)\.php#' => function($matches) {
                $this->currentDirectory = $matches[1];
                $this->currentPage = $matches[2];
            },
            // /public/directory/file (no extension)
            '#/public/([^/]+)/([^/]+)/?$#' => function($matches) {
                $this->currentDirectory = $matches[1];
                $this->currentPage = $matches[2];
            },
            // /public/file.php
            '#/public/([^/]+)\.php#' => function($matches) {
                $this->currentPage = $matches[1];
            },
            // /public/directory/
            '#/public/([^/]+)/?$#' => function($matches) {
                $this->currentDirectory = $matches[1];
                $this->currentPage = $matches[1];
            },
            // Root level /file.php
            '#/([^/]+)\.php#' => function($matches) {
                $this->currentPage = $matches[1];
            }
        ];
        
        foreach ($patterns as $pattern => $callback) {
            if (preg_match($pattern, $cleanUri, $matches)) {
                $callback($matches);
                break;
            }
        }
        
        // Handle special cases and aliases
        $this->normalizePageNames();
        
        // Debug logging
        error_log("NavigationManager: URI={$this->currentUri}, Page={$this->currentPage}, Dir={$this->currentDirectory}");
    }
    
    /**
     * Normalize page names and handle aliases
     */
    private function normalizePageNames() {
        $aliases = [
            'approvals' => 'loan_approvals',
            'listapp' => 'loan_approvals',
            'list_approval' => 'loan_approvals',
            'overdue_payments' => 'overdue_payments',
            'collection-sheets' => 'collection_sheets',
            'cash-blotter' => 'cash_blotter',
            'slr-documents' => 'slr_documents',
            'reports-analytics' => 'reports'
        ];
        
        if (isset($aliases[$this->currentPage])) {
            $this->currentPage = $aliases[$this->currentPage];
        }
        
        if (isset($aliases[$this->currentDirectory])) {
            $this->currentDirectory = $aliases[$this->currentDirectory];
        }
    }
    
    /**
     * Check if a navigation item is active
     */
    public function isNavItemActive($navId, $navItem) {
        // Check against active patterns
        if (isset($navItem['active_patterns'])) {
            foreach ($navItem['active_patterns'] as $pattern) {
                if (strpos($this->currentUri, $pattern) !== false) {
                    return true;
                }
            }
        }
        
        // Check direct page match
        if ($navId === $this->currentPage) {
            return true;
        }
        
        // Check directory match
        if (!empty($this->currentDirectory) && $navId === $this->currentDirectory) {
            return true;
        }
        
        // Special cases for compound navigation
        if ($navId === 'loans' && in_array($this->currentPage, ['loans', 'add', 'edit', 'view'])) {
            return true;
        }
        
        if ($navId === 'clients' && in_array($this->currentPage, ['clients', 'client'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get filtered navigation items for current user
     */
    public function getFilteredNavigation() {
        $filteredNav = [];
        
        foreach ($this->navStructure as $navId => $navItem) {
            if (Permissions::isAllowed($this->userRole, $navItem['roles'])) {
                $navItem['is_active'] = $this->isNavItemActive($navId, $navItem);
                $filteredNav[$navId] = $navItem;
            }
        }
        
        // Sort by priority
        uasort($filteredNav, function($a, $b) {
            return ($a['priority'] ?? 999) <=> ($b['priority'] ?? 999);
        });
        
        return $filteredNav;
    }
    
    /**
     * Get navigation items grouped by sections
     */
    public function getGroupedNavigation() {
        $filteredNav = $this->getFilteredNavigation();
        $groupedNav = [];
        
        foreach ($filteredNav as $navId => $navItem) {
            $group = $navItem['group'];
            if (!isset($groupedNav[$group])) {
                $groupedNav[$group] = [
                    'meta' => $this->navGroups[$group],
                    'items' => []
                ];
            }
            $groupedNav[$group]['items'][$navId] = $navItem;
        }
        
        // Sort groups by priority
        uasort($groupedNav, function($a, $b) {
            return ($a['meta']['priority'] ?? 999) <=> ($b['meta']['priority'] ?? 999);
        });
        
        return $groupedNav;
    }
    
    /**
     * Get filtered quick actions for current user
     */
    public function getFilteredQuickActions() {
        $filteredActions = [];
        
        foreach ($this->quickActions as $actionId => $action) {
            if (Permissions::isAllowed($this->userRole, $action['roles'])) {
                $filteredActions[$actionId] = $action;
            }
        }
        
        // Sort by priority
        uasort($filteredActions, function($a, $b) {
            return ($a['priority'] ?? 999) <=> ($b['priority'] ?? 999);
        });
        
        return $filteredActions;
    }
    
    /**
     * Get badge count for navigation items
     */
    public function getBadgeCount($badgeType) {
        $badgeConfig = NavigationConfig::getBadgeConfig();
        
        if (!isset($badgeConfig[$badgeType])) {
            return 0;
        }
        
        $config = $badgeConfig[$badgeType];
        
        try {
            if ($badgeType === 'pending_loans') {
                // Handle pending loans count
                if (Permissions::canViewLoanApprovals($this->userRole)) {
                    $loanService = new \LoanService();
                    $pendingLoans = $loanService->getLoansByStatus('pending_approval');
                    return is_array($pendingLoans) ? count($pendingLoans) : 0;
                }
            }
            
            // Add other badge types here as needed
            
        } catch (Exception $e) {
            error_log("NavigationManager: Error getting badge count for {$badgeType}: " . $e->getMessage());
        }
        
        return 0;
    }
    
    /**
     * Generate full URL from relative path
     */
    public function getFullUrl($relativePath) {
        return (APP_URL ?? '') . $relativePath;
    }
    
    /**
     * Get debug information
     */
    public function getDebugInfo() {
        return [
            'currentUri' => $this->currentUri,
            'currentPage' => $this->currentPage,
            'currentDirectory' => $this->currentDirectory,
            'userRole' => $this->userRole
        ];
    }
}