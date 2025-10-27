<?php
/**
 * Client Reports Controller
 * Generates comprehensive reports for client management with professional design
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once '../../public/init.php';

// Enforce role-based access control for Fanders Microfinance Staff
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account-officer']);

// Initialize services
$reportService = new ReportService();

// --- 1. Process Filters ---
require_once BASE_PATH . '/app/utilities/FilterUtility.php';
$filters = FilterUtility::sanitizeFilters($_GET);
$filters = FilterUtility::validateDateRange($filters);

// Additional client-specific filters
$clientFilters = [
    'status' => $filters['status'] ?? '',
    'location' => $filters['location'] ?? '',
    'loan_officer' => $filters['loan_officer'] ?? '',
    'min_age' => $filters['min_age'] ?? '',
    'max_age' => $filters['max_age'] ?? '',
    'gender' => $filters['gender'] ?? ''
];

$filters = array_merge($filters, $clientFilters);

// --- 2. Generate Report Data ---
$reportData = $reportService->generateClientReport($filters);

// Add array check to prevent fatal error
if (!is_array($reportData)) {
    $reportData = [];
}

// --- 3. Calculate Statistics ---
$clientStats = [
    'total_clients' => count($reportData),
    'active_clients' => count(array_filter($reportData, fn($client) => ($client['status'] ?? '') === 'active')),
    'inactive_clients' => count(array_filter($reportData, fn($client) => ($client['status'] ?? '') === 'inactive')),
    'male_clients' => count(array_filter($reportData, fn($client) => strtolower($client['gender'] ?? '') === 'male')),
    'female_clients' => count(array_filter($reportData, fn($client) => strtolower($client['gender'] ?? '') === 'female')),
    'clients_with_loans' => count(array_filter($reportData, fn($client) => ($client['total_loans'] ?? 0) > 0)),
    'total_loans' => array_sum(array_column($reportData, 'total_loans')),
    'total_loan_amount' => array_sum(array_column($reportData, 'total_loan_amount'))
];

// Calculate averages
$clientStats['average_loans_per_client'] = $clientStats['total_clients'] > 0 
    ? round($clientStats['total_loans'] / $clientStats['total_clients'], 1) 
    : 0;

$clientStats['average_loan_amount'] = $clientStats['clients_with_loans'] > 0 
    ? $clientStats['total_loan_amount'] / $clientStats['clients_with_loans'] 
    : 0;

// --- 4. Handle CSV Export ---
$exportFormat = $_GET['format'] ?? '';

if ($exportFormat === 'csv') {
    try {
        if (empty($reportData)) {
            throw new Exception('No data available for export.');
        }
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="client_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, ['Client Name', 'Phone', 'Email', 'Address', 'Gender', 'Status', 'Total Loans', 'Total Amount', 'Date Registered']);
        
        // Data
        foreach ($reportData as $client) {
            fputcsv($output, [
                $client['full_name'] ?? $client['name'],
                $client['phone'],
                $client['email'] ?? '',
                $client['address'] ?? '',
                $client['gender'] ?? '',
                ucfirst($client['status'] ?? 'active'),
                $client['total_loans'] ?? 0,
                $client['total_loan_amount'] ?? 0,
                $client['created_at'] ?? ''
            ]);
        }
        
        fclose($output);
    } catch (Exception $e) {
        $session->setFlash('error', 'Error exporting CSV: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/reports/clients.php');
    }
    exit;
}

// --- 5. Handle PDF Export ---
if ($exportFormat === 'pdf') {
    try {
        $reportService = new ReportService();
        $reportService->exportClientReportPDF($reportData, $filters);
        exit;
    } catch (Exception $e) {
        $session->setFlash('error', 'Error generating PDF: ' . $e->getMessage());
        header('Location: ' . APP_URL . '/public/reports/clients.php');
        exit;
    }
}

// Prepare data for template
$pageTitle = "Client Reports";
$reportMetrics = [
    'report_date' => date('F j, Y'),
    'report_period' => !empty($filters['date_from']) && !empty($filters['date_to']) 
        ? date('M j', strtotime($filters['date_from'])) . ' - ' . date('M j, Y', strtotime($filters['date_to']))
        : 'All Time',
    'active_percentage' => $clientStats['total_clients'] > 0 
        ? round(($clientStats['active_clients'] / $clientStats['total_clients']) * 100, 1) 
        : 0
];

include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
    <div class="content-wrapper">
        <!-- Dashboard Header -->
        <div class="notion-page-header mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #fff0e6;">
                            <i data-feather="users" style="width: 24px; height: 24px; color: #fd7e14;"></i>
                        </div>
                    </div>
                    <h1 class="notion-page-title mb-0">Client Reports</h1>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <div class="text-muted d-none d-md-block me-3">
                        <i data-feather="calendar" class="me-1" style="width: 14px; height: 14px;"></i>
                        <?= date('l, F j, Y') ?>
                    </div>
                    <div class="btn-group">
                        <a href="<?= APP_URL ?>/public/reports/clients.php?format=csv&<?= http_build_query($filters) ?>" class="btn btn-sm btn-outline-success">
                            <i data-feather="download" class="me-1" style="width: 14px; height: 14px;"></i> CSV
                        </a>
                        <a href="<?= APP_URL ?>/public/reports/clients.php?format=pdf&<?= http_build_query($filters) ?>" class="btn btn-sm btn-outline-danger">
                            <i data-feather="file-text" class="me-1" style="width: 14px; height: 14px;"></i> PDF
                        </a>
                    </div>
                    <a href="<?= APP_URL ?>/public/clients/index.php" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="arrow-left" class="me-1" style="width: 14px; height: 14px;"></i> Back to Clients
                    </a>
                </div>
            </div>
            <div class="notion-divider my-3"></div>
        </div>

        <!-- Flash Messages -->
        <?php if ($session->hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i data-feather="check-circle" class="me-2" style="width: 16px; height: 16px;"></i>
                <?= $session->getFlash('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($session->hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i data-feather="alert-circle" class="me-2" style="width: 16px; height: 16px;"></i>
                <?= $session->getFlash('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Report Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <i data-feather="filter" class="me-2" style="width:18px;height:18px;"></i>
                    <strong>Report Filters</strong>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="date_from" value="<?= $filters['date_from'] ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="date_to" value="<?= $filters['date_to'] ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Gender</label>
                        <select class="form-select" name="gender">
                            <option value="">All Genders</option>
                            <option value="male" <?= ($filters['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= ($filters['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="search" style="width: 16px; height: 16px;"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Report Summary -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-warning shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Total Clients</h6>
                                <h3 class="mb-0"><?= number_format($clientStats['total_clients']) ?></h3>
                            </div>
                            <i data-feather="users" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Active Clients</h6>
                                <h3 class="mb-0"><?= number_format($clientStats['active_clients']) ?></h3>
                            </div>
                            <i data-feather="user-check" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-primary shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">With Loans</h6>
                                <h3 class="mb-0"><?= number_format($clientStats['clients_with_loans']) ?></h3>
                            </div>
                            <i data-feather="credit-card" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="card-title text-uppercase small">Avg Loans</h6>
                                <h3 class="mb-0"><?= $clientStats['average_loans_per_client'] ?></h3>
                            </div>
                            <i data-feather="trending-up" class="icon-lg opacity-50" style="width: 3rem; height: 3rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Demographics Breakdown -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-primary shadow-sm">
                    <div class="card-body text-center">
                        <i data-feather="user" class="text-primary mb-2" style="width: 2rem; height: 2rem;"></i>
                        <h4 class="text-primary"><?= number_format($clientStats['male_clients']) ?></h4>
                        <small class="text-muted">Male Clients</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success shadow-sm">
                    <div class="card-body text-center">
                        <i data-feather="user" class="text-success mb-2" style="width: 2rem; height: 2rem;"></i>
                        <h4 class="text-success"><?= number_format($clientStats['female_clients']) ?></h4>
                        <small class="text-muted">Female Clients</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning shadow-sm">
                    <div class="card-body text-center">
                        <i data-feather="percent" class="text-warning mb-2" style="width: 2rem; height: 2rem;"></i>
                        <h4 class="text-warning"><?= $reportMetrics['active_percentage'] ?>%</h4>
                        <small class="text-muted">Active Rate</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Data Table -->
        <div class="card shadow-sm">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i data-feather="list" class="me-2" style="width:18px;height:18px;"></i>
                        <strong>Client Report Data</strong>
                        <span class="badge bg-primary ms-2"><?= count($reportData) ?> records</span>
                    </div>
                    <div class="text-muted small">
                        Period: <?= $reportMetrics['report_period'] ?>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($reportData)): ?>
                    <div class="text-center py-5">
                        <i data-feather="inbox" class="text-muted mb-3" style="width: 3rem; height: 3rem;"></i>
                        <h5 class="text-muted">No client data found</h5>
                        <p class="text-muted">Try adjusting your filters or date range to see results.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Client Name</th>
                                    <th style="width: 130px;">Phone</th>
                                    <th style="width: 100px;">Gender</th>
                                    <th class="text-center" style="width: 80px;">Loans</th>
                                    <th class="text-end">Total Amount</th>
                                    <th style="width: 100px;">Status</th>
                                    <th style="width: 110px;">Registered</th>
                                    <th style="width: 100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $client): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-warning text-white me-2" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                                    <?= strtoupper(substr($client['full_name'] ?? $client['name'] ?? 'U', 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold"><?= htmlspecialchars($client['full_name'] ?? $client['name'] ?? '') ?></div>
                                                    <?php if (!empty($client['email'])): ?>
                                                        <small class="text-muted"><?= htmlspecialchars($client['email']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($client['phone'])): ?>
                                                <a href="tel:<?= htmlspecialchars($client['phone']) ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($client['phone']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($client['gender'])): ?>
                                                <span class="badge <?= strtolower($client['gender']) === 'male' ? 'bg-primary' : 'bg-success' ?> px-2 py-1">
                                                    <?= htmlspecialchars(ucfirst($client['gender'])) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info px-2 py-1"><?= number_format($client['total_loans'] ?? 0) ?></span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-semibold">₱<?= number_format((float)($client['total_loan_amount'] ?? 0), 2) ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                            $status = strtolower($client['status'] ?? 'active');
                                            $statusClass = $status === 'active' ? 'bg-success' : 'bg-secondary';
                                            ?>
                                            <span class="badge <?= $statusClass ?> px-2 py-1">
                                                <?= htmlspecialchars(ucfirst($client['status'] ?? 'Active')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-nowrap">
                                                <?= !empty($client['created_at']) ? date('M d, Y', strtotime($client['created_at'])) : '' ?>
                                            </div>
                                            <?php if (!empty($client['created_at'])): ?>
                                                <small class="text-muted"><?= date('g:i A', strtotime($client['created_at'])) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?= APP_URL ?>/public/clients/view.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-outline-primary" title="View Client">
                                                    <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Executive Summary Footer -->
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-12">
                        <h6 class="text-muted mb-3">Executive Summary</h6>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="border-end pe-3">
                                    <div class="h5 mb-1"><?= number_format($clientStats['total_clients']) ?></div>
                                    <small class="text-muted">Total Clients</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border-end pe-3">
                                    <div class="h5 mb-1 text-success"><?= number_format($clientStats['active_clients']) ?></div>
                                    <small class="text-muted">Active Clients</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border-end pe-3">
                                    <div class="h5 mb-1 text-primary"><?= number_format($clientStats['clients_with_loans']) ?></div>
                                    <small class="text-muted">With Loans</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="h5 mb-1 text-warning">₱<?= number_format($clientStats['total_loan_amount'], 2) ?></div>
                                <small class="text-muted">Total Loan Value</small>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-top">
                            <small class="text-muted">
                                <i data-feather="clock" class="me-1" style="width: 14px; height: 14px;"></i>
                                Report generated on <?= date('F j, Y \a\t g:i A') ?> | Period: <?= $reportMetrics['report_period'] ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>