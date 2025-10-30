/**
 * Modal Utility Functions
 * Standardized modal management to prevent jittering and conflicts
 * Created: October 30, 2025
 */

(function(global) {
    'use strict';

    // Modal utility namespace
    const ModalUtils = {
        
        /**
         * Safely show a modal with proper instance management
         * @param {string} modalId - The modal element ID
         * @param {Object} options - Bootstrap modal options
         * @returns {Object} Modal instance
         */
        showModal: function(modalId, options = {}) {
            const modalElement = document.getElementById(modalId);
            if (!modalElement) {
                console.warn(`Modal with ID "${modalId}" not found`);
                return null;
            }
            
            // Use getOrCreateInstance to prevent conflicts
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement, options);
            modal.show();
            return modal;
        },

        /**
         * Safely hide a modal
         * @param {string} modalId - The modal element ID
         */
        hideModal: function(modalId) {
            const modalElement = document.getElementById(modalId);
            if (!modalElement) {
                console.warn(`Modal with ID "${modalId}" not found`);
                return;
            }
            
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
        },

        /**
         * Update modal content safely
         * @param {string} modalId - The modal element ID
         * @param {Object} content - Object with element IDs as keys and content as values
         */
        updateModalContent: function(modalId, content) {
            const modalElement = document.getElementById(modalId);
            if (!modalElement) {
                console.warn(`Modal with ID "${modalId}" not found`);
                return;
            }

            Object.keys(content).forEach(elementId => {
                const element = modalElement.querySelector(`#${elementId}`);
                if (element) {
                    if (content[elementId].html) {
                        element.innerHTML = content[elementId].html;
                    } else {
                        element.textContent = content[elementId];
                    }
                }
            });
        },

        /**
         * Create and show a dynamic modal with smooth animation
         * @param {string} modalHTML - The modal HTML content
         * @param {string} modalId - The modal ID (optional, will extract from HTML)
         * @param {Function} callback - Optional callback after modal is shown
         */
        createAndShowModal: function(modalHTML, modalId = null, callback = null) {
            // Extract modal ID if not provided
            if (!modalId) {
                const match = modalHTML.match(/id="([^"]*Modal[^"]*)"/);
                modalId = match ? match[1] : 'dynamicModal_' + Date.now();
            }

            // Remove existing modal with same ID to prevent conflicts
            const existingModal = document.getElementById(modalId);
            if (existingModal) {
                const existingInstance = bootstrap.Modal.getInstance(existingModal);
                if (existingInstance) {
                    existingInstance.dispose();
                }
                existingModal.remove();
            }

            // Add modal to DOM
            document.body.insertAdjacentHTML('beforeend', modalHTML);

            // Use requestAnimationFrame to ensure DOM is ready
            requestAnimationFrame(() => {
                const modalElement = document.getElementById(modalId);
                if (modalElement) {
                    const modal = new bootstrap.Modal(modalElement);
                    modal.show();

                    // Refresh feather icons if available
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }

                    // Call callback if provided
                    if (callback && typeof callback === 'function') {
                        callback(modal, modalElement);
                    }
                }
            });
        },

        /**
         * Set up confirmation modal with standardized behavior
         * @param {Object} config - Configuration object
         */
        setupConfirmationModal: function(config) {
            const {
                modalId,
                triggerSelector,
                formId = null,
                confirmCallback = null,
                updateContentCallback = null
            } = config;

            const modalElement = document.getElementById(modalId);
            const confirmButton = modalElement.querySelector('.btn-primary, .btn-danger, .btn-warning, .btn-success');
            
            if (!modalElement || !confirmButton) {
                console.warn(`Required elements not found for modal "${modalId}"`);
                return;
            }

            // Handle trigger buttons
            if (triggerSelector) {
                document.addEventListener('click', function(e) {
                    if (e.target.matches(triggerSelector)) {
                        e.preventDefault();
                        
                        // Update modal content if callback provided
                        if (updateContentCallback && typeof updateContentCallback === 'function') {
                            updateContentCallback(e.target, modalElement);
                        }
                        
                        ModalUtils.showModal(modalId);
                    }
                });
            }

            // Handle confirm button
            confirmButton.addEventListener('click', function() {
                if (confirmCallback && typeof confirmCallback === 'function') {
                    confirmCallback(modalElement);
                } else if (formId) {
                    const form = document.getElementById(formId);
                    if (form) {
                        form.submit();
                    }
                }
            });
        },

        /**
         * Initialize all modals with smooth transitions
         */
        initializeModalSystem: function() {
            // Add modal CSS if not already present
            if (!document.querySelector('link[href*="modals.css"]')) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = '/public/assets/css/modals.css';
                document.head.appendChild(link);
            }

            // Enhance all existing modals
            document.querySelectorAll('.modal').forEach(modal => {
                // Ensure proper fade class
                if (!modal.classList.contains('fade')) {
                    modal.classList.add('fade');
                }

                // Add smooth transition class
                const dialog = modal.querySelector('.modal-dialog');
                if (dialog && !dialog.classList.contains('modal-smooth')) {
                    dialog.classList.add('modal-smooth');
                }
            });

            // Global modal event handlers
            document.addEventListener('show.bs.modal', function(e) {
                // Pause any conflicting animations
                const modal = e.target;
                const conflictingElements = modal.querySelectorAll('.ripple-animation, .shake-animation');
                conflictingElements.forEach(el => {
                    el.style.animationPlayState = 'paused';
                });
            });

            document.addEventListener('shown.bs.modal', function(e) {
                // Refresh feather icons in modal
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            });

            document.addEventListener('hide.bs.modal', function(e) {
                // Resume animations when modal is hidden
                const modal = e.target;
                const pausedElements = modal.querySelectorAll('.ripple-animation, .shake-animation');
                pausedElements.forEach(el => {
                    el.style.animationPlayState = 'running';
                });
            });
        },

        /**
         * Dispose of all modal instances to prevent memory leaks
         */
        disposeAllModals: function() {
            document.querySelectorAll('.modal').forEach(modalElement => {
                const modalInstance = bootstrap.Modal.getInstance(modalElement);
                if (modalInstance) {
                    modalInstance.dispose();
                }
            });
        }
    };

    // Expose to global scope
    global.ModalUtils = ModalUtils;

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ModalUtils.initializeModalSystem);
    } else {
        ModalUtils.initializeModalSystem();
    }

})(window);