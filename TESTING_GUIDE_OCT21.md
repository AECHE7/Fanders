# 🧪 LOAN FIXES - TESTING GUIDE

**Date:** October 21, 2025  
**Status:** Ready for Testing  
**Estimated Testing Time:** 20-30 minutes

---

## ✅ QUICK CHECKLIST BEFORE TESTING

- [ ] All 5 fixes have been applied successfully
- [ ] No PHP syntax errors
- [ ] Application still starts without errors
- [ ] Database connection works
- [ ] At least one active client exists in database
- [ ] You have admin/staff access to the system

---

## 🧪 TEST SCENARIOS (Run in Order)

### TEST #1: Verify Application Still Works (5 minutes)

**Goal:** Ensure no breaking changes

**Steps:**
1. Navigate to Dashboard
   - Expected: ✓ Loads without errors
2. Navigate to Clients list
   - Expected: ✓ Loads client data
3. Navigate to Loans list
   - Expected: ✓ Loads loan data
4. Check browser console (F12)
   - Expected: ✓ No JavaScript errors

**Result:** ✓ PASS / ❌ FAIL

---

### TEST #2: Create New Loan with Eligible Client (10 minutes)

**Goal:** Test the main loan creation flow

**Preconditions:**
- Have an ACTIVE client without any loans
- Or have a client where the most recent loan status is not "Active"

**Steps:**

1. **Navigate to New Loan Application**
   - URL: `http://yoursite.com/public/loans/add.php`
   - Expected: ✓ Form loads with client dropdown

2. **Select Client**
   - Choose an active client from dropdown
   - Expected: ✓ Client selected, form shows eligibility check

3. **Enter Loan Details**
   - Loan Amount: **5000**
   - Loan Term: **17**
   - Expected: ✓ Values accepted

4. **Click Calculate Button**
   - Expected: ✓ Preview shows:
     - Principal: ₱5,000
     - Total Interest: ₱1,000
     - Insurance Fee: ₱425
     - Total Repayment: ₱6,425
     - Weekly Payment: ~₱378.24

5. **Click Submit Loan Application**
   - Expected: ✓ Redirects to loans list
   - Expected: ✓ Success message shows
   - Expected: ✓ New loan appears in list with status "Application"

6. **Verify Database**
   ```sql
   -- Check loan was created
   SELECT id, client_id, principal, status FROM loans 
   WHERE status = 'Application' 
   ORDER BY created_at DESC LIMIT 1;
   
   -- Expected: Row with principal=5000, status=Application
   ```

**Result:** ✓ PASS / ❌ FAIL

---

### TEST #3: Test Error Message Improvements (5 minutes)

**Goal:** Verify error messages are now visible

**Test 3A: Invalid Loan Amount**
1. Navigate to New Loan Application
2. Select any client
3. Enter Loan Amount: **500** (too low)
4. Click Calculate
   - Expected: ❌ Error message shows: "Loan amount must be at least ₱1,000."

**Test 3B: Amount Too High**
1. Repeat with Loan Amount: **60000** (too high)
2. Click Calculate
   - Expected: ❌ Error message shows: "Loan amount cannot exceed ₱50,000."

**Result:** ✓ PASS / ❌ FAIL

---

### TEST #4: Test Client Status Validation (5 minutes)

**Goal:** Verify inactive clients are properly blocked

**Preconditions:**
- Have an INACTIVE client in database
- Or have a BLACKLISTED client

**Steps:**

1. **Try to create loan with inactive client**
   - Option A: Query database to set client status to 'inactive'
   - Option B: Find or create an inactive client
   - URL: `/public/loans/add.php?client_id=[inactive_client_id]`
   - Expected: ❌ Error message: "Client must have active status to apply for loans..."

2. **Check dropdown**
   - Navigate to `/public/loans/add.php`
   - Check client dropdown
   - Expected: ✓ Inactive clients NOT in dropdown

**Result:** ✓ PASS / ❌ FAIL

---

### TEST #5: Test Active Loan Blocking (5 minutes)

**Goal:** Verify client with active loan can't apply for another

**Preconditions:**
- Have a client with status = 'Active' loan
- If none exist, create one using a different client

**Steps:**

1. **Try to create another loan for same client**
   - Select client with Active loan
   - Click Calculate
   - Expected: ❌ Error message: "Client already has an active loan..."

