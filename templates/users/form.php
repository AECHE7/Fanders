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
        <a href="<?= APP_URL ?>/public/users/index.php" class="btn btn-outline-secondary me-2 ripple-effect">Cancel</a>
        <button type="button" class="btn btn-primary px-4 ripple-effect" id="openConfirmModal" data-bs-toggle="modal" data-bs-target="#confirmUserSaveModal">
            <i data-feather="save" class="me-1" style="width: 16px; height: 16px;"></i>
            <?= isset($editUser['id']) ? 'Update Account' : 'Create Account' ?>
        </button>
    </div>
</form>

<!-- User Save Confirmation Modal -->
<div class="modal fade" id="confirmUserSaveModal" tabindex="-1" aria-labelledby="confirmUserSaveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="confirmUserSaveModalLabel">
                    <i data-feather="alert-circle" class="me-2" style="width:20px;height:20px;"></i>
                    <?= isset($editUser['id']) ? 'Confirm Staff Update' : 'Confirm Staff Creation' ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">You are about to <?= isset($editUser['id']) ? 'update the information for' : 'create a new staff account for' ?>:</p>
                <div class="card bg-light">
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Full Name:</dt>
                            <dd class="col-sm-8 fw-bold" id="modalUserName"><?= htmlspecialchars($editUser['name'] ?? '') ?></dd>
                            <dt class="col-sm-4">Email:</dt>
                            <dd class="col-sm-8" id="modalUserEmail"><?= htmlspecialchars($editUser['email'] ?? '') ?></dd>
                            <dt class="col-sm-4">Phone:</dt>
                            <dd class="col-sm-8" id="modalUserPhone"><?= htmlspecialchars($editUser['phone_number'] ?? '') ?></dd>
                            <dt class="col-sm-4">Role:</dt>
                            <dd class="col-sm-8" id="modalUserRole">
                                <span class="badge text-bg-primary">
                                    <?php 
                                        $roleDisplay = $editUser['role'] ?? '';
                                        if ($roleDisplay) {
                                            echo ucwords(str_replace('-', ' ', $roleDisplay));
                                        }
                                    ?>
                                </span>
                            </dd>
                            <dt class="col-sm-4">Status:</dt>
                            <dd class="col-sm-8" id="modalUserStatus">
                                <span class="badge text-bg-<?= ($editUser['status'] ?? 'active') === 'active' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($editUser['status'] ?? 'active') ?>
                                </span>
                            </dd>
                        </dl>
                    </div>
                </div>
                <p class="mt-3 mb-0 text-muted small">
                    <i data-feather="info" class="me-1" style="width:14px;height:14px;"></i>
                    <?= isset($editUser['id']) ? 'This action will update the staff member information and permissions.' : 'This will create a new staff account with system access.' ?>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i data-feather="x" class="me-1" style="width:16px;height:16px;"></i>
                    Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmUserSave">
                    <i data-feather="check" class="me-1" style="width:16px;height:16px;"></i>
                    <?= isset($editUser['id']) ? 'Confirm Update' : 'Confirm Creation' ?>
                </button>
            </div>
        </div>
    </div>
