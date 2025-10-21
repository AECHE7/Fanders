<?php
/**
 * Collection Sheets - View (Placeholder)
 */
require_once __DIR__ . '/../init.php';
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'cashier', 'account_officer']);

$pageTitle = 'View Collection Sheet';
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>
<main class="main-content">
  <div class="content-wrapper">
    <div class="notion-page-header mb-4">
      <div class="d-flex align-items-center">
        <div class="me-3">
          <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0f4fd;">
            <i data-feather="eye" style="width:24px;height:24px;color:#000;"></i>
          </div>
        </div>
        <h1 class="notion-page-title mb-0">View Collection Sheet</h1>
      </div>
      <div class="notion-divider my-3"></div>
    </div>

    <div class="alert alert-info">
      Posted sheet details and export actions (PDF/Excel) will appear here. Coming soon.
    </div>
  </div>
</main>
<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>
