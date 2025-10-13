<?php
/**
 * Borrow Book Form Template
 */
?>

<form action="<?= APP_URL ?>/public/books/borrow.php" method="post" class="notion-form needs-validation" novalidate>
    <?= $csrf->getTokenField() ?>

    <!-- Form Title with Icon -->
    <div class="notion-form-header mb-4">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center me-3" style="width: 38px; height: 38px; background-color: #eaf8f6;">
                <i data-feather="book" style="width: 20px; height: 20px; color: #0ca789;"></i>
            </div>
            <h5 class="mb-0">Borrow Book</h5>
        </div>
    </div>

    <!-- Borrower Information (Current User) -->
    <div class="mb-4 animate-on-scroll">
        <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0 me-2">Borrower Information</h6>
            <div class="notion-divider flex-grow-1"></div>
        </div>
        <div class="notion-form-group interactive-form-field">
            <?php if (isset($user) && $user): ?>
                <input type="hidden" id="user_id" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                <p class="form-control-plaintext">
                    <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                </p>
            <?php else: ?>
                <p class="text-danger">User information not available.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Book Selection -->
    <div class="mb-4 animate-on-scroll">
        <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0 me-2">Book Information</h6>
            <div class="notion-divider flex-grow-1"></div>
        </div>
        <div class="notion-form-group interactive-form-field">
            <select class="notion-form-select form-select" id="book_id" name="book_id" required>
                <?php if (isset($book) && $book): ?>
                    <option value="<?= htmlspecialchars($book['id']) ?>">
                        <?= htmlspecialchars($book['title']) ?> 
                        by <?= htmlspecialchars($book['author']) ?>
                        (Available: <?= $book['available_copies'] ?>)
                    </option>
                <?php else: ?>
                    <option value="">Choose a book...</option>
                <?php endif; ?>
            </select>
            <label for="book_id" class="notion-form-label">Select Book</label>
            <div class="invalid-feedback">Please select a book.</div>
        </div>
    </div>

    <!-- Duration -->
    <div class="mb-4 animate-on-scroll">
        <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0 me-2">Borrow Duration</h6>
            <div class="notion-divider flex-grow-1"></div>
        </div>
        <div class="notion-form-group interactive-form-field">
            <input type="number" class="notion-form-control" id="duration_days" name="duration_days" 
                   value="14" min="1" max="30" required placeholder=" ">
            <label for="duration_days" class="notion-form-label">Borrow Duration (days)</label>
            <div class="form-text">Maximum duration is 30 days.</div>
            <div class="invalid-feedback">Please enter a valid duration (1-30 days).</div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="d-flex justify-content-end mt-4">
        <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-outline-secondary me-2 ripple-effect">
            <i data-feather="x" class="me-1" style="width: 16px; height: 16px;"></i>Cancel
        </a>
        <button type="submit" class="btn btn-primary px-4 ripple-effect" id="submit-button">
            <i data-feather="book" class="me-1" style="width: 16px; height: 16px;"></i>Borrow Book
        </button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('.notion-form');
        const submitButton = document.getElementById('submit-button');

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
                } else {
                    // Show loading spinner
                    submitButton.innerHTML = 'Processing...';
                    submitButton.disabled = true;
                }
                form.classList.add('was-validated');
            });
        }

        // Add ripple effect to buttons
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
