/**
 * Interactive elements and UI/UX enhancements for Library Management System
 */

// Initialize interactive elements when the DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add hover-lift effect to cards
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        card.classList.add('hover-lift');
    });
    
    // Add staggered animation to dashboard stats
    const statCards = document.querySelectorAll('.dashboard-stats-container');
    statCards.forEach(container => {
        container.classList.add('stagger-fade-in');
    });
    
    // Add interactive behavior to list items
    const listItems = document.querySelectorAll('.list-group-item');
    listItems.forEach(item => {
        item.classList.add('interactive-list-item');
    });
    
    // Add focus-visible class to interactive elements
    const interactiveElements = document.querySelectorAll('a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
    interactiveElements.forEach(element => {
        element.classList.add('focus-visible');
    });
    
    // Add tooltips to action buttons
    setupTooltips();
    
    // Make tables interactive
    setupInteractiveTables();
    
    // Add fancy checkmarks to completed tasks or items
    setupCompletionCheckmarks();
    
    // Initialize custom form interactions
    setupFormInteractions();
    
    // Add search highlighting
    setupSearchHighlighting();
    
    // Add notification system
    setupNotifications();
    
    // Add scroll animations
    setupScrollAnimations();
});

/**
 * Add tooltips to action buttons and elements with data-tooltip attribute
 */
function setupTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        const tooltip = document.createElement('span');
        tooltip.className = 'tooltip-text';
        tooltip.textContent = element.getAttribute('data-tooltip');
        
        element.classList.add('custom-tooltip');
        element.appendChild(tooltip);
    });
}

/**
 * Make tables more interactive with sorting, highlighting and row actions
 */
function setupInteractiveTables() {
    const tables = document.querySelectorAll('.table');
    
    tables.forEach(table => {
        // Add hover class to tables for row highlighting
        table.classList.add('table-hover');
        
        // Get all sortable headers (th elements with data-sort attribute)
        const sortableHeaders = table.querySelectorAll('th[data-sort]');
        
        // Add click event to sortable headers
        sortableHeaders.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                const sortKey = this.getAttribute('data-sort');
                const sortDirection = this.getAttribute('data-sort-direction') === 'asc' ? 'desc' : 'asc';
                
                // Update sort direction attribute
                sortableHeaders.forEach(h => h.removeAttribute('data-sort-direction'));
                this.setAttribute('data-sort-direction', sortDirection);
                
                // Add sort indicator
                sortableHeaders.forEach(h => {
                    const indicator = h.querySelector('.sort-indicator');
                    if (indicator) indicator.remove();
                });
                
                const indicator = document.createElement('span');
                indicator.className = 'sort-indicator ms-1';
                indicator.innerHTML = sortDirection === 'asc' ? '↑' : '↓';
                this.appendChild(indicator);
                
                // Perform the actual sorting
                sortTable(table, sortKey, sortDirection);
            });
        });
        
        // Add click event to table rows (optional)
        const tableRows = table.querySelectorAll('tbody tr');
        tableRows.forEach(row => {
            if (row.getAttribute('data-href')) {
                row.style.cursor = 'pointer';
                row.addEventListener('click', function() {
                    window.location = this.getAttribute('data-href');
                });
            }
            
            // Add fade-in animation to rows
            row.classList.add('fade-in');
        });
    });
}

/**
 * Sort a table by the given key and direction
 */
function sortTable(table, key, direction) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Sort the rows
    rows.sort((a, b) => {
        let aValue = a.querySelector(`td[data-key="${key}"]`) ? 
                     a.querySelector(`td[data-key="${key}"]`).textContent : '';
        let bValue = b.querySelector(`td[data-key="${key}"]`) ? 
                     b.querySelector(`td[data-key="${key}"]`).textContent : '';
        
        // Try to convert to numbers if possible
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            aValue = aNum;
            bValue = bNum;
        }
        
        if (direction === 'asc') {
            return aValue > bValue ? 1 : -1;
        } else {
            return aValue < bValue ? 1 : -1;
        }
    });
    
    // Add highlight animation to sorted column
    rows.forEach(row => {
        const cell = row.querySelector(`td[data-key="${key}"]`);
        if (cell) {
            // Remove any existing highlight
            row.querySelectorAll('td').forEach(td => td.classList.remove('highlight-effect'));
            cell.classList.add('highlight-effect');
        }
    });
    
    // Clear the table and add the sorted rows
    tbody.innerHTML = '';
    rows.forEach(row => tbody.appendChild(row));
}

/**
 * Add animated checkmarks to completed tasks or items
 */
function setupCompletionCheckmarks() {
    const completedItems = document.querySelectorAll('.completed-item');
    
    completedItems.forEach(item => {
        const checkmark = document.createElement('div');
        checkmark.className = 'success-checkmark';
        checkmark.innerHTML = `
            <div class="check-icon">
                <span class="icon-line line-tip"></span>
                <span class="icon-line line-long"></span>
            </div>
        `;
        
        item.appendChild(checkmark);
    });
}

/**
 * Setup interactive form elements and animations
 */
