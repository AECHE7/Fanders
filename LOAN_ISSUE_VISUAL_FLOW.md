# Loan Creation Issue - Visual Flow Diagram

## 📊 Complete Loan Creation Flow with Issue Points

```
┌─────────────────────────────────────────────────────────────────────┐
│                   LOAN APPLICATION SUBMISSION FLOW                 │
└─────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ STEP 1: User loads /public/loans/add.php                             │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  GET /public/loans/add.php                                          │
│                     ↓                                               │
│  Initialize services & fetch active clients                        │
│     - LoanService                                                  │
│     - LoanCalculationService                                       │
│     - ClientService                                                │
│                     ↓                                               │
│  Generate CSRF token                                               │
│                     ↓                                               │
│  Display form with:                                                │
│     - Client dropdown (active clients only) ✓                      │
│     - Loan amount input (min: ₱1,000, max: ₱50,000)               │
│     - Loan term input (min: 4 weeks, max: 52 weeks)               │
│     - "Calculate" button                                           │
│                                                                      │
│  STATUS: ✓ WORKING                                                │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ STEP 2: User fills form & clicks "Calculate"                        │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  POST /public/loans/add.php                                         │
│     client_id=1, loan_amount=5000, loan_term=17                    │
│                     ↓                                               │
│  [add.php lines 42-44] CSRF validation                             │
│     if (!$csrf->validateRequest(false))  →  ✓ PASS                │
│                     ↓                                               │
│  [add.php lines 45-79] Form data processing                        │
│     Parse $_POST into $loan array                                  │
│                     ↓                                               │
│  [add.php lines 81-71] CALCULATION PHASE                           │
│     if ($loan['loan_amount'] > 0) {                                │
│         $loanCalculation = calculateLoan(5000, 17)                │
│     }                                                              │
│                     ↓                                               │
│  [LoanCalculationService::calculateLoan()]                         │
│     1. Validate term: 4 ≤ 17 ≤ 52  →  ✓ PASS                     │
│     2. Validate amount: 1000 ≤ 5000 ≤ 50000  →  ✓ PASS           │
│     3. Calculate interest: 5000 × 0.05 × 4 = 1000                │
│     4. Calculate total: 5000 + 1000 + 425 = 6425                 │
│     5. Generate payment schedule (17 weeks)                        │
│                     ↓ ✓ SUCCESS                                    │
│  [add.php line 71] Regenerate CSRF token                           │
│     $csrf->generateToken()                                         │
│                     ↓                                               │
│  Display calculation preview with:                                 │
│     - Principal: ₱5,000                                            │
│     - Total Interest: ₱1,000                                       │
│     - Insurance Fee: ₱425                                          │
│     - Total Repayment: ₱6,425                                      │
│     - Weekly Payment: ₱378.24 (approx)                            │
│     - New hidden form with "Submit" button                         │
│                                                                      │
│  STATUS: ✓ WORKING                                                │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ STEP 3: User clicks "Submit Loan Application" [FAILURE POINT]       │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  POST /public/loans/add.php (with hidden form)                     │
│     submit_loan=1                                                  │
│     client_id=1, loan_amount=5000, loan_term=17                   │
│     csrf_token=[regenerated_token]                                │
│                     ↓                                               │
│  [add.php line 43] CSRF validation                                 │
│     if (!$csrf->validateRequest(false))  →  ✓ PASS                │
│                     ↓                                               │
│  [add.php line 90-94] Loan submission triggered                   │
│     if (isset($_POST['submit_loan'])) {                           │
│         $loanId = $loanService->applyForLoan($loanData, $user_id)│
│     }                                                              │
│                     ↓                                               │
│  ╔════════════════════════════════════════════════════════════════╗│
│  ║ [LoanService::applyForLoan()] - CRITICAL POINT                ║│
│  ╚════════════════════════════════════════════════════════════════╝│
│                     ↓                                               │
│  1️⃣  Call validateLoanData($clientId=1, $principal=5000)          │
│     ├─ [LoanService line 486-489]                                │
│     │  Check client exists: findById(1)                          │
│     │  ✓ PASS: Client ID 1 exists                                │
│     │                                                            │
│     ├─ [LoanModel line 282-288] CHECK ACTIVE LOAN               │
│     │  ❌ POTENTIAL ISSUE #1: Case-sensitivity                  │
│     │                                                            │
│     │  Database may contain: 'active' or 'ACTIVE'               │
│     │  Query checks for: 'Active' (capital A)                   │
│     │                                                            │
│     │  $sql = "WHERE client_id = ? AND status = ?"              │
│     │  $sql = execute(1, 'Active')  ← Exact case match          │
│     │                                                            │
│     │  If DB has 'active' (lowercase):                          │
│     │     Query returns NULL                                    │
│     │     Client falsely appears eligible ❌                    │
│     │                                                            │
│     │  If DB has 'Active' (correct):                            │
│     │     Query returns loan record                             │
│     │     Error: "Client already has active loan" ✓             │
│     │                                                            │
│     ├─ [LoanModel line 290-295] CHECK DEFAULTED LOANS           │
│     │  Same case-sensitivity risk as above                      │
│     │  ❌ POTENTIAL ISSUE #2: Case-sensitivity                  │
│     │                                                            │
│     ├─ [LoanCalculationService::validateLoanAmount()]           │
│     │  Check: 1000 ≤ 5000 ≤ 50000  →  ✓ PASS                   │
│     │                                                            │
│     └─ Validation complete → Return TRUE ✓                       │
│                     ↓                                               │
│  2️⃣  Calculate loan again                                         │
│     $calculation = $loanCalculationService->calculateLoan()      │
│     ✓ PASS (same as step 2)                                      │
│                     ↓                                               │
│  3️⃣  [LoanService line 235-247] Prepare data for insert          │
│     $dataToCreate = [                                             │
│         'client_id' => 1,                                         │
│         'principal' => 5000,                                      │
│         'interest_rate' => 0.05,                                  │
│         'term_weeks' => 17,                                       │
│         'total_interest' => 1000,                                 │
│         'insurance_fee' => 425,                                   │
│         'total_loan_amount' => 6425,                              │
│         'status' => 'Application',                                │
│         'application_date' => '2025-10-21 14:30:00',             │
│     ]                                                             │
│                     ↓                                               │
│  4️⃣  [LoanModel::create()] INSERT INTO database                  │
│     ├─ Add defaults:                                             │
│     │  'created_at' => NOW()                                    │
│     │  'updated_at' => NOW()                                    │
│     │                                                            │
│     ├─ Filter to fillable fields ✓                              │
│     │  (All fields are in $fillable)                            │
│     │                                                            │
│     ├─ [BaseModel line 52-66] Build INSERT query                │
│     │  INSERT INTO loans (client_id, principal, ... )           │
│     │  VALUES (1, 5000, ... )                                   │
│     │                                                            │
│     ├─ Execute query                                            │
│     │  ❌ POTENTIAL ISSUE #3: Silent database failure            │
│     │     - Foreign key violation                               │
│     │     - NULL constraint violated                            │
│     │     - Type mismatch                                       │
│     │     - Connection error                                    │
│     │                                                            │
│     └─ Return new ID or FALSE                                   │
│                     ↓                                               │
│  5️⃣  Check result                                                 │
│     if ($newId) {                                                 │
│         ✓ SUCCESS: Loan created                                 │
│     } else {                                                     │
│         ❌ POTENTIAL ISSUE #4: Error message lost                │
│         $error = $loanModel->getLastError() ?: 'Generic'        │
│         If getLastError() returns "", we see fallback msg       │
│     }                                                            │
│                     ↓                                               │
│  STATUS: ❌ CAN FAIL (multiple points)                           │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ STEP 4: User sees result                                            │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  If $loanId is set (SUCCESS):                                       │
│     ✓ Redirect to /public/loans/index.php                         │
│     ✓ Display success message                                     │
│                                                                      │
│  If $loanId is FALSE (FAILURE):                                    │
│     ❌ Stay on same page                                          │
│     ❌ Display error message (or generic message)                 │
│     ❌ User sees form again with no data                          │
│                                                                      │
│  STATUS: May fail silently or with generic message                │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 🔴 CRITICAL FAILURE POINTS

```
┌─────────────────────────────────────────────────────────────────────┐
│ FAILURE POINT #1: Status Case-Sensitivity Mismatch                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│ Location: LoanModel.php line 282                                   │
│                                                                     │
│ Query:  SELECT * FROM loans                                        │
│         WHERE client_id = 1 AND status = 'Active'                 │
│                                                                     │
│ Scenario 1: Database has 'Active' (Correct)                       │
│    Query Result: [id:1, status:'Active', ...]                    │
│    Check: if ($result) → TRUE                                     │
│    Action: "Client already has active loan" error ✓               │
│    Outcome: CORRECT REJECTION                                     │
│                                                                     │
│ Scenario 2: Database has 'active' (Wrong Case)                    │
│    Query Result: NULL (no match)                                  │
│    Check: if ($result) → FALSE                                    │
│    Action: Continue validation (no error)                         │
│    Outcome: INCORRECT ACCEPTANCE ❌                               │
│                                                                     │
│ Scenario 3: Database has 'ACTIVE' (Wrong Case)                    │
│    Query Result: NULL (no match)                                  │
│    Check: if ($result) → FALSE                                    │
│    Action: Continue validation (no error)                         │
│    Outcome: INCORRECT ACCEPTANCE ❌                               │
│                                                                     │
│ FIX: Use LOWER() in query                                         │
│ WHERE client_id = 1 AND LOWER(status) = LOWER('Active')          │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│ FAILURE POINT #2: Empty Error Message Not Detected                 │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│ Location: add.php line 107                                         │
│                                                                     │
│ Code:  $submissionError = $loanService->getErrorMessage();         │
│        $error = $submissionError ?: "Failed to submit...";         │
│                                                                     │
│ Scenario 1: getErrorMessage() returns "Client not found"          │
│    $submissionError = "Client not found"                          │
│    $error = "Client not found"                                    │
│    Outcome: User sees correct error ✓                             │
│                                                                     │
│ Scenario 2: getErrorMessage() returns "" (empty string)           │
│    $submissionError = ""                                          │
│    "" ?: "Failed..." evaluates to "Failed..."                     │
│    $error = "Failed to submit..."                                 │
│    Outcome: User sees generic error (real error hidden) ❌        │
│                                                                     │
│ Scenario 3: getErrorMessage() returns "0" or "false"              │
│    (Rare but possible with certain error conditions)              │
│    Outcome: User sees generic error (real error hidden) ❌        │
│                                                                     │
│ FIX: Check for truly empty, not just falsy                        │
│ if (!$submissionError || trim($submissionError) === '') {         │
│     $error = "Failed to submit...";                               │
│ } else {                                                            │
│     $error = $submissionError;                                    │
│ }                                                                   │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│ FAILURE POINT #3: Database INSERT Fails Silently                   │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│ Location: BaseModel.php line 64-66                                 │
│                                                                     │
│ Code:  $result = $this->db->query($sql, array_values($filteredData));
│        if ($result) {                                              │
│            return (int) $this->db->lastInsertId();                │
│        }                                                            │
│        $this->setLastError('Failed to create record.');            │
│        return false;                                               │
│                                                                     │
│ Possible Failures:                                                 │
│    1. Foreign key constraint: client_id doesn't exist             │
│       → Database error: Foreign key constraint failed             │
│       → getLastError() may be set or empty                        │
│                                                                     │
│    2. NOT NULL column is NULL                                      │
│       → Database error: Column 'X' cannot be null                 │
│       → getLastError() may be set or empty                        │
│                                                                     │
│    3. Data type mismatch                                           │
│       → Database error: Incorrect data value                      │
│       → getLastError() may be set or empty                        │
│                                                                     │
│    4. Connection lost mid-query                                    │
│       → Database error: Connection gone away                      │
│       → getLastError() = "Connection lost"                        │
│                                                                     │
│    5. Permission denied                                            │
│       → Database error: Access denied for user                    │
│       → getLastError() = "Access denied"                          │
│                                                                     │
│ FIX: Better error logging and handling                             │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 📈 Validation State Machine

