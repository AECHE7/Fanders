# Loan Issue - Specific Code Locations & Fixes

## 📍 File-by-File Problem Analysis

---

## 1️⃣ `/workspaces/Fanders/public/loans/add.php`

### Issue Location 1A: Missing Error Handling After Calculation

**Lines:** 59-71

**Current Code:**
```php
if ($loan['loan_amount'] > 0) {
    $loanCalculation = $loanCalculationService->calculateLoan($loan['loan_amount'], $loan['loan_term']);
    if (!$loanCalculation) {
        $error = $loanCalculationService->getErrorMessage() ?: "Failed to calculate loan details.";
        error_log("Loan calculation error on add.php: " . $error);
    } else {
        $csrf->generateToken();
    }
}
```

**Problem:** 
- Calculation error doesn't prevent form submission
- Error is set in `$error` variable but might not be persistent
- User can still click "Submit Loan Application" even if calculation failed

**Recommendation:**
```php
if ($loan['loan_amount'] > 0) {
    $loanCalculation = $loanCalculationService->calculateLoan($loan['loan_amount'], $loan['loan_term']);
    if (!$loanCalculation) {
        $error = $loanCalculationService->getErrorMessage() ?: "Failed to calculate loan details.";
        error_log("Loan calculation error on add.php: " . $error);
        // Store error in session for persistence
        $session->setFlash('error', $error);
    } else {
        $csrf->generateToken();
        // Clear any previous errors on successful calculation
        $session->clearFlash('error');
    }
}
```

---

### Issue Location 1B: Empty Error Message After Submission

**Lines:** 100-115

**Current Code:**
```php
if ($loanId) {
    $debugLog .= "RESULT: SUCCESS - Loan created with ID: " . $loanId . "\n";
    error_log($debugLog);
    file_put_contents($debugFile, $debugLog, FILE_APPEND);
    
    $session->setFlash('success', 'Loan application submitted successfully. Pending Manager approval.');
    header('Location: ' . APP_URL . '/public/loans/index.php');
    exit;
} else {
    // Failure: Get the error message from the service
    $submissionError = $loanService->getErrorMessage();
    $debugLog .= "Service Error: " . ($submissionError ?: 'NO ERROR MESSAGE FROM SERVICE') . "\n";
    $debugLog .= "RESULT: FAILURE\n";
    
    error_log($debugLog);
    file_put_contents($debugFile, $debugLog, FILE_APPEND);
    
    $error = $submissionError ?: "Failed to submit loan application.";
    $session->setFlash('error', $error);
}
```

**Problem:** 
- Uses `?:` operator which treats empty string as falsy
- If `$submissionError` is empty string `""`, fallback message is used
- Real error is hidden in logs

**Recommendation:**
```php
if ($loanId) {
    $debugLog .= "RESULT: SUCCESS - Loan created with ID: " . $loanId . "\n";
    error_log($debugLog);
    file_put_contents($debugFile, $debugLog, FILE_APPEND);
    
    $session->setFlash('success', 'Loan application submitted successfully. Pending Manager approval.');
    header('Location: ' . APP_URL . '/public/loans/index.php');
    exit;
} else {
    // Failure: Get the error message from the service
    $submissionError = $loanService->getErrorMessage();
    
    // Better error message handling - check for truly empty, not just falsy
    if (!$submissionError || trim($submissionError) === '') {
        $error = "Failed to submit loan application. Check system logs for details.";
        error_log("CRITICAL: Loan submission failed but no error message provided. Client ID: " . $loan['client_id']);
    } else {
        $error = $submissionError;
    }
    
    $debugLog .= "Service Error: " . $error . "\n";
    $debugLog .= "RESULT: FAILURE\n";
    
    error_log($debugLog);
    file_put_contents($debugFile, $debugLog, FILE_APPEND);
    
    $session->setFlash('error', $error);
}
```

---

### Issue Location 1C: Eligibility Check at Page Load

**Lines:** 29-39

**Current Code:**
```php
// If a client_id is passed, check if they are eligible for a loan
if (!empty($loan['client_id'])) {
    if (!$loanService->canClientApplyForLoan($loan['client_id'])) {
        $session->setFlash('error', $loanService->getErrorMessage() ?: "Client is ineligible for a new loan.");
        header('Location: ' . APP_URL . '/public/clients/view.php?id=' . $loan['client_id']);
        exit;
    }
}
```

**Problem:**
- This only runs if `$_GET['client_id']` is set
- Doesn't catch eligibility issues when form is submitted
- Eligibility is re-checked in `validateLoanData()` anyway