function setupFormInteractions() {
    // Convert regular inputs to interactive form fields
    const formControls = document.querySelectorAll('.form-control:not(.interactive-converted)');
    
    formControls.forEach(input => {
        if (input.tagName === 'TEXTAREA') return; // Skip textareas for now
        
        const parent = input.parentElement;
        const label = parent.querySelector('label[for="' + input.id + '"]');
        
        if (label && !parent.classList.contains('interactive-form-field')) {
            parent.classList.add('interactive-form-field');
            input.setAttribute('placeholder', ' ');
            input.classList.add('interactive-converted');
            
            // Add focus animation
            input.addEventListener('focus', function() {
                parent.classList.add('is-focused');
            });
            
            input.addEventListener('blur', function() {
                parent.classList.remove('is-focused');
            });
        }
    });
    
    // Add animation to form submission
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                if (form.checkValidity()) {
                    this.classList.add('pulse');
                    setTimeout(() => {
                        this.classList.remove('pulse');
                    }, 2000);
                }
            });
        }
    });
}

/**
 * Setup search highlighting functionality
 */
function setupSearchHighlighting() {
    const searchInput = document.getElementById('search-input');
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        if (searchTerm.length < 2) {
            clearHighlights();
            return;
        }
        
        const content = document.querySelector('.search-content');
        if (!content) return;
        
        clearHighlights();
        
        // Find text nodes that contain the search term
        findAndHighlightText(content, searchTerm);
    });
}

/**
 * Find and highlight text in content
 */
function findAndHighlightText(element, searchTerm) {
    if (element.nodeType === Node.TEXT_NODE) {
        const text = element.nodeValue.toLowerCase();
        const index = text.indexOf(searchTerm);
        
        if (index >= 0) {
            const range = document.createRange();
            range.setStart(element, index);
            range.setEnd(element, index + searchTerm.length);
            
            const span = document.createElement('span');
            span.className = 'highlight-text';
            span.style.backgroundColor = 'yellow';
            span.style.color = 'black';
            
            range.surroundContents(span);
            return true;
        }
        return false;
    } else if (element.nodeType === Node.ELEMENT_NODE) {
        // Skip already highlighted elements
        if (element.classList && element.classList.contains('highlight-text')) {
            return false;
        }
        
        let found = false;
        const childNodes = Array.from(element.childNodes);
        
        for (let i = 0; i < childNodes.length; i++) {
            if (findAndHighlightText(childNodes[i], searchTerm)) {
                found = true;
                // Skip the next node as it might be the newly inserted span
                i++;
            }
        }
        
        return found;
    }
    
    return false;
}

/**
 * Clear search highlights
 */
function clearHighlights() {
    const highlights = document.querySelectorAll('.highlight-text');
    highlights.forEach(highlight => {
        const parent = highlight.parentNode;
        parent.replaceChild(document.createTextNode(highlight.textContent), highlight);
        parent.normalize();
    });
}

/**
 * Setup notification system
 */
function setupNotifications() {
    // Create notification container if it doesn't exist
    let notificationContainer = document.getElementById('notification-container');
    
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.id = 'notification-container';
        notificationContainer.style.position = 'fixed';
        notificationContainer.style.top = '20px';
        notificationContainer.style.right = '20px';
        notificationContainer.style.zIndex = '9999';
        document.body.appendChild(notificationContainer);
    }
    
    // Expose notification function globally
    window.showNotification = function(message, type = 'info', duration = 5000) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} fade-in`;
        notification.style.minWidth = '250px';
        notification.style.marginBottom = '10px';
        notification.style.borderRadius = '4px';
        notification.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
        notification.innerHTML = message;
        
        // Add close button
        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'btn-close';
        closeBtn.style.float = 'right';
        closeBtn.addEventListener('click', function() {
            notification.remove();
        });
        
        notification.appendChild(closeBtn);
        
        // Add to container
        notificationContainer.appendChild(notification);
        
        // Remove after duration
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, duration);
        
        return notification;
    };
}

/**
 * Setup scroll animations
 */
function setupScrollAnimations() {
    const elements = document.querySelectorAll('.animate-on-scroll');
    
    if (elements.length > 0 && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1
        });
        
        elements.forEach(element => {
            observer.observe(element);
        });
    } else {
        // Fallback for browsers that don't support IntersectionObserver
        elements.forEach(element => {
            element.classList.add('fade-in');
        });
    }
}

// Add search filtering to any element with data-search-input and data-search-target attributes
document.addEventListener('DOMContentLoaded', function() {
    const searchInputs = document.querySelectorAll('[data-search-input]');
    
    searchInputs.forEach(input => {
        const targetSelector = input.getAttribute('data-search-target');
        const items = document.querySelectorAll(targetSelector);
        
        input.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm) || searchTerm === '') {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});

// Add confirmation to delete actions
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    
    deleteButtons.forEach(button => {
        const message = button.getAttribute('data-confirm') || 'Are you sure you want to delete this item?';
        
        button.addEventListener('click', function(e) {
            if (!confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });
});

// Add tabs functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabContainers = document.querySelectorAll('.custom-tabs');
    
    tabContainers.forEach(container => {
        const tabs = container.querySelectorAll('[data-tab-target]');
        const tabContents = container.querySelectorAll('[data-tab-content]');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const target = this.getAttribute('data-tab-target');
                
                // Remove active class from all tabs and tab contents
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));
                
                // Add active class to current tab and tab content
                this.classList.add('active');
                const activeContent = container.querySelector(`[data-tab-content="${target}"]`);
                if (activeContent) {
                    activeContent.classList.add('active');
                }
            });
        });
        
        // Activate first tab by default
        if (tabs.length > 0) {
            tabs[0].click();
        }
    });
});
