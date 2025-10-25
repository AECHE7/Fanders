<?php
/**
 * Client Account Creation/Edit Form (templates/clients/form.php)
 * Enhanced modern design with consistent styling
 * Used by public/clients/add.php and public/clients/edit.php
 * Assumes the following variables are available from the controller:
 * @var array $clientData Contains client data (new or existing)
 * @var AuthService $auth The authenticated user service
 * @var CSRF $csrf The CSRF utility object
 */

$isEditing = isset($clientData['id']) && $clientData['id'] > 0;
$currentStatus = $clientData['status'] ?? 'active';
$currentIdType = $clientData['identification_type'] ?? '';
?>

<div class="enhanced-form-wrapper">
    <form action="" method="post" class="enhanced-form needs-validation" novalidate>
        <?= $csrf->getTokenField() ?>

        <!-- Enhanced Form Header -->
        <div class="enhanced-form-header">
            <div class="enhanced-form-header-icon">
                <i data-feather="<?= $isEditing ? 'edit-3' : 'user-plus' ?>" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <h1 class="enhanced-form-header-title">
                <?= $isEditing ? 'Edit Client Information' : 'Create New Client Account' ?>
            </h1>
            <p class="enhanced-form-header-subtitle">
                <?= $isEditing ? 'Update the client details below. All changes will be logged.' : 'Complete the form below to add a new client to the system' ?>
            </p>
        </div>

        <!-- Enhanced Form Body -->
        <div class="enhanced-form-body">

            <!-- Section 1: Personal Details -->
            <section class="enhanced-form-section">
                <div class="enhanced-form-section-header">
                    <div class="enhanced-form-section-icon">
                        <i data-feather="user" style="width: 20px; height: 20px; color: #667eea;"></i>
                    </div>
                    <h2 class="enhanced-form-section-title">Personal Details</h2>
                    <div class="enhanced-form-section-divider"></div>
                </div>

                <div class="row g-3">
                    <!-- Full Name -->
                    <div class="col-md-12">
                        <div class="enhanced-form-group">
                            <label for="name" class="enhanced-form-label required">Full Name</label>
                            <input 
                                type="text" 
                                class="enhanced-form-control" 
                                id="name" 
                                name="name"
                                value="<?= htmlspecialchars($clientData['name'] ?? '') ?>" 
                                required 
                                placeholder="Enter full legal name">
                            <div class="invalid-feedback">Please enter the client's full name.</div>
                        </div>
                    </div>

                    <!-- Phone Number -->
                    <div class="col-md-6">
                        <div class="enhanced-form-group">
                            <label for="phone_number" class="enhanced-form-label required">Phone Number</label>
                            <div class="enhanced-input-icon-wrapper">
                                <i data-feather="phone" class="enhanced-input-icon"></i>
                                <input 
                                    type="text" 
                                    class="enhanced-form-control" 
                                    id="phone_number" 
                                    name="phone_number"
                                    value="<?= htmlspecialchars($clientData['phone_number'] ?? '') ?>" 
                                    required 
                                    pattern="\d{8,15}" 
                                    placeholder="e.g., 09123456789">
                            </div>
                            <span class="enhanced-form-help">
                                <i data-feather="info" class="enhanced-form-help-icon"></i>
                                8-15 digits, must be unique
                            </span>
                            <div class="invalid-feedback">Enter a valid unique phone number (8-15 digits).</div>
                        </div>
                    </div>

                    <!-- Email Address -->
                    <div class="col-md-6">
                        <div class="enhanced-form-group">
                            <label for="email" class="enhanced-form-label">Email Address</label>
                            <div class="enhanced-input-icon-wrapper">
                                <i data-feather="mail" class="enhanced-input-icon"></i>
                                <input 
                                    type="email" 
                                    class="enhanced-form-control" 
                                    id="email" 
                                    name="email"
                                    value="<?= htmlspecialchars($clientData['email'] ?? '') ?>" 
                                    placeholder="email@example.com">
                            </div>
                            <span class="enhanced-form-help">Optional, but must be unique if provided</span>
                            <div class="invalid-feedback">Enter a valid and unique email address.</div>
                        </div>
                    </div>

                    <!-- Date of Birth -->
                    <div class="col-md-6">
                        <div class="enhanced-form-group">
                            <label for="date_of_birth" class="enhanced-form-label">Date of Birth</label>
                            <input 
                                type="date" 
                                class="enhanced-form-control" 
                                id="date_of_birth" 
                                name="date_of_birth"
                                value="<?= htmlspecialchars($clientData['date_of_birth'] ?? '') ?>" 
                                max="<?= date('Y-m-d', strtotime('-18 years')) ?>">
                            <span class="enhanced-form-help">
                                <i data-feather="calendar" class="enhanced-form-help-icon"></i>
                                Client must be at least 18 years old
                            </span>
                            <div class="invalid-feedback">Client must be at least 18 years old.</div>
                        </div>
                    </div>

                    <!-- Residential Address -->
                    <div class="col-md-6">
                        <div class="enhanced-form-group">
                            <label for="address" class="enhanced-form-label required">Residential Address</label>
                            <textarea 
                                class="enhanced-form-textarea" 
                                id="address" 
                                name="address" 
                                rows="1" 
                                required 
                                placeholder="Enter complete residential address"><?= htmlspecialchars($clientData['address'] ?? '') ?></textarea>
                            <div class="invalid-feedback">Please enter the client's residential address.</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section 2: Identification & Status -->
            <section class="enhanced-form-section">
                <div class="enhanced-form-section-header">
                    <div class="enhanced-form-section-icon">
                        <i data-feather="credit-card" style="width: 20px; height: 20px; color: #667eea;"></i>
                    </div>
                    <h2 class="enhanced-form-section-title">Identification & Status</h2>
                    <div class="enhanced-form-section-divider"></div>
                </div>

                <div class="row g-3">
                    <!-- ID Type -->
                    <div class="col-md-6">
                        <div class="enhanced-form-group">
                            <label for="identification_type" class="enhanced-form-label required">Primary ID Type</label>
                            <select class="enhanced-form-select" id="identification_type" name="identification_type" required>
                                <option value="">Select ID type...</option>
                                <option value="passport" <?= $currentIdType === 'passport' ? 'selected' : '' ?>>Passport</option>
                                <option value="drivers_license" <?= $currentIdType === 'drivers_license' ? 'selected' : '' ?>>Driver's License</option>
                                <option value="national_id" <?= $currentIdType === 'national_id' ? 'selected' : '' ?>>National ID</option>
                                <option value="philhealth" <?= $currentIdType === 'philhealth' ? 'selected' : '' ?>>PhilHealth ID</option>
                                <option value="sss" <?= $currentIdType === 'sss' ? 'selected' : '' ?>>SSS/GSIS ID</option>
                                <option value="other" <?= $currentIdType === 'other' ? 'selected' : '' ?>>Other Government ID</option>
                            </select>
                            <div class="invalid-feedback">Please select an identification type.</div>
                        </div>
                    </div>

                    <!-- ID Number -->
                    <div class="col-md-6">
                        <div class="enhanced-form-group">
                            <label for="identification_number" class="enhanced-form-label required">ID Number</label>
                            <input 
                                type="text" 
                                class="enhanced-form-control" 
                                id="identification_number" 
                                name="identification_number"
                                value="<?= htmlspecialchars($clientData['identification_number'] ?? '') ?>" 
                                required 
                                placeholder="Enter ID number">
                            <span class="enhanced-form-help">Must be unique in the system</span>
                            <div class="invalid-feedback">ID number is required and must be unique.</div>
                        </div>
                    </div>

                    <!-- Account Status -->
                    <div class="col-md-6">
                        <div class="enhanced-form-group">
                            <label for="status" class="enhanced-form-label required">Account Status</label>
                            <?php if ($auth->hasRole(['super-admin', 'admin'])): ?>
                                <select class="enhanced-form-select" id="status" name="status" required>
                                    <option value="active" <?= $currentStatus === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $currentStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="blacklisted" <?= $currentStatus === 'blacklisted' ? 'selected' : '' ?>>Blacklisted</option>
                                </select>
                            <?php else: ?>
                                <input type="text" class="enhanced-form-control" value="<?= ucfirst($currentStatus) ?>" disabled style="cursor: not-allowed;">
                                <input type="hidden" name="status" value="<?= htmlspecialchars($currentStatus) ?>">
                                <span class="enhanced-form-help">Only administrators can change account status</span>
                            <?php endif; ?>
                            <div class="invalid-feedback">Please select account status.</div>
                        </div>
                    </div>

                    <?php if ($isEditing && isset($clientData['created_at'])): ?>
                    <!-- Creation Date Info -->
                    <div class="col-md-12">
                        <div class="enhanced-form-alert enhanced-form-alert-info">
                            <i data-feather="clock" class="enhanced-form-alert-icon"></i>
                            <div>
                                <strong>Client Record Created:</strong> <?= date('M d, Y \a\t H:i A', strtotime($clientData['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <p class="enhanced-form-required-note">Required fields</p>
        </div>

        <!-- Enhanced Form Actions -->
        <div class="enhanced-form-actions">
            <div class="enhanced-form-actions-left">
                <a href="<?= APP_URL ?>/public/clients/index.php" class="enhanced-btn enhanced-btn-outline">
                    <i data-feather="x" class="enhanced-btn-icon"></i>
                    Cancel
                </a>
            </div>
            <div class="enhanced-form-actions-right">
                <button type="submit" class="enhanced-btn enhanced-btn-<?= $isEditing ? 'primary' : 'success' ?>">
                    <i data-feather="<?= $isEditing ? 'save' : 'user-check' ?>" class="enhanced-btn-icon"></i>
                    <?= $isEditing ? 'Update Client' : 'Create Client' ?>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Enhanced Form JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.enhanced-form');
    if (!form) return;
    
    // Form validation with enhanced feedback
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            
            // Find first invalid field and show shake animation
            const firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
                firstInvalid.focus();
                const group = firstInvalid.closest('.enhanced-form-group');
                if (group) {
                    group.classList.add('shake');
                    setTimeout(() => group.classList.remove('shake'), 500);
                }
            }
        }
        form.classList.add('was-validated');
    });

    // Enhanced focus effects for inputs
    document.querySelectorAll('.enhanced-form-control, .enhanced-form-select, .enhanced-form-textarea').forEach(control => {
        control.addEventListener('focus', function() {
            this.closest('.enhanced-form-group')?.classList.add('is-focused');
        });
        control.addEventListener('blur', function() {
            this.closest('.enhanced-form-group')?.classList.remove('is-focused');
        });
    });

    // Auto-resize textarea
    const addressTextarea = document.getElementById('address');
    if (addressTextarea) {
        addressTextarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
});
</script>
