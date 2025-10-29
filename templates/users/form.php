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
        <button type="button" class="btn btn-primary px-4 ripple-effect" id="openConfirmModal">
            <i data-feather="save" class="me-1" style="width: 16px; height: 16px;"></i>
            <?= isset($editUser['id']) ? 'Update Account' : 'Create Account' ?>
        </button>
    </div>
</form>

<!-- User Save Confirmation Modal - Custom Static Implementation -->
<div id="confirmUserSaveModal" class="custom-modal" style="display: none;">
    <div class="custom-modal-overlay"></div>
    <div class="custom-modal-dialog">
        <div class="custom-modal-content">
            <div class="custom-modal-header">
                <h5 class="custom-modal-title">
                    <i data-feather="alert-circle" class="me-2" style="width:20px;height:20px;"></i>
                    <?= isset($editUser['id']) ? 'Confirm Staff Update' : 'Confirm Staff Creation' ?>
                </h5>
                <button type="button" class="custom-modal-close" data-dismiss="modal" aria-label="Close">&times;</button>
            </div>
            <div class="custom-modal-body">
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
            <div class="custom-modal-footer">
                <button type="button" class="custom-btn custom-btn-secondary" data-dismiss="modal">
                    <i data-feather="x" class="me-1" style="width:16px;height:16px;"></i>
                    Cancel
                </button>
                <button type="button" class="custom-btn custom-btn-primary" id="confirmUserSave">
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

        // Add ripple effect to buttons
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

        // Modal confirmation functionality
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone_number');
        const roleSelect = document.getElementById('role');
        const statusSelect = document.getElementById('status');

        function updateModalContent() {
            const safe = (v, fallback = 'Not specified') => (v && v.trim()) ? v : fallback;
            const setText = (id, text) => { const el = document.getElementById(id); if (el) el.textContent = text; };
            
            setText('modalUserName', safe(nameInput ? nameInput.value : ''));
            setText('modalUserEmail', safe(emailInput ? emailInput.value : ''));
            setText('modalUserPhone', safe(phoneInput ? phoneInput.value : ''));

            // Update role badge
            const roleContainer = document.getElementById('modalUserRole');
            if (roleContainer) {
                let role = '';
                if (roleSelect && roleSelect.value) {
                    role = roleSelect.value;
                } else if (roleSelect) {
                    // Try to get from selected option text
                    const selectedOption = roleSelect.options[roleSelect.selectedIndex];
                    if (selectedOption && selectedOption.value) {
                        role = selectedOption.value;
                    }
                }
                
                // Format role text with proper capitalization and spacing
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

        // Update modal on input changes
        [nameInput, emailInput, phoneInput, roleSelect, statusSelect].forEach(input => {
            if (input) {
                input.addEventListener('input', updateModalContent);
                input.addEventListener('change', updateModalContent);
            }
        });

        // Ensure modal content is fresh when it opens
        const confirmModalEl = document.getElementById('confirmUserSaveModal');
        const openModalBtn = document.getElementById('openConfirmModal');
        
        if (openModalBtn && confirmModalEl) {
            openModalBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Check form validity before opening modal
                let isValid = true;
                
                // Validate password matching
                if (passwordInput && confirmPasswordInput) {
                    if (passwordInput.value && confirmPasswordInput.value && 
                        passwordInput.value !== confirmPasswordInput.value) {
                        isValid = false;
                        confirmPasswordInput.setCustomValidity('Passwords do not match.');
                    } else {
                        confirmPasswordInput.setCustomValidity('');
                    }
                }
                
                // Check HTML5 validation
                if (!form.checkValidity() || !isValid) {
                    // Show validation errors
                    form.classList.add('was-validated');
                    form.reportValidity();
                    
                    // Focus first invalid field
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
                    return; // Don't open modal
                }
                
                                // Form is valid, update modal content and show it
                updateModalContent();
                
                // Show custom modal (no animations, no transitions)
                confirmModalEl.style.display = 'block';
                document.body.style.overflow = 'hidden';
            });
        }

        // Modal close handler function
        function closeModal() {
            confirmModalEl.style.display = 'none';
            document.body.style.overflow = '';
        }

        // Close button handlers
        document.querySelectorAll('#confirmUserSaveModal [data-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', closeModal);
        });

        // Confirm save button handler
        const confirmBtn = document.getElementById('confirmUserSave');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                if (!form) {
                    console.error('Form not found');
                    return;
                }
                
                // Form was already validated before modal opened, just submit
                form.submit();
            });
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
       CUSTOM MODAL - NO ANIMATIONS, NO TRANSITIONS, NO HOVER EFFECTS
       ======================================== */
    
    .custom-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 9999;
        overflow-y: auto;
    }
    
    .custom-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1;
    }
    
    .custom-modal-dialog {
        position: relative;
        width: auto;
        max-width: 600px;
        margin: 1.75rem auto;
        z-index: 2;
        pointer-events: none;
    }
    
    .custom-modal-content {
        position: relative;
        display: flex;
        flex-direction: column;
        width: 100%;
        pointer-events: auto;
        background-color: #fff;
        background-clip: padding-box;
        border: 1px solid rgba(0, 0, 0, 0.2);
        border-radius: 0.5rem;
        outline: 0;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    .custom-modal-header {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1rem;
        border-bottom: 1px solid #dee2e6;
        border-top-left-radius: calc(0.5rem - 1px);
        border-top-right-radius: calc(0.5rem - 1px);
        background-color: #0d6efd;
        color: #fff;
    }
    
    .custom-modal-title {
        margin: 0;
        line-height: 1.5;
        font-size: 1.25rem;
        font-weight: 500;
        display: flex;
        align-items: center;
    }
    
    .custom-modal-close {
        padding: 0.5rem 0.5rem;
        margin: -0.5rem -0.5rem -0.5rem auto;
        background-color: transparent;
        border: 0;
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1;
        color: #fff;
        opacity: 0.8;
        cursor: pointer;
    }
    
    .custom-modal-body {
        position: relative;
        flex: 1 1 auto;
        padding: 1rem;
    }
    
    .custom-modal-footer {
        display: flex;
        flex-wrap: wrap;
        flex-shrink: 0;
        align-items: center;
        justify-content: flex-end;
        padding: 0.75rem;
        border-top: 1px solid #dee2e6;
        border-bottom-right-radius: calc(0.5rem - 1px);
        border-bottom-left-radius: calc(0.5rem - 1px);
    }
    
    .custom-btn {
        display: inline-block;
        font-weight: 400;
        line-height: 1.5;
        color: #212529;
        text-align: center;
        text-decoration: none;
        vertical-align: middle;
        cursor: pointer;
        user-select: none;
        background-color: transparent;
        border: 1px solid transparent;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        border-radius: 0.375rem;
        margin-left: 0.5rem;
    }
    
    .custom-btn-secondary {
        color: #fff;
        background-color: #6c757d;
        border-color: #6c757d;
    }
    
    .custom-btn-primary {
        color: #fff;
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    /* CRITICAL: Disable ALL transitions, animations, and transforms */
    .custom-modal *,
    .custom-modal *::before,
    .custom-modal *::after {
        transition: none !important;
        animation: none !important;
        transform: none !important;
    }
    
    /* Responsive adjustments */
    @media (max-width: 576px) {
        .custom-modal-dialog {
            margin: 0.5rem;
            max-width: calc(100% - 1rem);
        }
    }
</style>

