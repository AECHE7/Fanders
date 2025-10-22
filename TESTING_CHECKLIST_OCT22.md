# 🧪 Testing Checklist - October 22, 2025

## Overview
This document provides a comprehensive testing checklist for all features implemented today.

---

## 🎯 Feature 1: Overdue Management System

### Dashboard Widget Testing
- [ ] **Admin Dashboard**
  - [ ] Navigate to `/dashboard/admin.php`
  - [ ] Verify overdue alert banner appears (red background)
  - [ ] Check count displays correctly
  - [ ] Click "View Details" button → redirects to overdue report
  - [ ] If no overdue loans, banner should not appear

### Overdue Report Page Testing
- [ ] **Summary Statistics**
  - [ ] Navigate to `/payments/overdue_loans.php`
  - [ ] Verify 3 summary cards display:
    - Total overdue loans count
    - Total outstanding amount (₱)
    - Average days overdue
  - [ ] Numbers should match database reality

- [ ] **Search & Filter**
  - [ ] Test search by client name
  - [ ] Test search by loan ID
  - [ ] Test filter by "All Overdue Loans"
  - [ ] Test filter by "7+ Days Overdue"
  - [ ] Test filter by "14+ Days Overdue"
  - [ ] Test filter by "30+ Days Overdue"
  - [ ] Verify results update correctly

- [ ] **Loan Table**
  - [ ] Verify columns display: Loan ID, Client, Contact, Amount, Paid, Balance, Due Date, Days Overdue, Action
  - [ ] Check red "OVERDUE" badges appear
  - [ ] Days overdue should be accurate
  - [ ] "View Loan" button links correctly

- [ ] **CSV Export**
  - [ ] Click "Export to CSV" button
  - [ ] Verify file downloads
  - [ ] Open CSV and check data is correct
  - [ ] File name format: `overdue_loans_YYYYMMDD.csv`

### Visual Indicators Testing
- [ ] **Loan List Page**
  - [ ] Navigate to `/loans/list.php`
  - [ ] Overdue loans should have:
    - Red background row (`table-danger`)
    - Red badge with "⚠ OVERDUE (X days)"
    - Correct days overdue calculation

---

## 📋 Feature 2: Collection Sheet Workflow

### Account Officer Interface Testing
- [ ] **Add Collection Sheet Page**
  - [ ] Navigate to `/collection-sheets/add.php`
  - [ ] Verify client dropdown populates with active clients
  - [ ] Select a client with active loans
  - [ ] Verify loan dropdown populates via AJAX
  - [ ] Select a loan
  - [ ] Weekly payment amount should auto-fill
  - [ ] Override amount if needed
  - [ ] Click "Add Item" → should add to collection items list
  - [ ] Add multiple items from different clients
  - [ ] Verify total amount updates correctly
  - [ ] Click "Save as Draft" → should save with status "draft"
  - [ ] Click "Submit for Approval" → should save with status "submitted"

- [ ] **API Endpoint Testing**
  - [ ] Open browser console
  - [ ] Navigate to add collection sheet page
  - [ ] Select a client
  - [ ] Check network tab for `/api/get_client_loans.php` request
  - [ ] Verify JSON response contains loans array
  - [ ] Response should include: loan_id, loan_amount, weekly_payment

### Cashier Approval Workflow Testing
- [ ] **Review Collection Sheet**
  - [ ] Navigate to `/collection-sheets/index.php`
  - [ ] Find a "submitted" collection sheet
  - [ ] Click "Review" button → redirects to approve.php
  - [ ] Verify summary cards display:
    - Total items count
    - Total collection amount
    - Prepared by (officer name)
    - Submission time
  - [ ] Verify all collection items are listed with correct amounts

- [ ] **Approve Collection Sheet**
  - [ ] Click "Approve Collection Sheet" button
  - [ ] Status should change to "approved"
  - [ ] Redirect to collection sheets list
  - [ ] Verify status badge shows "Approved" (blue)

- [ ] **Reject Collection Sheet**
  - [ ] Open another submitted collection sheet
  - [ ] Click "Reject Collection Sheet"
  - [ ] Modal should appear asking for reason
  - [ ] Enter rejection reason
  - [ ] Click "Confirm Rejection"
  - [ ] Status should change to "rejected"
  - [ ] Verify rejection reason is saved

- [ ] **Post Payments**
  - [ ] Open an "approved" collection sheet
  - [ ] Click "Post All Payments"
  - [ ] Confirm action
  - [ ] Verify:
    - Payments are created in `payments` table
    - Loan balances are updated
    - Collection sheet status changes to "posted"
    - Cash blotter entries are created
    - Transaction logs are recorded

### Collection Sheets Index Testing
- [ ] **Status Badges**
  - [ ] Verify color coding:
    - Draft → Yellow/Warning
    - Submitted → Blue/Info
    - Approved → Green/Success
    - Posted → Dark/Primary
    - Rejected → Red/Danger

