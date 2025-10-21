<?php
/**
 * Client Account Creation/Edit Form (templates/clients/form.php)
 * Used by public/clients/add.php (uses $newClient as $clientData) and 
 * public/clients/edit.php (uses $editClient as $clientData).
 * * Assumes the following variables are available from the controller:
 * @var array $clientData Contains client data (new or existing)
 * @var AuthService $auth The authenticated user service
 * @var CSRF $csrf The CSRF utility object
 */

// If creating a new client, $clientData will be the $newClient default array.
$isEditing = isset($clientData['id']) && $clientData['id'] > 0;

// Helper to determine which status is selected
$currentStatus = $clientData['status'] ?? 'active'; 

// Helper to determine which identification type is selected
$currentIdType = $clientData['identification_type'] ?? '';
?>

<form action="" method="post" class="notion-form needs-validation" novalidate>
    <?= $csrf->getTokenField() ?>

    <!-- Form Title with Icon -->
    <div class="notion-form-header mb-4">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center me-3" style="width: 38px; height: 38px; background-color: #eaf8f6;">
                <i data-feather="user" style="width: 20px; height: 20px; color: #0ca789;"></i>
            </div>
            <h5 class="mb-0"><?= $isEditing ? 'Edit Client Information (ID: ' . $clientData['id'] . ')' : 'Create New Client Account' ?></h5>
        </div>
    </div>

    <!-- Basic Information Section -->
    <div class="mb-4 animate-on-scroll">
        <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0 me-2 text-primary">Personal Details</h6>
            <div class="notion-divider flex-grow-1"></div>
        </div>

        <div class="row g-3 stagger-fade-in">
            <!-- Full Name Field (Required) -->
            <div class="col-md-12">
                <div class="notion-form-group interactive-form-field">
                    <input type="text" class="notion-form-control" id="name" name="name"
                        value="<?= htmlspecialchars($clientData['name'] ?? '') ?>" required placeholder=" ">
                    <label for="name" class="notion-form-label">Full Name</label>
                    <div class="invalid-feedback">Please enter the client's full name.</div>
                </div>
            </div>

            <!-- Phone Field (Required & Unique) -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="text" class="notion-form-control" id="phone_number" name="phone_number"
                        value="<?= htmlspecialchars($clientData['phone_number'] ?? '') ?>" required pattern="\d{8,15}" placeholder=" ">
                    <label for="phone_number" class="notion-form-label">Phone Number (Required)</label>
                    <div class="invalid-feedback">Please enter a valid, unique phone number (8-15 digits).</div>
                </div>
            </div>

            <!-- Email Field (Optional but Unique) -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="email" class="notion-form-control" id="email" name="email"
                        value="<?= htmlspecialchars($clientData['email'] ?? '') ?>" placeholder=" ">
                    <label for="email" class="notion-form-label">Email Address (Optional)</label>
                    <div class="invalid-feedback">Please enter a valid and unique email address.</div>
                </div>
            </div>
            
             <!-- Date of Birth Field -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="date" class="notion-form-control" id="date_of_birth" name="date_of_birth"
                        value="<?= htmlspecialchars($clientData['date_of_birth'] ?? '') ?>" placeholder=" " max="<?= date('Y-m-d', strtotime('-18 years')) ?>">
                    <label for="date_of_birth" class="notion-form-label">Date of Birth (Optional, must be 18+)</label>
                    <div class="invalid-feedback">Client must be at least 18 years old.</div>
                </div>
            </div>
            
            <!-- Address Field -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <textarea class="notion-form-control" id="address" name="address" rows="1" required placeholder=" "><?= htmlspecialchars($clientData['address'] ?? '') ?></textarea>
                    <label for="address" class="notion-form-label">Residential Address (Required)</label>
                    <div class="invalid-feedback">Please enter the client's residential address.</div>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- Identification Section -->
    <div class="mb-4 animate-on-scroll">
        <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0 me-2 text-primary">Identification & Status</h6>
            <div class="notion-divider flex-grow-1"></div>
        </div>
        
        <div class="row g-3 stagger-fade-in">

            <!-- Identification Type Field -->
            <div class="col-md-6">
                <div class="notion-form-group">
                    <label for="identification_type" class="notion-form-label">Primary ID Type (Required)</label>
                    <select class="notion-form-select form-select" id="identification_type" name="identification_type" required>
                        <option value="">Select ID type...</option>
                        <option value="passport" <?= $currentIdType === 'passport' ? 'selected' : '' ?>>Passport</option>
                        <option value="drivers_license" <?= $currentIdType === 'drivers_license' ? 'selected' : '' ?>>Driver's License</option>
                        <option value="national_id" <?= $currentIdType === 'national_id' ? 'selected' : '' ?>>National ID</option>
                        <option value="philhealth" <?= $currentIdType === 'philhealth' ? 'selected' : '' ?>>PhilHealth ID</option>
                        <option value="sss" <?= $currentIdType === 'sss' ? 'selected' : '' ?>>SSS/GSIS ID</option>
                        <option value="other" <?= $currentIdType === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                    <div class="invalid-feedback">Please select an identification type.</div>
                </div>
            </div>

            <!-- Identification Number Field -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="text" class="notion-form-control" id="identification_number" name="identification_number"
                        value="<?= htmlspecialchars($clientData['identification_number'] ?? '') ?>" required placeholder=" ">
                    <label for="identification_number" class="notion-form-label">ID Number (Required & Unique)</label>
                    <div class="invalid-feedback">ID number is required and must be unique.</div>
                </div>
            </div>

            <!-- Account Status Dropdown (Only editable by Super Admin/Admin) -->
            <div class="col-md-6">
                <div class="notion-form-group">
                    <label for="status" class="notion-form-label">Account Status</label>
                    <?php if ($auth->hasRole(['super-admin', 'admin'])): ?>
                        <select class="notion-form-select form-select" id="status" name="status" required>
                            <option value="active" <?= $currentStatus === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $currentStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="blacklisted" <?= $currentStatus === 'blacklisted' ? 'selected' : '' ?>>Blacklisted</option>
                        </select>
                    <?php else: ?>
                        <input type="text" class="form-control" value="<?= ucfirst($currentStatus) ?>" disabled>
                        <input type="hidden" name="status" value="<?= htmlspecialchars($currentStatus) ?>">
                    <?php endif; ?>
                    <div class="invalid-feedback">Please select account status.</div>
                </div>
            </div>
            
            <?php if ($isEditing && $clientData['created_at']): ?>
                <div class="col-md-6 d-flex align-items-center">
                     <p class="text-muted small mb-0">Client Record created on: <?= date('M d, Y H:i A', strtotime($clientData['created_at'])) ?></p>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Form Actions -->
    <div class="d-flex justify-content-end mt-4">
        <a href="<?= APP_URL ?>/public/clients/index.php" class="btn btn-outline-secondary me-2 ripple-effect">Cancel</a>
        <button type="submit" class="btn btn-primary px-4 ripple-effect">
            <i data-feather="save" class="me-1" style="width: 16px; height: 16px;"></i>
            <?= $isEditing ? 'Update Client' : 'Create Client' ?>
        </button>
    </div>
