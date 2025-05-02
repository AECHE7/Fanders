/**
 * Main JavaScript file for Library Management System
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
    
    // Auto dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Date picker initialization for date fields
    const dateFields = document.querySelectorAll('.date-picker');
    if (dateFields.length > 0) {
        dateFields.forEach(function(field) {
            // If you add a date picker library, initialize it here
        });
    }
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    
    // Book search functionality
    const searchInput = document.getElementById('book-search');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const bookItems = document.querySelectorAll('.book-item');
            
            bookItems.forEach(function(item) {
                const title = item.querySelector('.book-title').textContent.toLowerCase();
                const author = item.querySelector('.book-author').textContent.toLowerCase();
                const isbn = item.querySelector('.book-isbn').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || author.includes(searchTerm) || isbn.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    if (forms.length > 0) {
        Array.from(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    }
});

// Function to generate PDF reports
function generatePDF(reportType) {
    const url = `${window.location.origin}/public/reports/generate-pdf.php?type=${reportType}`;
    window.open(url, '_blank');
}

// Function to toggle password visibility
function togglePasswordVisibility(inputId) {
    const passwordInput = document.getElementById(inputId);
    const icon = document.querySelector(`[data-target="${inputId}"] i`);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
