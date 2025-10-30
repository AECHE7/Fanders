/**
 * Modal Helper Utility
 * Provides consistent modal management across the application
 * Ensures Bootstrap 5 modal compatibility and prevents conflicts
 */

class ModalHelper {
    constructor() {
        this.activeModals = new Map();
        this.init();
    }

    init() {
        // Ensure Bootstrap is loaded
        if (typeof bootstrap === 'undefined') {
            console.error('Bootstrap JS is not loaded. Modal functionality will not work.');
            return;
        }

        // Global modal event listeners for better error handling
        document.addEventListener('show.bs.modal', (event) => {
            const modal = event.target;
            console.log('Modal showing:', modal.id);
        });

        document.addEventListener('shown.bs.modal', (event) => {
            const modal = event.target;
            this.activeModals.set(modal.id, modal);
        });

        document.addEventListener('hidden.bs.modal', (event) => {
            const modal = event.target;
            this.activeModals.delete(modal.id);
        });

        document.addEventListener('hide.bs.modal', (event) => {
            const modal = event.target;
            console.log('Modal hiding:', modal.id);
        });
    }

    /**
     * Safely show a modal by ID
     * @param {string} modalId - The modal element ID
     * @param {Object} options - Additional options
     */
    show(modalId, options = {}) {
        try {
            const modalElement = document.getElementById(modalId);
            if (!modalElement) {
                console.error(`Modal element with ID '${modalId}' not found`);
                return false;
            }

            // Check if modal is already active
            if (this.activeModals.has(modalId)) {
                console.warn(`Modal '${modalId}' is already active`);
                return true;
            }

            const modal = bootstrap.Modal.getOrCreateInstance(modalElement, {
                backdrop: options.backdrop || true,
                keyboard: options.keyboard || true,
                focus: options.focus || true,
                ...options
            });

            modal.show();
            return true;
        } catch (error) {
            console.error('Error showing modal:', error);
            return false;
        }
    }

    /**
     * Safely hide a modal by ID
     * @param {string} modalId - The modal element ID
     */
    hide(modalId) {
        try {
            const modalElement = document.getElementById(modalId);
            if (!modalElement) {
                console.error(`Modal element with ID '${modalId}' not found`);
                return false;
            }

            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
                return true;
            } else {
                console.warn(`No modal instance found for '${modalId}'`);
                return false;
            }
        } catch (error) {
            console.error('Error hiding modal:', error);
            return false;
        }
    }

    /**
     * Check if a modal is currently active
     * @param {string} modalId - The modal element ID
     */
    isActive(modalId) {
        return this.activeModals.has(modalId);
    }

    /**
     * Get all active modal IDs
     */
    getActiveModals() {
        return Array.from(this.activeModals.keys());
    }

    /**
     * Hide all active modals
     */
    hideAll() {
        this.activeModals.forEach((modalElement, modalId) => {
            this.hide(modalId);
        });
    }

    /**
     * Initialize modal triggers with proper error handling
     * Call this after DOM is ready
     */
    initializeTriggers() {
        // Handle data-bs-toggle="modal" triggers
        document.querySelectorAll('[data-bs-toggle="modal"]').forEach(trigger => {
            trigger.addEventListener('click', (event) => {
                const targetId = trigger.getAttribute('data-bs-target');
                if (targetId) {
                    const modalId = targetId.startsWith('#') ? targetId.substring(1) : targetId;
                    this.show(modalId);
                }
            });
        });

        // Handle custom modal triggers
        document.querySelectorAll('[data-modal-target]').forEach(trigger => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                const modalId = trigger.getAttribute('data-modal-target');
                if (modalId) {
                    this.show(modalId);
                }
            });
        });
    }
}

// Create global instance
const modalHelper = new ModalHelper();

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    modalHelper.initializeTriggers();

    // Re-initialize Feather icons when modals are shown
    document.addEventListener('shown.bs.modal', function() {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
});

// Export for module usage (if using modules)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ModalHelper;
}

// Make available globally
window.ModalHelper = ModalHelper;
window.modalHelper = modalHelper;
