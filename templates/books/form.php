<?php
/**
 * Book form template
 * Used for both adding and editing books
 * Notion-inspired interactive design
 */
?>

<form action="" method="post" class="notion-form needs-validation" novalidate>
    <?= $csrf->getTokenField() ?>
    
    <!-- Form Title with Icon -->
    <div class="notion-form-header mb-4">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center me-3" style="width: 38px; height: 38px; background-color: #f7ecff;">
                <i data-feather="book" style="width: 20px; height: 20px; color: #9d71ea;"></i>
            </div>
            <h5 class="mb-0"><?= isset($book['id']) ? 'Edit Book Details' : 'Add New Book' ?></h5>
        </div>
    </div>
    
    <!-- Basic Information Section -->
    <div class="mb-4 animate-on-scroll">
        <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0 me-2">Basic Information</h6>
            <div class="notion-divider flex-grow-1"></div>
        </div>
        
        <div class="row g-3 stagger-fade-in">
            <!-- Title Field -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="text" class="notion-form-control" id="title" name="title" 
                           value="<?= htmlspecialchars($book['title']) ?>" required placeholder=" ">
                    <label for="title" class="notion-form-label">Title</label>
                    <div class="invalid-feedback">Please enter a book title.</div>
                </div>
            </div>
            
            <!-- Author Field -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="text" class="notion-form-control" id="author" name="author" 
                           value="<?= htmlspecialchars($book['author']) ?>" required placeholder=" ">
                    <label for="author" class="notion-form-label">Author</label>
                    <div class="invalid-feedback">Please enter an author name.</div>
                </div>
            </div>
            
            <!-- ISBN Field -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="text" class="notion-form-control" id="isbn" name="isbn" 
                           value="<?= htmlspecialchars($book['isbn']) ?>" placeholder=" ">
                    <label for="isbn" class="notion-form-label">ISBN</label>
                    <small class="form-text text-muted">Optional: International Standard Book Number</small>
                </div>
            </div>
            
            <!-- Publication Year Field -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="number" class="notion-form-control" id="publication_year" name="publication_year" 
                           value="<?= htmlspecialchars($book['publication_year']) ?>" min="1800" 
                           max="<?= date('Y') ?>" placeholder=" ">
                    <label for="publication_year" class="notion-form-label">Publication Year</label>
                    <small class="form-text text-muted">Optional: Year the book was published</small>
                </div>
            </div>
            
            <!-- Publisher Field -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="text" class="notion-form-control" id="publisher" name="publisher" 
                           value="<?= htmlspecialchars($book['publisher']) ?>" placeholder=" ">
                    <label for="publisher" class="notion-form-label">Publisher</label>
                    <small class="form-text text-muted">Optional: Name of the publisher</small>
                </div>
            </div>
            
            <!-- Category Dropdown -->
            <div class="col-md-6">
                <div class="notion-form-group">
                    <label for="category_id" class="notion-form-label">Category</label>
                    <select class="notion-form-select form-select custom-select-animated" id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= ($book['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Please select a category.</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Inventory Section -->
    <div class="mb-4 animate-on-scroll">
        <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0 me-2">Inventory</h6>
            <div class="notion-divider flex-grow-1"></div>
        </div>
        
        <div class="row g-3">
            <!-- Total Copies Field -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="number" class="notion-form-control" id="total_copies" name="total_copies" 
                           value="<?= (int)$book['total_copies'] ?>" min="1" required placeholder=" ">
                    <label for="total_copies" class="notion-form-label">Total Copies</label>
                    <div class="invalid-feedback">Please enter at least 1 copy.</div>
                </div>
            </div>
            
            <!-- Available Copies Field with visual counter -->
            <div class="col-md-6">
                <div class="notion-form-group interactive-form-field">
                    <input type="number" class="notion-form-control" id="available_copies" name="available_copies" 
                           value="<?= (int)$book['available_copies'] ?>" min="0" 
                           max="<?= (int)$book['total_copies'] ?>" required placeholder=" ">
                    <label for="available_copies" class="notion-form-label">Available Copies</label>
                    <div class="invalid-feedback available-copies-feedback">Available copies cannot exceed total copies.</div>
                    
                    <!-- Visual Availability Indicator -->
                    <div class="availability-indicator mt-2">
                        <div class="availability-meter">
                            <div class="availability-meter-fill" style="width: <?= ($book['total_copies'] > 0) ? (($book['available_copies'] / $book['total_copies']) * 100) : 0 ?>%"></div>
                        </div>
                        <small class="availability-text text-muted">
                            <span id="available-ratio"><?= (int)$book['available_copies'] ?>/<?= (int)$book['total_copies'] ?></span> copies available
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Description Section -->
    <div class="mb-4 animate-on-scroll">
        <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0 me-2">Description</h6>
            <div class="notion-divider flex-grow-1"></div>
        </div>
        
        <div class="notion-form-group">
            <label for="description" class="notion-form-label">Book Description</label>
            <textarea class="notion-form-control auto-expand" id="description" name="description" rows="4"><?= htmlspecialchars($book['description']) ?></textarea>
            <small class="form-text text-muted">Optional: Brief description of the book's content</small>
            <div class="character-counter text-muted small mt-1 text-end">
                <span id="description-count">0</span> characters
            </div>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div class="d-flex justify-content-end mt-4">
        <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-outline-secondary me-2 ripple-effect">Cancel</a>
        <button type="submit" class="btn btn-primary px-4 ripple-effect">
            <i data-feather="save" class="me-1" style="width: 16px; height: 16px;"></i>
            <?= isset($book['id']) ? 'Update Book' : 'Add Book' ?>
        </button>
    </div>
</form>

<!-- JavaScript for Form Interactivity -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        const form = document.querySelector('.notion-form');
        if (form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // Find the first invalid field and focus it
                    const invalidField = form.querySelector(':invalid');
                    if (invalidField) {
                        invalidField.focus();
                        
                        // Add a shake animation to the invalid field's parent
                        const fieldGroup = invalidField.closest('.notion-form-group');
                        if (fieldGroup) {
                            fieldGroup.classList.add('shake-animation');
                            setTimeout(() => {
                                fieldGroup.classList.remove('shake-animation');
                            }, 820); // Animation duration + a bit extra
                        }
                    }
                } else {
                    // Add a pulse animation to the submit button
                    const submitBtn = form.querySelector('[type="submit"]');
                    if (submitBtn) {
                        submitBtn.classList.add('pulse');
                    }
                    
                    // Show a success checkmark that fades out
                    const formContainer = form.closest('.card-body');
                    const checkmark = document.createElement('div');
                    checkmark.className = 'floating-success-checkmark';
                    checkmark.innerHTML = `
                        <div class="success-checkmark">
                            <div class="check-icon">
                                <span class="icon-line line-tip"></span>
                                <span class="icon-line line-long"></span>
                            </div>
                        </div>
                    `;
                    
                    formContainer.appendChild(checkmark);
                    
                    // Remove the checkmark after animation completes
                    setTimeout(() => {
                        checkmark.style.opacity = '0';
                        setTimeout(() => {
                            checkmark.remove();
                        }, 500);
                    }, 2000);
                }
                form.classList.add('was-validated');
            });
        }
        
        // Total copies and available copies relationship with visual indicator
        const totalCopiesInput = document.getElementById('total_copies');
        const availableCopiesInput = document.getElementById('available_copies');
        const availabilityMeter = document.querySelector('.availability-meter-fill');
        const availabilityRatio = document.getElementById('available-ratio');
        
        if (totalCopiesInput && availableCopiesInput && availabilityMeter) {
            function updateAvailabilityIndicator() {
                const totalValue = parseInt(totalCopiesInput.value) || 0;
                const availableValue = parseInt(availableCopiesInput.value) || 0;
                
                // Update meter width
                const percentage = totalValue > 0 ? (availableValue / totalValue) * 100 : 0;
                availabilityMeter.style.width = percentage + '%';
                
                // Update color based on availability percentage
                if (percentage <= 25) {
                    availabilityMeter.className = 'availability-meter-fill bg-danger';
                } else if (percentage <= 50) {
                    availabilityMeter.className = 'availability-meter-fill bg-warning';
                } else if (percentage <= 75) {
                    availabilityMeter.className = 'availability-meter-fill bg-info';
                } else {
                    availabilityMeter.className = 'availability-meter-fill bg-success';
                }
                
                // Update text ratio
                availabilityRatio.textContent = availableValue + '/' + totalValue;
            }
            
            // Update max attribute when total copies changes
            totalCopiesInput.addEventListener('input', function() {
                const totalValue = parseInt(this.value) || 0;
                availableCopiesInput.setAttribute('max', totalValue);
                
                // Update available copies if it exceeds the new total
                const availableValue = parseInt(availableCopiesInput.value) || 0;
                if (availableValue > totalValue) {
                    availableCopiesInput.value = totalValue;
                }
                
                // Validate visually
                validateAvailableCopies();
                updateAvailabilityIndicator();
            });
            
            // Validate available copies on input
            availableCopiesInput.addEventListener('input', function() {
                validateAvailableCopies();
                updateAvailabilityIndicator();
            });
            
            function validateAvailableCopies() {
                const totalValue = parseInt(totalCopiesInput.value) || 0;
                const availableValue = parseInt(availableCopiesInput.value) || 0;
                
                if (availableValue > totalValue) {
                    availableCopiesInput.setCustomValidity('Available copies cannot exceed total copies');
                    document.querySelector('.available-copies-feedback').textContent = 'Available copies cannot exceed total copies.';
                } else {
                    availableCopiesInput.setCustomValidity('');
                }
            }
            
            // Initialize on page load
            updateAvailabilityIndicator();
        }
        
        // Interactive field focusing effects
        const notionFormControls = document.querySelectorAll('.notion-form-control, .notion-form-select');
        notionFormControls.forEach(control => {
            // Add focus class to parent
            control.addEventListener('focus', function() {
                this.closest('.notion-form-group').classList.add('is-focused');
            });
            
            // Remove focus class on blur
            control.addEventListener('blur', function() {
                this.closest('.notion-form-group').classList.remove('is-focused');
            });
        });
        
        // Auto-expand textarea
        const autoExpandTextareas = document.querySelectorAll('.auto-expand');
        autoExpandTextareas.forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
                
                // Update character count
                const countElement = document.getElementById(this.id + '-count');
                if (countElement) {
                    countElement.textContent = this.value.length;
                }
            });
            
            // Initial height adjustment and character count
            textarea.dispatchEvent(new Event('input'));
        });
        
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
        
        // Animated select on focus
        const customSelects = document.querySelectorAll('.custom-select-animated');
        customSelects.forEach(select => {
            select.addEventListener('focus', function() {
                this.classList.add('select-expanded');
            });
            
            select.addEventListener('blur', function() {
                this.classList.remove('select-expanded');
            });
        });
    });
