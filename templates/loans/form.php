<?php
/**
 * Loan Application Form Template (templates/loans/form.php)
 * Enhanced modern design with consistent styling across the application
 * Used by public/loans/add.php.
 * Assumes the following variables are available from the controller:
 * @var array $loan Current loan data (client_id, loan_amount)
 * @var array $clients Array of active clients for selection
 * @var array|null $loanCalculation Detailed calculation preview data
 * @var CSRF $csrf The CSRF utility object
 */
// Lock form fields after successful calculation to prevent edits
$isLocked = isset($loanCalculation) && !empty($loanCalculation) && empty($error);
?>

<div class="enhanced-form-wrapper">
    <form action="<?= APP_URL ?>/public/loans/add.php" method="post" id="loanForm" class="enhanced-form needs-validation" novalidate>
        <?= $csrf->getTokenField() ?>

        <!-- Enhanced Form Header -->
        <div class="enhanced-form-header">
            <div class="enhanced-form-header-icon">
                <i data-feather="dollar-sign" style="width: 24px; height: 24px; color: white;"></i>
            </div>
            <h1 class="enhanced-form-header-title">Loan Application</h1>
            <p class="enhanced-form-header-subtitle">Complete the form below to submit a new loan application for approval</p>
        </div>

        <!-- Enhanced Form Body -->
        <div class="enhanced-form-body">
            
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
            <!-- Pre-selected Client Alert -->
            <div class="enhanced-form-alert enhanced-form-alert-info">
                <i data-feather="user-check" class="enhanced-form-alert-icon"></i>
                <div>
                    <strong>Client Selected:</strong> <?= htmlspecialchars($preSelectedClient['name']) ?> (ID: <?= $preSelectedClient['id'] ?>)
                    <br>
                    <small>Loan application will be created for this client. <a href="<?= APP_URL ?>/public/loans/add.php" class="text-decoration-underline">Choose different client</a></small>
                </div>
            </div>
            <?php endif; ?>

            <!-- Section 1: Loan Details -->
            <section class="enhanced-form-section">
                <div class="enhanced-form-section-header">
                    <div class="enhanced-form-section-icon">
                        <i data-feather="file-text" style="width: 20px; height: 20px; color: #667eea;"></i>
                    </div>
                    <h2 class="enhanced-form-section-title">Loan Details</h2>
                    <div class="enhanced-form-section-divider"></div>
                </div>
                
                <div class="row g-3">
                    <!-- Client Selection -->
                    <div class="col-md-6">
                        <div class="enhanced-form-group">
                            <?php if ($preSelectedClient && !$isLocked): ?>
                                <label for="client_id" class="enhanced-form-label required">Client</label>
                                <div class="enhanced-form-control" style="background-color: #f7fafc; cursor: not-allowed; display: flex; align-items: center;">
                                    <i data-feather="user" style="width: 16px; height: 16px; color: #667eea; margin-right: 0.5rem;"></i>
                                    <?= htmlspecialchars($preSelectedClient['name']) ?> (ID: <?= $preSelectedClient['id'] ?>)
                                </div>
                                <input type="hidden" name="client_id" value="<?= htmlspecialchars($loan['client_id']) ?>">
                            <?php else: ?>
                                <label for="client_id" class="enhanced-form-label required">Select Client</label>
                                <select
                                    class="enhanced-form-select"
                                    id="client_id"
                                    name="client_id"
                                    <?= $isLocked ? 'disabled' : '' ?>
                                    required>
                                    <option value="">Choose a client...</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?= $client['id'] ?>" <?= (isset($loan['client_id']) && $loan['client_id'] == $client['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($client['name']) ?> (ID: <?= $client['id'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($isLocked): ?>
                                    <input type="hidden" name="client_id" value="<?= htmlspecialchars($loan['client_id']) ?>">
                                <?php endif; ?>
                                <div class="invalid-feedback">Please select a client for this loan.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Loan Amount -->
                    <div class="col-md-6">
                        <div class="enhanced-form-group">
                            <label for="loan_amount" class="enhanced-form-label required">Principal Amount (₱)</label>
                            <input
                                type="number"
                                class="enhanced-form-control"
                                id="loan_amount"
                                name="loan_amount"
                                value="<?= htmlspecialchars($loan['loan_amount'] ?? '') ?>"
                                <?= $isLocked ? 'readonly' : '' ?>
                                required
                                min="1000"
                                max="50000"
                                step="100"
                                placeholder="Enter loan amount">
                            <span class="enhanced-form-help">
                                <i data-feather="info" class="enhanced-form-help-icon"></i>
                                Minimum ₱1,000 - Maximum ₱50,000
                            </span>
                            <div class="invalid-feedback">Enter a valid amount between ₱1,000 and ₱50,000.</div>
                        </div>
                    </div>

                    <!-- Loan Term -->
                    <div class="col-md-6">
                        <div class="enhanced-form-group">
                            <label for="loan_term" class="enhanced-form-label required">Loan Term (Weeks)</label>
                            <input
                                type="number"
                                class="enhanced-form-control"
                                id="loan_term"
                                name="loan_term"
                                value="<?= htmlspecialchars($loan['loan_term'] ?? 17) ?>"
                                <?= $isLocked ? 'readonly' : '' ?>
                                required
                                min="4"
                                max="52"
                                step="1"
                                placeholder="Number of weeks">
                            <span class="enhanced-form-help">
                                <i data-feather="calendar" class="enhanced-form-help-icon"></i>
                                Minimum 4 weeks - Maximum 52 weeks
                            </span>
                            <div class="invalid-feedback">Enter a valid term between 4 and 52 weeks.</div>
                        </div>
                    </div>
                </div>

                <?php if (isset($loanCalculation) && !empty($loanCalculation) && empty($error)): ?>
                    <!-- Hidden fields preserved for final submission -->
                    <input type="hidden" name="client_id" value="<?= htmlspecialchars($loan['client_id']) ?>">
                    <input type="hidden" name="loan_amount" value="<?= htmlspecialchars($loan['loan_amount']) ?>">
                    <input type="hidden" name="loan_term" value="<?= htmlspecialchars($loan['loan_term']) ?>">
                <?php endif; ?>

                <p class="enhanced-form-required-note">Required fields</p>
            </section>
        </div>

        <!-- Enhanced Form Actions -->
        <div class="enhanced-form-actions">
            <div class="enhanced-form-actions-left">
                <a href="<?= APP_URL ?>/public/loans/index.php" class="enhanced-btn enhanced-btn-outline">
                    <i data-feather="x" class="enhanced-btn-icon"></i>
                    Cancel
                </a>
            </div>
            <div class="enhanced-form-actions-right">
                <?php if (!$isLocked): ?>
                <button id="calculateBtn" type="submit" name="calculate" class="enhanced-btn enhanced-btn-secondary">
                    <i data-feather="calculator" class="enhanced-btn-icon"></i>
                    Calculate Loan
                </button>
                <?php endif; ?>
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
        const isCalculateButton = event.submitter && event.submitter.name === 'calculate';
        
        if (!form.checkValidity() && !isCalculateButton) {
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

    // Lock form if calculation succeeded
    const isLocked = <?= (isset($isLocked) && $isLocked) ? 'true' : 'false' ?>;
    if (isLocked) {
        document.querySelectorAll('#loanForm input, #loanForm select').forEach(el => {
            if (!el.hasAttribute('readonly') && !el.disabled && el.type !== 'hidden') {
                el.setAttribute('readonly', 'readonly');
                el.style.backgroundColor = '#f7fafc';
                el.style.cursor = 'not-allowed';
            }
        });
        
        // Scroll to calculation preview
        setTimeout(() => {
            const preview = document.querySelector('.card.mt-4');
            if (preview) preview.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    }
});
</script>