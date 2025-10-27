# Fanders LMS - Quick Testing Guide by Role

**Purpose**: Give this checklist to your groupmates to test the system and provide feedback for final polishing.

---

## 🔐 **SUPER ADMIN TESTING** - Complete System Access

### **Test Login & Access**
- [ ] Login with Super Admin credentials
- [ ] Check you can access ALL system areas
- [ ] Verify "Super Admin" badge shows in user menu

### **Test User Management** (Core Function)
- [ ] Go to Users → Add New User
- [ ] Create one account for each role: Admin, Manager, Cashier, Account Officer
- [ ] Edit existing user profiles 
- [ ] Activate/Deactivate user accounts
- [ ] Reset passwords for users

### **Test System Administration**
- [ ] Access system configuration settings
- [ ] Check backup system status
- [ ] View system logs and audit trails

### **Test Complete Reporting Access**
- [ ] Generate loan portfolio reports (PDF/Excel export)
- [ ] View cash blotter reports
- [ ] Access all user activity reports

**Expected Result**: Full access to everything, can manage all users and system settings.

---

## 👨‍💼 **ADMIN TESTING** - User Management & System Oversight

### **Test Login & Access**
- [ ] Login with Admin credentials
- [ ] Check "Admin" badge displays correctly
- [ ] Verify cannot access Super Admin functions

### **Test Staff Management** (Core Function)
- [ ] Create Manager, Cashier, and Account Officer accounts
- [ ] **VERIFY**: Cannot create Super Admin accounts (should be restricted)
- [ ] Edit operational staff profiles
- [ ] Reset staff member passwords

### **Test Financial System Access**
- [ ] View all loans and their statuses
- [ ] Access payment history for all loans
- [ ] View cash blotter summaries
- [ ] Generate comprehensive financial reports

**Expected Result**: Can manage operational staff but not Super Admins, full financial oversight.

---

## 👨‍💼 **MANAGER TESTING** - Loan Approval & Financial Oversight  

### **Test Login & Dashboard**
- [ ] Login with Manager credentials
- [ ] View Manager dashboard with loan portfolio statistics
- [ ] Check "Manager" role badge displays

### **Test Loan Approval Workflow** (Core Function)
- [ ] Go to Loans → View pending applications
- [ ] Open a loan application for review
- [ ] **APPROVE** a loan application
  - [ ] Verify loan status changes to "approved"
  - [ ] Check loan calculations: Principal × 5% × 4 months + ₱425 insurance
  - [ ] Confirm 17-week payment schedule is generated
- [ ] **REJECT** a loan application (test both outcomes)

### **Test Financial Monitoring**
- [ ] Monitor total loan portfolio value on dashboard
- [ ] Review active loans and overdue payment alerts  
- [ ] Access real-time cash position from cash blotter
- [ ] Generate management reports (loan performance, cash flow)

### **Test SLR Access**
- [ ] Access SLR system to view approved loans
- [ ] Generate SLR documents for loan disbursement
- [ ] Download bulk SLR documents for reporting

**Expected Result**: Can approve/reject loans, full financial oversight, generate management reports.

---

## 💰 **CASHIER TESTING** - Payment Processing & Cash Management

### **Test Login & Dashboard**
- [ ] Login with Cashier credentials  
- [ ] View Cashier dashboard with daily cash summary
- [ ] Check "Cashier" role badge displays

### **Test Payment Processing** (Core Function)
- [ ] Go to Loans → Find active loan → Record Payment
- [ ] Enter payment amount and process
- [ ] **VERIFY**: Cannot enter payment larger than remaining balance
- [ ] Check loan balance updates immediately after payment
- [ ] Confirm payment appears in loan payment history

### **Test Collection Sheet Processing** (Core Function)
- [ ] Go to Collection Sheets → View submitted sheets from AOs
- [ ] **APPROVE** a collection sheet from Account Officer
- [ ] **POST PAYMENTS** from approved collection sheet
  - [ ] Click "Post All Payments" button
  - [ ] Verify all payments are recorded in system
  - [ ] Check loan balances update correctly
  - [ ] Confirm collection sheet status changes to "posted"

### **Test SLR Document Management** (Core Function)  
- [ ] Go to SLR system (/public/slr/)
- [ ] Generate SLR document for approved loan
- [ ] **PROCESS LOAN DISBURSEMENT**:
  - [ ] Generate SLR PDF for client signature
  - [ ] Verify client details and loan info are accurate
  - [ ] Check cash blotter records the outflow
  - [ ] Confirm loan status changes to "active"

### **Test Digital Cash Blotter** (Core Function)
- [ ] Access Cash Blotter (/public/cash-blotter/)
- [ ] **VERIFY INFLOWS**: Payments and collections appear correctly
- [ ] **VERIFY OUTFLOWS**: Loan disbursements appear correctly  
- [ ] Check daily cash balance calculation: Opening + Inflows - Outflows
- [ ] Generate and print daily cash blotter report

**Expected Result**: Can process payments, approve collections, generate SLRs, manage daily cash operations.

---

## 👥 **ACCOUNT OFFICER TESTING** - Field Collections & Client Management