- [ ] **Action Buttons**
  - [ ] Draft sheets → "Edit" button
  - [ ] Submitted sheets → "Review" button (cashier only)
  - [ ] Approved sheets → "Review" button
  - [ ] No button for posted/rejected sheets

---

## 💾 Feature 3: Automated Backups

### Backup Script Testing
- [ ] **Manual Backup Execution**
  - [ ] SSH into Railway: `railway connect`
  - [ ] Run: `bash /app/scripts/backup_database.sh`
  - [ ] Verify success message
  - [ ] Check backup file created: `ls -lh /app/backups/`
  - [ ] Filename format: `fanders_backup_YYYYMMDD_HHMMSS.sql.gz`

- [ ] **Backup Content Verification**
  - [ ] Extract backup: `gunzip -c backup.sql.gz | head -20`
  - [ ] Verify SQL commands are present
  - [ ] Check for table creation statements
  - [ ] Check for INSERT statements

- [ ] **Backup Log Testing**
  - [ ] Check log file: `cat /app/backups/backup.log`
  - [ ] Verify timestamp entries
  - [ ] Check for success/failure messages

- [ ] **Retention Policy Testing**
  - [ ] Create multiple test backups
  - [ ] Set BACKUP_RETENTION_DAYS=2
  - [ ] Run backup script
  - [ ] Verify old backups (>2 days) are deleted

### Restore Script Testing
- [ ] **Restore Execution**
  - [ ] Create a test database first (optional)
  - [ ] Run: `bash /app/scripts/restore_database.sh /app/backups/latest.sql.gz`
  - [ ] Enter "yes" when prompted
  - [ ] Verify restoration completes successfully

- [ ] **Data Verification After Restore**
  - [ ] Check key tables have data:
    ```bash
    railway run php test_db_connection.php
    ```
  - [ ] Verify record counts match expected values

### Railway Configuration Testing
- [ ] **Environment Variables**
  - [ ] Verify all DATABASE_* variables are set in Railway
  - [ ] Check BACKUP_RETENTION_DAYS is set (optional)

- [ ] **PostgreSQL Client Tools**
  - [ ] Verify `pg_dump` is available: `which pg_dump`
  - [ ] If not, add to nixpacks.toml

- [ ] **Cron Job Setup** (if using Railway Cron)
  - [ ] Configure cron service in Railway
  - [ ] Set schedule: `0 2 * * *` (daily at 2 AM)
  - [ ] Monitor first automated run
  - [ ] Check logs for success

---

## 🔐 Feature 4: UI Polish & Page Headers

### Page Header Verification
- [ ] **Check these pages have proper headers:**
  - [ ] `/loans/list.php` → "Active Loans"
  - [ ] `/payments/overdue_loans.php` → "Overdue Loans Report"
  - [ ] `/collection-sheets/index.php` → "Collection Sheets"
  - [ ] `/collection-sheets/add.php` → "New Collection Sheet"
  - [ ] `/collection-sheets/approve.php` → "Review Collection Sheet"
  - [ ] `/dashboard/admin.php` → "Dashboard"

### Navigation Testing
- [ ] **Sidebar Navigation**
  - [ ] Verify all menu items work
  - [ ] Active page is highlighted
  - [ ] Proper role-based menu visibility

---

## 🔍 Integration Testing

### End-to-End Collection Sheet Flow
1. **Account Officer Creates Sheet**
   - [ ] Login as account officer
   - [ ] Create new collection sheet
   - [ ] Add 3-5 collection items
   - [ ] Submit for approval

2. **Cashier Reviews**
   - [ ] Login as cashier
   - [ ] Open submitted collection sheet
   - [ ] Review all items
   - [ ] Approve the sheet

3. **Cashier Posts Payments**
   - [ ] Open approved sheet
   - [ ] Post all payments
   - [ ] Verify payments are recorded

4. **Verify Data Consistency**
   - [ ] Check `collection_sheets` table
   - [ ] Check `collection_sheet_items` table
   - [ ] Check `payments` table
   - [ ] Check `cash_blotter` table
   - [ ] Check loan balances updated

### End-to-End Overdue Management Flow
1. **Create Overdue Situation**
   - [ ] Manually set a loan's completion_date to past date
   - [ ] Ensure balance > 0

2. **Verify Detection**
   - [ ] Check admin dashboard shows alert
   - [ ] Check loan list shows red indicator
   - [ ] Check overdue report includes the loan

3. **Make Payment**
   - [ ] Record payment for overdue loan
   - [ ] Verify it disappears from overdue report
   - [ ] Verify dashboard alert updates

---

## 📊 Database Testing

### Schema Verification
- [ ] **Run SQL to check all tables:**
  ```sql
  SELECT table_name FROM information_schema.tables 
  WHERE table_schema = 'public' 
  ORDER BY table_name;
  ```
  
