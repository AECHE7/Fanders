# TODO: Implement Dashboard-Style Page Headers with Icons

## Overview
Add Notion-inspired page headers with icons to all navigation item pages, matching the dashboard design.

## Files to Update

- [ ] public/loans/index.php - Icon: file-text
- [ ] public/clients/index.php - Icon: users
- [ ] public/payments/index.php - Icon: dollar-sign
- [ ] public/cash_blotter/index.php - Icon: book-open
- [ ] public/reports/index.php - Icon: bar-chart-2
- [ ] public/transactions/index.php - Icon: activity
- [ ] public/users/index.php - Icon: user-check

## Header Structure
```php
<!-- Dashboard Header with Title, Date and Reports Links -->
<div class="notion-page-header mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <div class="me-3">
                <div class="page-icon rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: #f0f4fd;">
                    <i data-feather="[ICON]" style="width: 24px; height: 24px; color:rgb(0, 0, 0);"></i>
                </div>
            </div>
            <h1 class="notion-page-title mb-0">[PAGE TITLE]</h1>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <!-- Action buttons here -->
        </div>
    </div>
    <div class="notion-divider my-3"></div>
</div>
```

## Progress
- [x] Analyze dashboard header structure
- [x] Update loans/index.php ✅ COMPLETED
- [x] Update clients/index.php ✅ COMPLETED
- [x] Update payments/index.php ✅ COMPLETED
- [x] Update cash_blotter/index.php ✅ COMPLETED
- [x] Update reports/index.php ✅ COMPLETED
- [x] Update transactions/index.php ✅ COMPLETED
- [x] Update users/index.php ✅ COMPLETED
