<?php
/**
 * Footer template for the Library Management System
 */
?>

            </div><!-- end row -->
        </div><!-- end container-fluid -->
        
        <footer class="footer mt-auto py-3 bg-dark">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <span class="text-light">&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</span>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="text-light">Developed for Library Management</span>
                    </div>
                </div>
            </div>
        </footer>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
        <script src="<?= APP_URL ?>/assets/js/main.js"></script>
        <script>
            // Initialize feather icons
            feather.replace();
        </script>
    </body>
</html>