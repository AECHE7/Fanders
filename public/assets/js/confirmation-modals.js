/**
 * Unified Confirmation Modal System
 * Eliminates jittering by proper validation and timing
 * Created: October 30, 2025
 */

(function(global) {
    'use strict';

    const ConfirmationModals = {
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

            // Handle trigger button click - validate FIRST, then show modal
            triggerButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

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
                    
                    // Focus first invalid field with shake animation
                    const invalidField = form.querySelector(':invalid');
                    if (invalidField) {
                        invalidField.focus();
                        const fieldGroup = invalidField.closest('.notion-form-group, .form-group, .mb-3');
                        if (fieldGroup) {
                            fieldGroup.classList.add('shake-animation');
                            setTimeout(() => fieldGroup.classList.remove('shake-animation'), 820);
                        }
                    }
                    return;
                }

                // Update modal content if callback provided
                if (updateContentCallback && typeof updateContentCallback === 'function') {
                    updateContentCallback(form, modalElement);
                }

                // Show modal smoothly
                const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                
                // Use setTimeout to ensure smooth rendering
                setTimeout(() => {
                    modal.show();
                    
                    // Refresh feather icons in modal
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                }, 10);
            });

            // Handle confirm button click
            confirmButton.addEventListener('click', function() {
                // Final validation check before submit
                if (form.checkValidity()) {
                    // Hide modal first
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    if (modal) {
                        modal.hide();
                    }
                    
                    // Submit form after modal is hidden
                    setTimeout(() => {
                        form.submit();
                    }, 150);
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
         * Global modal event handlers to prevent jittering
         */
        initGlobalHandlers: function() {
            // Prevent backdrop from causing layout shifts
            document.addEventListener('show.bs.modal', function(e) {
                // Add class to body to prevent scroll
                document.body.style.paddingRight = `${window.innerWidth - document.documentElement.clientWidth}px`;
            });

            document.addEventListener('shown.bs.modal', function(e) {
                // Refresh feather icons
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
            });

            document.addEventListener('hide.bs.modal', function(e) {
                // Delay removing padding to prevent flash
                setTimeout(() => {
                    if (!document.querySelector('.modal.show')) {
                        document.body.style.paddingRight = '';
                    }
                }, 150);
            });

            // Handle escape key globally
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const openModal = document.querySelector('.modal.show');
                    if (openModal) {
                        const modal = bootstrap.Modal.getInstance(openModal);
                        if (modal) {
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