</div>

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
                    if (passwordInput.value !== confirmPasswordInput.value) {
                        confirmPasswordInput.setCustomValidity('Passwords do not match.');
                        valid = false;
                    } else {
                        confirmPasswordInput.setCustomValidity('');
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

    // Add ripple effect to buttons (consolidated into main DOMContentLoaded)
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('.notion-form');
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone_number');
        const roleSelect = document.getElementById('role');
        const statusSelect = document.getElementById('status');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('password_confirmation');

        // Ripple effect for buttons
        const buttons = document.querySelectorAll('.ripple-effect');
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                const rect = e.target.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                const ripple = document.createElement('span');
                ripple.className = 'ripple-animation';
                ripple.style.left = `${x}px`;
                ripple.style.top = `${y}px`;
                this.appendChild(ripple);
                setTimeout(() => ripple.remove(), 600);
            });
        });

        // Initialize confirmation modal using the new system
        ConfirmationModals.initFormConfirmation({
            formSelector: '.notion-form',
            modalId: 'confirmUserSaveModal',
            triggerButtonSelector: '#openConfirmModal',
            confirmButtonId: 'confirmUserSave',
            
            // Custom validation for password matching
            validateCallback: function(form) {
                if (passwordInput && confirmPasswordInput) {
                    if (passwordInput.value && confirmPasswordInput.value) {
                        if (passwordInput.value !== confirmPasswordInput.value) {
                            confirmPasswordInput.setCustomValidity('Passwords do not match.');
                            return false;
                        } else {
                            confirmPasswordInput.setCustomValidity('');
                        }
                    }
                }
                return true;
            },
            
            // Update modal content before showing
            updateContentCallback: function(form, modalElement) {
                const safe = (v, fallback = 'Not specified') => (v && v.trim()) ? v : fallback;
                const setText = (id, text) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = text;
                };
                
                setText('modalUserName', safe(nameInput ? nameInput.value : ''));
                setText('modalUserEmail', safe(emailInput ? emailInput.value : ''));
                setText('modalUserPhone', safe(phoneInput ? phoneInput.value : ''));

                // Update role badge
                const roleContainer = document.getElementById('modalUserRole');
                if (roleContainer && roleSelect) {
                    let role = roleSelect.value;
                    let roleText = 'Not specified';
                    if (role) {
                        roleText = role.split('-').map(word => 
                            word.charAt(0).toUpperCase() + word.slice(1)
                        ).join(' ');
                    }
                    roleContainer.innerHTML = `<span class="badge text-bg-primary">${roleText}</span>`;
                }

                // Update status badge if editing
                const statusElement = document.getElementById('modalUserStatus');
                if (statusElement && statusSelect) {
                    const status = statusSelect.value || 'active';
                    const badgeClass = status === 'active' ? 'success' : 'secondary';
                    const statusText = status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Active';
                    statusElement.innerHTML = `<span class="badge text-bg-${badgeClass}">${statusText}</span>`;
                }
            }
        });

        // Keep input fields synced with modal in real-time
        [nameInput, emailInput, phoneInput, roleSelect, statusSelect].forEach(input => {
            if (input) {
                input.addEventListener('input', function() {
                    // Update modal if it's visible
                    const modalElement = document.getElementById('confirmUserSaveModal');
                    if (modalElement && modalElement.classList.contains('show')) {
                        // Modal is open, update content
                        const safe = (v, fallback = 'Not specified') => (v && v.trim()) ? v : fallback;
                        const setText = (id, text) => {
                            const el = document.getElementById(id);
                            if (el) el.textContent = text;
                        };
                        
                        setText('modalUserName', safe(nameInput ? nameInput.value : ''));
                        setText('modalUserEmail', safe(emailInput ? emailInput.value : ''));
                        setText('modalUserPhone', safe(phoneInput ? phoneInput.value : ''));
                    }
                });
            }
        });
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
       MODAL ENHANCEMENTS - MINIMAL & SMOOTH
       ======================================== */
    
    /* Smooth fade transition for Bootstrap modals */
    #confirmUserSaveModal.modal.fade .modal-dialog {
        transition: transform 0.2s ease-out, opacity 0.2s ease-out;
    }
    
    #confirmUserSaveModal.modal.show .modal-dialog {
        transform: none;
    }
    
    /* Enhanced modal styling */
    #confirmUserSaveModal .modal-content {
        border: none;
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.2);
    }
    
    #confirmUserSaveModal .modal-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    /* Smooth button hover effects */
    #confirmUserSaveModal .btn {
        transition: all 0.15s ease-in-out;
    }
    
    #confirmUserSaveModal .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    }
    
    #confirmUserSaveModal .btn:active {
        transform: translateY(0);
    }
    
    #confirmUserSaveModal .btn-close:hover {
        transform: scale(1.1);
    }
    
    /* Smooth badge transitions */
    #confirmUserSaveModal .badge {
        transition: all 0.15s ease;
    }
</style>