```
                    ┌──────────────────┐
                    │  Form Submitted  │
                    └────────┬─────────┘
                             │
                             ▼
                    ┌──────────────────┐
                    │ CSRF Validation  │
                    └────────┬─────────┘
                             │
                    ┌────────┴────────┐
                    │                 │
              ❌ INVALID        ✓ VALID
                    │                 │
                    ▼                 ▼
            Error: "Invalid  ┌──────────────────┐
            Token"           │ Parse Form Data  │
                             └────────┬─────────┘
                                      │
                                      ▼
                             ┌──────────────────┐
                             │ Calculate Loan   │  (User clicked Calculate)
                             └────────┬─────────┘
                                      │
                             ┌────────┴────────┐
                             │                 │
                        ❌ INVALID        ✓ VALID
                             │                 │
                             ▼                 ▼
                      Error displayed    Display Preview
                      User retries       (Regenerate CSRF)
                                              │
                                              ▼
                                    ┌──────────────────┐
                                    │ User clicks      │
                                    │ "Submit Loan"    │
                                    └────────┬─────────┘
                                             │
                                             ▼
                                    ┌──────────────────┐
                                    │ Validate Data    │  ◄─── CRITICAL
                                    │ (Client, Amount) │
                                    └────────┬─────────┘
                                             │
                                    ┌────────┴────────┐
                                    │                 │
                               ❌ INVALID        ✓ VALID
                                    │                 │
                                    ▼                 ▼
                     Error: "Client already"    ┌──────────────────┐
                     "Client doesn't exist"     │ Calculate Again  │
                     "Amount too low"           └────────┬─────────┘
                     "Amount too high"                   │
                     "Client defaulted"                  ▼
                                             ┌──────────────────┐
                                             │ INSERT Database  │  ◄─── CRITICAL
                                             └────────┬─────────┘
                                                      │
                                             ┌────────┴────────┐
                                             │                 │
                                        ❌ FAILED        ✓ SUCCESS
                                             │                 │
                                             ▼                 ▼
                          Error: "Failed to"     Redirect to
                          "save loan"             Loans List
                          (or empty string)       Show Success
                                             
    KEY RISKS AT:
    🔴 Client check (case-sensitivity of status field)
    🔴 Amount validation (type coercion)
    🔴 Database insert (many possible failures)
    🔴 Error message handling (empty strings)
```

