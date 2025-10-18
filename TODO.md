# Uniform Filtering Implementation Plan

## Overview
Standardize filtering across all index pages (transactions, cash_blotter, payments, loans, clients) to use consistent parameter names, server-side filtering, and uniform form layouts.

## Tasks

### 1. Create FilterUtility Class
- [x] Create `app/utilities/FilterUtility.php`
- [x] Implement methods for:
  - Sanitizing GET parameters
  - Validating date ranges
  - Building filter arrays
  - Common validation logic

### 2. Update Transactions Index
- [x] Already has good server-side filtering
- [x] Update to use FilterUtility for consistency
- [x] Ensure parameter names match standard (date_from, date_to)

### 3. Update Cash Blotter Index
- [x] Change 'start_date'/'end_date' to 'date_from'/'date_to'
- [x] Use FilterUtility for validation
- [x] Ensure server-side filtering consistency

### 4. Update Payments Index
- [ ] Move from client-side to server-side filtering
- [ ] Add date filtering support
- [ ] Update PaymentService to support server-side filters
- [ ] Use FilterUtility

### 5. Update Loans Index
- [ ] Change 'start_date'/'end_date' to 'date_from'/'date_to'
- [ ] Use FilterUtility
- [ ] Ensure LoanService supports date filtering

### 6. Update Clients Index
- [ ] Add date filtering (created_at, updated_at)
- [ ] Update ClientService for date filters
- [ ] Use FilterUtility

### 7. Standardize Filter Form Layout
- [ ] Create consistent HTML structure across all pages
- [ ] Uniform button styling and layout
- [ ] Consistent field ordering

### 8. Testing
- [ ] Test filtering on each page
- [ ] Verify date validation
- [ ] Check performance with large datasets
- [ ] Ensure no regressions in existing functionality
