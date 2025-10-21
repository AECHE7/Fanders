# Quick Diagnosis Guide - Loan Submission Issue

## What Error Message Did You See?

When you submitted the loan form and it didn't redirect, you should have seen an error message displayed on the page. **Please tell me EXACTLY what error message appears** when you try to submit the loan.

Here are the possible error messages and what they mean:

### Error Message Options:

1. **"Invalid security token. Please refresh and try again."**
   - Cause: CSRF token validation failed
   - Fix Needed: Clear browser cache, reload page

2. **"Client already has an active loan and cannot apply for another."**
   - Cause: The selected client already has an ACTIVE loan (not just pending)
   - Fix Needed: Use a different client, or complete the existing loan first

3. **"Client has defaulted loans and must settle their account before applying."**
   - Cause: The client has defaulted/written-off loans
   - Fix Needed: Mark old loans as completed, use a different client

4. **"Client is ineligible for a new loan."**
   - Cause: Client eligibility check failed (from line 32 of add.php)
   - Fix Needed: Use a different client

5. **"Loan amount must be at least ₱1,000."** or **"Loan amount cannot exceed ₱50,000."**
   - Cause: Amount entered is outside the valid range
   - Fix Needed: Enter amount between 1000 and 50000

6. **"Failed to save loan application."**
   - Cause: Database error when inserting the loan record
   - Fix Needed: Check database connection, check if loans table exists

7. **"Failed to submit loan application."** (generic message)
   - Cause: Unspecified error
   - Fix Needed: Check PHP error logs

8. **"Failed to calculate loan details."** (on Calculate step)
   - Cause: Loan calculation failed on second attempt
   - Fix Needed: Try with different amount/term

---

## What To Do Now:

### Step 1: Note the Exact Error Message
When you submit the form again, **carefully read and copy the exact error message** that appears.

### Step 2: Share the Error Message
Tell me:
- The exact error message displayed
- The loan amount you entered
- The term (weeks) you entered
- The client name/ID you selected

### Step 3: Check Server Logs (if available)
If you have server access, check logs for entries like:
```
"Loan submission attempt: client=X, amount=Y, term=Z"
"Loan creation failed: [ERROR MESSAGE]"
"Loan created successfully. Loan ID: X"
```

These logs would be in your PHP error log (location varies by server).

---

## Most Likely Issue

Based on my analysis of the code, the **most probable issues are:**

1. **Database Connection Error** (Error: "Failed to save loan application.")
   - The loans table might not be properly set up
   - The database connection is failing

2. **Client Eligibility Error** (Error: "Client already has an active loan...")
   - The selected client has an active loan from a previous test
   - Need to use a different client

3. **Form Data Not Being Passed Correctly** (Error: "Failed to calculate...")
   - The hidden form fields aren't sending the data correctly
   - This is less likely but possible

---

## Next Steps

Please reply with:
1. The **exact error message** you see
2. The **client ID** and **amount/term** you used
3. Any **PHP error log messages** if available

With this information, I can provide a specific fix!
