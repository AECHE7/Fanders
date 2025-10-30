<?php
/**
 * Client Account Creation/Edit Form - REFACTORED (templates/clients/form.php)
 * Used by public/clients/add.php (uses $newClient as $clientData) and 
 * public/clients/edit.php (uses $editClient as $clientData).
 * 
 * REFACTORED PATTERN: Form is INSIDE modal (Option A - View Pages Pattern)
 * - Zero jittering guaranteed
 * - Minimal JavaScript
 * - Uses native Bootstrap
 * - Simplest code
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

<!-- Page Header (outside modal) -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center">
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
    <a href="<?= APP_URL ?>/public/clients/index.php" class="btn btn-outline-secondary">
        <i data-feather="arrow-left" class="me-1" style="width: 16px; height: 16px;"></i>
        Back to Clients
    </a>
</div>

<!-- Info Card: Click button to open form modal -->
<div class="card shadow-sm">
    <div class="card-body text-center py-5">
        <div class="mb-4">
            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                 style="width: 80px; height: 80px; background-color: #eaf8f6;">
                <i data-feather="user-plus" style="width: 40px; height: 40px; color: #0ca789;"></i>
            </div>
        </div>
        
        <h5 class="mb-2">
            <?= $isEditing ? 'Update Client Information' : 'Add a New Client to the System' ?>
        </h5>
        <p class="text-muted mb-4">
            <?= $isEditing 
                ? 'Click the button below to update this client\'s details, identification, and status.'
                : 'Click the button below to create a new client account with their personal information, contact details, and identification.' 
            ?>
        </p>
        
        <?php if ($isEditing && $clientData['created_at']): ?>
            <p class="text-muted small mb-3">
                <i data-feather="clock" class="me-1" style="width: 14px; height: 14px;"></i>
                Client created on: <?= date('F d, Y \a\t h:i A', strtotime($clientData['created_at'])) ?>
            </p>
        <?php endif; ?>
        
        <!-- Button to open modal with form inside -->
        <button type="button" class="btn btn-primary btn-lg px-5" 
                id="openClientFormModal">
            <i data-feather="<?= $isEditing ? 'edit' : 'plus' ?>" class="me-2" style="width: 18px; height: 18px;"></i>
            <?= $isEditing ? 'Open Edit Form' : 'Open Client Form' ?>
        </button>
    </div>
</div>

<!-- Client Form Modal (Form is INSIDE) -->
<div class="modal fade" id="clientFormModal" tabindex="-1" 
     aria-labelledby="clientFormModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="clientFormModalLabel">
                    <i data-feather="user" class="me-2" style="width: 20px; height: 20px;"></i>
                    <?= $isEditing ? 'Edit Client Information (ID: ' . $clientData['id'] . ')' : 'Create New Client Account' ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- FORM STARTS HERE - Inside Modal Body -->
            <form action="" method="post" class="needs-validation" novalidate id="clientForm">
                <?= $csrf->getTokenField() ?>
                
                <div class="modal-body">
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
                
                <!-- Modal Footer with Submit Button -->
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i data-feather="x" class="me-1" style="width: 16px; height: 16px;"></i>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="<?= $isEditing ? 'save' : 'check' ?>" class="me-1" style="width: 16px; height: 16px;"></i>
                        <?= $isEditing ? 'Save Changes' : 'Create Client' ?>
                    </button>
                </div>
            </form>
            <!-- FORM ENDS HERE -->
        </div>
    </div>
</div>

<script>
// Enhanced anti-jitter client form system
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('clientForm');
    const openModalButton = document.getElementById('openClientFormModal');
    const clientModal = document.getElementById('clientFormModal');
    
    // Initialize anti-jitter modal system
    const initClientModal = () => {
        if (!openModalButton || !clientModal || !form) return;
        
        // Enhanced form validation
        const validateClientForm = () => {
            return form.checkValidity();
        };
        
        // Anti-jitter modal opening
        openModalButton.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            try {
                // Use enhanced modal system if available
                if (window.ModalUtils && typeof ModalUtils.showModal === 'function') {
                    await ModalUtils.showModal('clientFormModal', {
                        backdrop: 'static',
                        keyboard: true
                    });
                } else {
                    // Fallback to direct Bootstrap
                    const modal = bootstrap.Modal.getOrCreateInstance(clientModal, {
                        backdrop: 'static',
                        keyboard: true
                    });
                    modal.show();
                }
            } catch (error) {
                console.warn('Modal system failed, using fallback:', error);
                // Direct fallback
                const modal = new bootstrap.Modal(clientModal);
                modal.show();
            }
        });
        
        // Enhanced form submission with validation
        form.addEventListener('submit', function(event) {
            if (!validateClientForm()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Focus on first invalid field with gentle animation
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                    
                    // Gentle error indication without jittering
                    const fieldGroup = firstInvalid.closest('.mb-3, .form-group');
                    if (fieldGroup) {
                        fieldGroup.classList.add('form-validation-error');
                        setTimeout(() => {
                            fieldGroup.classList.remove('form-validation-error');
                        }, 300);
                    }
                    
                    // Smooth scroll to invalid field within modal
                    firstInvalid.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center',
                        inline: 'nearest'
                    });
                }
            }
            form.classList.add('was-validated');
        });
    };
    
    // Enhanced modal event handlers
    if (clientModal) {
        clientModal.addEventListener('shown.bs.modal', function() {
            // Refresh feather icons with error handling
            if (typeof feather !== 'undefined') {
                try {
                    feather.replace();
                } catch (error) {
                    console.warn('Feather icons refresh failed:', error);
                }
            }
            
            // Focus on first input for better UX
            const firstInput = form.querySelector('input[type="text"], input[type="email"], select');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        });
        
        clientModal.addEventListener('hidden.bs.modal', function() {
            // Clear validation state when modal closes
            form.classList.remove('was-validated');
            
            // Clear custom validation messages
            const invalidElements = form.querySelectorAll(':invalid');
            invalidElements.forEach(element => {
                element.setCustomValidity('');
            });
        });
    }
    
    // Initialize the system
    initClientModal();
});
</script>

<style>
/* ================================================
   ANTI-JITTER CLIENT MODAL ENHANCEMENTS
   ================================================ */

