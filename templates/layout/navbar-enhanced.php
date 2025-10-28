<?php
/**
 * Enhanced Navigation Template for Fanders Microfinance System
 * Uses NavigationManager for robust, maintainable navigation
 */

require_once BASE_PATH . '/app/utilities/NavigationManager.php';

// Initialize navigation manager
$navManager = new NavigationManager($userRole ?? 'client');

// Get navigation data
$groupedNavigation = $navManager->getGroupedNavigation();
$quickActions = $navManager->getFilteredQuickActions();
$debugInfo = $navManager->getDebugInfo();

// Get pending counts for badges
$pendingLoansCount = $navManager->getBadgeCount('pending_loans');
$overdueCount = $navManager->getBadgeCount('overdue_count');
?>

<div class="sidebar-wrapper">
    <nav class="sidebar bg-light border-end" id="sidebarMenu">
        <div class="position-sticky pt-3" style="height: calc(100vh - 56px); overflow-y: auto;">
            
            <!-- Debug Info (only in development) -->
            <?php if (defined('DEBUG_MODE') && DEBUG_MODE): ?>
                <div class="px-3 mb-2">
                    <small class="text-muted font-monospace" style="font-size: 0.7rem;">
                        Debug: <?= htmlspecialchars($debugInfo['currentPage']) ?> | <?= htmlspecialchars($debugInfo['currentDirectory']) ?>
                    </small>
                </div>
            <?php endif; ?>
            
            <!-- Navigation Groups -->
            <?php foreach ($groupedNavigation as $groupId => $groupData): ?>
                <?php $groupMeta = $groupData['meta']; ?>
                <?php $groupItems = $groupData['items']; ?>
                
                <!-- Group Separator -->
                <?php if ($groupMeta['show_separator']): ?>
                    <div class="nav-group-separator"></div>
                    <div class="group-title">
                        <i data-feather="<?= htmlspecialchars($groupMeta['icon']) ?>" class="group-title-icon"></i>
                        <?= htmlspecialchars($groupMeta['title']) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Navigation Items -->
                <ul class="nav flex-column px-2 mb-2">
                    <?php foreach ($groupItems as $navId => $navItem): ?>
                        <?php
                        $isActive = $navItem['is_active'];
                        $isUrgent = isset($navItem['urgent']) && $navItem['urgent'];
                        $isPriority = isset($navItem['highlight_important']) && $navItem['highlight_important'];
                        $showBadge = isset($navItem['show_badge']) && $navItem['show_badge'];
                        $badgeCount = 0;
                        
                        // Get badge count
                        if ($showBadge && isset($navItem['badge_type'])) {
                            $badgeCount = $navManager->getBadgeCount($navItem['badge_type']);
                        } elseif ($navId === 'loan_approvals') {
                            $badgeCount = $pendingLoansCount;
                        } elseif ($navId === 'overdue_payments') {
                            $badgeCount = $overdueCount;
                        }
                        
                        $cssClasses = ['nav-item-link'];
                        if ($isActive) $cssClasses[] = 'active';
                        if ($isPriority) $cssClasses[] = 'priority-item';
                        if ($isUrgent) $cssClasses[] = 'urgent-item';
                        ?>
                        
                        <li class="nav-item mb-1">
                            <a href="<?= htmlspecialchars($navManager->getFullUrl($navItem['url'])) ?>" 
                               class="<?= implode(' ', $cssClasses) ?>"
                               data-nav-id="<?= htmlspecialchars($navId) ?>"
                               data-title="<?= htmlspecialchars($navItem['title']) ?>"
                               aria-current="<?= $isActive ? 'page' : 'false' ?>">
                                
                                <i data-feather="<?= htmlspecialchars($navItem['icon']) ?>" class="nav-icon"></i>
                                <span class="nav-text"><?= htmlspecialchars($navItem['title']) ?></span>
                                
                                <?php if ($badgeCount > 0): ?>
                                    <span class="badge bg-<?= $isUrgent ? 'warning' : 'danger' ?> nav-badge" 
                                          data-count="<?= $badgeCount ?>">
                                        <?= $badgeCount ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endforeach; ?>

            <!-- Urgent Actions (High Priority Items) -->
            <?php if ($pendingLoansCount > 5): ?>
                <div class="nav-group-separator"></div>
                <div class="group-title text-warning">
                    <i data-feather="alert-triangle" class="group-title-icon"></i>
                    ⚠️ Urgent Actions
                </div>
                <ul class="nav flex-column px-2 mb-2">
                    <li class="nav-item mb-1">
                        <a href="<?= htmlspecialchars($navManager->getFullUrl('/public/loans/approvals.php')) ?>" 
                           class="nav-item-link urgent-item <?= ($debugInfo['currentPage'] === 'loan_approvals') ? 'active' : '' ?>"
                           data-title="Critical: Review <?= $pendingLoansCount ?> pending loans">
                            <i data-feather="zap" class="nav-icon"></i>
                            <span class="nav-text">Critical Priority!</span>
                            <span class="badge bg-danger nav-badge" data-count="<?= $pendingLoansCount ?>">
                                <?= $pendingLoansCount ?>
                            </span>
                        </a>
                    </li>
                </ul>
            <?php endif; ?>

            <!-- Quick Actions Section -->
            <?php if (!empty($quickActions)): ?>
                <div class="quick-actions">
                    <div class="quick-actions-title">
                        <i data-feather="zap" style="width: 12px; height: 12px; margin-right: 6px;"></i>
                        Quick Actions
                    </div>
                    
                    <div class="d-grid gap-2">
                        <?php foreach ($quickActions as $actionId => $action): ?>
                            <?php 
                            // Skip review approvals if no pending items and not showing as fallback
                            if ($actionId === 'review_approvals' && $pendingLoansCount === 0 && isset($action['show_if_pending'])) {
                                continue;
                            }
                            
                            $buttonText = $action['title'];
                            if ($actionId === 'review_approvals' && $pendingLoansCount > 0) {
                                $buttonText = "Review Approvals ({$pendingLoansCount})";
                            }
                            ?>
                            
                            <a href="<?= htmlspecialchars($navManager->getFullUrl($action['url'])) ?>" 
                               class="btn btn-sm <?= htmlspecialchars($action['class']) ?> quick-action-btn"
                               data-action-id="<?= htmlspecialchars($actionId) ?>"
                               data-title="<?= htmlspecialchars($action['title']) ?>">
                                <i data-feather="<?= htmlspecialchars($action['icon']) ?>" class="quick-action-icon"></i>
                                <span class="quick-action-text"><?= htmlspecialchars($buttonText) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Footer Info -->
            <div class="px-3 py-2 mt-auto">
                <small class="text-muted d-block" style="font-size: 0.7rem;">
                    <?= htmlspecialchars($userRole) ?> | <?= date('M d, Y') ?>
                </small>
            </div>
        </div>
    </nav>
