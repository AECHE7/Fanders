<?php
/**
 * Session Timeout Modal Template
 * Displays a modal when session is about to expire
 */
?>

<!-- Session Timeout Modal -->
<div class="modal fade" id="sessionTimeoutModal" tabindex="-1" aria-labelledby="sessionTimeoutModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="sessionTimeoutModalLabel">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12,6 12,12 16,14"></polyline>
                    </svg>
                    Session Timeout Warning
                </h5>
            </div>
            <div class="modal-body text-center">
                <div class="mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-warning mb-3">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12,6 12,12 16,14"></polyline>
                    </svg>
                    <p class="mb-3">Your session is about to expire due to inactivity. Would you like to extend your session or log out?</p>
                    <div class="alert alert-info">
                        <small><strong>Time remaining:</strong> <span id="countdownTimer">30</span> seconds</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <form id="sessionActionForm" method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <button type="submit" name="action" value="extend" class="btn btn-primary me-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                            <polyline points="23,4 23,10 17,10"></polyline>
                            <path d="M20.49,15A9,9,0,1,1,5.64,5.64L23,10"></path>
                        </svg>
                        Extend Session
                    </button>
                    <button type="submit" name="action" value="logout" class="btn btn-outline-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1">
                            <path d="M9,21H5a2,2,0,0,1-2-2V5a2,2,0,0,1,2-2h4"></path>
                            <polyline points="16,17 21,12 16,7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </polyline>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Session timeout modal functionality
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($session->get('session_timed_out')): ?>
        // Show modal immediately if session has timed out
        showSessionTimeoutModal();
    <?php else: ?>
        // Check for session timeout every 30 seconds
        setInterval(checkSessionTimeout, 30000);
    <?php endif; ?>
});

function checkSessionTimeout() {
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'check_session_timeout=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.timed_out) {
            showSessionTimeoutModal();
        }
    })
    .catch(error => {
        console.error('Error checking session timeout:', error);
    });
}

function showSessionTimeoutModal() {
    const modal = new bootstrap.Modal(document.getElementById('sessionTimeoutModal'), {
        backdrop: 'static',
        keyboard: false
    });
    modal.show();

    // Start countdown timer
    let timeLeft = 30;
    const timerElement = document.getElementById('countdownTimer');

    const countdownInterval = setInterval(() => {
        timeLeft--;
        timerElement.textContent = timeLeft;

        if (timeLeft <= 0) {
            clearInterval(countdownInterval);
            // Auto logout when timer reaches 0
            document.querySelector('button[name="action"][value="logout"]').click();
        }
    }, 1000);

    // Clear interval when modal is hidden (user made a choice)
    document.getElementById('sessionTimeoutModal').addEventListener('hidden.bs.modal', function() {
        clearInterval(countdownInterval);
    });
}

// Handle session action form submission via AJAX
document.getElementById('sessionActionForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('<?= APP_URL ?>/public/session_extend.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.action === 'extend') {
                // Hide modal and show success message
                bootstrap.Modal.getInstance(document.getElementById('sessionTimeoutModal')).hide();

                // Show success toast or alert
                showToast('Session extended successfully!', 'success');

                // Refresh the page to reset activity timer
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else if (data.action === 'logout') {
                // Redirect to login
                window.location.href = '<?= APP_URL ?>/public/login.php';
            }
        } else {
            showToast(data.message || 'An error occurred', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while processing your request', 'error');
    });
});

function showToast(message, type) {
    // Simple toast implementation - you can enhance this
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
        ${message}
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 5000);
}
</script>
