# Users: Add Form Layout + Reconfirmation Modal Fix (Oct 29, 2025)

This change fixes the reconfirmation modal behavior when creating a user and tidies the layout of the Add User form.

## What was wrong

- The confirm modal's "Confirm" button did nothing due to JavaScript scope errors. Variables like `form`, `passwordInput`, and `confirmPasswordInput` were referenced in a different `DOMContentLoaded` handler where they weren't defined.
- The Bootstrap grid had a nested `.row` inside another `.row`, which can break layout and spacing.
- Minor PHP inconsistencies: `$userId` in the form template pointed to the entire current user array instead of the ID, leading to incorrect role visibility checks.

## What changed

- Consolidated all modal and validation JavaScript into a single `DOMContentLoaded` block so shared variables are in scope.
- Added a `show.bs.modal` listener to refresh modal content right when it opens.
- Corrected Bootstrap grid usage and made inputs responsive with `col-12 col-md-6` where appropriate.
- Fixed `$currentUserId` derivation and role/status visibility logic for create vs edit and self-edit cases.

## Files touched

- `templates/users/form.php`

## How to verify

1. Go to Users > Add.
2. Fill in the form and click "Create Account".
   - The confirmation modal should open with the latest input values.
3. Click "Confirm Creation".
   - If all fields are valid and passwords match, the form submits.
   - If invalid, the modal closes and the form shows validation errors; the first invalid field is focused and briefly shakes.
4. On narrow screens, fields stack cleanly with consistent spacing.

## Notes

- CSRF token is preserved and submitted normally.
- Role and status pickers are shown/hidden according to current user permissions.
