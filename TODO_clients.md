# TODO: Convert public/clients/ folder to Microfinance Client Management

## Overview
Convert all PHP files in public/clients/ from user management system to Fanders Microfinance client (borrower) management system.

## Tasks

### 1. Create ClientService
- [] Create ClientService.php in app/services/ to handle client business logic
- [] Implement methods for CRUD operations, validation, stats, etc.

### 2. Update index.php
- [] Replace UserService with ClientService
- [] Update role checks to microfinance roles (super-admin, admin, manager, account_officer, cashier)
- [] Change URLs from /public/users/ to /public/clients/
- [] Update template includes to use client templates
- [] Update search/filter functionality for clients
- [] Update status filters (active, inactive, blacklisted)

### 3. Update add.php
- [] Replace UserService with ClientService
- [] Update role checks to microfinance roles
- [] Change URLs and redirects
- [] Update form processing for client creation
- [] Add client-specific fields (address, date_of_birth, identification)
- [] Update template includes

### 4. Update edit.php
- [] Replace UserService with ClientService
- [] Update role checks
- [] Change URLs and redirects
- [] Update form processing for client editing
- [] Handle client-specific fields
- [] Update template includes

### 5. Update view.php
- [] Replace UserService/TransactionService with ClientService/LoanService/PaymentService
- [] Update role checks
- [] Change user logic to client logic
- [] Update to show loan history and payment history
- [] Update template includes
- [] Update action buttons and permissions

### 6. Update reset_pw.php
- [] Decide if password reset is needed for clients (clients might not have login access)
- [] If needed, update to use ClientService
- [] If not needed, remove or redirect

### 7. Create/Update Templates
- [] Create templates/clients/form.php based on templates/users/form.php
- [] Create templates/clients/list.php based on templates/users/list.php
- [] Create templates/clients/view.php based on templates/users/view.php
- [] Update for client-specific fields and loan/payment history

### 8. Update References
- [] Update navbar links to /public/clients/
- [] Update any other references to user management URLs

### 9. Testing
- [] Test client CRUD operations
- [] Test role-based access controls
- [] Test integration with loans (clients can apply for loans)
- [] Test search and filtering

## Dependencies
- ClientModel exists and has required methods
- ClientService needs to be created
- templates/clients/ folder needs templates
- Database schema supports client operations
- Integration with LoanService for client loans
