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
    <div class="mb-4">
        <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0 me-2">Basic Information</h6>
            <div class="notion-divider flex-grow-1"></div>
        </div>
        
        <div class="row g-3">
            <!-- Title Field -->
            <div class="col-md-6">
                <div class="notion-form-group">
                    <label for="title" class="notion-form-label">Title</label>
                    <input type="text" class="notion-form-control" id="title" name="title" value="<?= htmlspecialchars($book['title']) ?>" required>
                    <div class="invalid-feedback">Please enter a book title.</div>
                </div>
            </div>
            
            <!-- Author Field -->
            <div class="col-md-6">
                <div class="notion-form-group">
                    <label for="author" class="notion-form-label">Author</label>
                    <input type="text" class="notion-form-control" id="author" name="author" value="<?= htmlspecialchars($book['author']) ?>" required>
                    <div class="invalid-feedback">Please enter an author name.</div>
                </div>
            </div>
            
            <!-- ISBN Field -->
            <div class="col-md-6">
                <div class="notion-form-group">
                    <label for="isbn" class="notion-form-label">ISBN</label>
                    <input type="text" class="notion-form-control" id="isbn" name="isbn" value="<?= htmlspecialchars($book['isbn']) ?>">
                    <small class="form-text text-muted">Optional: International Standard Book Number</small>
                </div>
            </div>
            
            <!-- Publication Year Field -->
            <div class="col-md-6">
                <div class="notion-form-group">
                    <label for="publication_year" class="notion-form-label">Publication Year</label>
                    <input type="number" class="notion-form-control" id="publication_year" name="publication_year" value="<?= htmlspecialchars($book['publication_year']) ?>" min="1800" max="<?= date('Y') ?>">
                    <small class="form-text text-muted">Optional: Year the book was published</small>
                </div>
            </div>
            
            <!-- Publisher Field -->
            <div class="col-md-6">
                <div class="notion-form-group">
                    <label for="publisher" class="notion-form-label">Publisher</label>
                    <input type="text" class="notion-form-control" id="publisher" name="publisher" value="<?= htmlspecialchars($book['publisher']) ?>">
                    <small class="form-text text-muted">Optional: Name of the publisher</small>
                </div>
            </div>
            
            <!-- Category Dropdown -->
            <div class="col-md-6">
                <div class="notion-form-group">
                    <label for="category_id" class="notion-form-label">Category</label>
                    <select class="notion-form-select form-select" id="category_id" name="category_id" required>
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
    <div class="mb-4">
        <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0 me-2">Inventory</h6>
            <div class="notion-divider flex-grow-1"></div>
        </div>
        
        <div class="row g-3">
            <!-- Total Copies Field -->
            <div class="col-md-6">
                <div class="notion-form-group">
                    <label for="total_copies" class="notion-form-label">Total Copies</label>
                    <input type="number" class="notion-form-control" id="total_copies" name="total_copies" value="<?= (int)$book['total_copies'] ?>" min="1" required>
                    <div class="invalid-feedback">Please enter at least 1 copy.</div>
                </div>
            </div>
            
            <!-- Available Copies Field -->
            <div class="col-md-6">
                <div class="notion-form-group">
                    <label for="available_copies" class="notion-form-label">Available Copies</label>
                    <input type="number" class="notion-form-control" id="available_copies" name="available_copies" value="<?= (int)$book['available_copies'] ?>" min="0" max="<?= (int)$book['total_copies'] ?>" required>
                    <div class="invalid-feedback available-copies-feedback">Available copies cannot exceed total copies.</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Description Section -->
    <div class="mb-4">
        <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0 me-2">Description</h6>
            <div class="notion-divider flex-grow-1"></div>
        </div>
        
        <div class="notion-form-group">
            <label for="description" class="notion-form-label">Book Description</label>
            <textarea class="notion-form-control" id="description" name="description" rows="4"><?= htmlspecialchars($book['description']) ?></textarea>
            <small class="form-text text-muted">Optional: Brief description of the book's content</small>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div class="d-flex justify-content-end mt-4">
        <a href="<?= APP_URL ?>/public/books/index.php" class="btn btn-outline-secondary me-2">Cancel</a>
        <button type="submit" class="btn btn-primary px-4">
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
                }
                form.classList.add('was-validated');
            });
        }
        
        // Total copies and available copies relationship
        const totalCopiesInput = document.getElementById('total_copies');
        const availableCopiesInput = document.getElementById('available_copies');
        
        if (totalCopiesInput && availableCopiesInput) {
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
            });
            
            // Validate available copies on input
            availableCopiesInput.addEventListener('input', validateAvailableCopies);
            
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
    });
</script>