</div>

<!-- Enhanced Navigation JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Navigation enhancement functionality
    const navManager = {
        // Initialize navigation enhancements
        init() {
            this.handleActiveStates();
            this.handleMobileNavigation();
            this.handleAccessibility();
            this.logNavigationData();
        },
        
        // Enhance active state handling
        handleActiveStates() {
            const activeLinks = document.querySelectorAll('.nav-item-link.active');
            activeLinks.forEach(link => {
                // Add smooth scroll behavior for active items
                if (link.getBoundingClientRect().top > window.innerHeight) {
                    link.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        },
        
        // Handle mobile navigation
        handleMobileNavigation() {
            const sidebar = document.getElementById('sidebarMenu');
            const toggleButton = document.querySelector('[data-bs-toggle="collapse"]');
            
            if (toggleButton && sidebar) {
                // Close sidebar when clicking on nav items on mobile
                document.querySelectorAll('.nav-item-link').forEach(link => {
                    link.addEventListener('click', () => {
                        if (window.innerWidth < 768) {
                            const bsCollapse = new bootstrap.Collapse(sidebar, { toggle: false });
                            bsCollapse.hide();
                        }
                    });
                });
            }
        },
        
        // Enhance accessibility
        handleAccessibility() {
            document.querySelectorAll('.nav-item-link').forEach(link => {
                // Add proper ARIA labels
                const title = link.dataset.title;
                if (title) {
                    link.setAttribute('aria-label', title);
                }
                
                // Handle keyboard navigation
                link.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        link.click();
                    }
                });
            });
        },
        
        // Log navigation data for debugging
        logNavigationData() {
            console.group('Navigation Debug Information');
            console.log('Current Page:', <?= json_encode($debugInfo['currentPage']) ?>);
            console.log('Current Directory:', <?= json_encode($debugInfo['currentDirectory']) ?>);
            console.log('Current URI:', <?= json_encode($debugInfo['currentUri']) ?>);
            console.log('User Role:', <?= json_encode($debugInfo['userRole']) ?>);
            console.log('Pending Loans:', <?= $pendingLoansCount ?>);
            console.log('Active Navigation Items:', document.querySelectorAll('.nav-item-link.active').length);
            console.groupEnd();
        }
    };
    
    // Initialize navigation manager
    navManager.init();
    
    // Handle badge updates (if needed for real-time updates)
    window.updateNavigationBadges = function(badgeData) {
        Object.entries(badgeData).forEach(([type, count]) => {
            const badges = document.querySelectorAll(`[data-count]`);
            badges.forEach(badge => {
                const navLink = badge.closest('.nav-item-link, .quick-action-btn');
                if (navLink && navLink.href.includes(type)) {
                    badge.textContent = count;
                    badge.dataset.count = count;
                    badge.style.display = count > 0 ? 'inline' : 'none';
                }
            });
        });
    };
});
</script>

<!-- Navigation Styles -->
<style>
/* Additional styles for this specific navigation instance */
.nav-item-link[data-nav-id="dashboard"] {
    font-weight: 600;
}

.quick-action-btn[data-action-id="review_approvals"] {
    position: relative;
    overflow: hidden;
}

.quick-action-btn[data-action-id="review_approvals"]::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.quick-action-btn[data-action-id="review_approvals"]:hover::before {
    left: 100%;
}

/* Responsive adjustments for this navigation */
@media (max-width: 768px) {
    .quick-actions {
        margin: 16px 8px;
    }
    
    .group-title {
        margin: 8px 12px 4px;
        font-size: 0.7rem;
    }
}
</style>