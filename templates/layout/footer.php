<?php
/**
 * Footer template for the Fanders Microfinance Loan Management System
 */
?>


    <footer class="footer mt-auto py-4 bg-light border-top">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary me-2">
                            <path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        <span class="text-muted fw-medium">&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</span>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        Powered by Fanders Microfinance Technology
                    </small>
                </div>
            </div>
        </div>
    </footer>

        <!-- Bootstrap JS Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

        <!-- Feather Icons -->
        <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>

        <!-- Global variables for JavaScript -->
        <script>
            const APP_URL = "<?= APP_URL ?>";
            const APP_NAME = "<?= APP_NAME ?>";
            const CURRENT_USER = <?= json_encode($auth->getCurrentUser() ?? []) ?>;
        </script>

        <!-- Custom JavaScript files -->
        <script src="<?= APP_URL ?>/assets/js/main.js"></script>
        <script src="<?= APP_URL ?>/assets/js/interactive.js"></script>

        <!-- Initialize components -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize feather icons
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }

                // Enable tooltips everywhere
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

                // Enable popovers
                const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
                popoverTriggerList.map(function (popoverTriggerEl) {
                    return new bootstrap.Popover(popoverTriggerEl);
                });

                // Add loading states to forms
                document.querySelectorAll('form').forEach(form => {
                    form.addEventListener('submit', function() {
                        const submitBtn = form.querySelector('button[type="submit"]');
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
                        }
                    });
                });

                // Auto-hide alerts after 5 seconds
                document.querySelectorAll('.alert').forEach(alert => {
                    setTimeout(() => {
                        if (alert.classList.contains('alert-dismissible')) {
                            const bsAlert = new bootstrap.Alert(alert);
                            bsAlert.close();
                        }
                    }, 5000);
                });

                // Toggleable Sidebar Functionality
                const sidebarToggle = document.getElementById('sidebarToggle');
                const sidebar = document.getElementById('sidebarMenu');
                const mainContent = document.querySelector('.main-content');
                const layout = document.querySelector('.layout');

                if (sidebarToggle && sidebar) {
                    // Load sidebar state from localStorage
                    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                    if (sidebarCollapsed) {
                        sidebar.classList.add('sidebar-collapsed');
                        sidebar.classList.remove('sidebar-expanded');
                        if (layout) layout.classList.add('sidebar-hidden');
                    } else {
                        sidebar.classList.add('sidebar-expanded');
                        sidebar.classList.remove('sidebar-collapsed');
                        if (layout) layout.classList.remove('sidebar-hidden');
                    }

                    // Toggle sidebar on button click
                    sidebarToggle.addEventListener('click', function() {
                        const isCollapsed = sidebar.classList.contains('sidebar-collapsed');

                        if (isCollapsed) {
                            sidebar.classList.remove('sidebar-collapsed');
                            sidebar.classList.add('sidebar-expanded');
                            if (layout) layout.classList.remove('sidebar-hidden');
                            localStorage.setItem('sidebarCollapsed', 'false');
                        } else {
                            sidebar.classList.add('sidebar-collapsed');
                            sidebar.classList.remove('sidebar-expanded');
                            if (layout) layout.classList.add('sidebar-hidden');
                            localStorage.setItem('sidebarCollapsed', 'true');
                        }

                        // Update toggle button icon
                        const toggleIcon = sidebarToggle.querySelector('i');
                        if (toggleIcon) {
                            toggleIcon.setAttribute('data-feather', isCollapsed ? 'sidebar' : 'x');
                            if (typeof feather !== 'undefined') {
                                feather.replace();
                            }
                        }

                        // Force layout recalculation for responsive content
                        setTimeout(() => {
                            window.dispatchEvent(new Event('resize'));
                        }, 350);
                    });

                    // Handle window resize
                    window.addEventListener('resize', function() {
                        if (window.innerWidth < 768) {
                            // On mobile, always show expanded sidebar
                            sidebar.classList.remove('sidebar-collapsed');
                            sidebar.classList.add('sidebar-expanded');
                            if (layout) layout.classList.remove('sidebar-hidden');
                        }
                    });
                }
            });
        </script>
    </body>
</html>

<?php include_once BASE_PATH . '/templates/layout/session_timeout_modal.php'; ?>