<?php
/**
 * Book Form Template (ENHANCED FOR ADD/EDIT, UNIFIED PUBLISHED_YEAR)
 * Handles both adding and editing books robustly.
 */
?>


<form action="" method="post" class="notion-form needs-validation" novalidate>
    <?= $csrf->getTokenField() ?>

    <!-- Form Title and Icon -->
    <div class="notion-form-header mb-4">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center me-3" style="width:38px;height:38px;background:#f7ecff;">
                <i data-feather="book" style="width:20px;height:20px;color:#9d71ea;" aria-hidden="true"></i>
            </div>
            <h1 class="mb-0 h5"><?= isset($book['id']) ? 'Edit Book Details' : 'Add New Book' ?></h1>
        </div>
    </div>

    <!-- Basic Information Section -->
    <section class="mb-4 animate-on-scroll" aria-labelledby="basic-info-title">
        <div class="d-flex align-items-center mb-3">
            <h2 class="mb-0 me-2 h6" id="basic-info-title">Basic Information</h2>
            <div class="notion-divider flex-grow-1"></div>
        </div>
        <div class="row g-3 stagger-fade-in">
            <!-- Title -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <label for="title" class="notion-form-label">Title <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        class="notion-form-control"
                        id="title"
                        name="title"
                        value="<?= htmlspecialchars($book['title'] ?? '') ?>"
                        required
                        autocomplete="off"
                        maxlength="255"
                        aria-required="true"
                        placeholder="Book title">
                    <div class="invalid-feedback">Please enter a book title.</div>
                </div>
            </div>
            <!-- Author -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <label for="author" class="notion-form-label">Author <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        class="notion-form-control"
                        id="author"
                        name="author"
                        value="<?= htmlspecialchars($book['author'] ?? '') ?>"
                        required
                        autocomplete="off"
                        maxlength="255"
                        aria-required="true"
                        placeholder="Author name">
                    <div class="invalid-feedback">Please enter an author name.</div>
                </div>
            </div>
            <!-- Published Year -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <label for="published_year" class="notion-form-label">Published Year</label>
                    <input
                        type="number"
                        class="notion-form-control"
                        id="published_year"
                        name="published_year"
                        value="<?= htmlspecialchars(
                            $book['published_year'] ?? $book['published_year'] ?? ''
                        ) ?>"
                        min="1800"
                        max="<?= date('Y') ?>"
                        step="1"
                        placeholder="ex: 2020"
                        inputmode="numeric"
                        aria-describedby="pub-year-range">
                    <small id="pub-year-range" class="form-text text-muted">Between 1800 and <?= date('Y') ?></small>
                </div>
            </div>
            <!-- Category -->
            <div class="col-md-6">
                <div class="notion-form-group">
                    <label for="category_id" class="notion-form-label">Category <span class="text-danger">*</span></label>
                    <select
                        class="notion-form-select form-select custom-select-animated"
                        id="category_id"
                        name="category_id"
                        required
                        aria-required="true">
                        <option value="">Select Category</option>
                        <option value="1" <?= (isset($book['category_id']) && $book['category_id'] == 1) ? 'selected' : '' ?>>Fiction</option>
                        <option value="2" <?= (isset($book['category_id']) && $book['category_id'] == 2) ? 'selected' : '' ?>>Non-Fiction</option>
                        <option value="3" <?= (isset($book['category_id']) && $book['category_id'] == 3) ? 'selected' : '' ?>>Science</option>
                        <option value="4" <?= (isset($book['category_id']) && $book['category_id'] == 4) ? 'selected' : '' ?>>Technology</option>
                        <option value="5" <?= (isset($book['category_id']) && $book['category_id'] == 5) ? 'selected' : '' ?>>History</option>
                        <option value="6" <?= (isset($book['category_id']) && $book['category_id'] == 6) ? 'selected' : '' ?>>Philosophy</option>
                        <option value="7" <?= (isset($book['category_id']) && $book['category_id'] == 7) ? 'selected' : '' ?>>Art</option>
                        <option value="8" <?= (isset($book['category_id']) && $book['category_id'] == 8) ? 'selected' : '' ?>>Reference</option>
                    </select>
                    <div class="invalid-feedback">Please select a category.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Inventory Section -->
    <section class="mb-4 animate-on-scroll" aria-labelledby="inventory-title">
        <div class="d-flex align-items-center mb-3">
            <h2 class="mb-0 me-2 h6" id="inventory-title">Inventory</h2>
            <div class="notion-divider flex-grow-1"></div>
        </div>
        <div class="row g-3">
            <!-- Total Copies -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <label for="total_copies" class="notion-form-label">Total Copies <span class="text-danger">*</span></label>
                    <input
                        type="number"
                        class="notion-form-control"
                        id="total_copies"
                        name="total_copies"
                        value="<?= (int)($book['total_copies'] ?? 1) ?>"
                        min="1"
                        required
                        step="1"
                        aria-required="true"
                        inputmode="numeric"
                        placeholder="Total number in stock">
                    <div class="invalid-feedback">Please enter at least 1 copy.</div>
                </div>
            </div>
            <!-- Available Copies & Meter -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <label for="available_copies" class="notion-form-label">Available Copies <span class="text-danger">*</span></label>
                    <input
                        type="number"
                        class="notion-form-control"
                        id="available_copies"
                        name="available_copies"
                        value="<?= (int)($book['available_copies'] ?? 1) ?>"
                        min="0"
                        max="<?= (int)($book['total_copies'] ?? 1) ?>"
                        required
                        step="1"
                        aria-required="true"
                        inputmode="numeric"
                        placeholder="Currently available">
                    <div class="invalid-feedback available-copies-feedback">Available copies cannot exceed total copies.</div>
                    <!-- Availability visual -->
                    <div class="availability-indicator mt-2" aria-live="polite">
                        <div class="availability-meter" aria-hidden="true">
                            <div class="availability-meter-fill"
                                 style="width: <?= (isset($book['total_copies']) && $book['total_copies'] > 0)
                                     ? (($book['available_copies'] / $book['total_copies']) * 100) : 0 ?>%">
                            </div>
                        </div>
                        <small class="availability-text text-muted">
                            <span id="available-ratio"><?= (int)($book['available_copies'] ?? 1) ?>/<?= (int)($book['total_copies'] ?? 1) ?></span> copies available
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Form Actions -->
    <div class="d-flex justify-content-end mt-4">
        <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-outline-secondary me-2 ripple-effect" tabindex="0">Cancel</a>
        <button type="submit" class="btn btn-primary px-4 ripple-effect" aria-label="<?= isset($book['id']) ? 'Update Book' : 'Add Book' ?>">
            <i data-feather="save" class="me-1" style="width:16px;height:16px;" aria-hidden="true"></i>
            <?= isset($book['id']) ? 'Update Book' : 'Add Book' ?>
        </button>
    </div>