**Observation:**
- Actually this is fine - it's just an early check for direct links
- The real validation happens in `applyForLoan()` → `validateLoanData()`

**Status:** ✓ No fix needed

---

## 2️⃣ `/workspaces/Fanders/app/services/LoanService.php`

### Issue Location 2A: Validation Data Missing Type Coercion

**Lines:** 483-495

**Current Code:**
```php
private function validateLoanData(array $loanData, $excludeId = null) {
    $clientId = $loanData['client_id'] ?? null;
    $principal = $loanData['principal'] ?? null;

    // Use BaseService validation for basic requirements
    if (!$this->validate(['client_id' => $clientId, 'principal' => $principal], [
        'client_id' => 'required|numeric',
        'principal' => 'required|numeric|positive'
    ])) {
        return false;
    }

    // Check if client exists
    if (!$this->clientModel->findById($clientId)) {
        $this->setErrorMessage('Selected client does not exist.');
        return false;
    }
```

**Problem:**
- `$principal` is passed as-is from form (could be string like "5000.00")
- Validation should ensure it's a number before using in calculations
- Type casting should happen before validation

**Recommendation:**
```php
private function validateLoanData(array $loanData, $excludeId = null) {
    // Ensure proper type casting
    $clientId = !empty($loanData['client_id']) ? (int)$loanData['client_id'] : null;
    $principal = !empty($loanData['principal']) ? (float)$loanData['principal'] : null;

    // Use BaseService validation for basic requirements
    if (!$this->validate(['client_id' => $clientId, 'principal' => $principal], [
        'client_id' => 'required|numeric',
        'principal' => 'required|numeric|positive'
    ])) {
        return false;
    }

    // Check if client exists
    if (!$this->clientModel->findById($clientId)) {
        $this->setErrorMessage('Selected client does not exist.');
        return false;
    }
```

---

### Issue Location 2B: Missing Validation in canClientApplyForLoan()

**Lines:** 183-206

**Current Code:**
```php
public function canClientApplyForLoan($clientId) {
    // Check if client exists
    if (!$this->clientModel->findById($clientId)) {
        $this->setErrorMessage('Selected client does not exist.');
        return false;
    }

    // Check for active loan
    if ($this->loanModel->getClientActiveLoan($clientId)) {
        $this->setErrorMessage('Client already has an active loan and cannot apply for another.');
        return false;
    }

    // Check for defaulted loan
    if ($this->loanModel->hasClientDefaultedLoan($clientId)) {
        $this->setErrorMessage('Client has defaulted loans and must settle their account before applying.');
        return false;
    }

    return true;
}
```

**Problem:**
- Doesn't check if client is 'active' status
- Doesn't check if client is 'blacklisted'
- `findById()` returns client even if status is 'inactive'

**Recommendation:**
```php
public function canClientApplyForLoan($clientId) {
    // Check if client exists
    $client = $this->clientModel->findById($clientId);
    if (!$client) {
        $this->setErrorMessage('Selected client does not exist.');
        return false;
    }
    
    // Check if client is active
    if ($client['status'] !== 'active') {
        $this->setErrorMessage('Client must have active status to apply for loans. Current status: ' . ucfirst($client['status']));
        return false;
    }
    
    // Check if client is blacklisted
    if ($client['status'] === 'blacklisted') {
        $this->setErrorMessage('This client is blacklisted and cannot apply for loans.');
        return false;
    }

    // Check for active loan
    if ($this->loanModel->getClientActiveLoan($clientId)) {
        $this->setErrorMessage('Client already has an active loan and cannot apply for another.');
        return false;
    }

    // Check for defaulted loan
    if ($this->loanModel->hasClientDefaultedLoan($clientId)) {
        $this->setErrorMessage('Client has defaulted loans and must settle their account before applying.');
        return false;
    }

    return true;
}
```

---

### Issue Location 2C: Error Message Not Set Before Validation Failure

**Lines:** 212-260

**Current Code:**
```php
public function applyForLoan(array $loanData, $userId) {
    $principal = $loanData['principal'] ?? null;
    $clientId = $loanData['client_id'] ?? null;

    // 1. Validate required fields and unique loan status
    if (!$this->validateLoanData(['client_id' => $clientId, 'principal' => $principal])) {
        return false;
    }
    
    // ... rest of code ...
    
    $newId = $this->loanModel->create($dataToCreate);

    if (!$newId) {
         $this->setErrorMessage($this->loanModel->getLastError() ?: 'Failed to save loan application.');
         return false;
    }
```

**Problem:**
- If `validateLoanData()` returns false, error is already set ✓
- But if `create()` fails, uses `getLastError()` which might be empty
- Should log the error for debugging

