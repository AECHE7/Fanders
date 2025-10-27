<?php
/**
 * Loan Application Form Template (templates/loans/form.php)
 * Used by public/loans/add.php.
 * * Assumes the following variables are available from the controller:
 * @var array $loan Current loan data (client_id, loan_amount)
 * @var array $clients Array of active clients for selection
 * @var array|null $loanCalculation Detailed calculation preview data
 * @var CSRF $csrf The CSRF utility object
 */
// Lock form fields after successful calculation to prevent edits
$isLocked = isset($loanCalculation) && !empty($loanCalculation) && empty($error);

// Get dynamic loan amount limits from the service
$loanCalcService = new LoanCalculationService();
$loanLimits = $loanCalcService->getLoanAmountLimits();
?>

<form action="<?= APP_URL ?>/public/loans/add.php" method="post" id="loanForm" class="notion-form needs-validation" novalidate>
    <?= $csrf->getTokenField() ?>

    <!-- Form Title and Icon -->
    <div class="notion-form-header mb-4">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center me-3" style="width:38px;height:38px;background:#f7ecff;">
                <i data-feather="dollar-sign" style="width:20px;height:20px;color:#9d71ea;" aria-hidden="true"></i>
            </div>
            <h1 class="mb-0 h5">Loan Application Details</h1>
        </div>
    </div>

    <!-- Loan Information Section -->
    <section class="mb-4 animate-on-scroll" aria-labelledby="basic-info-title">
        <div class="d-flex align-items-center mb-3">
            <h2 class="mb-0 me-2 h6 text-primary">Required Details</h2>
            <div class="notion-divider flex-grow-1"></div>
        </div>
        
        <?php 
        // Check if client is pre-selected from URL and get client details
        $preSelectedClient = null;
        if (!empty($loan['client_id'])) {
            foreach ($clients as $client) {
                if ($client['id'] == $loan['client_id']) {
                    $preSelectedClient = $client;
                    break;
                }
            }
        }
        ?>
        
        <?php if ($preSelectedClient): ?>
        <!-- Pre-selected Client Display -->
        <div class="alert alert-info mb-4 d-flex align-items-center" role="alert">
            <i data-feather="user-check" class="me-2" style="width: 20px; height: 20px;"></i>
            <div>
                <strong>Client Selected:</strong> <?= htmlspecialchars($preSelectedClient['name']) ?> (ID: <?= $preSelectedClient['id'] ?>)
                <br>
                <small class="text-muted">Loan application will be created for this client</small>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row g-3 stagger-fade-in">
            <!-- Client Selection -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <?php if ($preSelectedClient && !$isLocked): ?>
                        <!-- Show read-only display when pre-selected -->
                        <div class="form-control bg-light d-flex align-items-center" style="height: 46px;">
                            <i data-feather="user" class="me-2" style="width: 16px; height: 16px; color: #9d71ea;"></i>
                            <span><?= htmlspecialchars($preSelectedClient['name']) ?> (ID: <?= $preSelectedClient['id'] ?>)</span>
                        </div>
                        <input type="hidden" name="client_id" value="<?= htmlspecialchars($loan['client_id']) ?>">
                        <small class="form-text text-muted">
                            <a href="<?= APP_URL ?>/public/loans/add.php" class="text-decoration-none">
                                <i data-feather="refresh-cw" style="width: 12px; height: 12px;"></i> Choose different client
                            </a>
                        </small>
                    <?php else: ?>
                        <!-- Show dropdown for manual selection -->
                        <select
                            class="notion-form-select form-select custom-select-animated"
                            id="client_id"
                            name="client_id"
                            <?= $isLocked ? 'disabled' : '' ?>
                            required
                            aria-required="true">
                            <option value="">Select a client...</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>" <?= (isset($loan['client_id']) && $loan['client_id'] == $client['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($client['name']) ?> (ID: <?= $client['id'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($isLocked): ?>
                            <input type="hidden" name="client_id" value="<?= htmlspecialchars($loan['client_id']) ?>">
                        <?php endif; ?>
                        <div class="invalid-feedback">Please select a client.</div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Loan Amount -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input
                        type="number"
                        class="notion-form-control"
                        id="loan_amount"
                        name="loan_amount"
                        value="<?= htmlspecialchars($loan['loan_amount'] ?? '') ?>"
                        <?= $isLocked ? 'readonly' : '' ?>
                        required
                        min="<?= $loanLimits['minimum'] ?>"
                        max="<?= $loanLimits['maximum'] ?>"
                        step="100"
                        aria-required="true"
                        placeholder="Principal Amount (₱)">
                    <small class="form-text text-muted"><?= $loanLimits['range_display'] ?></small>
                    <div class="invalid-feedback">Please enter a valid loan amount between <?= $loanLimits['range_display'] ?>.</div>
                </div>
            </div>
        </div>
        <div class="row g-3 stagger-fade-in">
            <!-- Loan Term -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <?php
                    // Include LoanTermHelper for conversational terms
                    require_once BASE_PATH . '/app/utilities/LoanTermHelper.php';
                    $termOptions = LoanTermHelper::getCommonTermOptions();
                    ?>
                    <select
                        class="notion-form-select form-select custom-select-animated"
                        id="loan_term"
                        name="loan_term"
                        <?= $isLocked ? 'disabled' : '' ?>
                        required
                        aria-required="true">
                        <option value="">Select loan term...</option>
                        <?php foreach ($termOptions as $weeks => $label): ?>
                            <option value="<?= $weeks ?>" <?= (isset($loan['loan_term']) && $loan['loan_term'] == $weeks) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                        <!-- Custom option -->
                        <option value="custom" <?= (isset($loan['loan_term']) && !array_key_exists($loan['loan_term'], $termOptions)) ? 'selected' : '' ?>>
                            Custom Term...
                        </option>
                    </select>
                    <?php if ($isLocked): ?>
                        <input type="hidden" name="loan_term" value="<?= htmlspecialchars($loan['loan_term']) ?>">
                    <?php endif; ?>
                    
                    <!-- Custom input field (hidden by default) -->
                    <input
                        type="number"
                        class="notion-form-control mt-2"
                        id="custom_loan_term"
                        name="custom_loan_term"
                        style="display: none;"
                        min="4"
                        max="52"
                        step="1"
                        placeholder="Enter custom weeks (4-52)">
                    
                    <small class="form-text text-muted">Choose from common terms or select custom for 4-52 weeks</small>
                    <div class="invalid-feedback">Please select a valid loan term.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Form Actions -->
    <div class="d-flex justify-content-end mt-4">
        <!-- The Calculate button will submit the form and show the preview - hide after successful calculation -->
        <?php if (!$isLocked): ?>
        <button id="calculateBtn" type="submit" name="calculate" class="btn btn-outline-info me-2 ripple-effect" aria-label="Calculate Loan">
            <i data-feather="calculator" class="me-1" style="width:16px;height:16px;" aria-hidden="true"></i>
            Calculate
        </button>
        <?php endif; ?>
        <?php if (isset($loanCalculation) && !empty($loanCalculation) && empty($error)): ?>
            <!-- Hidden fields preserved; Submit button moved to preview for clearer UX -->
            <input type="hidden" name="client_id" value="<?= htmlspecialchars($loan['client_id']) ?>">
            <input type="hidden" name="loan_amount" value="<?= htmlspecialchars($loan['loan_amount']) ?>">
            <input type="hidden" name="loan_term" value="<?= htmlspecialchars($loan['loan_term']) ?>">
        <?php endif; ?>
        
        <!-- The Submit button will only appear in the controller preview section -->
        <a href="<?= APP_URL ?>/public/loans/index.php" class="btn btn-outline-secondary px-4 ripple-effect">
            Cancel
        </a>
    </div>
