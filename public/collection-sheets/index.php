<?php
/**
 * Collection Sheets - Landing Page (Placeholder)
 * This page provides an entry point for the Collection Sheets module per FR-006/UR-006.
 */

// Centralized initialization (handles sessions, auth, CSRF, and autoloader)
require_once __DIR__ . '/../init.php';

// Enforce role-based access control (staff roles)
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account_officer', 'cashier']);

$pageTitle = 'Collection Sheets';
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>

<main class="main-content">
  <div class="content-wrapper">
    <!-- Header -->
    <div class="notion-page-header mb-4">
      <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <div class="me-3">
            <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0f4fd;">
              <i data-feather="clipboard" style="width: 24px; height: 24px; color:#000;"></i>
            </div>
          </div>
          <h1 class="notion-page-title mb-0">Collection Sheets</h1>
        </div>
        <div class="text-muted d-none d-md-block">
          <i data-feather="calendar" class="me-1" style="width:14px;height:14px;"></i>
          <?= date('l, F j, Y') ?>
        </div>
      </div>
      <div class="notion-divider my-3"></div>
    </div>

    <!-- Under Development Notice -->
    <div class="alert alert-warning d-flex align-items-start" role="alert">
      <i data-feather="alert-circle" class="me-2" style="width:20px;height:20px;"></i>
      <div>
        <strong>Module in progress.</strong> This section is being implemented based on the requirements in paper1–paper3 (FR-006, FR-007, UR-006).
      </div>
    </div>

    <!-- Planned Features per Requirements -->
    <div class="card shadow-sm mb-4">
      <div class="card-header d-flex align-items-center">
        <i data-feather="list" class="me-2" style="width:18px;height:18px;"></i>
        <h5 class="mb-0">Planned Features</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-lg-7">
            <ul class="mb-0">
              <li><strong>Account Officer submission</strong> interface to enter daily client collections (bulk grid entry with client lookup).</li>
              <li><strong>Pending submissions queue</strong> with status (draft/submitted/posted) and per-officer totals.</li>
              <li><strong>Cashier review & posting</strong> that converts submitted rows into official payments (FR-004) with CSRF and audit logging.</li>
              <li><strong>Cash Blotter integration</strong>: auto-aggregate posted collections into the daily cash blotter inflow (FR-006).</li>
              <li><strong>Traceability</strong>: store officer ID, date, and references for each posted item (FR-007, audit trail FR-010).</li>
              <li><strong>Exports</strong>: PDF/Excel collection sheets per officer/day for printing or archiving.</li>
            </ul>
          </div>
          <div class="col-lg-5">
            <div class="bg-light rounded p-3 h-100">
              <h6 class="fw-semibold">Design Notes</h6>
              <ul class="small mb-0">
                <li>New tables (proposed): <code>collection_sheets</code>, <code>collection_sheet_items</code>.</li>
                <li>Roles: Account Officers create/submit; Cashiers review/post; Managers view/override.</li>
                <li>Error handling: detect invalid client/loan, duplicate postings, and out-of-range amounts.</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Links -->
    <div class="card shadow-sm">
      <div class="card-header d-flex align-items-center">
        <i data-feather="compass" class="me-2" style="width:18px;height:18px;"></i>
        <h5 class="mb-0">Quick Links</h5>
      </div>
      <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
          <a href="<?= APP_URL ?>/public/payments/index.php" class="btn btn-outline-primary btn-sm">
            <i data-feather="dollar-sign" class="me-1" style="width:14px;height:14px;"></i>
            Payments
          </a>
          <a href="<?= APP_URL ?>/public/cash_blotter/index.php" class="btn btn-outline-primary btn-sm">
            <i data-feather="book-open" class="me-1" style="width:14px;height:14px;"></i>
            Cash Blotter
          </a>
          <a href="<?= APP_URL ?>/public/reports/index.php" class="btn btn-outline-secondary btn-sm">
            <i data-feather="bar-chart-2" class="me-1" style="width:14px;height:14px;"></i>
            Reports
          </a>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>