**Recommendation:**
```php
public function applyForLoan(array $loanData, $userId) {
    $principal = $loanData['principal'] ?? null;
    $clientId = $loanData['client_id'] ?? null;

    // 1. Validate required fields and unique loan status
    if (!$this->validateLoanData(['client_id' => $clientId, 'principal' => $principal])) {
        error_log("Loan validation failed for client_id=$clientId, principal=$principal");
        return false;
    }
    
    // ... rest of code ...
    
    $newId = $this->loanModel->create($dataToCreate);

    if (!$newId) {
        $lastError = $this->loanModel->getLastError() ?: 'Unknown error during loan creation';
        $this->setErrorMessage('Failed to save loan application: ' . $lastError);
        error_log("Loan creation failed: " . $lastError . " Data: " . json_encode($dataToCreate));
        return false;
    }
```

---

## 3️⃣ `/workspaces/Fanders/app/models/LoanModel.php`

### Issue Location 3A: Status Check Case-Sensitivity

**Lines:** 282-288

**Current Code:**
```php
public function getClientActiveLoan($clientId) {
    $sql = "SELECT * FROM {$this->table}
            WHERE client_id = ? AND status = ?
            ORDER BY created_at DESC LIMIT 1";

    return $this->db->single($sql, [$clientId, self::STATUS_ACTIVE]);
}
```

**Problem:**
- If database contains 'active' instead of 'Active', query returns nothing
- Client appears eligible when they have an active loan
- Silent failure

**Recommendation:**
```php
public function getClientActiveLoan($clientId) {
    $sql = "SELECT * FROM {$this->table}
            WHERE client_id = ? AND LOWER(status) = LOWER(?)
            ORDER BY created_at DESC LIMIT 1";

    return $this->db->single($sql, [$clientId, self::STATUS_ACTIVE]);
}
```

**Or better - Standardize database first:**
```sql
-- Run this to fix database values
UPDATE loans SET status = 'Application' WHERE LOWER(status) = 'application';
UPDATE loans SET status = 'Approved' WHERE LOWER(status) = 'approved';
UPDATE loans SET status = 'Active' WHERE LOWER(status) = 'active';
UPDATE loans SET status = 'Completed' WHERE LOWER(status) = 'completed';
UPDATE loans SET status = 'Defaulted' WHERE LOWER(status) = 'defaulted';
```

---

### Issue Location 3B: Defaulted Loan Check

**Lines:** 290-295

**Current Code:**
```php
public function hasClientDefaultedLoan($clientId) {
    $sql = "SELECT COUNT(*) as count FROM {$this->table}
            WHERE client_id = ? AND status = ?";
    $result = $this->db->single($sql, [$clientId, self::STATUS_DEFAULTED]);
    return $result && $result['count'] > 0;
}
```

**Problem:**
- Same case-sensitivity issue
- Should use LOWER() for comparison

**Recommendation:**
```php
public function hasClientDefaultedLoan($clientId) {
    $sql = "SELECT COUNT(*) as count FROM {$this->table}
            WHERE client_id = ? AND LOWER(status) = LOWER(?)";
    $result = $this->db->single($sql, [$clientId, self::STATUS_DEFAULTED]);
    return $result && $result['count'] > 0;
}
```

---

### Issue Location 3C: Missing Validation Fields

**Lines:** 13-20

**Current Code:**
```php
protected $fillable = [
    'client_id', 'principal', 'interest_rate', 'term_weeks',
    'total_interest', 'insurance_fee', 'total_loan_amount',
    'status', 'application_date', 'approval_date', 'disbursement_date',
    'completion_date', 'created_at', 'updated_at'
];
```

**Analysis:**
- All fields needed by `LoanService::applyForLoan()` are included ✓
- `application_date` is fillable ✓
- `created_at` and `updated_at` are fillable ✓

**Status:** ✓ No fix needed

---

## 4️⃣ `/workspaces/Fanders/app/models/ClientModel.php`

### Issue Location 4A: Active Clients Filter

**Lines:** 236-239

**Current Code:**
```php
public function getAllForSelect() {
    $sql = "SELECT id, name FROM {$this->table} WHERE status = ? ORDER BY name ASC";
    return $this->db->resultSet($sql, [self::STATUS_ACTIVE]);
}
```

**Analysis:**
- Only returns active clients ✓
- This is correct - users should only be able to select active clients

**Status:** ✓ No fix needed

---

## 5️⃣ `/workspaces/Fanders/app/services/LoanCalculationService.php`

### Issue Location 5A: Type Coercion in Validation