</form>

<style>
/* Notion Form Styles and Animations (Copied from existing code) */
.notion-form-group { position: relative; margin-bottom: 1.5rem; }
.notion-form-control, .notion-form-select {
    padding: 0.75rem 1rem; border: 1px solid #e0e0e0; border-radius: 8px;
    transition: all 0.3s ease; width: 100%; font-size: 1rem; line-height: 1.5;
    background-color: #fff;
}
.notion-form-control:focus, .notion-form-select:focus {
    border-color: #0ca789; box-shadow: 0 0 0 0.2rem rgba(12, 167, 137, 0.25); outline: none;
}
.notion-form-label {
    position: absolute; top: 0.75rem; left: 1rem; padding: 0 0.25rem;
    pointer-events: none; transition: all 0.3s ease; background-color: white;
    color: #6c757d; font-size: 1rem;
}
.notion-form-group input:focus ~ .notion-form-label,
.notion-form-group input:not(:placeholder-shown) ~ .notion-form-label,
.notion-form-group textarea:focus ~ .notion-form-label,
.notion-form-group textarea:not(:placeholder-shown) ~ .notion-form-label {
    top: -0.65rem; font-size: 0.8rem; color: #0ca789;
}
.notion-form-group input:required:invalid:not(:placeholder-shown) { border-color: #dc3545; }
.notion-form-group input:required:invalid:not(:placeholder-shown) ~ .notion-form-label { color: #dc3545; }
.notion-divider { height: 1px; background-color: #e0e0e0; margin-left: 1rem; }
.text-primary { color: #0ca789 !important; }

/* Custom animations and effects */
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('.notion-form');

        // Form validation and shake effect
        if (form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
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
        }
    });
</script>