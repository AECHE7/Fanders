<?php
/**
 * Collection Sheets - Add (Placeholder)
 */
require_once __DIR__ . '/../init.php';
$auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account_officer']);

$pageTitle = 'New Collection Sheet';
include_once BASE_PATH . '/templates/layout/header.php';
include_once BASE_PATH . '/templates/layout/navbar.php';
?>
<main class="main-content">
  <div class="content-wrapper">
    <div class="notion-page-header mb-4">
      <div class="d-flex align-items-center">
        <div class="me-3">
          <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0f4fd;">
            <i data-feather="plus-circle" style="width:24px;height:24px;color:#000;"></i>
          </div>
        </div>
        <h1 class="notion-page-title mb-0">New Collection Sheet</h1>
      </div>
      <div class="notion-divider my-3"></div>
    </div>

    <div class="alert alert-info">
      This form will allow Account Officers to create a draft sheet and add multiple client payments. Coming soon.
    </div>
  </div>
</main>
<?php include_once BASE_PATH . '/templates/layout/footer.php'; ?>