</form>

<!-- Improved JS for Form Interactivity and Accessibility -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inline helper for updating description counter
    const desc = document.getElementById('description');
    const descCount = document.getElementById('description-count');
    if(desc && descCount) {
        desc.addEventListener('input', function() {
            descCount.textContent = this.value.length;
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        desc.dispatchEvent(new Event('input'));
    }

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

    // Relation between total/available copies
    const totalCopiesInput = document.getElementById('total_copies');
    const availableCopiesInput = document.getElementById('available_copies');
    const availabilityMeter = document.querySelector('.availability-meter-fill');
    const availabilityRatio = document.getElementById('available-ratio');
    function updateAvailabilityUI() {
        const total = parseInt(totalCopiesInput.value) || 0;
        const available = parseInt(availableCopiesInput.value) || 0;
        const pct = total > 0 ? (available / total) * 100 : 0;
        if(availabilityMeter) {
            availabilityMeter.style.width = pct + '%';
            let color = 'bg-success';
            if(pct <= 25) color = 'bg-danger';
            else if(pct <= 50) color = 'bg-warning';
            else if(pct <= 75) color = 'bg-info';
            availabilityMeter.className = 'availability-meter-fill ' + color;
        }
        if(availabilityRatio) {
            availabilityRatio.textContent = available + '/' + total;
        }
    }
    function validateAvailableCopies() {
        const total = parseInt(totalCopiesInput.value) || 0;
        const available = parseInt(availableCopiesInput.value) || 0;
        if(available > total) {
            availableCopiesInput.setCustomValidity('Available copies cannot exceed total copies');
            document.querySelector('.available-copies-feedback').textContent = 'Available copies cannot exceed total copies.';
        } else {
            availableCopiesInput.setCustomValidity('');
        }
    }
    if(totalCopiesInput && availableCopiesInput) {
        totalCopiesInput.addEventListener('input', function() {
            const total = parseInt(this.value) || 0;
            availableCopiesInput.setAttribute('max', total);
            if(parseInt(availableCopiesInput.value) > total) {
                availableCopiesInput.value = total;
            }
            validateAvailableCopies();
            updateAvailabilityUI();
        });
        availableCopiesInput.addEventListener('input', function() {
            validateAvailableCopies();
            updateAvailabilityUI();
        });
        updateAvailabilityUI();
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
.availability-meter {
    height: 5px;
    background: #e9ecef;
    border-radius: 3px;
    margin-top: 5px;
    overflow: hidden;
}
.availability-meter-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s, background 0.3s;
}
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
.auto-expand { min-height: 100px; overflow-y: hidden; resize: none; transition: height 0.2s; }
.notion-form-label { font-weight: 600; }
.notion-divider { height: 1px; background: #f0e9ff; border-radius: 1px; }
</style>