</form>

<!-- Improved JS for Form Interactivity and Accessibility -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Standard Bootstrap validation setup
    const form = document.querySelector('.notion-form');
    if(form) {
        form.addEventListener('submit', function(event) {
            // Allow submission for calculate button even if form is invalid
            const isCalculateButton = event.submitter && event.submitter.name === 'calculate';

            if(!form.checkValidity() && !isCalculateButton) {
                event.preventDefault();
                event.stopPropagation();
                // Find first invalid field and trigger shake/focus
                const firstInvalid = form.querySelector(':invalid');
                if(firstInvalid) {
                    firstInvalid.focus();
                    const group = firstInvalid.closest('.notion-form-group');
                    if(group) {
                        group.classList.add('shake-animation');
                        setTimeout(() => group.classList.remove('shake-animation'), 820);
                    }
                }
            }
            form.classList.add('was-validated');
        });
    }

    // Input/Select focus effects
    document.querySelectorAll('.notion-form-control, .notion-form-select').forEach(control => {
        control.addEventListener('focus', function() {
            this.closest('.notion-form-group').classList.add('is-focused');
        });
        control.addEventListener('blur', function() {
            this.closest('.notion-form-group').classList.remove('is-focused');
        });
    });

    // Add ripple to buttons
    document.querySelectorAll('.ripple-effect').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const x = e.clientX - e.target.getBoundingClientRect().left;
            const y = e.clientY - e.target.getBoundingClientRect().top;
            const ripple = document.createElement('span');
            ripple.className = 'ripple-animation';
            ripple.style.left = `${x}px`;
            ripple.style.top = `${y}px`;
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });
    // Handle custom loan term selection
    const loanTermSelect = document.getElementById('loan_term');
    const customTermInput = document.getElementById('custom_loan_term');
    
    if (loanTermSelect && customTermInput) {
        loanTermSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customTermInput.style.display = 'block';
                customTermInput.required = true;
                customTermInput.focus();
            } else {
                customTermInput.style.display = 'none';
                customTermInput.required = false;
                customTermInput.value = '';
            }
        });
        
        // Update hidden loan_term value when custom is entered
        customTermInput.addEventListener('input', function() {
            if (loanTermSelect.value === 'custom' && this.value) {
                // Set the actual loan_term value for form submission
                loanTermSelect.setAttribute('data-custom-value', this.value);
            }
        });
        
        // Before form submission, update loan_term with custom value if needed
        document.getElementById('loanForm').addEventListener('submit', function() {
            if (loanTermSelect.value === 'custom' && customTermInput.value) {
                // Create a hidden input with the custom value
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'loan_term';
                hiddenInput.value = customTermInput.value;
                this.appendChild(hiddenInput);
            }
        });
    }

    // If calculation succeeded, lock the form inputs and focus preview
    const isLocked = <?= (isset($isLocked) && $isLocked) ? 'true' : 'false' ?>;
    if (isLocked) {
        document.querySelectorAll('#loanForm input, #loanForm select').forEach(el => {
            if (!el.hasAttribute('readonly') && !el.disabled) {
                el.setAttribute('readonly', 'readonly');
            }
            if (!el.disabled) el.classList.add('locked-input');
        });
        // Scroll to calculation preview if present
        const preview = document.querySelector('.card.mt-4');
        if (preview) preview.scrollIntoView({behavior: 'smooth'});
        // Client-side debug console message
        console.info('Loan calculation complete - form locked. Submit when ready.');
        // Hide calculate button as a fallback
        const calcBtn = document.getElementById('calculateBtn');
        if (calcBtn) calcBtn.style.display = 'none';
    }
});
</script>

