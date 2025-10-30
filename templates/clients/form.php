<?php
/**
 * Client Account Creation/Edit Form - SIMPLIFIED (templates/clients/form.php)
 * NO MODALS - Direct form to eliminate jittering issues
 * 
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

<!-- Direct Client Form - NO MODAL -->
<form action="" method="post" class="needs-validation" novalidate id="clientForm">
    <?= $csrf->getTokenField() ?>
    
    <div class="row">
        <div class="col-12">
            <!-- Form Header -->
            <div class="d-flex align-items-center mb-4">
                <div class="rounded d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px; background-color: #eaf8f6;">
                    <i data-feather="user" style="width: 24px; height: 24px; color: #0ca789;"></i>
                </div>
                <div>
                    <h4 class="mb-0"><?= $isEditing ? 'Edit Client Information' : 'Create New Client Account' ?></h4>
                    <?php if ($isEditing): ?>
                        <p class="text-muted small mb-0">Client ID: <?= $clientData['id'] ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Basic Information Section -->
            <div class="mb-4">
                <div class="d-flex align-items-center mb-3">
                    <h6 class="mb-0 me-2 text-primary">
                        <i data-feather="user" class="me-1" style="width: 18px; height: 18px;"></i>
                        Personal Details
                    </h6>
                    <div style="flex-grow: 1; height: 1px; background-color: #e0e0e0; margin-left: 1rem;"></div>
                </div>

                <div class="row g-3">
                    <!-- Full Name Field (Required) -->
                    <div class="col-md-12">
                        <label for="name" class="form-label">
                            Full Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="name" name="name"
                            value="<?= htmlspecialchars($clientData['name'] ?? '') ?>" 
                            required
                            placeholder="Enter client's full name">
                        <div class="invalid-feedback">Please enter the client's full name.</div>
                    </div>

                            <!-- Phone Field (Required & Unique) -->
                            <div class="col-md-6">
                                <label for="phone_number" class="form-label">
                                    Phone Number <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="phone_number" name="phone_number"
                                    value="<?= htmlspecialchars($clientData['phone_number'] ?? '') ?>" 
                                    required 
                                    pattern="\d{8,15}"
                                    placeholder="e.g., 09123456789">
                                <div class="invalid-feedback">Please enter a valid phone number (8-15 digits).</div>
                            </div>

                            <!-- Email Field (Optional but Unique) -->
                            <div class="col-md-6">
                                <label for="email" class="form-label">
                                    Email Address <span class="text-muted small">(Optional)</span>
                                </label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?= htmlspecialchars($clientData['email'] ?? '') ?>"
                                    placeholder="client@example.com">
                                <div class="invalid-feedback">Please enter a valid email address.</div>
                            </div>
                            
                            <!-- Date of Birth Field -->
                            <div class="col-md-6">
                                <label for="date_of_birth" class="form-label">
                                    Date of Birth <span class="text-muted small">(Must be 18+)</span>
                                </label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth"
                                    value="<?= htmlspecialchars($clientData['date_of_birth'] ?? '') ?>" 
                                    max="<?= date('Y-m-d', strtotime('-18 years')) ?>">
                                <div class="invalid-feedback">Client must be at least 18 years old.</div>
                            </div>
                            
                            <!-- Address Field -->
                            <div class="col-md-6">
                                <label for="address" class="form-label">
                                    Residential Address <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="address" name="address" 
                                          rows="2" required
                                          placeholder="Enter complete residential address"><?= htmlspecialchars($clientData['address'] ?? '') ?></textarea>
                                <div class="invalid-feedback">Please enter the client's residential address.</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Identification Section -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <h6 class="mb-0 me-2 text-primary">
                                <i data-feather="credit-card" class="me-1" style="width: 18px; height: 18px;"></i>
                                Identification & Status
                            </h6>
                            <div style="flex-grow: 1; height: 1px; background-color: #e0e0e0; margin-left: 1rem;"></div>
                        </div>
                        
                        <div class="row g-3">
                            <!-- Identification Type Field -->
                            <div class="col-md-6">
                                <label for="identification_type" class="form-label">
                                    Primary ID Type <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="identification_type" name="identification_type" required>
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

                            <!-- Identification Number Field -->
                            <div class="col-md-6">
                                <label for="identification_number" class="form-label">
                                    ID Number <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="identification_number" name="identification_number"
                                    value="<?= htmlspecialchars($clientData['identification_number'] ?? '') ?>" 
                                    required
                                    placeholder="Enter unique ID number">
                                <div class="invalid-feedback">ID number is required and must be unique.</div>
                            </div>

                            <!-- Account Status Dropdown (Only editable by Super Admin/Admin) -->
                            <div class="col-md-6">
                                <label for="status" class="form-label">Account Status</label>
                                <?php if ($auth->hasRole(['super-admin', 'admin'])): ?>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" <?= $currentStatus === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $currentStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                        <option value="blacklisted" <?= $currentStatus === 'blacklisted' ? 'selected' : '' ?>>Blacklisted</option>
                                    </select>
                                    <div class="form-text">Active clients can apply for loans</div>
                                <?php else: ?>
                                    <input type="text" class="form-control" value="<?= ucfirst($currentStatus) ?>" disabled>
                                    <input type="hidden" name="status" value="<?= htmlspecialchars($currentStatus) ?>">
                                    <div class="form-text text-muted">Only admins can change status</div>
                                <?php endif; ?>
                                <div class="invalid-feedback">Please select account status.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Info Alert -->
                    <div class="alert alert-info d-flex align-items-start">
                        <i data-feather="info" class="me-2 mt-1" style="width: 18px; height: 18px; flex-shrink: 0;"></i>
                        <div>
                            <strong>Important:</strong> 
                            <?= $isEditing 
                                ? 'Changes will be saved immediately. Make sure all information is accurate before submitting.'
                                : 'Phone number and identification number must be unique. The client will be able to apply for loans once the account is created.' 
                            ?>
                        </div>
                    </div>
                </div>
                
            <!-- Form Actions -->
            <div class="d-flex justify-content-between mt-4">
                <a href="<?= APP_URL ?>/public/clients/index.php" class="btn btn-outline-secondary">
                    <i data-feather="arrow-left" class="me-1" style="width: 16px; height: 16px;"></i>
                    Back to Clients
                </a>
                <button type="submit" class="btn btn-primary px-4">
                    <i data-feather="<?= $isEditing ? 'save' : 'check' ?>" class="me-1" style="width: 16px; height: 16px;"></i>
                    <?= $isEditing ? 'Save Changes' : 'Create Client' ?>
                </button>
            </div>
        </div>
    </div>
</form>

<script>
// Simple client form validation - NO MODALS
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('clientForm');
    
    if (form) {
        // Simple form validation
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Focus on first invalid field
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            } else {
                // Form is valid, show loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.disabled) {
                    submitBtn.disabled = true;
                    const originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
                }
            }
            form.classList.add('was-validated');
        });
    }
    
    // Initialize Feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>

<style>
/* Simple Form Styling - NO MODAL */
.form-control:focus,
.form-select:focus {
    border-color: #0ca789;
    box-shadow: 0 0 0 0.2rem rgba(12, 167, 137, 0.25);
}

.text-primary {
    color: #0ca789 !important;
}

.form-label .text-danger {
    font-size: 0.875rem;
}

.btn {
    transition: transform 0.15s ease-out, box-shadow 0.15s ease-out;
}

.btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}
</style>