---

## 🎯 Decision Tree: What's Failing?

```
                        Loan creation fails
                              │
                              ▼
                     ┌──────────────┐
                     │ Can you see  │
                     │ the error    │
                     │ message?     │
                     └──┬──────────┬┘
                        │          │
                   YES  │          │  NO
                        ▼          ▼
                   ┌─────────┐  "Failed to
                   │ Is it   │  submit..."
                   │ about   │  (generic)
                   │ active  │
                   │ loan?   │  This means:
                   └──┬─┬──┬─┘  - Error message empty
                   Y  │ │ │   - Status case issue
                      ▼ │ │   - Database query failed
                   Issue: │ │   - Connection error
                   Client has
                   active loan │ ▼
                   (but they  │ "Amount too
                   shouldn't) │  low" / "high"
                              │
                              │ Issue:
                              │ Form validation
                              │ (usually frontend)
                              ▼
                         Check the
                         loan amount
                         (₱1K-50K)

                        "Client not    No error but
                        found"         form fails
                              │               │
                              ▼               ▼
                         Invalid      Silent failure:
                         client_id    - Database error
                                       - Connection lost
                                       - Permission denied
                                       - Foreign key failed
```

---

## 📊 Data Flow Visualization

```
User Input                 Validation                  Processing
═════════════════════════════════════════════════════════════════════

┌─────────────────┐
│  Client ID: 1   │
│  Amount: 5000   │  ─────────────────────────────────────┐
│  Term: 17       │                                        │
└─────────────────┘                                        │
                                                            ▼
                                                   ┌─────────────────┐
                                                   │ CSRF Check ✓    │
                                                   └─────────────────┘
                                                            │
                                                            ▼
                  LoanCalculationService                  │
        ┌──────────────────────────────┐                 │
        │ Term: 4 ≤ 17 ≤ 52 ✓          │◄────────────────┘
        │ Amount: 1K ≤ 5K ≤ 50K ✓       │
        │ Interest: 5000×0.05×4 = 1000  │
        │ Total: 6425                   │
        │ Payment: 378.24/week          │
        └──────────────────────────────┘
                     │
                     ▼ (Preview shown, user clicks Submit)
                     │
        ┌────────────────────────────────┐
        │ LoanService::applyForLoan()    │
        │                                │
        │ 1. validateLoanData()          │
        │    ├─ Client exists? ✓         │
        │    ├─ Has active loan?         │◄─── 🔴 RISK: Case issue
        │    ├─ Has defaulted?           │◄─── 🔴 RISK: Case issue
        │    └─ Amount valid? ✓          │
        │                                │
        │ 2. Calculate Again ✓           │
        │                                │
        │ 3. LoanModel::create()         │◄─── 🔴 RISK: DB insert fail
        │    ├─ Filter fields            │
        │    ├─ Build INSERT query       │
        │    └─ Execute                  │◄─── 🔴 RISK: Silent fail
        │                                │
        │ 4. Check result                │
        │    ├─ If ID set: SUCCESS ✓     │
        │    └─ If FALSE: ERROR ❌       │◄─── 🔴 RISK: Error lost
        └────────────────────────────────┘
                     │
        ┌────────────┴────────────┐
        │                         │
     SUCCESS                    FAILURE
        │                         │
        ▼                         ▼
    Redirect to        Display error (or generic)
    Loans List         User stays on form
    Show success msg   Form data lost
```

---