<!-- CSS: General Form Aesthetics (Copied for completeness) -->
<style>
@keyframes shake { 0%,100%{transform:translateX(0);}10%,30%,50%,70%,90%{transform:translateX(-5px);}20%,40%,60%,80%{transform:translateX(5px);} }
.shake-animation { animation: shake 0.8s ease; }
.ripple-effect { position: relative; overflow: hidden; }
.ripple-animation {
    position: absolute; border-radius: 50%;
    background: rgba(255,255,255,0.7); width:100px; height:100px;
    margin-top:-50px; margin-left:-50px; animation: ripple 0.6s linear;
    transform: scale(0); opacity: 1;
}
@keyframes ripple { to{transform:scale(2.5);opacity:0;} }
.custom-select-animated { transition: all 0.3s; }
.custom-select-animated:focus { transform: scale(1.02); }
.select-expanded { box-shadow: 0 0 0 3px rgba(157,113,234,0.25); }
.notion-form-label { font-weight: 600; }
.notion-divider { height: 1px; background: #f0e9ff; border-radius: 1px; }

/* Notion Form Control Styles */
.notion-form-group { position: relative; margin-bottom: 1.5rem; }
.notion-form-control, .notion-form-select {
    padding: 0.75rem 1rem; border: 1px solid #e0e0e0; border-radius: 8px;
    transition: all 0.3s ease; width: 100%; font-size: 1rem; line-height: 1.5;
    background-color: #fff;
}
.notion-form-control:focus, .notion-form-select:focus {
    border-color: #9d71ea; box-shadow: 0 0 0 0.2rem rgba(157, 113, 234, 0.25); outline: none;
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
    top: -0.65rem; font-size: 0.8rem; color: #9d71ea;
}
.notion-form-group input:required:invalid:not(:placeholder-shown) { border-color: #dc3545; }
.notion-form-group input:required:invalid:not(:placeholder-shown) ~ .notion-form-label { color: #dc3545; }
.locked-input { background-color: #f8f9fa; }
.text-primary { color: #9d71ea !important; }
</style>