2. **In database, verify:**
   ```sql
   -- Find a client with active loan
   SELECT DISTINCT client_id FROM loans WHERE LOWER(status) = LOWER('Active') LIMIT 1;
   
   -- Try to create new loan for that client
   ```

**Result:** ✓ PASS / ❌ FAIL

---

### TEST #6: Test Defaulted Loan Blocking (5 minutes)

**Goal:** Verify client with defaulted loan can't apply

**Preconditions:**
- Have a client with a 'Defaulted' loan (or manually set one)

**Steps:**

1. **Update loan to Defaulted (if needed)**
   ```sql
   UPDATE loans SET status = 'Defaulted' WHERE id = [some_loan_id];
   ```

2. **Try to create new loan for that client**
   - Navigate to `/public/loans/add.php?client_id=[defaulted_client_id]`
   - Expected: ❌ Error message: "Client has defaulted loans..."

3. **Revert database**
   ```sql
   UPDATE loans SET status = 'Active' WHERE id = [some_loan_id];
   ```

**Result:** ✓ PASS / ❌ FAIL

---

## 📊 TEST RESULTS SUMMARY

After completing all tests, fill in this summary:

| Test # | Test Name | Expected | Actual | Result |
|--------|-----------|----------|--------|--------|
| 1 | App Still Works | ✓ No errors | ? | ✓/❌ |
| 2 | Loan Creation Flow | ✓ Loan created | ? | ✓/❌ |
| 3A | Error: Amount Too Low | ✓ Error message | ? | ✓/❌ |
| 3B | Error: Amount Too High | ✓ Error message | ? | ✓/❌ |
| 4 | Inactive Client Blocked | ❌ Blocked | ? | ✓/❌ |
| 5 | Active Loan Blocked | ❌ Blocked | ? | ✓/❌ |
| 6 | Defaulted Loan Blocked | ❌ Blocked | ? | ✓/❌ |
| **OVERALL** | **All Tests** | **All Pass** | ? | **✓/❌** |

---

## 🐛 IF A TEST FAILS

### General Troubleshooting

1. **Check PHP Error Log**
   ```bash
   tail -50 /var/log/php.log | grep -i "loan\|error"
   ```

2. **Check Application Logs**
   - Location: Depends on your setup
   - Look for: "Loan validation failed", "Loan creation failed", "CRITICAL"

3. **Check Database**
   ```sql
   -- Verify loans table has correct status values
   SELECT DISTINCT status FROM loans;
   
   -- Should show: Application, Approved, Active, Completed, Defaulted
   ```

4. **Check CSRF Token**
   - Form might be failing CSRF validation
   - Clear browser cookies and try again

5. **Test Direct Database Insert**
   ```sql
   INSERT INTO loans (client_id, principal, interest_rate, term_weeks, 
                      total_interest, insurance_fee, total_loan_amount, 
                      status, application_date, created_at, updated_at)
   VALUES (1, 5000, 0.05, 17, 1000, 425, 6425, 'Application', NOW(), NOW(), NOW());
   ```
   - If this works: database is fine, issue is in code
   - If this fails: database issue

---

## ✅ SUCCESS CRITERIA

All tests pass when:
- [x] Application loads without errors
- [x] New loans can be created for eligible clients
- [x] Error messages display properly
- [x] Inactive clients are blocked
- [x] Clients with active loans are blocked
- [x] Clients with defaulted loans are blocked
- [x] Loan appears in database with correct status
- [x] No PHP errors in logs
- [x] No JavaScript errors in browser

---

## 🎯 NEXT STEPS AFTER TESTING

### If All Tests Pass ✅
1. Run a few more manual tests with different scenarios
2. Deploy to production
3. Monitor error logs for 24 hours
4. Verify existing loans still work

### If Any Test Fails ❌
1. Check error logs (see troubleshooting above)
2. Identify which fix might be causing issue
3. Review the specific changed code
4. Rollback that specific fix if needed
5. Run tests again

---

## 📝 TESTING NOTES

**Testing Environment:**
- Date: _______________
- Tester: _______________
- Environment: Production / Staging / Development
- Browser: _______________

**Additional Notes:**
- _______________________________________________________________
- _______________________________________________________________
- _______________________________________________________________

---

**Testing Guide Created:** October 21, 2025  
**Status:** Ready to Test ✅  
**Estimated Time:** 20-30 minutes  

