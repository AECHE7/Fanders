/**
 * Modal Utility Functions
 * Advanced jittering prevention and smooth modal management
 * Enhanced: October 30, 2025
 */

(function(global) {
    'use strict';

    // Modal utility namespace with anti-jitter enhancements
    const ModalUtils = {
        
        // Active modals tracking
        activeModals: new Set(),
        animationFrameId: null,
        
        /**
         * Anti-jitter modal showing with proper timing
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
            
            // Prevent showing if already active
            if (this.activeModals.has(modalId)) {
                console.warn(`Modal "${modalId}" is already active`);
                return bootstrap.Modal.getInstance(modalElement);
            }
            
            // Mark as opening to prevent animation conflicts
            document.body.classList.add('modal-opening');
            modalElement.classList.add('modal-preparing');
            
            // Pause conflicting animations BEFORE showing
            this._pauseConflictingAnimations(modalElement);
            
            // Use requestAnimationFrame for smooth rendering
            return new Promise((resolve) => {
                this.animationFrameId = requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        modalElement.classList.remove('modal-preparing');
                        
                        const modal = bootstrap.Modal.getOrCreateInstance(modalElement, {
                            backdrop: options.backdrop !== false,
                            keyboard: options.keyboard !== false,
                            focus: options.focus !== false,
                            ...options
                        });
                        
                        // Track active modal
                        this.activeModals.add(modalId);
                        
                        modal.show();
                        
                        // Clean up opening state
                        setTimeout(() => {
                            document.body.classList.remove('modal-opening');
                        }, 200);
                        
                        resolve(modal);
                    });
                });
            });
        },

        /**
         * Safely hide a modal with anti-jitter measures
         * @param {string} modalId - The modal element ID
         * @returns {Promise} Promise that resolves when modal is hidden
         */
        hideModal: function(modalId) {
            const modalElement = document.getElementById(modalId);
            if (!modalElement) {
                console.warn(`Modal with ID "${modalId}" not found`);
                return Promise.resolve();
            }
            
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (!modal) {
                console.warn(`No modal instance found for "${modalId}"`);
                return Promise.resolve();
            }
            
            return new Promise((resolve) => {
                // Mark as closing
                document.body.classList.add('modal-closing');
                
                // Listen for hidden event
                const handleHidden = () => {
                    modalElement.removeEventListener('hidden.bs.modal', handleHidden);
                    this.activeModals.delete(modalId);
                    document.body.classList.remove('modal-closing');
                    this._resumeConflictingAnimations(modalElement);
                    resolve();
                };
                
                modalElement.addEventListener('hidden.bs.modal', handleHidden);
                modal.hide();
            });
        },
        
        /**
         * Pause animations that conflict with modal transitions
         * @private
         */
        _pauseConflictingAnimations: function(modalElement) {
            const conflictingElements = document.querySelectorAll(
                '.ripple-animation, .shake-animation, .stagger-fade-in > *, .animate-on-scroll'
            );
            
            conflictingElements.forEach(element => {
                if (!modalElement.contains(element)) {
                    element.style.animationPlayState = 'paused';
                    element.style.transitionDelay = '0s';
                }
            });
        },
        
        /**
         * Resume previously paused animations
         * @private
         */
        _resumeConflictingAnimations: function(modalElement) {
            setTimeout(() => {
                const pausedElements = document.querySelectorAll('[style*="animation-play-state: paused"]');
                pausedElements.forEach(element => {
                    element.style.animationPlayState = '';
                    element.style.transitionDelay = '';
                });
            }, 100);
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
         * Initialize comprehensive anti-jitter modal system
         */
        initializeModalSystem: function() {
            // Enhanced modal preparation
            document.querySelectorAll('.modal').forEach(modal => {
                // Ensure proper fade class
                if (!modal.classList.contains('fade')) {
                    modal.classList.add('fade');
                }

                // Force hardware acceleration
                const dialog = modal.querySelector('.modal-dialog');
                if (dialog) {
                    dialog.style.transform = 'translateZ(0)';
                    dialog.style.backfaceVisibility = 'hidden';
                }
            });

            // Comprehensive global modal event handlers
            document.addEventListener('show.bs.modal', (e) => {
                const modal = e.target;
                const modalId = modal.id;
                
                // Add to active tracking
                this.activeModals.add(modalId);
                
                // Prevent body scroll with smooth compensation
                this._preventBodyScroll();
                
                // Pause ALL conflicting animations globally
                this._pauseConflictingAnimations(modal);
                
                // Mark body state
                document.body.classList.add('modal-opening');
            });

            document.addEventListener('shown.bs.modal', (e) => {
                const modal = e.target;
                
                // Clean up opening state
                document.body.classList.remove('modal-opening');
                document.body.classList.add('modal-active');
                
                // Refresh feather icons with error handling
                if (typeof feather !== 'undefined') {
                    try {
                        feather.replace();
                    } catch (error) {
                        console.warn('Feather icons refresh failed:', error);
                    }
                }
                
                // Focus management for accessibility
                const focusableElement = modal.querySelector('input, select, textarea, button');
                if (focusableElement) {
                    setTimeout(() => focusableElement.focus(), 100);
                }
            });

            document.addEventListener('hide.bs.modal', (e) => {
                const modal = e.target;
                document.body.classList.add('modal-closing');
                document.body.classList.remove('modal-active');
            });

            document.addEventListener('hidden.bs.modal', (e) => {
                const modal = e.target;
                const modalId = modal.id;
                
                // Remove from active tracking
                this.activeModals.delete(modalId);
                
                // Restore body scroll if no other modals
                if (this.activeModals.size === 0) {
                    this._restoreBodyScroll();
                }
                
                // Resume animations
                setTimeout(() => {
                    this._resumeConflictingAnimations(modal);
                    document.body.classList.remove('modal-closing');
                }, 50);
            });
        },
        
        /**
         * Prevent body scroll with smooth scrollbar compensation
         * @private
         */
        _preventBodyScroll: function() {
            if (document.body.style.overflow === 'hidden') return;
            
            // Calculate scrollbar width
            const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
            
            // Apply styles smoothly
            document.body.style.paddingRight = scrollbarWidth + 'px';
            document.body.style.overflow = 'hidden';
        },
        
        /**
         * Restore body scroll smoothly
         * @private
         */
        _restoreBodyScroll: function() {
            // Delay removal to prevent flicker
            setTimeout(() => {
                document.body.style.paddingRight = '';
                document.body.style.overflow = '';
            }, 100);
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