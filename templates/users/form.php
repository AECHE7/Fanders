<?php
/**
 * User Account Creation/Edit Form
 */
$auth = new AuthService();

$currentUser = $auth->getCurrentUser();
$userId = $currentUser;
                    <?php if (isset($editUser['created_at'])): ?>
                        <!-- Account Created Info -->
                        <div class="enhanced-form-group">
                            <div class="enhanced-alert enhanced-alert-success">
                                <div class="enhanced-alert-icon">
                                    <i data-feather="calendar"></i>
                                </div>
                                <div class="enhanced-alert-content">
                                    <strong>Account Created:</strong> <?= date('F d, Y \\a\\t g:i A', strtotime($editUser['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div> <!-- .enhanced-form-grid -->
            </section> <!-- .enhanced-form-section -->

        </div> <!-- .enhanced-form-body -->

        <!-- Form Actions -->
        <div class="enhanced-form-actions d-flex justify-content-end mt-4">
            <a href="<?= APP_URL ?>/public/users/index.php" class="btn btn-outline-secondary me-2 ripple-effect">Cancel</a>
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
                                <?php else: ?>
                                    Must enter password.
                                <?php endif; ?>
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
                    <?php else: ?>
                        <!-- Password change not allowed for this user -->
                        <div class="enhanced-form-group">
                            <div class="enhanced-alert enhanced-alert-info">
                                <div class="enhanced-alert-icon">
                                    <i data-feather="info"></i>
                                </div>
                                <div class="enhanced-alert-content">
                                    <?php if (isset($editUser['role']) && $editUser['role'] === 'super-admin'): ?>
                                        Only super-admins can change super-admin passwords.
                                    <?php else: ?>
                                        Contact your administrator to change this user's password.
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

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
                        <?php if ($userId !== ($user['id'] ?? null)): ?>
                            <!-- Role Field -->
                            <div class="enhanced-form-group">
                                <label for="role" class="enhanced-form-label required">Role</label>
                                <div class="enhanced-form-input-wrapper">
                                    <select class="enhanced-form-control enhanced-form-select" id="role" name="role" required>
                                        <option value="">Select role...</option>
                                        <?php 
                                            $selectedRole = strtolower(trim($editUser['role'] ?? ''));
                                        ?>
                                        <?php if ($userRole === 'super-admin'): ?>
                                            <option value="super-admin" <?= $selectedRole === 'super-admin' ? 'selected' : '' ?>>Super Admin</option>
                                            <option value="admin" <?= $selectedRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        <?php endif; ?>
                                        <option value="manager" <?= $selectedRole === 'manager' ? 'selected' : '' ?>>Manager</option>
                                        <option value="cashier" <?= $selectedRole === 'cashier' ? 'selected' : '' ?>>Cashier</option>
                                        <option value="account-officer" <?= $selectedRole === 'account-officer' ? 'selected' : '' ?>>Account Officer</option>
                                    </select>
                                    <div class="enhanced-form-icon">
                                        <i data-feather="users"></i>
                                    </div>
                                </div>
                                <div class="enhanced-form-error">Please select a role.</div>
                                <?php if ($userRole === 'admin'): ?>
                                    <div class="enhanced-form-help">Admins can only add operational staff accounts (Manager, Cashier, or Account Officer).</div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="role" value="<?= htmlspecialchars($userRole) ?>">
                            <div class="enhanced-form-group">
                                <div class="enhanced-alert enhanced-alert-info">
                                    <div class="enhanced-alert-icon">
                                        <i data-feather="info"></i>
                                    </div>
                                    <div class="enhanced-alert-content">
                                        You cannot change your own role. Current role: <strong><?= htmlspecialchars(ucwords(str_replace('-', ' ', $userRole))) ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Account Status Field -->
                    <div class="enhanced-form-group">
                        <label for="status" class="enhanced-form-label required">Account Status</label>
                        <?php if (in_array($currentUser['role'], ['super-admin', 'admin'])): ?>
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
                        <?php else: ?>
                            <div class="enhanced-form-input-wrapper">
                                <input type="text" class="enhanced-form-control" value="<?= ucfirst($editUser['status'] ?? '') ?>" disabled>
                                <div class="enhanced-form-icon">
                                    <i data-feather="lock"></i>
                                </div>
                            </div>
                            <input type="hidden" name="status" value="<?= htmlspecialchars($editUser['status'] ?? '') ?>">
                            <div class="enhanced-form-help">Contact administrator to change account status.</div>
                        <?php endif; ?>
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
        </div>
    </div>
    
    <!-- Form Actions -->
    <div class="d-flex justify-content-end mt-4">
        <a href="<?= APP_URL ?>/public/users/index.php" class="btn btn-outline-secondary me-2 ripple-effect">Cancel</a>
        <button type="submit" class="btn btn-primary px-4 ripple-effect">
            <i data-feather="save" class="me-1" style="width: 16px; height: 16px;"></i>
            <?= isset($editUser['id']) ? 'Update Account' : 'Create Account' ?>
        </div>
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

