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
?>

<form action="" method="post" class="notion-form needs-validation" novalidate>
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
        <div class="row g-3 stagger-fade-in">
            <!-- Client Selection -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <select
                        class="notion-form-select form-select custom-select-animated"
                        id="client_id"
                        name="client_id"
                        required
                        aria-required="true">
                        <option value="">Select a client...</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id'] ?>" <?= (isset($loan['client_id']) && $loan['client_id'] == $client['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($client['name']) ?> (ID: <?= $client['id'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select a client.</div>
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
                        required
                        min="1000"
                        max="50000"
                        step="100"
                        aria-required="true"
                        placeholder="Principal Amount (₱)">
                    <small class="form-text text-muted">Minimum ₱1,000 - Maximum ₱50,000</small>
                    <div class="invalid-feedback">Please enter a valid loan amount between ₱1,000 and ₱50,000.</div>
                </div>
            </div>
        </div>
        <div class="row g-3 stagger-fade-in">
            <!-- Loan Term -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input
                        type="number"
                        class="notion-form-control"
                        id="loan_term"
                        name="loan_term"
                        value="<?= htmlspecialchars($loan['loan_term'] ?? 17) ?>"
                        required
                        min="4"
                        max="52"
                        step="1"
                        aria-required="true"
                        placeholder="Loan Term (Weeks)">
                    <small class="form-text text-muted">Minimum 4 weeks - Maximum 52 weeks</small>
                    <div class="invalid-feedback">Please enter a valid loan term between 4 and 52 weeks.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Form Actions -->
    <div class="d-flex justify-content-end mt-4">
        <!-- The Calculate button will submit the form and show the preview -->
        <button type="submit" name="calculate" class="btn btn-outline-info me-2 ripple-effect" aria-label="Calculate Loan">
            <i data-feather="calculator" class="me-1" style="width:16px;height:16px;" aria-hidden="true"></i>
            Calculate
        </button>
        
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
.text-primary { color: #9d71ea !important; }
</style>