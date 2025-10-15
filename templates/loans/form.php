<?php
/**
 * Loan Application Form Template
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

    <!-- Basic Information Section -->
    <section class="mb-4 animate-on-scroll" aria-labelledby="basic-info-title">
        <div class="d-flex align-items-center mb-3">
            <h2 class="mb-0 me-2 h6" id="basic-info-title">Loan Information</h2>
            <div class="notion-divider flex-grow-1"></div>
        </div>
        <div class="row g-3 stagger-fade-in">
            <!-- Client Selection -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <label for="client_id" class="notion-form-label">Client <span class="text-danger">*</span></label>
                    <select
                        class="notion-form-select form-select custom-select-animated"
                        id="client_id"
                        name="client_id"
                        required
                        aria-required="true">
                        <option value="">Select Client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id'] ?>" <?= (isset($loan['client_id']) && $loan['client_id'] == $client['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($client['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select a client.</div>
                </div>
            </div>
            <!-- Loan Amount -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <label for="loan_amount" class="notion-form-label">Loan Amount (₱) <span class="text-danger">*</span></label>
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
                        placeholder="Enter loan amount">
                    <small class="form-text text-muted">Minimum ₱1,000 - Maximum ₱50,000</small>
                    <div class="invalid-feedback">Please enter a valid loan amount between ₱1,000 and ₱50,000.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Form Actions -->
    <div class="d-flex justify-content-end mt-4">
        <button type="submit" name="calculate" class="btn btn-outline-info me-2 ripple-effect" aria-label="Calculate Loan">
            <i data-feather="calculator" class="me-1" style="width:16px;height:16px;" aria-hidden="true"></i>
            Calculate
        </button>
        <button type="submit" name="submit_loan" class="btn btn-primary px-4 ripple-effect" aria-label="Submit Loan Application">
            <i data-feather="send" class="me-1" style="width:16px;height:16px;" aria-hidden="true"></i>
            Submit Application
        </button>
    </div>
</form>

<!-- Improved JS for Form Interactivity and Accessibility -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap-style validation
    const form = document.querySelector('.notion-form');
    if(form) {
        form.addEventListener('submit', function(event) {
            if(!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();

                // Focus first invalid input
                const firstInvalid = form.querySelector(':invalid');
                if(firstInvalid) {
                    firstInvalid.focus();
                    const group = firstInvalid.closest('.notion-form-group');
                    if(group) {
                        group.classList.add('shake-animation');
                        setTimeout(() => group.classList.remove('shake-animation'), 820);
                    }
                }
            } else {
                // Visual feedback on valid submit
                const submitBtn = form.querySelector('[type="submit"]');
                if(submitBtn) submitBtn.classList.add('pulse');
                const cardBody = form.closest('.card-body') || document.body;
                const checkmark = document.createElement('div');
                checkmark.className = 'floating-success-checkmark';
                checkmark.innerHTML = `<div class="success-checkmark"><div class="check-icon"><span class="icon-line line-tip"></span><span class="icon-line line-long"></span></div></div>`;
                cardBody.appendChild(checkmark);
                setTimeout(() => {
                    checkmark.style.opacity = '0';
                    setTimeout(() => checkmark.remove(), 500);
                }, 2000);
            }
            form.classList.add('was-validated');
        });
    }

    // Focus effect for inputs/selects
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

    // Animated select on focus
    document.querySelectorAll('.custom-select-animated').forEach(select => {
        select.addEventListener('focus', function() {
            this.classList.add('select-expanded');
        });
        select.addEventListener('blur', function() {
            this.classList.remove('select-expanded');
        });
    });
});
</script>

<!-- CSS: Improved comments and accessibility focus -->
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
.floating-success-checkmark {
    position: fixed; top:50%; left:50%; transform:translate(-50%,-50%);
    z-index: 9999; background:rgba(255,255,255,.9);
    border-radius:50%; padding:20px;
    box-shadow:0 4px 20px rgba(0,0,0,.15);
    transition: opacity 0.5s;
}
.notion-form-label { font-weight: 600; }
.notion-divider { height: 1px; background: #f0e9ff; border-radius: 1px; }
</style>
