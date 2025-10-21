# Detailed Code Trace - Loan Submission Flow

## Complete Flow Walkthrough

### STEP 1: User Loads /public/loans/add.php

**Code Execution:**
```
Line 15-18: Check authentication
  → $auth->checkRoleAccess(['super-admin', 'admin', 'manager', 'account-officer'])

Line 19-22: Initialize $loan array with defaults
  → $loan['client_id'] = ''
  → $loan['loan_amount'] = ''
  → $loan['loan_term'] = 17

Line 23: Get $clients from database
  → $clientService->getAllForSelect()
```

**Expected Result:** Page loads with empty form showing client dropdown

---

### STEP 2: User Fills Form and Clicks "Calculate"

**User Input:**
```
POST to /public/loans/add.php
  client_id: 1
  loan_amount: 5000
  loan_term: 17
  csrf_token: TOKEN_A
  calculate: (button name)
```

**Code Execution:**
```
Line 41: $_SERVER['REQUEST_METHOD'] === 'POST' → TRUE

Line 42-44: CSRF Validation
  if (!$csrf->validateRequest(false)) → FALSE (token is valid)
  
Line 46-49: Extract and sanitize POST data
  $loan['client_id'] = (int)'1' = 1
  $loan['loan_amount'] = (float)'5000' = 5000.0
  $loan['loan_term'] = (int)'17' = 17

Line 52: if ($loan['loan_amount'] > 0) → TRUE

Line 53: $loanCalculation = $loanCalculationService->calculateLoan(5000, 17)
  → Calculates loan details
  → Returns array with:
     - principal: 5000
     - total_interest: 1000
     - insurance_fee: 425
     - total_loan_amount: 6425
     - weekly_payment_base: 378.24
     - payment_schedule: [array of 17 weeks]

Line 54: if (!$loanCalculation) → FALSE (calculation succeeded)

Line 60: $csrf->generateToken() → Generates new TOKEN_B

Line 65: if (isset($_POST['submit_loan'])) → FALSE (only 'calculate' button was pressed)
  → Skip the loan creation block

Line 93-95: if ($error) → FALSE (no error)
  → Skip flash message

Line 101-207: Render the page with:
  - Initial form from templates/loans/form.php (with Calculate button)
  - Error/success alerts (empty in this case)

Line 133: if ($loanCalculation && empty($error)) → TRUE

Line 135-207: Show the preview section with:
  - Loan calculation details displayed
  - Hidden form with:
    - client_id: 1
    - loan_amount: 5000
    - loan_term: 17
    - csrf_token: TOKEN_B (the newly generated token)
  - Submit button
```

**Expected Result:** 
- Preview appears on page showing calculation
- Hidden form is ready for submission with TOKEN_B

---

### STEP 3: User Clicks "Submit Loan Application" Button

**User Input:**
```
POST to /public/loans/add.php (from hidden form)
  client_id: 1
  loan_amount: 5000
  loan_term: 17
  csrf_token: TOKEN_B
  submit_loan: 1 (button with value="1")
```

**Code Execution:**

