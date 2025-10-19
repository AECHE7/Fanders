# Session Extension Feature Implementation

## Information Gathered
- Current session timeout logic is incomplete: `checkSessionTimeout()` only checks if user is not logged in AND has `last_activity`, but doesn't validate time-based expiration
- `SESSION_LIFETIME` is set to 1800 seconds (30 minutes) in `app/config/config.php`
- Session class doesn't track `last_activity` timestamp
- `public/init.php` currently redirects to login page on timeout
- Need to create user-friendly extension prompt instead of automatic logout

## Plan
### 1. Update Session Class (`app/core/Session.php`) ✅
- Add method to set/update `last_activity` timestamp on each request
- Ensure `last_activity` is set during session initialization

### 2. Fix AuthService Timeout Logic (`app/services/AuthService.php`) ✅
- Update `checkSessionTimeout()` to properly check time-based expiration
- Method should return true if session has expired based on `SESSION_LIFETIME`

### 3. Create Session Extension Page (`public/session_extend.php`) ✅
- Create page with modal asking user to extend session or logout
- Include CSRF protection for the form
- Handle both "Extend Session" and "Logout" actions
- Redirect back to original page after extension

### 4. Update Initialization Logic (`public/init.php`) ✅
- Change timeout redirect from login page to session extension page
- Store original URL in session for post-extension redirect

### 5. Update Navbar Template (`templates/layout/navbar.php`) (Optional)
- Consider adding session status indicator if needed

## Dependent Files to be edited
- `app/core/Session.php` - Add last_activity tracking ✅
- `app/services/AuthService.php` - Fix timeout checking logic ✅
- `public/init.php` - Change redirect destination ✅
- `public/session_extend.php` - New file for extension prompt ✅
- `templates/session_extend.php` - New template for the extension UI ✅

## Followup steps
- Test the complete flow: normal activity -> timeout detection -> extension prompt -> extend/logout actions
- Verify CSRF protection works
- Test redirect back to original page after extension
- Ensure session extension properly resets the timeout
