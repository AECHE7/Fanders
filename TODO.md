# TODO: Convert public/loans/ folder to Microfinance Loan Management

## Overview
Convert all PHP files in public/loans/ from library management system to Fanders Microfinance loan management system.

## Tasks

### 1. Update index.php
- [] Replace BookService with LoanService
- [] Update role checks to microfinance roles (super-admin, admin, manager, account_officer, cashier)
- [] Change URLs from /public/books/ to /public/loans/
- [] Update template includes to use loan templates
- [] Update search/filter functionality for loans

### 2. Update add.php
- [] Replace BookService with LoanService
- [] Update role checks to microfinance roles
- [] Change URLs and redirects
- [] Update form processing for loan applications
- [] Update template includes

### 3. Update view.php
- [ ] Replace BookService/TransactionService with LoanService/PaymentService
- [ ] Update role checks
- [ ] Change book-related logic to loan-related logic
- [ ] Update transaction history to payment history
- [ ] Update template includes
- [ ] Update action buttons and permissions

### 4. Update edit.php
- [ ] Replace BookService with LoanService
- [ ] Update role checks
- [ ] Change URLs and redirects
- [ ] Update form processing for loan editing
- [ ] Update template includes

### 5. Update borrow.php
- [ ] Rename/restructure to loan approval/disbursement functionality
- [ ] Replace TransactionService with PaymentService
- [ ] Update role checks
- [ ] Change borrowing logic to loan disbursement logic
- [ ] Update template includes

### 6. Update archive.php, archived.php, restore.php, delete.php, bulk_delete.php, bulk_restore.php, delete_book.php
- [ ] Replace BookService with LoanService
- [ ] Update role checks
- [ ] Change archive/restore/delete logic to loan status management
- [ ] Update URLs and redirects
- [ ] Update template includes

### 7. Verify Services Exist
- [ ] Ensure LoanService exists and has required methods
- [ ] Ensure PaymentService exists for payment tracking
- [ ] Update any missing service methods

### 8. Update Templates
- [ ] Ensure templates/loans/ folder has all required templates
- [ ] Update template includes in PHP files
- [ ] Verify template functionality matches loan management

### 9. Testing
- [ ] Test loan application workflow
- [ ] Test loan approval/disbursement
- [ ] Test payment recording
- [ ] Test role-based access controls
- [ ] Test search and filtering

## Dependencies
- LoanService must be available
- PaymentService must be available
- templates/loans/ templates must exist
- Database schema must support loan operations