**Lines:** 41-50

**Current Code:**
```php
public function validateLoanAmount($principal) {
    if ($principal < 1000) {
        $this->setErrorMessage('Loan amount must be at least ₱1,000.');
        return false;
    }
    if ($principal > 50000) {
        $this->setErrorMessage('Loan amount cannot exceed ₱50,000.');
        return false;
    }
    return true;
}
```

**Problem:**
- Type coercion with `<` operator
- If `$principal` is string "5000abc", it converts to 5000
- If `$principal` is "abc5000", it converts to 0

**Recommendation:**
```php
public function validateLoanAmount($principal) {
    // Ensure it's a number
    if (!is_numeric($principal)) {
        $this->setErrorMessage('Loan amount must be a valid number.');
        return false;
    }
    
    $principal = (float)$principal;
    
    if ($principal < 1000) {
        $this->setErrorMessage('Loan amount must be at least ₱1,000.');
        return false;
    }
    if ($principal > 50000) {
        $this->setErrorMessage('Loan amount cannot exceed ₱50,000.');
        return false;
    }
    return true;
}
```

---

## 6️⃣ `/workspaces/Fanders/app/core/BaseService.php`

### Issue Location 6A: Validation Rules Not Comprehensive

**Lines:** 88-135

**Current Code:**
```php
protected function validate($data, $rules) {
    foreach ($rules as $field => $rule) {
        
        // Check if field is required but missing or empty
        if (strpos($rule, 'required') !== false && (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === ''))) {
            $this->setErrorMessage(ucfirst($field) . " is required.");
            return false;
        }
        
        if (isset($data[$field])) {
            $value = $data[$field];

            // Email validation
            if (strpos($rule, 'email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->setErrorMessage(ucfirst($field) . " must be a valid email address.");
                return false;
            }
            
            // Numeric validation
            if (strpos($rule, 'numeric') !== false && !is_numeric($value)) {
                $this->setErrorMessage(ucfirst($field) . " must be a number.");
                return false;
            }

            // ... more validation ...
        }
    }
    
    return true;
}
```

**Problem:**
- After 'numeric' check passes, field is NOT cast to number
- Downstream code still receives string

**Recommendation:**
```php
protected function validate($data, $rules) {
    foreach ($rules as $field => $rule) {
        
        if (strpos($rule, 'required') !== false && (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === ''))) {
            $this->setErrorMessage(ucfirst($field) . " is required.");
            return false;
        }
        
        if (isset($data[$field])) {
            $value = $data[$field];

            // Numeric validation
            if (strpos($rule, 'numeric') !== false && !is_numeric($value)) {
                $this->setErrorMessage(ucfirst($field) . " must be a number.");
                return false;
            }
            
            // Cast to appropriate type if validation passed
            if (strpos($rule, 'numeric') !== false) {
                // This ensures the original $data is modified
                $data[$field] = is_float($data[$field]) ? $data[$field] : (float)$data[$field];
            }
            
            // ... rest of validation ...
        }
    }
    
    return true;
}
```

---

## 🔧 Priority Fixes

### 🔴 **HIGH PRIORITY - Fix These First**

1. **Database Status Standardization**
   ```sql
   UPDATE loans SET status = 'Application' WHERE LOWER(status) = 'application';
   UPDATE loans SET status = 'Approved' WHERE LOWER(status) = 'approved';
   UPDATE loans SET status = 'Active' WHERE LOWER(status) = 'active';
   UPDATE loans SET status = 'Completed' WHERE LOWER(status) = 'completed';
   UPDATE loans SET status = 'Defaulted' WHERE LOWER(status) = 'defaulted';
   ```

2. **Fix Status Comparison in LoanModel**
   - Add LOWER() to status comparisons
   - File: `/workspaces/Fanders/app/models/LoanModel.php` lines 282-295

3. **Fix Error Message Handling in add.php**
   - Better empty string checking
   - File: `/workspaces/Fanders/public/loans/add.php` lines 100-115

### 🟡 **MEDIUM PRIORITY - Fix These Next**

4. **Add Client Status Check in LoanService**
   - File: `/workspaces/Fanders/app/services/LoanService.php` lines 183-206

5. **Add Better Logging**
   - File: `/workspaces/Fanders/app/services/LoanService.php` lines 212-260

### 🟢 **LOW PRIORITY - Nice to Have**

6. **Type Coercion in Validation**
   - File: `/workspaces/Fanders/app/services/LoanCalculationService.php` lines 41-50

7. **Calculation Error Persistence**
   - File: `/workspaces/Fanders/public/loans/add.php` lines 59-71

---

