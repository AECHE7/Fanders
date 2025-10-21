/**
 * Enhanced form handling for preventing CSRF issues
 * Specifically addresses the "Invalid security token" error in client creation
 */

document.addEventListener('DOMContentLoaded', function() {
    // Enhanced form submission handling for client forms
    const clientForms = document.querySelectorAll('form.notion-form');
    
    clientForms.forEach(function(form) {
        let isSubmitting = false;
        
        form.addEventListener('submit', function(e) {
            // Prevent double submission which can cause CSRF issues
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            
            // Mark as submitting
            isSubmitting = true;
            
            // Add visual feedback
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                const originalDisabled = submitBtn.disabled;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
                
                // Re-enable after timeout to handle errors
                setTimeout(() => {
                    submitBtn.disabled = originalDisabled;
                    submitBtn.innerHTML = originalText;
                    isSubmitting = false;
                }, 15000);
            }
            
            // Disable all form inputs to prevent modification during submission
            const inputs = form.querySelectorAll('input, select, textarea, button');
            inputs.forEach(input => {
                if (input.type !== 'submit') {
                    input.setAttribute('readonly', 'readonly');
                    input.style.backgroundColor = '#f8f9fa';
                }
            });
        });
    });
    
    // Add CSRF token refresh mechanism for long-running sessions
    let csrfRefreshTimer;
    
    function refreshCSRFToken() {
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) {
            // Update all CSRF hidden inputs on the page
            const csrfInputs = document.querySelectorAll('input[name="csrf_token"]');
            csrfInputs.forEach(input => {
                input.value = metaToken.getAttribute('content');
            });
        }
    }
    
    // Refresh CSRF tokens every 10 minutes to prevent expiry issues
    csrfRefreshTimer = setInterval(refreshCSRFToken, 600000);
    
    // Clear timer when page unloads
    window.addEventListener('beforeunload', function() {
        if (csrfRefreshTimer) {
            clearInterval(csrfRefreshTimer);
        }
    });
});

// Debug function for CSRF issues
function debugCSRFToken() {
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    const formTokens = document.querySelectorAll('input[name="csrf_token"]');
    
    console.log('CSRF Debug Info:');
    console.log('Meta token:', metaToken ? metaToken.getAttribute('content') : 'None');
    console.log('Form tokens:', Array.from(formTokens).map(input => input.value));
    console.log('Total forms with CSRF:', formTokens.length);
}

// Make debug function available globally
window.debugCSRFToken = debugCSRFToken;