### **Test Login & Access**
- [ ] Login with Account Officer credentials
- [ ] View AO dashboard (limited to assigned functions)
- [ ] Check "Account Officer" role badge displays

### **Test Client Access Restrictions**
- [ ] **VERIFY**: Can only see assigned clients (not all clients)
- [ ] **VERIFY**: Cannot access loan approval functions
- [ ] **VERIFY**: Cannot access cash blotter or SLR systems  
- [ ] **VERIFY**: Cannot access admin or reporting functions

### **Test Collection Sheet Management** (Core Function)
- [ ] Go to Collection Sheets → Create New Collection Sheet
- [ ] **ADD CLIENT COLLECTIONS**:
  - [ ] Select assigned client with active loan
  - [ ] Enter payment amount collected from client
  - [ ] Add collection date and any notes
  - [ ] **VERIFY**: Cannot enter amount larger than loan balance
- [ ] **ADD MULTIPLE CLIENTS** to same collection sheet
- [ ] Review collection sheet totals and details
- [ ] **SUBMIT** collection sheet to Cashier
  - [ ] Verify status changes to "submitted"  
  - [ ] Check cannot edit after submission

### **Test Field Operations** 
- [ ] **MOBILE TESTING**: Access system on phone/tablet
- [ ] Test collection entry forms work on mobile
- [ ] Simulate collecting payments from multiple clients
- [ ] Handle partial payments and payment deferrals

### **Test Limited Reporting**
- [ ] View personal collection history
- [ ] Access assigned client payment schedules  
- [ ] Review weekly collection targets vs. actual collections

**Expected Result**: Can create and manage collection sheets for assigned clients only, mobile-friendly interface.

---

## 🔄 **COMPLETE WORKFLOW TESTING** (Test Together)

### **End-to-End Loan Process** - Test with multiple roles
1. **Admin**: Create new client account
2. **Admin/Manager**: Create loan application for client  
3. **Manager**: Review and approve loan application
4. **Cashier**: Generate SLR document and process disbursement
5. **Account Officer**: Create collection sheet with client payment
6. **Cashier**: Approve collection sheet and post payments
7. **Verify**: Loan progresses through: application → approved → active → completed

### **Daily Operations Testing**
- [ ] Process multiple loan disbursements (test cash outflows)
- [ ] Process multiple payment collections (test cash inflows)  
- [ ] Verify cash blotter shows accurate daily totals
- [ ] Generate end-of-day reports from different roles

---

## 🛡️ **SECURITY TESTING**

### **Access Control Testing**
- [ ] **Account Officer** tries to access Manager functions → Should FAIL
- [ ] **Cashier** tries to access Admin functions → Should FAIL  
- [ ] **Manager** tries to access Super Admin functions → Should FAIL
- [ ] Verify proper error messages when access is denied

### **Session Security**
- [ ] Test session timeout after 30+ minutes of inactivity
- [ ] Verify logout clears all data properly
- [ ] Test multiple users can login simultaneously

---

## 📊 **SYSTEM PERFORMANCE TESTING**

### **Load Testing**
- [ ] Have multiple people login at the same time
- [ ] Process multiple payments simultaneously  
- [ ] Generate multiple reports at once
- [ ] Check system stays responsive under normal use

### **Financial Accuracy Testing**
- [ ] **CRITICAL**: Verify loan calculations are always correct
- [ ] Check payment processing maintains accurate balances
- [ ] Confirm cash blotter totals match payment records
- [ ] Test interest calculations to centavo precision

---

## 📋 **FEEDBACK FORM** - Fill this out after testing

**Your Name**: _________________  
**Role Tested**: ________________  
**Date**: ____________________

### **Rate Each Area (1-10)**:
- Login & Navigation: ___/10
- Core Functions: ___/10  
- User Interface: ___/10
- Mobile Friendliness: ___/10
- Speed & Performance: ___/10

### **What Works Well**: 
_________________________________

### **Problems Found**:
_________________________________

### **Suggestions for Improvement**:
_________________________________

### **Overall System Rating**: ___/10

### **Ready for Production?** ☐ Yes ☐ No ☐ Needs Minor Fixes

---

## 🎯 **TESTING PRIORITIES**

### **CRITICAL TESTS** (Must work perfectly):
1. **Loan calculation accuracy** (5% × 4 months + ₱425)
2. **Payment processing** (real-time balance updates)
3. **Cash blotter accuracy** (inflows/outflows match)
4. **Role-based access control** (proper restrictions)
5. **Collection sheet workflow** (AO → Cashier → Posted)

### **IMPORTANT TESTS**:
1. **SLR document generation** (accurate client/loan data)
2. **User management functions** (create/edit/activate users)  
3. **Report generation** (PDF/Excel exports work)
4. **Mobile responsiveness** (works on phones/tablets)
5. **Session security** (timeout, logout, access control)

### **NICE TO HAVE**:
1. **System performance** (fast response times)
2. **User interface polish** (clean, intuitive design)
3. **Advanced reporting** (analytics, charts, insights)

---

**Instructions**: Give each person this checklist for their assigned role. Have them test everything thoroughly and fill out the feedback form. Focus extra attention on the "CRITICAL TESTS" - these must work perfectly before going live!