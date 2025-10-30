/**
 * Unified Confirmation Modal System
 * Anti-jitter enhanced with comprehensive timing controls
 * Enhanced: October 30, 2025
 */

(function(global) {
    'use strict';

    const ConfirmationModals = {
        
        // Track active operations to prevent conflicts
        activeOperations: new Set(),
        /**
         * Initialize form confirmation modal
         * @param {Object} config Configuration object
         */
        initFormConfirmation: function(config) {
            const {
                formSelector,
                modalId,
                triggerButtonSelector,
                confirmButtonId,
                updateContentCallback = null,
                validateCallback = null
            } = config;

            const form = document.querySelector(formSelector);
            const modalElement = document.getElementById(modalId);
            const triggerButton = document.querySelector(triggerButtonSelector);
            const confirmButton = document.getElementById(confirmButtonId);

            if (!form || !modalElement || !triggerButton || !confirmButton) {
                console.warn('Missing required elements for form confirmation modal');
                return;
            }

            // Remove any data-bs-toggle to prevent automatic modal opening
            triggerButton.removeAttribute('data-bs-toggle');
            triggerButton.removeAttribute('data-bs-target');

            // Enhanced trigger button with anti-jitter measures
            triggerButton.addEventListener('click', async function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Prevent multiple rapid clicks
                const operationId = modalId + '_trigger';
                if (ConfirmationModals.activeOperations.has(operationId)) {
                    return;
                }
                ConfirmationModals.activeOperations.add(operationId);

                try {
                    // Run custom validation if provided
                    let isValid = true;
                    if (validateCallback && typeof validateCallback === 'function') {
                        isValid = validateCallback(form);
                    }

                    // Check HTML5 form validity
                    if (!form.checkValidity()) {
                        isValid = false;
                    }

                    // If invalid, show validation errors and stop
                    if (!isValid) {
                        form.classList.add('was-validated');
                        form.reportValidity();
                        
                        // Gentle error indication without jittering
                        const invalidField = form.querySelector(':invalid');
                        if (invalidField) {
                            invalidField.focus();
                            const fieldGroup = invalidField.closest('.notion-form-group, .form-group, .mb-3');
                            if (fieldGroup) {
                                fieldGroup.classList.add('form-validation-error');
                                setTimeout(() => fieldGroup.classList.remove('form-validation-error'), 300);
                            }
                        }
                        return;
                    }

                    // Update modal content if callback provided
                    if (updateContentCallback && typeof updateContentCallback === 'function') {
                        updateContentCallback(form, modalElement);
                    }

                    // Use enhanced modal system if available, otherwise fallback
                    if (window.ModalUtils && typeof ModalUtils.showModal === 'function') {
                        await ModalUtils.showModal(modalId);
                    } else {
                        // Fallback with anti-jitter timing
                        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                        
                        requestAnimationFrame(() => {
                            modal.show();
                            
                            // Refresh feather icons in modal
                            if (typeof feather !== 'undefined') {
                                try {
                                    feather.replace();
                                } catch (error) {
                                    console.warn('Feather icons refresh failed:', error);
                                }
                            }
                        });
                    }
                } finally {
                    // Clear operation lock after delay
                    setTimeout(() => {
                        ConfirmationModals.activeOperations.delete(operationId);
                    }, 300);
                }
            });

            // Enhanced confirm button with anti-jitter submission
            confirmButton.addEventListener('click', async function() {
                // Prevent multiple rapid clicks
                const operationId = modalId + '_confirm';
                if (ConfirmationModals.activeOperations.has(operationId)) {
                    return;
                }
                ConfirmationModals.activeOperations.add(operationId);
                
                try {
                    // Final validation check
                    if (!form.checkValidity()) {
                        return;
                    }
                    
                    // Disable button to prevent double submission
                    confirmButton.disabled = true;
                    const originalText = confirmButton.innerHTML;
                    confirmButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
                    
                    // Use enhanced modal system if available
                    if (window.ModalUtils && typeof ModalUtils.hideModal === 'function') {
                        await ModalUtils.hideModal(modalId);
                        setTimeout(() => form.submit(), 50);
                    } else {
                        // Fallback with smooth hiding
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        }
                        
                        setTimeout(() => form.submit(), 150);
                    }
                } catch (error) {
                    console.warn('Confirm operation failed:', error);
                    // Restore button state on error
                    confirmButton.disabled = false;
                    confirmButton.innerHTML = originalText;
                } finally {
                    // Clear operation lock
                    setTimeout(() => {
                        ConfirmationModals.activeOperations.delete(operationId);
                    }, 1000);
                }
            });
        },

        /**
         * Initialize action confirmation modal (for delete, approve, etc.)
         * @param {Object} config Configuration object
         */
        initActionConfirmation: function(config) {
            const {
                modalId,
                triggerSelector,
                confirmButtonId,
                formId = null,
                updateContentCallback = null,
                beforeShowCallback = null,
                afterConfirmCallback = null
            } = config;

            const modalElement = document.getElementById(modalId);
            const confirmButton = document.getElementById(confirmButtonId);

            if (!modalElement || !confirmButton) {
                console.warn(`Missing required elements for action confirmation modal: ${modalId}`);
                return;
            }

            // Use event delegation for dynamically added trigger buttons
            document.addEventListener('click', function(e) {
                const trigger = e.target.closest(triggerSelector);
                if (!trigger) return;

                e.preventDefault();
                e.stopPropagation();

                // Run before show callback if provided
                if (beforeShowCallback && typeof beforeShowCallback === 'function') {
                    const shouldShow = beforeShowCallback(trigger, modalElement);
                    if (shouldShow === false) return;
                }

                // Update modal content with data attributes
                if (updateContentCallback && typeof updateContentCallback === 'function') {
                    updateContentCallback(trigger, modalElement);
                } else {
                    // Default: update content based on data attributes
                    ConfirmationModals._updateModalFromDataAttributes(trigger, modalElement);
                }

                // Show modal smoothly
                setTimeout(() => {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                    modal.show();
                    
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                }, 10);
            });

            // Handle confirm button click
            confirmButton.addEventListener('click', function() {
                if (afterConfirmCallback && typeof afterConfirmCallback === 'function') {
                    afterConfirmCallback(modalElement);
                } else if (formId) {
                    const form = document.getElementById(formId);
                    if (form) {
                        // Hide modal first
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        }
                        
                        // Submit form after modal animation
                        setTimeout(() => {
                            form.submit();
                        }, 150);
                    }
                }
            });
        },

        /**
         * Internal helper to update modal content from data attributes
         * @private
         */
        _updateModalFromDataAttributes: function(trigger, modalElement) {
            const dataAttributes = trigger.dataset;
            
            Object.keys(dataAttributes).forEach(key => {
                // Look for corresponding element in modal
                const targetId = 'modal' + key.charAt(0).toUpperCase() + key.slice(1);
                const targetElement = modalElement.querySelector(`#${targetId}`);
                
                if (targetElement) {
                    targetElement.textContent = dataAttributes[key];
                }
            });
        },

        /**
         * Initialize dynamic modal (for collection sheets, etc.)
         * @param {string} modalHTML HTML content for modal
         * @param {string} modalId Modal ID
         * @param {Function} callback Optional callback after modal shown
         */
        showDynamicModal: function(modalHTML, modalId, callback = null) {
            // Remove existing modal with same ID
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

            // Use requestAnimationFrame for smooth rendering
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    const modalElement = document.getElementById(modalId);
                    if (modalElement) {
                        const modal = new bootstrap.Modal(modalElement, {
                            backdrop: 'static',
                            keyboard: true
                        });
                        
                        modal.show();

                        // Refresh feather icons
                        if (typeof feather !== 'undefined') {
                            feather.replace();
                        }

                        // Call callback if provided
                        if (callback && typeof callback === 'function') {
                            callback(modal, modalElement);
                        }
                    }
                });
            });
        },

        /**
         * Enhanced global modal handlers with comprehensive anti-jitter
         */
        initGlobalHandlers: function() {
            // Enhanced body scroll prevention
            document.addEventListener('show.bs.modal', function(e) {
                const modal = e.target;
                
                // Mark body state
                document.body.classList.add('modal-opening');
                
                // Smooth scrollbar compensation (only if ModalUtils not handling it)
                if (!window.ModalUtils) {
                    const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
                    document.body.style.paddingRight = scrollbarWidth + 'px';
                    document.body.style.overflow = 'hidden';
                }
                
                // Pause conflicting animations globally
                const conflictingElements = document.querySelectorAll(
                    '.ripple-animation, .shake-animation, .stagger-fade-in > *, .animate-on-scroll'
                );
                conflictingElements.forEach(element => {
                    if (!modal.contains(element)) {
                        element.style.animationPlayState = 'paused';
                        element.style.transitionDelay = '0s';
                    }
                });
            });

            document.addEventListener('shown.bs.modal', function(e) {
                // Clean up opening state
                document.body.classList.remove('modal-opening');
                document.body.classList.add('modal-active');
                
                // Enhanced feather icons refresh
                if (typeof feather !== 'undefined') {
                    try {
                        feather.replace();
                    } catch (error) {
                        console.warn('Feather icons refresh failed:', error);
                    }
                }
            });

            document.addEventListener('hide.bs.modal', function(e) {
                document.body.classList.add('modal-closing');
                document.body.classList.remove('modal-active');
            });

            document.addEventListener('hidden.bs.modal', function(e) {
                const modal = e.target;
                
                // Restore scroll only if no other modals (and ModalUtils not handling it)
                if (!window.ModalUtils && !document.querySelector('.modal.show')) {
                    setTimeout(() => {
                        document.body.style.paddingRight = '';
                        document.body.style.overflow = '';
                    }, 100);
                }
                
                // Resume animations after delay
                setTimeout(() => {
                    const pausedElements = document.querySelectorAll('[style*="animation-play-state: paused"]');
                    pausedElements.forEach(element => {
                        element.style.animationPlayState = '';
                        element.style.transitionDelay = '';
                    });
                    
                    document.body.classList.remove('modal-closing');
                }, 100);
            });

            // Enhanced escape key handling
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !e.defaultPrevented) {
                    const openModal = document.querySelector('.modal.show');
                    if (openModal) {
                        const modal = bootstrap.Modal.getInstance(openModal);
                        if (modal) {
                            e.preventDefault();
                            modal.hide();
                        }
                    }
                }
            });
        }
    };

    // Expose to global scope
    global.ConfirmationModals = ConfirmationModals;

    // Auto-initialize global handlers
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            ConfirmationModals.initGlobalHandlers();
        });
    } else {
        ConfirmationModals.initGlobalHandlers();
    }

})(window);