- [ ] **Expected tables:**
  - [ ] users
  - [ ] clients
  - [ ] loans
  - [ ] payments
  - [ ] cash_blotter
  - [ ] transaction_logs
  - [ ] collection_sheets (if exists)
  - [ ] collection_sheet_items (if exists)

### Data Integrity Testing
- [ ] **Check foreign key constraints:**
  ```sql
  SELECT * FROM information_schema.table_constraints 
  WHERE constraint_type = 'FOREIGN KEY';
  ```

- [ ] **Verify no orphaned records:**
  ```sql
  -- Payments without loans
  SELECT * FROM payments WHERE loan_id NOT IN (SELECT loan_id FROM loans);
  
  -- Loans without clients
  SELECT * FROM loans WHERE client_id NOT IN (SELECT client_id FROM clients);
  ```

---

## 🚀 Performance Testing

### Page Load Times
- [ ] Dashboard loads in < 2 seconds
- [ ] Loan list loads in < 3 seconds
- [ ] Overdue report loads in < 3 seconds
- [ ] Collection sheet creation loads in < 2 seconds

### AJAX Performance
- [ ] Client loan API responds in < 500ms
- [ ] Network tab shows no unnecessary requests

### Database Query Performance
- [ ] Check slow query log
- [ ] Verify indexes exist on:
  - [ ] loans.client_id
  - [ ] loans.completion_date
  - [ ] payments.loan_id
  - [ ] collection_sheet_items.collection_sheet_id

---

## 🔒 Security Testing

### Authentication & Authorization
- [ ] **Unauthenticated access blocked:**
  - [ ] Try accessing `/collection-sheets/approve.php` without login
  - [ ] Should redirect to login page

- [ ] **Role-based access:**
  - [ ] Account officer can create collection sheets
  - [ ] Only cashiers can approve/post payments
  - [ ] Admin can access all features

### Input Validation
- [ ] **SQL Injection Prevention:**
  - [ ] Try entering `' OR '1'='1` in search fields
  - [ ] Should be escaped/sanitized

- [ ] **XSS Prevention:**
  - [ ] Try entering `<script>alert('XSS')</script>` in text fields
  - [ ] Should be HTML-escaped on display

### CSRF Protection
- [ ] All forms have CSRF tokens
- [ ] Form submissions validate tokens

---

## 📱 Mobile Responsiveness Testing

### Test on different screen sizes:
- [ ] Desktop (1920x1080)
- [ ] Tablet (768x1024)
- [ ] Mobile (375x667)

### Pages to test:
- [ ] Dashboard
- [ ] Loan list
- [ ] Overdue report
- [ ] Collection sheet creation
- [ ] Collection sheet approval

### Elements to verify:
- [ ] Tables are scrollable on mobile
- [ ] Buttons are tap-friendly (min 44x44px)
- [ ] Forms are usable on small screens
- [ ] Navigation menu is responsive

---

## 📝 Documentation Testing

### User Documentation
- [ ] README files are up to date
- [ ] Installation instructions work
- [ ] API documentation is accurate

### Code Documentation
- [ ] Complex functions have comments
- [ ] Service methods are documented
- [ ] SQL queries are explained

---

## ✅ Final Verification

### Pre-Deployment Checklist
- [ ] All tests above are passing
- [ ] No console errors in browser
- [ ] No PHP errors in logs
- [ ] Database schema is correct
- [ ] Backups are working
- [ ] Railway configuration is complete

### Deployment Checklist
- [ ] Code is pushed to Railway
- [ ] Environment variables are set
- [ ] Database migrations are run
- [ ] Cron jobs are configured
- [ ] Monitoring is set up

### Post-Deployment Verification
- [ ] Site is accessible
- [ ] SSL certificate is valid
- [ ] All features work on production
- [ ] Backups run successfully
- [ ] Logs show no errors

---

## 🐛 Known Issues & Workarounds

### Issue 1: AJAX Doesn't Load Loans
**Symptoms:** Client dropdown populates, but loan dropdown stays empty  
**Fix:** Check browser console for errors, verify API endpoint is accessible

### Issue 2: Backup Script Fails
**Symptoms:** "pg_dump: command not found"  
**Fix:** Install postgresql-client in Railway (see RAILWAY_BACKUP_GUIDE.md)

### Issue 3: Collection Sheet Doesn't Submit
**Symptoms:** No collection items added  
**Fix:** Ensure at least one item is added before submitting

---

## 📊 Testing Results Template

Use this template to track your testing:

```markdown
## Testing Session: [Date/Time]
**Tester:** [Your Name]
**Environment:** Railway Production / Local Dev

### Results Summary
- ✅ Passed: X/Y tests
- ❌ Failed: X tests
- ⚠️ Warnings: X issues

### Failed Tests
1. [Test Name] - [Reason] - [Priority: High/Medium/Low]
2. ...

### Notes
- [Any observations or concerns]
```

---

**Last Updated:** October 22, 2025  
**Status:** Ready for testing ✅  
**Estimated Testing Time:** 2-3 hours for complete checklist