```
Line 41: $_SERVER['REQUEST_METHOD'] === 'POST' → TRUE

Line 42-44: CSRF Validation
  if (!$csrf->validateRequest(false)) → FALSE (TOKEN_B matches session)
  
Line 46-49: Extract and sanitize POST data
  $loan['client_id'] = (int)'1' = 1
  $loan['loan_amount'] = (float)'5000' = 5000.0
  $loan['loan_term'] = (int)'17' = 17

Line 52: if ($loan['loan_amount'] > 0) → TRUE

Line 53: $loanCalculation = $loanCalculationService->calculateLoan(5000, 17)
  → Calculation runs AGAIN
  → Should return same values as before
  → If it fails here, error_log at line 57 and continue

Line 65: if (isset($_POST['submit_loan'])) → TRUE (submit_loan field exists)

Line 68-73: Map data to service format
  $loanData = [
    'client_id' => 1,
    'principal' => 5000.0,
    'term_weeks' => 17
  ]

Line 75: $loanId = $loanService->applyForLoan($loanData, $user['id'])

   *** NOW ENTERING LOANSERVICE::applyForLoan() ***

   Line 212-220: Extract and validate
     $principal = 5000
     $clientId = 1
     
     if (!$this->validateLoanData(...)) → Check validation
   
   *** IN validateLoanData() ***
   
   Line 475-480: BaseService validation
     - Check: client_id is required and numeric → PASS
     - Check: principal is required, numeric, positive → PASS
   
   Line 486: Check if client exists
     $this->clientModel->findById(1)
     → If NO: Set error "Selected client does not exist." → RETURN FALSE
     
   Line 493: Check for active loan
     $this->loanModel->getClientActiveLoan(1)
     → Queries for status = 'Active' (not 'Application')
     → If has active: Set error "Client already has an active loan..." → RETURN FALSE
   
   Line 499: Check for defaulted loan
     $this->loanModel->hasClientDefaultedLoan(1)
     → If has defaulted: Set error "Client has defaulted loans..." → RETURN FALSE
   
   Line 505: Validate loan amount
     $this->loanCalculationService->validateLoanAmount(5000)
     → Check: 5000 >= 1000 && 5000 <= 50000 → TRUE
     → Check passes → RETURN TRUE
   
   Line 510: Return TRUE from validateLoanData()
   
   *** BACK IN applyForLoan() ***
   
   Line 224: $termWeeks = 17 (from $loanData)
   
   Line 227: $calculation = $loanCalculationService->calculateLoan(5000, 17)
     → SECOND CALCULATION (first was on Calculate step)
     → If fails: error_log at line 227, setErrorMessage, return FALSE
   
   Line 235: Map to dataToCreate
     $dataToCreate = [
       'client_id' => 1,
       'principal' => 5000,
       'interest_rate' => 0.05,
       'term_weeks' => 17,
       'total_interest' => 1000,
       'insurance_fee' => 425,
       'total_loan_amount' => 6425,
       'status' => 'Application',
       'application_date' => current timestamp
     ]
   
   Line 247: $newId = $this->loanModel->create($dataToCreate)
   
   *** IN LOANMODEL::create() ***
   
   Line 378: Set status = 'Application' (already set above)
   Line 379: Set created_at = current timestamp
   Line 380: Set updated_at = current timestamp
   
   Line 383: return parent::create($data)
   
   *** IN BASEMODEL::create() ***
   
   Line 50: Filter data to only fillable fields
     $filteredData = array_intersect_key($dataToCreate, array_flip($this->fillable))
     
   Line 52: Build SQL INSERT statement
     INSERT INTO loans (client_id, principal, interest_rate, term_weeks, total_interest, insurance_fee, total_loan_amount, status, application_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
   
   Line 63: $result = $this->db->query($sql, array_values($filteredData))
     → If database error: setLastError(), return FALSE
     → If successful: return (int)$this->db->lastInsertId() → e.g., 47
   
   *** BACK IN LOANSERVICE::applyForLoan() ***
   
   Line 249: if (!$newId) → FALSE (newId = 47)
   
   Line 256: if (class_exists('TransactionService'))
     → Log transaction if service exists
   
   Line 261: return $newId → 47

Line 79: if ($loanId) → TRUE (loanId = 47)

Line 82: error_log("Loan created successfully. Loan ID: 47")

Line 83: $session->setFlash('success', 'Loan application submitted successfully...')

Line 84: header('Location: ' . APP_URL . '/public/loans/index.php')
  → REDIRECT TO LOANS LIST PAGE

Line 85: exit;
```

**Expected Result:** 
- Redirect to `/public/loans/index.php`
- Success message appears: "Loan application submitted successfully..."
- New loan appears in list with status 'Application'

---

## Where Can It Fail?

### Failure Point 1: CSRF Token Invalid
- **Line 42-44** in add.php
- **Error:** "Invalid security token. Please refresh and try again."
- **Likely?** UNLIKELY (token was regenerated after Calculate step)

### Failure Point 2: Client Doesn't Exist
- **Line 486** in LoanService
- **Error:** "Selected client does not exist."
- **Likely?** If client was deleted or doesn't exist in DB

### Failure Point 3: Client Has Active Loan
- **Line 493** in LoanService
- **Error:** "Client already has an active loan and cannot apply for another."
- **Likely?** If testing with same client twice

### Failure Point 4: Client Has Defaulted Loan
- **Line 499** in LoanService
- **Error:** "Client has defaulted loans and must settle their account before applying."
- **Likely?** If testing with same client

### Failure Point 5: Calculation Fails on Submit
- **Line 227** in LoanService (second calculation)
- **Error:** Whatever LoanCalculationService returns
- **Likely?** If calculation logic changed or data is invalid

### Failure Point 6: Database Insert Fails
- **Line 247** in LoanService
- **Line 63** in BaseModel
- **Error:** "Failed to save loan application." (if DB provides error), or "$this->loanModel->getLastError()"
- **Likely?** If loans table doesn't exist or has constraint violations
- **Possible causes:**
  - Foreign key constraint: client_id doesn't exist in clients table
  - Column type mismatch: e.g., principal should be DECIMAL, not INT
  - Missing columns in loans table
  - Unique constraint violation

---

## Debugging Strategy

### To Find the Exact Failure:

1. **Check the error message displayed** on the form
   - This tells you which failure point is being hit

2. **If "Failed to save loan application":**
   - Check: Does the clients table have the client_id you're using?
   - Check: Does the loans table exist?
   - Check: Do the loans table columns match the schema?

3. **If "Client already has an active loan":**
   - Check: Clean database of old test loans with status 'Active'
   - Or: Use a different client for testing

4. **If no error message at all:**
   - Page might be redirecting but not showing success
   - Check: Does /public/loans/index.php exist and load?
   - Check: Is the success flash message being set?

---

## Most Likely Problem

Based on code review, the **MOST LIKELY issue** is:

**Database Insert Failure** (Error: "Failed to save loan application.")

This could be caused by:
- ✓ Foreign key constraint on client_id
- ✓ Missing columns in loans table
- ✓ Data type mismatch
- ✓ Database permissions issue

**Second Most Likely:**

**Client Eligibility** (Error: "Client already has an active loan...")

This would happen if:
- ✓ Testing with same client multiple times
- ✓ Previous test didn't clean up database
- ✓ Client status was set to something unexpected
