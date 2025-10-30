<?php
/**
 * User Account Creation/Edit Form
 */
$auth = new AuthService();

$currentUser = $auth->getCurrentUser();
$currentUserId = $currentUser['id'] ?? null;
?>

<form action="" method="post" class="notion-form needs-validation" novalidate>
    <?= $csrf->getTokenField() ?>
    
    <!-- Form Title with Icon -->
    <div class="notion-form-header mb-4">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center me-3" style="width: 38px; height: 38px; background-color: #eaf8f6;">
                <i data-feather="user" style="width: 20px; height: 20px; color: #0ca789;"></i>
            </div>
            <h5 class="mb-0"><?= isset($editUser['id']) ? 'Edit User Account' : 'Create User Account' ?></h5>
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
            <div class="col-12">
                <div class="notion-form-group interactive-form-field">
                    <input type="text" class="notion-form-control" id="name" name="name"
                        value="<?= htmlspecialchars($editUser['name'] ?? '') ?>" required placeholder=" ">
                    <label for="name" class="notion-form-label">Full Name</label>
                    <div class="invalid-feedback">Please enter your full name.</div>
                </div>
            </div>

            <!-- Email Field -->
            <div class="col-12 col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="email" class="notion-form-control" id="email" name="email"
                        value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" required placeholder=" ">
                    <label for="email" class="notion-form-label">Email Address</label>
                    <div class="invalid-feedback">Please enter a valid and unique email address.</div>
                </div>
            </div>

            <!-- Phone Field -->
            <div class="col-12 col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="text" class="notion-form-control" id="phone_number" name="phone_number"
                        value="<?= htmlspecialchars($editUser['phone_number'] ?? '') ?>" required pattern="\d{8,15}" placeholder=" ">
                    <label for="phone_number" class="notion-form-label">Phone Number</label>
                    <div class="invalid-feedback">Please enter a valid and unique phone number.</div>
                </div>
            </div>

            <!-- Password Field -->
             <?php
                // Password conditions
                $create = !isset($editUser['id']);
                $isEditingSelf = isset($editUser['id']) && $editUser['id'] == $currentUser['id'];
                $isSuperAdminEditingOthers = isset($editUser['id']) && 
                                               $editUser['id'] != $currentUser['id'] && 
                                               $currentUser['role'] === 'super-admin';
                $canEditPassword = $create || $isEditingSelf || $isSuperAdminEditingOthers;
                
                // Prevent staff from editing super-admin passwords
                if (isset($editUser['role']) && $editUser['role'] === 'super-admin' && 
                    $currentUser['role'] !== 'super-admin') {
                    $canEditPassword = false;
                }
            ?>
            <?php if ($canEditPassword): ?>
            <div class="col-12 col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="password" class="notion-form-control" id="password" name="password"
                        <?= isset($editUser['id']) ? '' : 'required' ?> placeholder=" ">
                    <label for="password" class="notion-form-label">Password</label>
                    <div class="invalid-feedback">Please enter a password.</div>
                    <small class="form-text text-muted">
                        <?php if (isset($editUser['id'])): ?>
                            <?php if ($isSuperAdminEditingOthers): ?>
                                Leave empty to keep current password, or enter new password to change it.
                            <?php else: ?>
                                Leave empty to keep current password.
                            <?php endif; ?>
                        <?php else: ?>
                            Must enter password.
                        <?php endif; ?>
                    </small>
                </div>
            </div>

            <!-- Confirm Password Field -->
            <div class="col-12 col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="password" class="notion-form-control" id="password_confirmation" name="password_confirmation"
                        <?= isset($editUser['id']) ? '' : 'required' ?> placeholder=" ">
                    <label for="password_confirmation" class="notion-form-label">Confirm Password</label>
                    <div class="invalid-feedback">Passwords do not match.</div>
                </div>
            </div>
            <?php else: ?>
                <!-- Password change not allowed for this user -->
                <div class="col-12">
                    <div class="alert alert-info">
                        <i data-feather="info"></i>
                        <?php if (isset($editUser['role']) && $editUser['role'] === 'super-admin'): ?>
                            Only super-admins can change super-admin passwords.
                        <?php else: ?>
                            Contact your administrator to change this user's password.
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Role & Account Status Dropdowns in the same row -->
            <?php
                $isEditing = isset($editUser['id']);
                $isEditingSelf = $isEditing && (($editUser['id'] ?? null) == $currentUserId);
                $currentRole = strtolower($currentUser['role'] ?? '');
                $selectedRole = strtolower(trim($editUser['role'] ?? ''));
            ?>

            <?php if (in_array($currentRole, ['super-admin', 'admin'])): ?>
                <?php if (!$isEditingSelf): ?>
                    <div class="col-12 col-md-6">
                        <div class="notion-form-group">
                            <label for="role" class="notion-form-label">Role</label>
                            <select class="notion-form-select form-select" id="role" name="role" required>
                                <option value="">Select role...</option>
                                <?php if ($currentRole === 'super-admin'): ?>
                                    <option value="super-admin" <?= $selectedRole === 'super-admin' ? 'selected' : '' ?>>Super Admin</option>
                                    <option value="admin" <?= $selectedRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <?php endif; ?>
                                <option value="manager" <?= $selectedRole === 'manager' ? 'selected' : '' ?>>Manager</option>
                                <option value="cashier" <?= $selectedRole === 'cashier' ? 'selected' : '' ?>>Cashier</option>
                                <option value="account-officer" <?= $selectedRole === 'account-officer' ? 'selected' : '' ?>>Account Officer</option>
                            </select>
                            <div class="invalid-feedback">Please select a role.</div>
                            <?php if ($currentRole === 'admin'): ?>
                                <small class="form-text text-muted">Admins can only add operational staff accounts (Manager, Cashier, or Account Officer).</small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="role" value="<?= htmlspecialchars($editUser['role'] ?? ($currentUser['role'] ?? '')) ?>">
                <?php endif; ?>
            <?php endif; ?>

            <!-- Account Status Dropdown -->
            <div class="col-12 col-md-6">
                <div class="notion-form-group">
                    <label for="status" class="notion-form-label">Account Status</label>
                    <?php if (in_array($currentRole, ['super-admin', 'admin'])): ?>
                        <select class="notion-form-select form-select" id="status" name="status" required>
                            <option value="">Select status...</option>
                            <option value="active" <?= ($editUser['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($editUser['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    <?php else: ?>
                        <input type="text" class="form-control" value="<?= ucfirst($editUser['status'] ?? '') ?>" disabled>
                        <input type="hidden" name="status" value="<?= htmlspecialchars($editUser['status'] ?? '') ?>">
                    <?php endif; ?>
                    <div class="invalid-feedback">Please select account status.</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div class="d-flex justify-content-end mt-4">
        <a href="<?= APP_URL ?>/public/users/index.php" class="btn btn-outline-secondary me-2">
            <i data-feather="arrow-left" class="me-1" style="width: 16px; height: 16px;"></i>
            Back to Users
        </a>
        <button type="submit" class="btn btn-primary px-4" onclick="return confirm('Are you sure you want to <?= isset($editUser['id']) ? 'update this user account' : 'create this user account' ?>?');">
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

    // Simple form validation
    if (form) {
        form.addEventListener('submit', function(event) {
            let valid = true;

            // Password matching validation
            if (passwordInput && confirmPasswordInput) {
                if (passwordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.setCustomValidity('Passwords do not match.');
                    valid = false;
                } else {
                    confirmPasswordInput.setCustomValidity('');
                }
            }

            // Basic HTML5 validation
            if (!form.checkValidity() || !valid) {
                event.preventDefault();
                event.stopPropagation();
                
                // Focus on first invalid field
                const invalidField = form.querySelector(':invalid');
                if (invalidField) {
                    invalidField.focus();
                }
            }
            
            form.classList.add('was-validated');
        });

        // Real-time password confirmation validation
        if (passwordInput && confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                if (passwordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.setCustomValidity('Passwords do not match.');
                } else {
                    confirmPasswordInput.setCustomValidity('');
                }
            });
        }
    }
});
</script>

