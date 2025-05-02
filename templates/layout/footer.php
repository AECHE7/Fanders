<?php
/**
 * Footer template for the Library Management System
 */
?>

                </main>
            </div><!-- end row -->
        </div><!-- end container-fluid -->
        
        <footer class="footer mt-auto py-3">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <span class="text-muted">&copy; <?= date('Y') ?> <?= APP_NAME ?></span>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="text-muted">Made with <i data-feather="heart" class="text-danger" style="width: 16px; height: 16px;"></i> for Libraries</span>
                    </div>
                </div>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
        <!-- Global variables for JavaScript -->
        <script>
            const APP_URL = "<?= APP_URL ?>";
        </script>
        <script src="<?= APP_URL ?>/assets/js/main.js"></script>
        <script>
            // Initialize feather icons
            feather.replace();
            
            // Enable tooltips everywhere
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            })
            
            // Enable popovers
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
            var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl)
            })
        </script>
    </body>
</html>