/* Stabilize modal structure */
#clientFormModal {
    --modal-transition-duration: 0.2s;
}

#clientFormModal .modal-dialog {
    transform: translateZ(0);
    backface-visibility: hidden;
    contain: layout style;
}

#clientFormModal .modal-content {
    transform: translateZ(0);
    overflow: hidden;
}

/* Enhanced modal body with smooth scrolling */
#clientFormModal .modal-body {
    max-height: 70vh;
    overflow-y: auto;
    contain: layout style;
    scrollbar-width: thin;
}

#clientFormModal .modal-body::-webkit-scrollbar {
    width: 6px;
}

#clientFormModal .modal-body::-webkit-scrollbar-track {
    background: #f8f9fa;
    border-radius: 3px;
}

#clientFormModal .modal-body::-webkit-scrollbar-thumb {
    background: #dee2e6;
    border-radius: 3px;
}

#clientFormModal .modal-body::-webkit-scrollbar-thumb:hover {
    background: #adb5bd;
}

/* Anti-jitter form controls */
#clientFormModal .form-control,
#clientFormModal .form-select {
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    transform: translateZ(0);
}

#clientFormModal .form-control:focus,
#clientFormModal .form-select:focus {
    border-color: #0ca789;
    box-shadow: 0 0 0 0.2rem rgba(12, 167, 137, 0.25);
    outline: none;
}

/* Smooth button interactions */
#clientFormModal .btn {
    transition: transform 0.15s ease-out, box-shadow 0.15s ease-out;
    transform: translateZ(0);
}

#clientFormModal .btn:hover:not(:disabled) {
    transform: translateY(-1px) translateZ(0);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
}

#clientFormModal .btn:active {
    transform: translateY(0) translateZ(0);
    transition-duration: 0.05s;
}

/* Enhanced close button */
#clientFormModal .btn-close {
    transition: transform 0.15s ease-in-out;
}

#clientFormModal .btn-close:hover {
    transform: scale(1.1) translateZ(0);
}

/* Prevent validation animation conflicts */
#clientFormModal .form-validation-error {
    animation: clientModalShake 0.3s ease-in-out;
}

@keyframes clientModalShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-3px); }
    75% { transform: translateX(3px); }
}

/* Improve spacing and typography */
#clientFormModal .text-primary {
    color: #0ca789 !important;
}

#clientFormModal .form-label .text-danger {
    font-size: 0.875rem;
}

#clientFormModal .modal-body > .mb-4:last-child {
    margin-bottom: 0 !important;
}

#clientFormModal .alert {
    font-size: 0.9rem;
}
</style>