<style>
    /* Form field animation for validation errors */
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    .shake-animation { animation: shake 0.8s ease; }
    
    /* Prevent form animations from re-triggering when modal opens */
    .notion-form .stagger-fade-in > * {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }
    
    /* Disable animate-on-scroll inside form to prevent conflicts */
    .notion-form .animate-on-scroll {
        animation: none !important;
    }
    
    /* ========================================
       ENHANCED ANTI-JITTER MODAL SYSTEM
       ======================================== */
    
    /* CRITICAL: Disable all form animations when modal is opening/active */
    .modal-opening .notion-form .stagger-fade-in > *,
    .modal-active .notion-form .stagger-fade-in > *,
    .modal-opening .animate-on-scroll,
    .modal-active .animate-on-scroll {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }
    
    /* Prevent shake animation conflicts */
    .modal-active .shake-animation {
        animation: none !important;
    }
    
    /* Enhanced user modal specific styles */
    #confirmUserSaveModal {
        --modal-transition-duration: 0.2s;
    }
    
    /* Stabilize modal content */
    #confirmUserSaveModal .modal-content {
        transform: translateZ(0);
        backface-visibility: hidden;
    }
    
    /* Smooth interactive elements */
    #confirmUserSaveModal .btn {
        transition: transform 0.15s ease-out, box-shadow 0.15s ease-out;
        transform: translateZ(0);
    }
    
    #confirmUserSaveModal .btn:hover:not(:disabled) {
        transform: translateY(-1px) translateZ(0);
    }
    
    #confirmUserSaveModal .btn:active {
        transform: translateY(0) translateZ(0);
        transition-duration: 0.05s;
    }
</style>

<style>
/* Simple form validation styling */
.notion-form .was-validated .form-control:invalid,
.notion-form .was-validated .form-select:invalid {
    border-color: #dc3545;
}

.notion-form .was-validated .form-control:valid,
.notion-form .was-validated .form-select:valid {
    border-color: #198754;
}
</style>