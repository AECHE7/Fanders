<?php
/**
 * Reports List Template
 * Displays available reports with quick access links
 */
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Reports</h1>
                <div>
                    <small class="text-muted">Generate and export various reports</small>
                </div>
            </div>

            <div class="row">
                <!-- Loan Reports -->
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i data-feather="file-text" class="text-primary" style="width: 48px; height: 48px;"></i>
                            </div>
                            <h5 class="card-title">Loan Reports</h5>
                            <p class="card-text text-muted small">View loan disbursements, status, and performance metrics</p>
                            <a href="<?= APP_URL ?>/public/reports/loans.php" class="btn btn-primary btn-sm">
                                <i data-feather="eye" class="me-1" style="width: 14px; height: 14px;"></i>View Report
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Payment Reports -->
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i data-feather="dollar-sign" class="text-success" style="width: 48px; height: 48px;"></i>
                            </div>
                            <h5 class="card-title">Payment Reports</h5>
                            <p class="card-text text-muted small">Track payment collections and transaction history</p>
                            <a href="<?= APP_URL ?>/public/reports/payments.php" class="btn btn-success btn-sm">
                                <i data-feather="eye" class="me-1" style="width: 14px; height: 14px;"></i>View Report
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Client Reports -->
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i data-feather="users" class="text-info" style="width: 48px; height: 48px;"></i>
                            </div>
                            <h5 class="card-title">Client Reports</h5>
                            <p class="card-text text-muted small">Analyze client portfolio and loan history</p>
                            <a href="<?= APP_URL ?>/public/reports/clients.php" class="btn btn-info btn-sm">
                                <i data-feather="eye" class="me-1" style="width: 14px; height: 14px;"></i>View Report
                            </a>
                        </div>
                    </div>
                </div>

                <!-- User Reports -->
                <?php if (in_array($_SESSION['role'], ['super-admin', 'admin'])): ?>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i data-feather="user-check" class="text-warning" style="width: 48px; height: 48px;"></i>
                            </div>
                            <h5 class="card-title">User Reports</h5>
                            <p class="card-text text-muted small">Staff activity and user management reports</p>
                            <a href="<?= APP_URL ?>/public/reports/users.php" class="btn btn-warning btn-sm">
                                <i data-feather="eye" class="me-1" style="width: 14px; height: 14px;"></i>View Report
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Advanced Reports Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Advanced Reports</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Financial Summary -->
                                <div class="col-md-6 col-lg-3 mb-3">
                                    <div class="d-flex align-items-center">
                                        <i data-feather="trending-up" class="text-primary me-3" style="width: 24px; height: 24px;"></i>
                                        <div>
                                            <h6 class="mb-1">Financial Summary</h6>
                                            <small class="text-muted">Monthly financial overview</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Overdue Loans -->
                                <div class="col-md-6 col-lg-3 mb-3">
                                    <div class="d-flex align-items-center">
                                        <i data-feather="alert-triangle" class="text-danger me-3" style="width: 24px; height: 24px;"></i>
                                        <div>
                                            <h6 class="mb-1">Overdue Loans</h6>
                                            <small class="text-muted">Loans past due date</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Collection Sheet -->
                                <div class="col-md-6 col-lg-3 mb-3">
                                    <div class="d-flex align-items-center">
                                        <i data-feather="clipboard" class="text-success me-3" style="width: 24px; height: 24px;"></i>
                                        <div>
                                            <h6 class="mb-1">Collection Sheet</h6>
                                            <small class="text-muted">Daily collection tracking</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cash Blotter -->
                                <div class="col-md-6 col-lg-3 mb-3">
                                    <div class="d-flex align-items-center">
                                        <i data-feather="dollar-sign" class="text-info me-3" style="width: 24px; height: 24px;"></i>
                                        <div>
                                            <h6 class="mb-1">Cash Blotter</h6>
                                            <small class="text-muted">Daily cash flow report</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <a href="<?= APP_URL ?>/public/reports/index.php?type=financial" class="btn btn-outline-primary w-100">
                                        <i data-feather="bar-chart-2" class="me-2" style="width: 16px; height: 16px;"></i>
                                        Financial Summary
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="<?= APP_URL ?>/public/reports/index.php?type=overdue" class="btn btn-outline-danger w-100">
                                        <i data-feather="alert-triangle" class="me-2" style="width: 16px; height: 16px;"></i>
                                        Overdue Report
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="<?= APP_URL ?>/public/transactions/index.php" class="btn btn-outline-info w-100">
                                        <i data-feather="activity" class="me-2" style="width: 16px; height: 16px;"></i>
                                        Transaction Log
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="<?= APP_URL ?>/public/cash-blotter/index.php" class="btn btn-outline-success w-100">
                                        <i data-feather="dollar-sign" class="me-2" style="width: 16px; height: 16px;"></i>
                                        Cash Blotter
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
