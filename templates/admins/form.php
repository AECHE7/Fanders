<?php
/**
 * User Account Creation/Edit Form
 */
?>

<form action="" method="post" class="notion-form needs-validation" novalidate>
    <?= $csrf->getTokenField() ?>
    
    <!-- Form Title with Icon -->
    <div class="notion-form-header mb-4">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center me-3" style="width: 38px; height: 38px; background-color: #eaf8f6;">
                <i data-feather="user" style="width: 20px; height: 20px; color: #0ca789;"></i>
            </div>
            <h5 class="mb-0"><?= isset($editUser['id']) ? 'Edit Admin Account' : 'Create Admin Account' ?></h5>
        </div>
    </div>
    
    <!-- Basic Information Section -->
    <div class="mb-4 animate-on-scroll">
        <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0 me-2">Account Information</h6>
            <div class="notion-divider flex-grow-1"></div>
        </div>
        
        <div class="row g-3 stagger-fade-in">
            <!-- Full Name Field -->
            <div class="col-md-12">
                <div class="notion-form-group interactive-form-field">
                    <input type="text" class="notion-form-control" id="name" name="name"
                        value="<?= htmlspecialchars($editUser['name'] ?? '') ?>" required placeholder=" ">
                    <label for="name" class="notion-form-label">Full Name</label>
                    <div class="invalid-feedback">Please enter your full name.</div>
                </div>
            </div>

            <!-- Email Field -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="email" class="notion-form-control" id="email" name="email"
                        value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" required placeholder=" ">
                    <label for="email" class="notion-form-label">Email Address</label>
                    <div class="invalid-feedback">Please enter a valid and unique email address.</div>
                </div>
            </div>

            <!-- Phone Field -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="text" class="notion-form-control" id="phone_number" name="phone_number"
                        value="<?= htmlspecialchars($editUser['phone_number'] ?? '') ?>" required pattern="\d{8,15}" placeholder=" ">
                    <label for="phone_number" class="notion-form-label">Phone Number</label>
                    <div class="invalid-feedback">Please enter a valid and unique phone number.</div>
                </div>
            </div>

            <!-- Password Field -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <div class="input-group">
                        <input type="password" class="notion-form-control" id="password" name="password"
                            <?= isset($editUser['id']) ? '' : 'required' ?> placeholder=" ">
                        <button class="btn btn-outline-secondary" type="button" id="toggle-password"
                            onclick="togglePasswordVisibility('password')">
                            <i data-feather="eye" id="password-toggle-icon"></i>
                        </button>
                    </div>
                    <label for="password" class="notion-form-label">Password</label>
                    <div class="invalid-feedback">Please enter a password.</div>
                    <small class="form-text text-muted">
                        <?= isset($editUser['id']) ? 'Leave empty to keep current password.' : 'Must enter password.' ?>
                    </small>
                </div>
            </div>

            <!-- Confirm Password Field -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="password" class="notion-form-control" id="password_confirmation" name="password_confirmation"
                        <?= isset($editUser['id']) ? '' : 'required' ?> placeholder=" ">
                    <label for="password_confirmation" class="notion-form-label">Confirm Password</label>
                    <div class="invalid-feedback">Passwords do not match.</div>
                </div>
            </div>

            <!-- Role Dropdown -->
            <?php if (in_array($userRole, ['super-admin', 'admin'])): ?>
            <div class="col-md-6">
                <div class="notion-form-group">
                    <label for="role" class="notion-form-label">Role</label>
                    <select class="notion-form-select form-select" id="role" name="role" required>
                        <option value="">Select role...</option>
                        <?php if ($userRole === 'super-admin'): ?>
                            <option value="super-admin" <?= ($editUser['role'] ?? '') === 'super-admin' ? 'selected' : '' ?>>Super Admin</option>
                            <option value="admin" <?= ($editUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <?php endif; ?>
                        <option value="student" <?= ($editUser['role'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="staff" <?= ($editUser['role'] ?? '') === 'staff' ? 'selected' : '' ?>>Staff</option>
                        <option value="other" <?= ($editUser['role'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                    <div class="invalid-feedback">Please select a role.</div>
                    <?php if ($userRole === 'admin'): ?>
                        <small class="form-text text-muted">Admins can only add borrower accounts (Students, Staff, or Others).</small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Account Status Dropdown -->
            <div class="col-md-6">
                <div class="notion-form-group">
                    <label for="status" class="notion-form-label">Account Status</label>
                    <select class="notion-form-select form-select" id="status" name="status" required>
                        <option value="">Select status...</option>
                        <option value="active" <?= ($editUser['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($editUser['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <div class="invalid-feedback">Please select account status.</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div class="d-flex justify-content-end mt-4">
        <a href="<?= APP_URL ?>/public/users/index.php" class="btn btn-outline-secondary me-2 ripple-effect">Cancel</a>
        <button type="submit" class="btn btn-primary px-4 ripple-effect">
            <i data-feather="save" class="me-1" style="width: 16px; height: 16px;"></i>
            <?= isset($editUser['id']) ? 'Update Account' : 'Create Account' ?>
        </button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('.notion-form');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('password_confirmation');

        // Form validation
        if (form) {
            form.addEventListener('submit', function(event) {
                let valid = true;

                if (passwordInput && confirmPasswordInput) {
                    if(passwordInput.value !== confirmPasswordInput.value) {
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
                        const fieldGroup = invalidField.closest('.notion-form-group');
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

            if(passwordInput && confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', function() {
                    if(passwordInput.value !== confirmPasswordInput.value) {
                        confirmPasswordInput.setCustomValidity("Passwords do not match.");
                    } else {
                        confirmPasswordInput.setCustomValidity("");
                    }
                });
            }
        }
    });

    // Password visibility toggle
    function togglePasswordVisibility(inputId) {
        const passwordInput = document.getElementById(inputId);
        const toggleIcon = document.getElementById(inputId + '-toggle-icon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.setAttribute('data-feather', 'eye-off');
        } else {
            passwordInput.type = 'password';
            toggleIcon.setAttribute('data-feather', 'eye');
        }
        feather.replace();
    }

    // Add ripple effect to buttons
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = document.querySelectorAll('.ripple-effect');
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                const x = e.clientX - e.target.getBoundingClientRect().left;
                const y = e.clientY - e.target.getBoundingClientRect().top;
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
