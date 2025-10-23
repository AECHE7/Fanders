# CSRF Token Timing Issue Fix - October 23, 2025

## Problem Statement
Management actions (Activate, Deactivate, Blacklist, Delete) in `public/clients/view.php` failed with "Invalid security token" error, even after the initial fix. The same actions worked perfectly in `public/clients/index.php`.

## Investigation

### First Fix Attempt (Incorrect)
Initially identified and removed `setTokenName('csrf_token_view')` which was causing a token name mismatch. However, this didn't solve the problem completely.

### Root Cause Discovery
The real issue was **TOKEN TIMING** - the order of operations in the code:

#### Broken Flow (view.php):
```php
// 1. Every request (including POST) starts here
Line 26: $csrfToken = $csrf->generateToken(); // ❌ Creates NEW token

// 2. User fills form with token from step 1
// 3. User submits form

// 4. POST request arrives
// 5. Code executes from top again
Line 26: $csrfToken = $csrf->generateToken(); // ❌ Creates ANOTHER new token!

// 6. Validation happens
Line 54: if (!$csrf->validateRequest(false)) // ❌ FAILS!
    // Why? It's validating against the NEWEST token (from line 26)
    // But the form submitted the PREVIOUS token
    // Token mismatch = validation fails
```

#### Working Flow (index.php):
```php
// 1. Request arrives
// No generateToken() call at all

// 2. POST validation
Line 77: if (!$csrf->validateRequest(false)) // ✅ SUCCEEDS!
    // Why? It validates against token from session
    // Form submitted that same token
    // Token match = validation succeeds

// 3. Forms use $csrfToken from init.php (never regenerated)
```

## The Fix

### Changes Made to `public/clients/view.php`:

**Before:**
```php
// Line 23-26 (BEFORE POST handling)
$clientService = new ClientService();
$loanService = new LoanService();
$csrfToken = $csrf->generateToken(); // ❌ Too early!
$currentUser = $auth->getCurrentUser() ?: [];

// ... POST validation happens later at line 54 ...
```

**After:**
```php
// Line 23-25 (BEFORE POST handling)
$clientService = new ClientService();
$loanService = new LoanService();
// ✅ Token generation removed from here
$currentUser = $auth->getCurrentUser() ?: [];

// ... POST validation happens ...

// Line 100 (AFTER POST handling)
// Generate CSRF token for forms (AFTER POST validation to avoid regenerating before validation)
$csrfToken = $csrf->getToken(); // ✅ Now it's safe!
```

### Key Differences:
1. **Moved token retrieval** from BEFORE to AFTER POST handling
2. **Changed from `generateToken()` to `getToken()`**:
   - `generateToken()` = Force create a NEW token (overwrites old one)
   - `getToken()` = Get existing token or create if none exists
3. **Preserves token across requests** for proper validation

## Why This Works

### Request Flow Now:
1. **Initial page load (GET request)**:
   - Line 100: `getToken()` retrieves or creates token
   - Token stored in session: `abc123`
   - Form displays with token: `abc123`

2. **User submits form (POST request)**:
   - POST data includes: `csrf_token=abc123`
   - Code executes from top
   - Line 54: `validateRequest()` checks POST data
   - Looks for session token: `abc123` ✅ Still there!
   - Compares with POST token: `abc123` ✅ Match!
   - Validation **SUCCEEDS**
   - Action executes successfully
   - Redirects back

3. **After redirect (new GET request)**:
   - Line 100: `getToken()` gets existing token
   - Form displays with same valid token
   - Ready for next submission

## Comparison: view.php vs index.php

| Aspect | index.php (Working) | view.php (Fixed) |
|--------|-------------------|-----------------|
| Token generation | Never calls generateToken() | Calls getToken() after POST |
| Token source | Uses init.php token | Gets/reuses session token |
| Token timing | N/A | After validation |
| Result | ✅ Works | ✅ Works now |

## Testing

To verify the fix works:

1. Navigate to a client profile: `public/clients/view.php?id=1`
2. Click any management action button (Activate, Deactivate, etc.)
3. Confirm in the modal
4. **Expected**: Status changes successfully with success message
5. **Before fix**: "Invalid security token" error

## Technical Notes

### Why generateToken() was problematic:
```php
public function generateToken() {
    $token = bin2hex(random_bytes($this->tokenLength));
    $this->session->set($this->tokenName, $token); // ← Overwrites old token!
    return $token;
}
```

### Why getToken() is correct:
```php
public function getToken() {
    if (!$this->session->has($this->tokenName)) {
        return $this->generateToken(); // Only if missing
    }
    return $this->session->get($this->tokenName); // Reuse existing
}
```

## Lessons Learned

1. **Token lifecycle matters**: Don't regenerate tokens before validating them
2. **Order of operations is critical**: Validate with old token, then get/generate for forms
3. **Use getToken() for display**: Only use generateToken() when you explicitly want a new token
4. **Compare working examples**: index.php gave us the clue to the solution
5. **CSRF timing is subtle**: The bug was just 3 lines in the wrong place

## Files Modified
- `public/clients/view.php` - Fixed token timing issue

## Result
✅ All client status management actions now work correctly in both:
- `public/clients/index.php` (was already working)
- `public/clients/view.php` (now fixed)
