<?php
/**
 * Staff User Account Creation/Edit Form for Fanders Microfinance System
 */
?>

<div class="enhanced-form-wrapper">
    <form action="" method="post" class="enhanced-form needs-validation" novalidate>
        <?= $csrf->getTokenField() ?>
        
        <!-- Enhanced Form Header -->
        <div class="enhanced-form-header">
            <div class="enhanced-form-header-icon">
                <i data-feather="user-plus"></i>
            </div>
            <h1 class="enhanced-form-header-title">
                <?= isset($editUser['id']) ? 'Edit Staff Account' : 'Create Staff Account' ?>
            </h1>
            <p class="enhanced-form-header-subtitle">
                <?= isset($editUser['id']) ? 'Update staff member information and access level' : 'Create a new staff account for the system' ?>
            </p>
        </div>

        <!-- Enhanced Form Body -->
        <div class="enhanced-form-body">
    
            <!-- Account Information Section -->
            <section class="enhanced-form-section">
                <div class="enhanced-form-section-header">
                    <div class="enhanced-form-section-icon">
                        <i data-feather="user-check"></i>
                    </div>
                    <h2 class="enhanced-form-section-title">Account Information</h2>
                    <div class="enhanced-form-section-divider"></div>
                </div>

                <div class="enhanced-form-grid">
                    <!-- Full Name Field -->
                    <div class="enhanced-form-group">
                        <label for="name" class="enhanced-form-label required">Full Name</label>
                        <div class="enhanced-form-input-wrapper">
                            <input type="text" 
                                class="enhanced-form-control" 
                                id="name" 
                                name="name"
                                value="<?= htmlspecialchars($editUser['name'] ?? '') ?>" 
                                required 
                                placeholder="Enter full name">
                            <div class="enhanced-form-icon">
                                <i data-feather="user"></i>
                            </div>
                        </div>
                        <div class="enhanced-form-error">Please enter your full name.</div>
                    </div>

                    <!-- Email Field -->
                    <div class="enhanced-form-group">
                        <label for="email" class="enhanced-form-label required">Email Address</label>
                        <div class="enhanced-form-input-wrapper">
                            <input type="email" 
                                class="enhanced-form-control" 
                                id="email" 
                                name="email"
                                value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" 
                                required 
                                placeholder="Enter email address">
                            <div class="enhanced-form-icon">
                                <i data-feather="mail"></i>
                            </div>
                        </div>
                        <div class="enhanced-form-error">Please enter a valid and unique email address.</div>
                    </div>

                    <!-- Phone Field -->
                    <div class="enhanced-form-group">
                        <label for="phone_number" class="enhanced-form-label required">Phone Number</label>
                        <div class="enhanced-form-input-wrapper">
                            <input type="text" 
                                class="enhanced-form-control" 
                                id="phone_number" 
                                name="phone_number"
                                value="<?= htmlspecialchars($editUser['phone_number'] ?? '') ?>" 
                                required 
                                pattern="\d{8,15}" 
                                placeholder="Enter phone number">
                            <div class="enhanced-form-icon">
                                <i data-feather="phone"></i>
                            </div>
                        </div>
                        <div class="enhanced-form-error">Please enter a valid and unique phone number.</div>
                    </div>

                    <!-- Password Field -->
                    <div class="enhanced-form-group">
                        <label for="password" class="enhanced-form-label <?= isset($editUser['id']) ? '' : 'required' ?>">Password</label>
                        <div class="enhanced-form-input-wrapper">
                            <input type="password" 
                                class="enhanced-form-control" 
                                id="password" 
                                name="password"
                                <?= isset($editUser['id']) ? '' : 'required' ?> 
                                placeholder="Enter password">
                            <button class="enhanced-form-addon" type="button" id="toggle-password"
                                onclick="togglePasswordVisibility('password')">
                                <i data-feather="eye" id="password-toggle-icon"></i>
                            </button>
                        </div>
                        <div class="enhanced-form-error">Please enter a password.</div>
                        <div class="enhanced-form-help">
                            <?= isset($editUser['id']) ? 'Leave empty to keep current password.' : 'Must enter password.' ?>
                        </div>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="enhanced-form-group">
                        <label for="password_confirmation" class="enhanced-form-label <?= isset($editUser['id']) ? '' : 'required' ?>">Confirm Password</label>
                        <div class="enhanced-form-input-wrapper">
                            <input type="password" 
                                class="enhanced-form-control" 
                                id="password_confirmation" 
                                name="password_confirmation"
                                <?= isset($editUser['id']) ? '' : 'required' ?> 
                                placeholder="Confirm password">
                            <div class="enhanced-form-icon">
                                <i data-feather="lock"></i>
                            </div>
                        </div>
                        <div class="enhanced-form-error">Passwords do not match.</div>
                    </div>

                </div>
            </section>

            <!-- Permissions & Access Section -->
            <section class="enhanced-form-section">
                <div class="enhanced-form-section-header">
                    <div class="enhanced-form-section-icon">
                        <i data-feather="shield"></i>
                    </div>
                    <h2 class="enhanced-form-section-title">Permissions & Access</h2>
                    <div class="enhanced-form-section-divider"></div>
                </div>

                <div class="enhanced-form-grid">
                    <?php if (in_array($userRole, ['super-admin', 'admin'])): ?>
                        <!-- Role Field -->
                        <div class="enhanced-form-group">
                            <label for="role" class="enhanced-form-label required">Role</label>
                            <div class="enhanced-form-input-wrapper">
                                <select class="enhanced-form-control enhanced-form-select" id="role" name="role" required>
                                    <option value="">Select role...</option>
                                    <?php if ($userRole === 'super-admin'): ?>
                                        <option value="super-admin" <?= ($editUser['role'] ?? '') === 'super-admin' ? 'selected' : '' ?>>Super Admin</option>
                                        <option value="admin" <?= ($editUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        <option value="manager" <?= ($editUser['role'] ?? '') === 'manager' ? 'selected' : '' ?>>Manager</option>
                                    <?php endif; ?>
                                    <option value="account_officer" <?= ($editUser['role'] ?? '') === 'account_officer' ? 'selected' : '' ?>>Account Officer</option>
                                    <option value="cashier" <?= ($editUser['role'] ?? '') === 'cashier' ? 'selected' : '' ?>>Cashier</option>
                                </select>
                                <div class="enhanced-form-icon">
                                    <i data-feather="users"></i>
                                </div>
                            </div>
                            <div class="enhanced-form-error">Please select a role.</div>
                            <?php if ($userRole === 'admin'): ?>
                                <div class="enhanced-form-help">Admins can only add limited staff accounts (Account Officers or Cashiers).</div>
                            <?php endif; ?>
                        </div>

                        <!-- Account Status Field -->
                        <div class="enhanced-form-group">
                            <label for="status" class="enhanced-form-label required">Account Status</label>
                            <div class="enhanced-form-input-wrapper">
                                <select class="enhanced-form-control enhanced-form-select" id="status" name="status" required>
                                    <option value="">Select status...</option>
                                    <option value="active" <?= ($editUser['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= ($editUser['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                                <div class="enhanced-form-icon">
                                    <i data-feather="activity"></i>
                                </div>
                            </div>
                            <div class="enhanced-form-error">Please select account status.</div>
                        </div>

                        <?php if (isset($editUser['created_at'])): ?>
                            <!-- Account Created Info -->
                            <div class="enhanced-form-group">
                                <div class="enhanced-alert enhanced-alert-success">
                                    <div class="enhanced-alert-icon">
                                        <i data-feather="calendar"></i>
                                    </div>
                                    <div class="enhanced-alert-content">
                                        <strong>Account Created:</strong> <?= date('F d, Y \a\t g:i A', strtotime($editUser['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </section>

        </div> <!-- .enhanced-form-body -->
        <!-- Form Actions -->
        <div class="enhanced-form-actions">
            <a href="<?= APP_URL ?>/public/admins/index.php" class="btn btn-outline-secondary me-2 ripple-effect">Cancel</a>
            <button type="submit" class="btn btn-primary px-4 ripple-effect">
                <i data-feather="save" class="me-1" style="width: 16px; height: 16px;"></i>
                <?= isset($editUser['id']) ? 'Update Account' : 'Create Account' ?>
            </button>
        </div>

    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.enhanced-form');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('password_confirmation');

    // Replace feather icons (if available)
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    // Form validation
    if (form) {
        form.addEventListener('submit', function(event) {
            let valid = true;

            if (passwordInput && confirmPasswordInput) {
                if (passwordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.setCustomValidity("Passwords do not match.");
                    valid = false;
                } else {
                    confirmPasswordInput.setCustomValidity("");
                }
            }

            if (!form.checkValidity() || !valid) {
                event.preventDefault();
                event.stopPropagation();
                const invalidField = form.querySelector(':invalid');
                if (invalidField) {
                    invalidField.focus();
                    const fieldGroup = invalidField.closest('.enhanced-form-group');
                    if (fieldGroup) {
                        fieldGroup.classList.add('shake-animation');
                        setTimeout(() => {
                            fieldGroup.classList.remove('shake-animation');
                        }, 820);
                    }
                }
            }
            form.classList.add('was-validated');
        });

        if (passwordInput && confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                if (passwordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.setCustomValidity("Passwords do not match.");
                } else {
                    confirmPasswordInput.setCustomValidity("");
                }
            });
        }
    }

    // Add ripple effect to buttons
    const buttons = document.querySelectorAll('.ripple-effect');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const ripple = document.createElement('span');
            ripple.className = 'ripple-animation';
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;
            this.appendChild(ripple);
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
});

// Password visibility toggle
function togglePasswordVisibility(inputId) {
    const passwordInput = document.getElementById(inputId);
    const toggleIcon = document.getElementById(inputId + '-toggle-icon');

    if (!passwordInput) return;

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        if (toggleIcon) toggleIcon.setAttribute('data-feather', 'eye-off');
    } else {
        passwordInput.type = 'password';
        if (toggleIcon) toggleIcon.setAttribute('data-feather', 'eye');
    }
    if (typeof feather !== 'undefined') feather.replace();
}
</script>

<style>
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    .shake-animation { animation: shake 0.8s ease; }
    .ripple-effect { position: relative; overflow: hidden; }
    .ripple-animation {
        position: absolute; border-radius: 50%; background-color: rgba(255,255,255,0.7);
        width: 100px; height: 100px; margin-top: -50px; margin-left: -50px;
        animation: ripple 0.6s linear; transform: scale(0); opacity: 1;
    }
    @keyframes ripple {
        to { transform: scale(2.5); opacity: 0; }
    }
</style>
