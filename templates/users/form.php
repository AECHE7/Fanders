<?php
/**
 * User form template
 * Used for both adding and editing users
 * Notion-inspired interactive design
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
            <h5 class="mb-0"><?= isset($newUser['id']) ? 'Edit User Details' : 'Add New User' ?></h5>
        </div>
    </div>
    
    <!-- Basic Information Section -->
    <div class="mb-4 animate-on-scroll">
        <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0 me-2">User Information</h6>
            <div class="notion-divider flex-grow-1"></div>
        </div>
        
        <div class="row g-3 stagger-fade-in">
            <!-- First Name Field -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="text" class="notion-form-control" id="first_name" name="first_name" 
                        value="<?= htmlspecialchars($newUser['first_name']) ?>" required placeholder=" ">
                    <label for="first_name" class="notion-form-label">First Name</label>
                    <div class="invalid-feedback">Please enter a first name.</div>
                </div>
            </div>
            
            <!-- Last Name Field -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="text" class="notion-form-control" id="last_name" name="last_name" 
                        value="<?= htmlspecialchars($newUser['last_name']) ?>" required placeholder=" ">
                    <label for="last_name" class="notion-form-label">Last Name</label>
                    <div class="invalid-feedback">Please enter a last name.</div>
                </div>
            </div>
            
            <!-- Username Field -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="text" class="notion-form-control" id="username" name="username" 
                        value="<?= htmlspecialchars($newUser['username']) ?>" required placeholder=" ">
                    <label for="username" class="notion-form-label">Username</label>
                    <div class="invalid-feedback">Please enter a username.</div>
                </div>
            </div>
            
            <!-- Email Field -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="email" class="notion-form-control" id="email" name="email" 
                        value="<?= htmlspecialchars($newUser['email']) ?>" required placeholder=" ">
                    <label for="email" class="notion-form-label">Email</label>
                    <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>
            </div>
            
            <!-- Password Field -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <div class="input-group">
                        <input type="password" class="notion-form-control" id="password" name="password" 
                            <?= isset($newUser['id']) ? '' : 'required' ?> placeholder=" ">
                        <button class="btn btn-outline-secondary" type="button" id="toggle-password" 
                            onclick="togglePasswordVisibility('password')">
                            <i data-feather="eye" id="password-toggle-icon"></i>
                        </button>
                    </div>
                    <label for="password" class="notion-form-label">Password</label>
                    <div class="invalid-feedback">Please enter a password.</div>
                    <small class="form-text text-muted">
                        <?= isset($newUser['id']) ? 'Leave empty to keep current password.' : 'Auto-generated if left empty.' ?>
                    </small>
                </div>
            </div>
            
            <!-- Role Dropdown -->
            <div class="col-md-6">
                <div class="notion-form-group">
                    <label for="role_id" class="notion-form-label">Role</label>
                    <select class="notion-form-select form-select" id="role_id" name="role_id" 
                        <?= $userRole == ROLE_SUPER_ADMIN ? '' : 'disabled' ?> required>
                        <?php if ($userRole == ROLE_SUPER_ADMIN): ?>
                            <option value="<?= ROLE_SUPER_ADMIN ?>" <?= ($newUser['role_id'] == ROLE_SUPER_ADMIN) ? 'selected' : '' ?>>
                                Super Admin
                            </option>
                            <option value="<?= ROLE_ADMIN ?>" <?= ($newUser['role_id'] == ROLE_ADMIN) ? 'selected' : '' ?>>
                                Admin
                            </option>
                        <?php endif; ?>
                        <option value="<?= ROLE_BORROWER ?>" <?= ($newUser['role_id'] == ROLE_BORROWER || $newUser['role_id'] == '') ? 'selected' : '' ?>>
                            Borrower
                        </option>
                    </select>
                    <div class="invalid-feedback">Please select a role.</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div class="d-flex justify-content-end mt-4">
        <a href="<?= APP_URL ?>/public/users/index.php" class="btn btn-outline-secondary me-2 ripple-effect">Cancel</a>
        <button type="submit" class="btn btn-primary px-4 ripple-effect">
            <i data-feather="save" class="me-1" style="width: 16px; height: 16px;"></i>
            <?= isset($newUser['id']) ? 'Update User' : 'Add User' ?>
        </button>
    </div>
</form>

<!-- JavaScript for Form Interactivity -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        const form = document.querySelector('.notion-form');
        if (form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // Find the first invalid field and focus it
                    const invalidField = form.querySelector(':invalid');
                    if (invalidField) {
                        invalidField.focus();
                        
                        // Add a shake animation to the invalid field's parent
                        const fieldGroup = invalidField.closest('.notion-form-group');
                        if (fieldGroup) {
                            fieldGroup.classList.add('shake-animation');
                            setTimeout(() => {
                                fieldGroup.classList.remove('shake-animation');
                            }, 820); // Animation duration + a bit extra
                        }
                    }
                } else {
                    // Add a pulse animation to the submit button
                    const submitBtn = form.querySelector('[type="submit"]');
                    if (submitBtn) {
                        submitBtn.classList.add('pulse');
                    }
                }
                form.classList.add('was-validated');
            });
        }
        
        // Password strength meter
        const passwordInput = document.getElementById('password');
        const strengthIndicator = document.createElement('div');
        strengthIndicator.className = 'password-strength mt-2';
        strengthIndicator.innerHTML = '<div class="strength-meter"><div class="strength-meter-fill" style="width: 0%"></div></div><small class="strength-text text-muted">Password strength: none</small>';
        
        if (passwordInput) {
            // Insert strength indicator after password field
            passwordInput.parentNode.insertAdjacentHTML('afterend', strengthIndicator.outerHTML);
            
            const strengthMeter = document.querySelector('.strength-meter-fill');
            const strengthText = document.querySelector('.strength-text');
            
            passwordInput.addEventListener('input', function() {
                const val = passwordInput.value;
                let strength = 0;
                let strengthClass = '';
                let text = 'Password strength: ';
                
                if (val.length >= 8) strength += 25;
                if (val.match(/[a-z]/)) strength += 25;
                if (val.match(/[A-Z]/)) strength += 25;
                if (val.match(/[0-9]/)) strength += 15;
                if (val.match(/[^a-zA-Z0-9]/)) strength += 10;
                
                // Set the strength meter value and styling
                if (strength < 30) {
                    strengthClass = 'bg-danger';
                    text += 'weak';
                } else if (strength < 60) {
                    strengthClass = 'bg-warning';
                    text += 'moderate';
                } else if (strength < 80) {
                    strengthClass = 'bg-info';
                    text += 'good';
                } else {
                    strengthClass = 'bg-success';
                    text += 'strong';
                }
                
                strengthMeter.style.width = strength + '%';
                strengthMeter.className = 'strength-meter-fill ' + strengthClass;
                strengthText.textContent = text;
            });
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
        feather.replace(); // Re-render feather icons
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
    /* Shake animation for invalid fields */
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    .shake-animation {
        animation: shake 0.8s ease;
    }
    
    /* Password strength meter styling */
    .strength-meter {
        height: 5px;
        background-color: #e9ecef;
        border-radius: 3px;
        margin-top: 5px;
        overflow: hidden;
    }
    
    .strength-meter-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.3s ease, background-color 0.3s ease;
    }
    
    /* Ripple effect styles */
    .ripple-effect {
        position: relative;
        overflow: hidden;
    }
    
    .ripple-animation {
        position: absolute;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.7);
        width: 100px;
        height: 100px;
        margin-top: -50px;
        margin-left: -50px;
        animation: ripple 0.6s linear;
        transform: scale(0);
        opacity: 1;
    }
    
    @keyframes ripple {
        to {
            transform: scale(2.5);
            opacity: 0;
        }
    }
</style>