</script>

<style>
    /* Shake animation for invalid fields */
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    
    .shake-animation {
        animation: shake 0.8s ease;
    }
    
    /* Availability meter styling */
    .availability-meter {
        height: 5px;
        background-color: #e9ecef;
        border-radius: 3px;
        margin-top: 5px;
        overflow: hidden;
    }
    
    .availability-meter-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.3s ease, background-color 0.3s ease;
    }
    
    /* Ripple effect styles */
    .ripple-effect {
        position: relative;
        overflow: hidden;
    }
    
    .ripple-animation {
        position: absolute;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.7);
        width: 100px;
        height: 100px;
        margin-top: -50px;
        margin-left: -50px;
        animation: ripple 0.6s linear;
        transform: scale(0);
        opacity: 1;
    }
    
    @keyframes ripple {
        to {
            transform: scale(2.5);
            opacity: 0;
        }
    }
    
    /* Animated select styles */
    .custom-select-animated {
        transition: all 0.3s ease;
    }
    
    .custom-select-animated:focus {
        transform: scale(1.02);
    }
    
    .select-expanded {
        box-shadow: 0 0 0 3px rgba(var(--notion-blue-rgb), 0.25);
    }
    
    /* Floating success checkmark */
    .floating-success-checkmark {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 9999;
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 50%;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        transition: opacity 0.5s ease;
    }
    
    /* Auto-expanding textarea */
    .auto-expand {
        min-height: 100px;
        overflow-y: hidden;
        resize: none;
        transition: height 0.2s ease;
    }
</style>